<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $table = 'zone';

    public $timestamps = false;

    protected $fillable = [
        'zoom',
        'lat_from',
        'lat_to',
        'long_from',
        'long_to',
        'center_lat',
        'center_long'
    ];

}