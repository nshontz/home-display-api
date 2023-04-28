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

    public function fetchDinnerList()
    {

        $url = "https://icalendar.anylist.com/" . $this->anyListIcalKey . ".ics";
        $events = Cache::remember($url, Carbon::now()->addHours(1), function () use ($url) {
            $ical = new ICal($url);
            return collect($ical->events());
        });

        $weekStart = Carbon::now()->startOfWeek()->subDay()->startOfDay();
        $weekEnd = Carbon::now()->endOfWeek()->endOfDay();

        $upcomingWeek = $events->filter(function ($event) use ($weekStart, $weekEnd) {
            $eventDate = Carbon::parse($event->dtstart)->setHour(18);
            return $eventDate->isAfter($weekStart) && $eventDate->isBefore($weekEnd);
        })->map(function ($event) {
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
