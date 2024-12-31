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
                from ((select group_concat(distinct title) as title,
                              max(date)                    as date,
                              1                            as weight
                       from (select title,
                                    date,
                                    case
                                        when (JSON_TYPE(JSON_EXTRACT(`event`, '$.location')) = 'NULL') then title
                                        else JSON_EXTRACT(`event`, '$.location')
                                        end as dinner
                             from dinners) as dinnerList
                       group by dinner
                       having DATEDIFF(CURDATE(), max(date)) > 20
                       order by count(*) desc
                       limit 10)
                      union
                      (select title,
                              date,
                              2 as weight
                       from dinners
                       where date_format(date, '%Y') != date_format(now(), '%Y')
                         and date_format(date, '%j') >= (date_format(now(), '%j') - 3)
                         and date_format(date, '%j') <= (date_format(now(), '%j') + 3)
                         and  abs(DATEDIFF((select max(date) from dinners as history where history.title = dinners.title limit 1), CURDATE())) > 20)) as historical
                group by title
                order by sum(weight) desc;";

        return collect(DB::select($sql));
    }

    public static function proteinFrequency() {
        return Dinner::select([
            DB::raw('ifnull(proteins.name, "Other") as name'),
            DB::raw('ifnull(proteins.color, "#555555") as color'),
            'proteins.vegetarian',
            DB::raw('count(*) as freq')
        ])
            ->leftJoin('proteins', 'dinners.protein_id', 'proteins.id')
            ->groupBy('protein_id')
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
