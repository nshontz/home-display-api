<?php

namespace App\Services;

use App\Models\DailyWeather;
use Carbon\Carbon;
use Httpful\Exception\JsonParseException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PHPUnit\Exception;

class Weather
{
    private $latitude = null;
    private $longitude = null;
    private $locationData = null;
    private $stations = null;

    public function __construct(float $latitude, float $longitude)
    {
        $this->longitude = $longitude;
        $this->latitude = $latitude;

        $weatherUrl = "https://api.weather.gov/points/" . $this->latitude . ',' . $this->longitude;

        $this->locationData = Cache::remember($weatherUrl, Carbon::now()->addHours(5), function () use ($weatherUrl) {
            return \Httpful\Request::get($weatherUrl)
                ->expectsJson()
                ->send();
        })->body;

        $stationsUrl = $this->locationData->properties->observationStations;
        try {
            $this->stations = Cache::remember($stationsUrl, Carbon::now()->addHours(5), function () use ($stationsUrl) {
                return \Httpful\Request::get($stationsUrl)
                    ->expectsJson()
                    ->send();
            })->body;
        } catch (JsonParseException $e) {
            Log::error($e->getMessage(), $e->getTrace());
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
        }

    }

    public function forecast($startDate, $clearCache = false)
    {
        $url = $this->locationData->properties->forecast;
        $forecast = null;
        if ($clearCache) {
            Cache::forget($url);
        }
        try {
            $forecast = Cache::remember($url, Carbon::now()->addHours(5), function () use ($url) {
                return \Httpful\Request::get($url)
                    ->expectsJson()
                    ->send();
            });

        } catch (JsonParseException $e) {
            Log::error($e->getMessage(), $e->getTrace());
        } catch (Exception $e) {
            Log::error($e->getMessage(), $e->getTrace());
        }
        if ($forecast?->body?->properties) {
            collect($forecast->body?->properties->periods)->map(function ($day) {
                return (object)[
                    'day' => Carbon::parse($day->startTime)->format('Y-m-d'),
                    'startTime' => $day->startTime,
                    'name' => $day->name,
                    'temperature' => $day->temperature,
                    'temperatureUnit' => $day->temperatureUnit,
                    'windSpeed' => $day->windSpeed,
                    'windDirection' => $day->windDirection,
                    'icon' => $day->icon,
                    'icon_alt' => $this->locateAlternativeIcon($day->icon),
                    'shortForecast' => $day->shortForecast,
                    'detailedForecast' => $day->detailedForecast,
                    'object' => $day
                ];
            })->groupBy('day')->map(function ($weatherPeriods, $day) {
                $temps = $weatherPeriods->pluck('temperature')->sort();
                $high = $temps->max();
                $low = $temps->min();

                $day = DailyWeather::firstOrNew([
                    'day' => $day,
                ]);
                if (empty($day->high) || $high > $day->high) {
                    $day->high = $high;
                }
                if (empty($day->low) || $low < $day->low) {
                    $day->low = $low;
                }
                $day->icon = $weatherPeriods->first()->icon;
                $day->icon_alt = $this->locateAlternativeIcon($weatherPeriods->first()->icon);
                $day->short_forecast = $weatherPeriods->first()->shortForecast;
                $day->periods = $weatherPeriods;
                $day->save();
                return $day;
            });
        }


        return DailyWeather::where('day', '>=', $startDate)->get();
    }

    public function current($clearCache = false)
    {
        if ($this->stations) {

            $url = collect($this->stations?->observationStations)->first() . '/observations/latest';
            if ($clearCache) {
                Cache::forget($url);
            }
            try {
                $current = Cache::remember($url, Carbon::now()->addHours(5), function () use ($url) {
                    return \Httpful\Request::get($url)
                        ->expectsJson()
                        ->send();
                })->body;

            } catch (JsonParseException $e) {
                Log::error($e->getMessage(), $e->getTrace());
            } catch (Exception $e) {
                Log::error($e->getMessage(), $e->getTrace());
            }
            return [
                'icon_alt' => $this->locateAlternativeIcon($current->properties->icon),
                'icon' => $current->properties->icon,
                'maxTemperatureLast24Hours' => $current->properties->maxTemperatureLast24Hours->value,
                'text_description' => $current->properties->textDescription,
                'current_temp' => ($current->properties->temperature->value * (9 / 5)) + 32
            ];

        } else {
            return null;
        }
    }

    private function locateAlternativeIcon($iconPath)
    {
        $path = str_replace(['/icons/land/', 'night/', 'day/'], '', parse_url($iconPath)['path']);
        $path = str_replace(',', '-', $path);
        if (is_numeric(stripos($path, 'rain'))) {
            if (
                is_numeric(stripos($path, 'rain-10')) ||
                is_numeric(stripos($path, 'rain-20')) ||
                is_numeric(stripos($path, 'rain-30'))
            ) {
                $path = 'rain';
            }
            if (
                is_numeric(stripos($path, 'rain-40')) ||
                is_numeric(stripos($path, 'rain-50')) ||
                is_numeric(stripos($path, 'rain-60'))
            ) {
                $path = 'rain-medium';
            }
            if (
                is_numeric(stripos($path, 'rain-70')) ||
                is_numeric(stripos($path, 'rain-80')) ||
                is_numeric(stripos($path, 'rain-90')) ||
                is_numeric(stripos($path, 'rain-100'))
            ) {
                $path = 'rain-heavy';
            }
            if (is_numeric(stripos($path, 'tsra'))) {
                $path = 'tsra';
            }
        }

        $iconName = $path . '.png';
//        dump($iconName);
        if (file_exists(public_path('icons/' . $iconName))) {
            $iconPath = asset('icons/' . $iconName);
        }

        return $iconPath;
    }
}
