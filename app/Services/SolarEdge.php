<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SolarEdge
{
    private $siteId = null;
    private $baseUrl = null;
    private $apiKey = null;


    public function __construct($siteId, $apiKey, $baseUrl = null)
    {
        $this->siteId = $siteId;
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? 'https://monitoringapi.solaredge.com/site';

    }

    private function buildUrl($path, $params)
    {
        $params['api_key'] = $this->apiKey;

        return $this->baseUrl . '/' . $this->siteId . '/' . $path . "?" . http_build_query($params);
    }

    private function httpRequest($url, $clearCache = false, $cacheTimeout = 1)
    {
        if ($clearCache) {
            Cache::forget($url);
        }
        return Cache::remember($url, Carbon::now()->addHours($cacheTimeout), function () use ($url) {
            return \Httpful\Request::get($url)
                ->expectsJson()
                ->send();
        })->body;
    }

    public function energy(Carbon $startDate, $clearCache = false)
    {
        $solarData = $this->httpRequest($this->buildUrl('energy', [
            'timeUnit' => 'DAY',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $startDate->clone()->addDays(7)->format('Y-m-d'),
        ]), $clearCache);

        return $solarData?->energy;
    }


    public function powerDetails(Carbon $startDate, $clearCache = false)
    {
        $url = $this->buildUrl('powerDetails', [
            'meters' => collect(['Production', 'Consumption', 'SelfConsumption', 'FeedIn', 'Purchased'])->implode(','),
            'endTime' => $startDate->format('Y-m-d h:m:s'),
            'startTime' => $startDate->subDay(7)->format('Y-m-d h:m:s'),
        ]);
        $solarData = $this->httpRequest($url, $clearCache);
        return $solarData?->energy;
    }

    public function energyDetails(Carbon $startDate, $clearCache = false)
    {
        $url = $this->buildUrl('energyDetails', [
            'timeUnit' => 'DAY',
            'meters' => collect(['Production', 'Consumption', 'SelfConsumption', 'FeedIn', 'Purchased'])->implode(','),
            'endTime' => $startDate->format('Y-m-d h:m:s'),
            'startTime' => $startDate->subDay(7)->format('Y-m-d h:m:s'),
        ]);
        $solarData = $this->httpRequest($url);
        return $solarData?->energyDetails;
    }


    public function benefits($clearCache = false)
    {
        $benefits = $this->httpRequest($this->buildUrl('envBenefits', [
            'systemUnits' => 'Imperial'
        ]), $clearCache);

        return [
            'benefits' => $benefits?->envBenefits
        ];

    }
}
