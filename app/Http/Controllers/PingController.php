<?php

namespace App\Http\Controllers;

use App\Tracker;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PingController extends Controller
{
    public function ping(Request $request)
    {
        $this->validate($request, [
            'name'      => 'required|max:255',
            'lat_from'  => 'required',
            'lat_to'    => 'required',
            'long_from' => 'required',
            'long_to'   => 'required'
        ]);

        $data = $request->all();
        Tracker::updateOrCreate(['name' => $data['name']], $data);

        return response()->json(['status' => 'ok']);
    }
}
