<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Packet extends Model
{
    protected $table = 'packet';

    protected $fillable = [
        'latitude',
        'longitude',
        'altitude',
        'from',
        'to',
        'format',
        'symbol',
        'symbol_table',
        'comment',
        'object_name',
        'speed',
        'message_text',
        'raw',
        'hash'
    ];

    public function scopeBoundaries($query, $lat_from, $lat_to, $long_from, $long_to) {
        return $query->where('latitude', '>=', $lat_from)
            ->where('latitude', '<=', $lat_to)
            ->where('longitude', '>=', $long_from)
            ->where('longitude', '<=', $long_to);
    }

    public function scopeInLastMinutes($query, $minutes) {
        return $query->where('created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL ' . $minutes . ' MINUTE)'));
    }

    public function scopeWithCallname($query, $callname) {
        return $query->where('from', '=', $callname);
    }
}