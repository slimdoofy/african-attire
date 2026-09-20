<?php
/**
 * includes/logistics_fee.php
 *
 * Distance-based logistics fee calculator.
 *
 * Strategy (in order of preference):
 *   1. Google Maps Distance Matrix API — if key is configured
 *   2. Haversine formula using city_coordinates table — offline, free
 *   3. Flat base fare — if no city can be resolved
 *
 * All fees returned in NGN. USD derived via usdRate().
 */

/**
 * Extract the most likely city name from a free-text address string.
 * Takes the second-to-last comma/newline segment (typically the city).
 */
function extractCityFromAddress(string $address): string {
    $lines = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n|,/', $address))
    ));
    $count = count($lines);
    if ($count === 0) return '';
    if ($count === 1) return $lines[0];
    if ($count === 2) return $lines[0];   // city, country
    return $lines[$count - 2];            // second-to-last = city
}

/**
 * Look up lat/lng for a city name from the city_coordinates table.
 * Tries exact match first, then partial (LIKE).
 *
 * @return array|null ['lat' => float, 'lng' => float, 'city' => string] or null
 */
function getCityCoords(string $cityName): ?array {
    $cityName = trim($cityName);
    if (!$cityName) return null;

    // Exact match
    $row = DB::fetch(
        "SELECT lat, lng, city FROM city_coordinates WHERE city = ? LIMIT 1",
        [$cityName]
    );
    if ($row) return $row;

    // Case-insensitive partial match
    $row = DB::fetch(
        "SELECT lat, lng, city FROM city_coordinates
         WHERE city LIKE ? ORDER BY LENGTH(city) ASC LIMIT 1",
        ['%' . $cityName . '%']
    );
    return $row ?: null;
}

/**
 * Calculate straight-line distance (km) between two lat/lng points
 * using the Haversine formula.
 */
function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $R    = 6371.0;  // Earth's radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a    = sin($dLat/2) * sin($dLat/2)
          + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
          * sin($dLng/2) * sin($dLng/2);
    $c    = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($R * $c, 2);
}

/**
 * Get road distance via Google Maps Distance Matrix API.
 * Returns km (float) or null on failure.
 */
function googleMapsDistance(string $origin, string $destination): ?float {
    $key = getSetting('google_maps_api_key', '');
    if (!$key) return null;

    $url = 'https://maps.googleapis.com/maps/api/distancematrix/json?'
         . http_build_query([
               'origins'      => $origin,
               'destinations' => $destination,
               'mode'         => 'driving',
               'units'        => 'metric',
               'key'          => $key,
           ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    if (!$resp) return null;
    $data = json_decode($resp, true);

    $meters = $data['rows'][0]['elements'][0]['distance']['value'] ?? null;
    if (!$meters) return null;
    return round($meters / 1000, 2);
}

/**
 * Calculate the logistics fee for an order.
 *
 * @param string $merchantCity   Pickup city (from shop.pickup_city or shop.city)
 * @param string $deliveryAddress Full delivery address text
 * @return array {
 *   distance_km: float|null,
 *   fee_ngn:     float,
 *   fee_usd:     float,
 *   method:      string,   // 'google_maps'|'haversine'|'base_fare'
 *   origin_city: string,
 *   dest_city:   string,
 *   breakdown:   string,   // human-readable explanation
 * }
 */
function calculateLogisticsFee(string $merchantCity, string $deliveryAddress): array {
    $baseFareNgn = (float)getSetting('logistics_base_fare_ngn', '2000');
    $perKmNgn    = (float)getSetting('logistics_per_km_ngn',    '150');
    $minFeeNgn   = (float)getSetting('logistics_min_fee_ngn',   '2500');
    $rate        = usdRate();

    $destCity    = extractCityFromAddress($deliveryAddress);
    $originCity  = trim($merchantCity);

    // ── Try Google Maps first ─────────────────────────────────
    if ($originCity && $destCity) {
        $gmKm = googleMapsDistance(
            $originCity . ', Nigeria',
            $destCity . (strpos($deliveryAddress, 'Nigeria') !== false ? ', Nigeria' : '')
        );
        if ($gmKm !== null) {
            $feeNgn = max($minFeeNgn, $baseFareNgn + ($perKmNgn * $gmKm));
            return [
                'distance_km' => $gmKm,
                'fee_ngn'     => round($feeNgn, 2),
                'fee_usd'     => round($feeNgn / $rate, 2),
                'method'      => 'google_maps',
                'origin_city' => $originCity,
                'dest_city'   => $destCity,
                'breakdown'   => sprintf(
                    'Base ₦%s + (%s km × ₦%s/km) = ₦%s [via Google Maps]',
                    number_format($baseFareNgn, 0),
                    number_format($gmKm, 1),
                    number_format($perKmNgn, 0),
                    number_format($feeNgn, 0)
                ),
            ];
        }
    }

    // ── Haversine via city_coordinates table ──────────────────
    $originCoords = $originCity ? getCityCoords($originCity) : null;
    $destCoords   = $destCity   ? getCityCoords($destCity)   : null;

    if ($originCoords && $destCoords) {
        $km     = haversineKm(
            (float)$originCoords['lat'], (float)$originCoords['lng'],
            (float)$destCoords['lat'],   (float)$destCoords['lng']
        );
        // Apply 1.35 road factor: straight-line distance ≈ 74% of road distance
        $roadKm = round($km * 1.35, 2);
        $feeNgn = max($minFeeNgn, $baseFareNgn + ($perKmNgn * $roadKm));
        return [
            'distance_km' => $roadKm,
            'fee_ngn'     => round($feeNgn, 2),
            'fee_usd'     => round($feeNgn / $rate, 2),
            'method'      => 'haversine',
            'origin_city' => $originCoords['city'],
            'dest_city'   => $destCoords['city'],
            'breakdown'   => sprintf(
                'Base ₦%s + (%s km road est. × ₦%s/km) = ₦%s [city coordinates]',
                number_format($baseFareNgn, 0),
                number_format($roadKm, 1),
                number_format($perKmNgn, 0),
                number_format($feeNgn, 0)
            ),
        ];
    }

    // ── Fallback: base fare only ──────────────────────────────
    $feeNgn = $baseFareNgn;
    return [
        'distance_km' => null,
        'fee_ngn'     => round($feeNgn, 2),
        'fee_usd'     => round($feeNgn / $rate, 2),
        'method'      => 'base_fare',
        'origin_city' => $originCity,
        'dest_city'   => $destCity,
        'breakdown'   => sprintf(
            'Base fare only ₦%s (city coordinates not found for: %s → %s)',
            number_format($feeNgn, 0),
            $originCity ?: 'unknown',
            $destCity ?: 'unknown'
        ),
    ];
}
