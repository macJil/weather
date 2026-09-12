<?php

/**
 * HELPER FUNCTIONS: api_helpers.php
 * 
 * This file contains reusable functions to:
 * 1. Fetch data from external REST APIs (Weather, Soil, Topography).
 * 2. Calculate landslide risk metrics (Runoff, Infiltration, Saturation).
 * 3. Handle parallel API requests and caching.
 */

// --- 1. PARALLEL API FETCHING ---

/**
 * Fetches data from multiple URLs simultaneously using cURL multi-handle.
 */
function fetchParallelData(array $urls) {
    $mh = curl_multi_init();
    $handles = [];
    $results = [];

    foreach ($urls as $key => $url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, "BaguioWeatherMonitor/1.0");
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }

    $active = null;
    do {
        $status = curl_multi_exec($mh, $active);
    } while ($active || $status == CURLM_CALL_MULTI_PERFORM);

    foreach ($handles as $key => $ch) {
        $results[$key] = json_decode(curl_multi_getcontent($ch), true);
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $results;
}

// --- 2. CACHING HELPERS ---

/**
 * Retrieves terrain/soil data from the local cache if it exists.
 */
function getCachedLocationData($conn, $lat, $lng) {
    $lat = round($lat, 3);
    $lng = round($lng, 3);
    $stmt = mysqli_prepare($conn, "SELECT slope, soil_type FROM location_cache WHERE lat = ? AND lng = ?");
    mysqli_stmt_bind_param($stmt, "dd", $lat, $lng);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/**
 * Stores terrain/soil data into the local cache.
 */
function storeCacheData($conn, $lat, $lng, $slope, $soilType) {
    $lat = round($lat, 3);
    $lng = round($lng, 3);
    $stmt = mysqli_prepare($conn, "INSERT IGNORE INTO location_cache (lat, lng, slope, soil_type) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ddds", $lat, $lng, $slope, $soilType);
    mysqli_stmt_execute($stmt);
}

// --- 3. EXISTING API HELPERS ---

function fetchJson($url, $postData = null) {
    $options = [
        'http' => [
            'timeout' => 30,
            'header' => "User-Agent: BaguioWeatherMonitor/1.0\r\n"
        ]
    ];
    if ($postData !== null) {
        $options['http']['method'] = 'POST';
        $options['http']['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $options['http']['content'] = http_build_query($postData);
    }
    $response = @file_get_contents($url, false, stream_context_create($options));
    return $response === false ? null : json_decode($response, true);
}

/**
 * Get Soil Type from ISRIC SoilGrids API
 */
function getSoilType($latitude, $longitude) {
    $url = 'https://rest.isric.org/soilgrids/v2.0/classification/query?' . http_build_query([
        'lon' => $longitude,
        'lat' => $latitude,
        'number_classes' => 1
    ]);
    $data = fetchJson($url);
    return $data['wrb_class_name'] ?? 'Unknown';
}

/**
 * Uses OpenTopoData (SRTM 90m) to calculate terrain slope at a coordinate.
 * Samples elevations around the point to approximate the gradient.
 */
function getSlopeAngle($latitude, $longitude) {
    // Define sample points to approximate gradient
    $points = [
        [$latitude, $longitude],
        [$latitude + 0.0009, $longitude],
        [$latitude - 0.0009, $longitude],
        [$latitude, $longitude + 0.001],
        [$latitude, $longitude - 0.001]
    ];

    $locations = implode('|', array_map(function ($point) {
        return $point[0] . ',' . $point[1];
    }, $points));

    $url = 'https://api.opentopodata.org/v1/srtm90m?locations=' . urlencode($locations);
    $response = @file_get_contents($url);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (($data['status'] ?? '') !== 'OK' || count($data['results'] ?? []) !== 5) {
        return null;
    }

    $elevations = array_map(function ($result) {
        return $result['elevation'] ?? null;
    }, $data['results']);

    if (in_array(null, $elevations, true)) {
        return null;
    }

    // Mathematical gradient calculation
    $metersPerDegreeLatitude = 111320;
    $metersPerDegreeLongitude = 111320 * cos(deg2rad($latitude));
    $northSouthDistance = 0.0018 * $metersPerDegreeLatitude;
    $eastWestDistance = 0.002 * $metersPerDegreeLongitude;
    $northSouthGradient = ($elevations[1] - $elevations[2]) / $northSouthDistance;
    $eastWestGradient = ($elevations[3] - $elevations[4]) / $eastWestDistance;

    return rad2deg(atan(sqrt(
        ($northSouthGradient ** 2) + ($eastWestGradient ** 2)
    )));
}

function getWeatherData($lat, $lng) {
    $url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lng" .
           "&current=temperature_2m,relative_humidity_2m,surface_pressure,wind_speed_10m,wind_gusts_10m,precipitation,precipitation_probability,rain,showers,soil_moisture_0_to_1cm" .
           "&daily=et0_fao_evapotranspiration,precipitation_sum" .
           "&timezone=auto";
    return fetchJson($url);
}

function getLocationName($lat, $lng) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lng";
    $data = fetchJson($url);
    if (!$data || !isset($data['address'])) return "Unknown Location";
    $address = $data['address'];
    $parts = [$address['road'] ?? null, $address['barangay'] ?? null, $address['city'] ?? null, $address['state'] ?? null];
    return implode(", ", array_filter($parts)) ?: "Unknown Location";
}

// --- 4. RISK CALCULATION MODELS ---

function calculateRunoffCoefficient(string $soilType, float $slope): float {
    // Exact mapping of WRB soil classes to runoff coefficients (aligned with risk_models.php)
    $runoffFactors = [
        // High Risk Soils (Clayey / Unstable) -> High Runoff
        'andosols' => 0.45, 'vertisols' => 0.45, 'planosols' => 0.45, 'stagnosols' => 0.45,
        'alisols' => 0.45, 'acrisols' => 0.45, 'plinthosols' => 0.45, 'leptosols' => 0.45,

        // Moderate Risk Soils -> Moderate Runoff
        'cambisols' => 0.35, 'luvisols' => 0.35, 'lixisols' => 0.35, 'umbrisols' => 0.35,
        'podzols' => 0.35, 'regosols' => 0.35, 'nitisols' => 0.35, 'gleysols' => 0.35, 'histosols' => 0.35,

        // Normal/Low Risk Soils -> Normal Runoff
        'chernozems' => 0.28, 'phaeozems' => 0.28, 'kastanozems' => 0.28, 'anthrosols' => 0.28,
        'technosols' => 0.28, 'fluvisols' => 0.28, 'ferralsols' => 0.28,

        // Minimal Risk Soils (Sandy / Well-drained) -> Low Runoff
        'arenosols' => 0.18, 'calcisols' => 0.18, 'gypsisols' => 0.18, 'durisols' => 0.18,
        'solonchaks' => 0.18, 'solonetz' => 0.18, 'cryosols' => 0.18
    ];

    $soilType = strtolower(trim($soilType));
    $baseCoefficient = $runoffFactors[$soilType] ?? 0.35; // Fallback to loam-equivalent (0.35)

    // Steeper slopes = more runoff
    $slopeFactor = 1.0;
    if ($slope > 30) $slopeFactor = 1.4;
    elseif ($slope > 20) $slopeFactor = 1.3;
    elseif ($slope > 15) $slopeFactor = 1.2;
    elseif ($slope > 10) $slopeFactor = 1.1;

    return min(0.95, $baseCoefficient * $slopeFactor);
}

function calculateInfiltrationRate(string $soilType, float $soilMoisture): float {
    // Aligned infiltration rates by soil type (mm/h)
    $infiltrationRates = [
        // High Risk / Low Infiltration
        'andosols' => 3.0, 'vertisols' => 3.0, 'planosols' => 3.0, 'stagnosols' => 3.0,
        'alisols' => 3.0, 'acrisols' => 3.0, 'plinthosols' => 3.0, 'leptosols' => 3.0,

        // Moderate Infiltration
        'cambisols' => 10.0, 'luvisols' => 10.0, 'lixisols' => 10.0, 'umbrisols' => 10.0,
        'podzols' => 10.0, 'regosols' => 10.0, 'nitisols' => 10.0, 'gleysols' => 10.0, 'histosols' => 10.0,

        // Normal Infiltration
        'chernozems' => 15.0, 'phaeozems' => 15.0, 'kastanozems' => 15.0, 'anthrosols' => 15.0,
        'technosols' => 15.0, 'fluvisols' => 15.0, 'ferralsols' => 15.0,

        // High Infiltration (Sandy)
        'arenosols' => 25.0, 'calcisols' => 25.0, 'gypsisols' => 25.0, 'durisols' => 25.0,
        'solonchaks' => 25.0, 'solonetz' => 25.0, 'cryosols' => 25.0
    ];

    $soilType = strtolower(trim($soilType));
    $maxInfiltration = $infiltrationRates[$soilType] ?? 10.0;

    $saturationReduction = 1.0 - $soilMoisture;
    return max(0.1, $maxInfiltration * $saturationReduction);
}

function calculateSoilSaturation(float $soilMoisture, float $rainRate, float $et0, float $infiltrationRate): float {
    $net = $rainRate - $et0;
    return ($net > 0) ? min(1.0, $soilMoisture + (max(0, $net - $infiltrationRate) / 100.0)) : max(0.0, $soilMoisture + ($net / 50.0));
}
