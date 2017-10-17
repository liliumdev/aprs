<?php

namespace App\Http\Controllers;

use App\Tracker;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrackerController extends Controller
{
    public function all()
    {
        return response()->json(Tracker::all());
    }
}
