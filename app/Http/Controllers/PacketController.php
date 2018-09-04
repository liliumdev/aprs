<?php

namespace App\Http\Controllers;

use App\Packet;
use App\Tracker;
use App\Zone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

class PacketController extends Controller
{
    protected $minimum_movement_meters = 75;
    protected $maximum_allowed_rectangle = 15;

    public function get_positions(Request $request) {
        $this->validate($request, [
            'lat_from'  => 'required|numeric',
            'lat_to' => 'required|numeric',
            'long_from'  => 'required|numeric',
            'long_to' => 'required|numeric',
            'filter' => 'required|in:callsign,no',
            'minutes' => 'integer|min:15|max:360',
            'since_id' => 'integer'
        ]);

        $data = $request->all();

        if($data['lat_from'] > $data['lat_to'])
            $this->swap_vars($data['lat_from'], $data['lat_to']);

        if($data['long_from'] > $data['long_to'])
            $this->swap_vars($data['long_from'], $data['long_to']);

        if (abs($data['lat_from'] - $data['lat_to']) > $this->maximum_allowed_rectangle ||
            abs($data['long_from'] - $data['long_to'] > $this->maximum_allowed_rectangle))
            return response()->json(['status' => 'bad', 'message' => 'Rectangle is too big.']);


        $positions = Packet::boundaries($data['lat_from'], $data['lat_to'], $data['long_from'], $data['long_to'])
           ->inLastMinutes($request->has('minutes') ? $data['minutes'] : 60);

        if($data['filter'] == 'callsign' && isset($data['callsign'])) {
            $positions->withCallname($data['callsign']);
        } else {
            $positions = $positions->whereRaw('`id` in (select max(`id`) from `packet` group by `from`)');
        }

        return response()->json($positions->get());
    }

    public function get_last_seen($callsign) {
        $packet = Packet::where('from', $callsign)->orderBy('created_at', 'DESC')->first();
        if($packet == null)
            return response()->json(['status' => 'bad', 'message' => 'Could not find this callsign.']);

        return response()->json([
            'status' => 'ok',
            'lat' => $packet->latitude,
            'lng' => $packet->longitude,
            'seen' => $packet->created_at->toDateTimeString()
        ]);
    }

    public function get_zones($zoom, $minutes) {
        if($zoom != 4 && $zoom != 7 || ($minutes < 15 || $minutes > 360))
            return response()->json(['status' => 'bad', 'message' => 'Wrong parameters.']);

        $zone_stats = DB::table('zone')
            ->select('zone.*', DB::raw('count(packet.id) as zone_count'))
            ->leftJoin('packet', 'zone.id', '=', 'packet.zone_z' . $zoom)
            ->where('zone.zoom', '=', $zoom)
            ->where('packet.created_at', '>=', DB::raw('DATE_SUB(NOW(), INTERVAL ' . $minutes . ' MINUTE)'))
            ->groupBy('zone.id')
            ->get();

        return response()->json($zone_stats);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
            'altitude' => 'present',
            'from' => 'present',
            'to' => 'present',
            'format' => 'present',
            'symbol' => 'present',
            'symbol_table' => 'present',
            'comment' => 'present',
            'object_name' => 'present',
            'speed' => 'present',
            'message_text' => 'present',
            'raw' => 'required',
            'hash' => 'required'
        ]);

        $data = $request->all();

        if(Packet::where('hash', $data['hash'])->first() !== null) {
            return response()->json(['status' => 'bad', 'message' => 'Packet already exists.']);
        }

        if($this->is_duplicate_packet($data['latitude'], $data['longitude'], $data['from'])) {
            return response()->json(['status' => 'bad', 'message' => 'This object already reported the same location in the last hour.']);
        }

        $packet = new Packet($data);
        $zones = $this->get_zone($data['latitude'], $data['longitude']);
        if($zones['4'] !== false && $zones['7'] !== false) {
            $packet->zone_z4 = $zones['4'];
            $packet->zone_z7 = $zones['7'];
            $packet->save();
            return response()->json(['status' => 'ok']);
        }

        return response()->json(['status' => 'bad', 'message' => 'Could not get appropriate zone.']);
    }

    public function get_zone($lat, $long)
    {
        $zones = Zone::where('lat_from', '<=', $lat)
            ->where('lat_to', '>=', $lat)
            ->where('long_from', '<=', $long)
            ->where('long_to', '>=', $long)
            ->orderBy('zoom', 'ASC')
            ->get();

        if($zones->count() == 0)
            return ['4' => false, '7' => false];

        return ['4' => $zones[0]->id, '7' => $zones[1]->id];
    }

    public function is_duplicate_packet($new_lat, $new_long, $from)
    {
        if($from == '') return false;

        $last_packet = Packet::where('from', $from)
            ->orderBy('id', 'DESC')
            ->first();

        if($last_packet != null) {
            // Last ping is at least an hour old
            if ($last_packet->updated_at <= Carbon::now()->subHour()) {
                return false;
            }

            $last_lat = $last_packet->latitude;
            $last_long = $last_packet->longitude;

            if($this->geo_distance($last_lat, $last_long, $new_lat, $new_long) < $this->minimum_movement_meters)
                return true;
        }

        return false;
    }

    function geo_distance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000)
    {
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }

    public function swap_vars(&$a, &$b) {
        $c = $a;
        $a = $b;
        $b = $c;
    }
}
