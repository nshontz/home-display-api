<?php

namespace App\Services;

use App\Models\Dinner;
use Carbon\Carbon;
use ICal\ICal;
use Illuminate\Support\Facades\Cache;

class AnyList
{
    private $anyListIcalKey = null;

    public function __construct($anyListIcalKey)
    {
        $this->anyListIcalKey = $anyListIcalKey;
    }

    public function fetchDinnerList($clearCache = false)
    {
        $url = "https://icalendar.anylist.com/" . $this->anyListIcalKey . ".ics";
        if($clearCache) {
            Cache::forget($url);
        }
        $events = Cache::remember($url, Carbon::now()->addHours(1), function () use ($url) {
            $ical = new ICal($url);
            return collect($ical->events());
        });

        $upcomingWeek = $events->map(function ($event) {
            $dinner = Dinner::firstOrNew(['uid' => $event->uid]);
            $dinner->title = $event->summary;
            $dinner->date = Carbon::parse($event->dtstart);
            $dinner->event = $event;
            $dinner->save();
            return $dinner;
        })->sortBy('date');


        return $upcomingWeek->values();
    }
}
