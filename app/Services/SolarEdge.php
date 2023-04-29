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

    private function httpRequest($url, $cacheTimeout = 1)
    {
        return Cache::remember($url, Carbon::now()->addHours($cacheTimeout), function () use ($url) {
            return \Httpful\Request::get($url)
                ->expectsJson()
                ->send();
        })->body;
    }

    public function energy(Carbon $startDate)
    {
        $solarData = $this->httpRequest($this->buildUrl('energy', [
            'timeUnit' => 'DAY',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $startDate->addDays(7)->format('Y-m-d'),
        ]));

        return $solarData?->energy;
    }


    public function details(Carbon $startDate)
    {
        $solarData = $this->httpRequest($this->buildUrl('powerDetails', [
            'meters' => 'PRODUCTION,CONSUMPTION',
            'startTime' => $startDate->format('Y-m-d'),
            'endTime' => $startDate->addDays(7)->format('Y-m-d'),
        ]));

        return $solarData?->energy;
    }


    public function benefits()
    {
        $benefits = $this->httpRequest($this->buildUrl('envBenefits', [
            'systemUnits' => 'Imperial'
        ]));

        return [
            'benefits' => $benefits?->envBenefits
        ];

    }
}
