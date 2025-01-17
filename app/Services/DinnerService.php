<?php

namespace App\Services;

use App\Models\Dinner;
use Illuminate\Support\Facades\DB;

class DinnerService
{
    public static function freqency() {
        return  Dinner::limit(10)
            ->groupBy('title')
            ->select([
                'title',
                DB::raw('count(title) as freq'),
                DB::raw('min(created_at) as created_at_min'),
                DB::raw('max(created_at) as created_at_max')
            ])
            ->where('created_at', '>', now()->subYear())
            ->orderBy('freq', 'desc')
            ->get();
    }

    public static function recommendations(): \Illuminate\Support\Collection
    {
        $sql = "select title,
                       sum(weight) as weight
                from ((select string_agg(distinct title, ',') as title,
                              max(date)                       as date,
                              1                               as weight
                       from (select title,
                                    date,
                                    case
                                        when ((event::jsonb->>'location') is null) then title
                                        else event::jsonb->>'location'
                                        end as dinner
                             from dinners) as dinnerList
                       group by dinner
                       having DATE_PART('day',(current_date - max(date))) > 20
                       order by count(*) desc
                       limit 10)
                      union
                      (select title,
                              date,
                              2 as weight
                       from dinners
                       where extract(year from date) != extract(year from current_date)
                         and extract(doy from date) >= (extract(doy from current_date) - 3)
                         and extract(doy from date) <= (extract(doy from current_date) + 3)
                         and abs(DATE_PART('day',current_date - (select max(date)
                                                 from dinners as history
                                                 where history.title = dinners.title
                                                 limit 1))) > 20)
                      ) as historical
                group by title
                order by sum(weight) desc;";

        return collect(DB::select($sql));
    }

    public static function proteinFrequency() {
        return Dinner::select([
            DB::raw("coalesce(proteins.name, 'Other') as name"),
            DB::raw("coalesce(proteins.color, '#555555') as color"),
            'proteins.vegetarian',
            DB::raw('count(*) as freq')
        ])
            ->leftJoin('proteins', 'dinners.protein_id', 'proteins.id')
            ->groupBy('proteins.id')
            ->orderBy('freq', 'desc')
            ->get();
    }

    public static function vegetarianFrequency() {
        $proteinFrequency = self::proteinFrequency();

        return $proteinFrequency
            ->groupBy('vegetarian')
            ->mapWithKeys(function ($proteinGroups, $isVegetarian) {
                return [$isVegetarian ? 'Vegetarian' : 'Omnivorian' => $proteinGroups->pluck('freq')->sum()];
            });
    }
}
