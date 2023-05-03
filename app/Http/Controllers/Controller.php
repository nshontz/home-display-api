<?php

namespace App\Http\Controllers;

use App\Models\Dinner;
use App\Services\AnyList;
use App\Services\SolarEdge;
use App\Services\Weather;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\Request;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected $anyList, $solarEdge, $weather, $startDate;
    private bool $forceRefresh = false;

    public function __construct()
    {
        $this->solarEdge = new SolarEdge(env('SOLAR_EDGE_SITE_ID'), env('SOLAR_EDGE_API_KEY'));
        $this->anyList = new AnyList(env('ANYLIST_CODE'));
        $location = explode(',', env('WEATHER_LOCATION'));
        $this->weather = new Weather($location[0], $location[1]);
        if (Carbon::now()->isSaturday()) {
            $this->startDate = Carbon::now()->endOfWeek()->subDays(1)->startOfDay();
        } else {
            $this->startDate = Carbon::now()->startOfWeek()->subDays(2)->startOfDay();
        }
    }

    public function home(Request $request)
    {
        if ($request->filled('start_date')) {
            $this->startDate = Carbon::parse($request->start_date);
        }
        if ($request->filled('force_refresh') && $request->force_refresh == 1) {
            $this->forceRefresh = true;
        }

        $dinnerList = $this->anyList->fetchDinnerList($this->forceRefresh);
        $solar = $this->solarEdge->energy($this->startDate, $this->forceRefresh);
        $weatherData = $this->weather->forecast($this->forceRefresh);

        $days = collect();
        $solar->values = collect($solar->values)->map(function ($day) use ($solar) {
            $day->unit = $solar->unit;
            return $day;
        });

        for ($i = 0; $i < 7; $i++) {

            $day = clone($this->startDate);
            $day = $day->addDays($i);

            $dayData = (object)[
                'date' => $day,
                'date_display' => $day->format('l M d'),
            ];

            $dayData->dinner = $dinnerList->filter(function ($dinner) use ($day) {
                return $dinner->date->format('Y-m-d') == $day->format('Y-m-d');
            })->first();

            $dayData->weather = $weatherData->filter(function ($weatherDay) use ($day) {
                return
                    Carbon::parse($weatherDay->startTime)->format('Y-m-d') ==
                    $day->format('Y-m-d');
            })->first();

            $dayData->solar = collect($solar->values)->filter(function ($solarData) use ($day) {
                return
                    Carbon::parse($solarData->date)->format('Y-m-d') ==
                    $day->format('Y-m-d');
            })->first();

            $days->push($dayData);
        }

        return response()->json([
            'days' => $days,
            'solar_benefits' => $this->solarEdge->benefits(),
        ]);
    }

    public function dinner(Request $request, $uid)
    {
        $validatedData = $request->validate([
            'complete' => ['nullable', 'boolean']
        ]);


        $dinner = Dinner::where('uid', $uid)->first();
        $dinner->complete = $validatedData['complete'] ? Carbon::now() : null;
        $dinner->save();

        return $dinner;
    }


}
