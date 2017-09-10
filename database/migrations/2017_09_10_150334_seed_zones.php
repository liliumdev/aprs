<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Zone;

class SeedZones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $zones_setup = [
            [
                'dimension' => 4,
                'zoom' => 4
            ],
            [
                'dimension' => 6,
                'zoom' => 7
            ]
        ];

        foreach($zones_setup as $setup) {
            $zones = $this->getZones($setup['dimension']);
            foreach($zones as $zone) {
                $zone['zoom'] = $setup['zoom'];
                Zone::create($zone);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Zone::truncate();
    }

    public function getZones($dimension)
    {
        $zones = [];

        $min_lat = -90;
        $min_long = -180;

        $lat_step = 180/$dimension;
        $long_step = 360/$dimension;

        $from_lat = $min_lat;

        for($i = 0; $i < $dimension; $i++) {
            $to_lat = $from_lat + $lat_step;
            $from_long = $min_long;

            for($j = 0; $j < $dimension; $j++) {
                $to_long = $from_long + $long_step;
                $center_lat = ($from_lat + $to_lat) / 2;
                $center_long = ($from_long + $to_long) / 2;

                $zones[] = [
                    'lat_from' => $from_lat,
                    'lat_to' => $to_lat,
                    'long_from' => $from_long,
                    'long_to' => $to_long,
                    'center_lat' => $center_lat,
                    'center_long' => $center_long
                ];

                $from_long += $long_step;
                if($from_long > 180)
                    $from_long = $min_long;
            }

            $from_lat += $lat_step;
            if($from_lat > 90)
                $from_lat = $min_lat;
        }

        return $zones;
    }
}
