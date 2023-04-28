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

    }

    public function forecast()
    {

        $forecastUrl = $this->locationData->properties->forecast;

        $forecast = Cache::remember($forecastUrl, Carbon::now()->addHours(5), function () use ($forecastUrl) {
            return \Httpful\Request::get($forecastUrl)
                ->expectsJson()
                ->send();
        });

        return collect($forecast->body->properties->periods)->map(function ($day) {
            return (object)[
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
        });

    }

    private function locateAlternativeIcon($iconPath)
    {

        $iconName = collect(explode('/',parse_url($iconPath)['path']))->last(). '.jpg';
        if (file_exists(public_path('icons/' . $iconName))) {
            $iconPath = asset('icons/' . $iconName);
        }

        return $iconPath;
    }
}
