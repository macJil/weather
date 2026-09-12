<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("../config/database.php");
require_once("../includes/api_helpers.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lat'], $_POST['lng'])) {
    http_response_code(400);
    exit("Invalid weather log request.");
}

$lat = (float)$_POST['lat'];
$lng = (float)$_POST['lng'];

// 1. Fetch Location Name from Nominatim
$locationName = getLocationName($lat, $lng);
$timestamp = date("m/d/Y, h:i:s A"); // Format: "9/13/2026, 12:00:00 AM"

// 2. Fetch Weather Data from Open-Meteo
$weather = getWeatherData($lat, $lng);
if (!$weather) {
    http_response_code(500);
    exit("Unable to fetch weather data from Open-Meteo.");
}

// Extract current weather
$current = $weather['current'] ?? [];
$daily   = $weather['daily'] ?? [];

$rainRate          = (float)($current['precipitation'] ?? 0);
$soilMoisture      = (float)($current['soil_moisture_0_to_1cm'] ?? 0);
$temperature       = (float)($current['temperature_2m'] ?? 0);
$humidity          = (float)($current['relative_humidity_2m'] ?? 0);
$pressure          = (float)($current['surface_pressure'] ?? 0);
$windSpeed         = (float)($current['wind_speed_10m'] ?? 0);
$windGusts         = (float)($current['wind_gusts_10m'] ?? 0);
$precipProbability = (float)($current['precipitation_probability'] ?? 0);
$rainAmount        = (float)($current['rain'] ?? 0);
$showersAmount     = (float)($current['showers'] ?? 0);

// Extract daily weather
$dailyRain = (float)($daily['precipitation_sum'][0] ?? 0);
$et0       = (float)($daily['et0_fao_evapotranspiration'][0] ?? 0);

// 3. Fetch Terrain & Soil Info
$slope = getSlopeAngle($lat, $lng) ?? 0;
$soilType = getSoilType($lat, $lng);
if ($soilType === 'Unknown') {
    $soilType = 'loam'; // Default fallback
}

// 4. Calculate Risk Metrics
$runoffCoefficient = calculateRunoffCoefficient($soilType, $slope);
$infiltrationRate = calculateInfiltrationRate($soilType, $soilMoisture);
$soilSaturation = calculateSoilSaturation($soilMoisture, $rainRate, $et0, $infiltrationRate);
$surfaceFlow = $rainRate * $runoffCoefficient;

// 5. Save to Database
$stmt = mysqli_prepare($conn, "INSERT INTO weather_logs (
        timestamp, location_name, latitude, longitude, rain_rate, daily_rain,
        soil_moisture, slope_angle, soil_type, temperature, humidity, pressure,
        wind_speed, wind_gusts, precip_probability, rain_amount, showers_amount,
        et0_evapotranspiration, runoff_coefficient, infiltration_rate,
        surface_flow, soil_saturation_index
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    exit("Database Error: Unable to prepare weather log. " . mysqli_error($conn));
}

mysqli_stmt_bind_param(
    $stmt,
    "ssddddddsddddddddddddd",
    $timestamp, $locationName, $lat, $lng, $rainRate, $dailyRain,
    $soilMoisture, $slope, $soilType, $temperature, $humidity, $pressure,
    $windSpeed, $windGusts, $precipProbability, $rainAmount, $showersAmount,
    $et0, $runoffCoefficient, $infiltrationRate,
    $surfaceFlow, $soilSaturation
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    exit("Database Error: Unable to save weather log. " . mysqli_error($conn));
}

// 6. Return the updated logs table
include("../partials/logs_table.php");
