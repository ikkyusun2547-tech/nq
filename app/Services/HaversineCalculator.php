<?php

namespace App\Services;

class HaversineCalculator
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Great-circle distance between two lat/lng points, in meters.
     */
    public static function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($deltaLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }
}
