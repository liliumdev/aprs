<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    protected $table = 'tracker';

    protected $fillable = [
        'name',
        'lat_from',
        'lat_to',
        'long_from',
        'long_to'
    ];

}