<?php

/**
 * Fetch JSON data from a URL using file_get_contents and stream_context
 */
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
 * Get Slope Angle from OpenTopoData API (SRTM 90m)
 */
function getSlopeAngle($latitude, $longitude) {
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

/**
 * Get Weather Data from Open-Meteo API
 */
function getWeatherData($lat, $lng) {
    $url = "https://api.open-meteo.com/v1/forecast?latitude=$lat&longitude=$lng" .
           "&current=temperature_2m,relative_humidity_2m,surface_pressure,wind_speed_10m,wind_gusts_10m,precipitation,precipitation_probability,rain,showers,soil_moisture_0_to_1cm" .
           "&daily=et0_fao_evapotranspiration,precipitation_sum" .
           "&timezone=auto";
    
    return fetchJson($url);
}

/**
 * Get Location Name from Nominatim (OpenStreetMap) API
 */
function getLocationName($lat, $lng) {
    $url = "https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=$lat&lon=$lng";
    $data = fetchJson($url);
    
    if (!$data || !isset($data['address'])) {
        return "Unknown Location";
    }

    $address = $data['address'];
    $locationParts = [
        $address['road'] ?? null,
        $address['barangay'] ?? null,
        $address['quarter'] ?? null,
        $address['village'] ?? null,
        $address['neighbourhood'] ?? null,
        $address['suburb'] ?? null,
        $address['city'] ?? null,
        $address['municipality'] ?? null,
        $address['town'] ?? null,
        $address['state'] ?? null
    ];

    return implode(", ", array_filter($locationParts)) ?: "Unknown Location";
}

// === RUNOFF & HYDRAULICS CALCULATIONS ===

/**
 * Calculate Runoff Coefficient based on soil type and slope
 */
function calculateRunoffCoefficient(string $soilType, float $slope): float {
    $soilRunoffFactors = [
        'andosols' => 0.45, 'vertisols' => 0.40, 'clay' => 0.45, 'clay loam' => 0.40,
        'sandy clay' => 0.35, 'silty clay' => 0.42, 'loam' => 0.35,
        'sandy loam' => 0.30, 'silt loam' => 0.35, 'silt' => 0.32,
        'sandy' => 0.25, 'peaty' => 0.30, 'organic' => 0.28
    ];

    $soilType = strtolower(trim($soilType));
    $baseCoefficient = $soilRunoffFactors[$soilType] ?? 0.35;

    $slopeFactor = 1.0;
    if ($slope > 30) $slopeFactor = 1.4;
    elseif ($slope > 20) $slopeFactor = 1.3;
    elseif ($slope > 15) $slopeFactor = 1.2;
    elseif ($slope > 10) $slopeFactor = 1.1;

    return min(0.95, $baseCoefficient * $slopeFactor);
}

/**
 * Calculate Infiltration Rate (mm/h)
 */
function calculateInfiltrationRate(string $soilType, float $soilMoisture): float {
    $soilInfiltration = [
        'sandy' => 25.0, 'sandy loam' => 15.0, 'loamy sand' => 20.0,
        'loam' => 10.0, 'silt loam' => 8.0, 'silty clay loam' => 6.0,
        'clay loam' => 5.0, 'clay' => 3.0, 'sandy clay' => 4.0,
        'silty clay' => 2.5, 'peaty' => 20.0, 'organic' => 18.0
    ];

    $soilType = strtolower(trim($soilType));
    $maxInfiltration = $soilInfiltration[$soilType] ?? 10.0;

    $saturationReduction = 1.0 - $soilMoisture;
    return max(0.1, $maxInfiltration * $saturationReduction);
}

/**
 * Calculate Soil Saturation Index (0-1 scale)
 */
function calculateSoilSaturation(float $soilMoisture, float $rainRate, float $et0, float $infiltrationRate): float {
    $netWater = $rainRate - $et0;

    if ($netWater > 0) {
        $waterAfterInfiltration = max(0, $netWater - $infiltrationRate);
        return min(1.0, $soilMoisture + ($waterAfterInfiltration / 100.0));
    } else {
        return max(0.0, $soilMoisture + ($netWater / 50.0));
    }
}
