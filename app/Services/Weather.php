<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class Weather
{
    private $latitude = null;
    private $longitude = null;
    private $locationData = null;

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
        $this->stations = Cache::remember($stationsUrl, Carbon::now()->addHours(5), function () use ($stationsUrl) {
            return \Httpful\Request::get($stationsUrl)
                ->expectsJson()
                ->send();
        })->body;

    }

    public function forecast($clearCache = false)
    {
        $url = $this->locationData->properties->forecast;
        if ($clearCache) {
            Cache::forget($url);
        }
        $forecast = Cache::remember($url, Carbon::now()->addHours(5), function () use ($url) {
            return \Httpful\Request::get($url)
                ->expectsJson()
                ->send();
        });

        return collect($forecast->body->properties->periods)->map(function ($day) {
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
            $day = (object)[
                'startTime' => $day,
            ];
            $temps = $weatherPeriods->pluck('temperature')->sort();
            $day->high = $temps->pop();
            $day->low = $temps->pop();
            $day->icon = $weatherPeriods->first()->icon;
            $day->icon_alt = $this->locateAlternativeIcon($weatherPeriods->first()->icon);
            $day->shortForecast = $weatherPeriods->first()->shortForecast;

            $day->periods = $weatherPeriods;
            return $day;
        });

    }

    public function current($clearCache = false)
    {
        $url = collect($this->stations->observationStations)->first() . '/observations/latest';
        if ($clearCache) {
            Cache::forget($url);
        }

        $current = Cache::remember($url, Carbon::now()->addHours(5), function () use ($url) {
            return \Httpful\Request::get($url)
                ->expectsJson()
                ->send();
        })->body;

        return [
            'icon_alt' => $this->locateAlternativeIcon($current->properties->icon),
            'icon' => $current->properties->icon,
            'maxTemperatureLast24Hours' => $current->properties->maxTemperatureLast24Hours->value,
            'text_description' => $current->properties->textDescription,
            'current_temp' => ($current->properties->temperature->value * (9 / 5)) + 32
        ];;
    }

    private function locateAlternativeIcon($iconPath)
    {

        $iconName = collect(explode('/', parse_url($iconPath)['path']))->last() . '.png';
//        dd($iconName);
        if (file_exists(public_path('icons/' . $iconName))) {
            $iconPath = asset('icons/' . $iconName);
        }

        return $iconPath;
    }
}
