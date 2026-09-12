<?php
// Simple weather processing script
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once("config/database.php");
require_once("includes/helpers/api_fetchers.php");
require_once("includes/risk_models/landslide_risk.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lat'], $_POST['lng'])) {
    http_response_code(400);
    exit("Invalid request.");
}

$latitude = (float)$_POST['lat'];
$longitude = (float)$_POST['lng'];

// 1. Fetch data sequentially
$weatherData = getWeatherData($latitude, $longitude);
$locationName = getLocationName($latitude, $longitude);
$slopeDegrees = getSlopeAngle($latitude, $longitude) ?? 0;
$soilType = getSoilType($latitude, $longitude);
if ($soilType === 'Unknown') $soilType = 'loam';

$timestamp = date("m/d/Y, h:i:s A");

// 2. Process Logic
$current = $weatherData['current'] ?? [];
$daily   = $weatherData['daily'] ?? [];

$rainRate      = (float)($current['precipitation'] ?? 0);
$soilMoisture  = (float)($current['soil_moisture_0_to_1cm'] ?? 0);
$et0           = (float)($daily['et0_fao_evapotranspiration'][0] ?? 0);
$temp          = (float)($current['temperature_2m'] ?? 0);
$hum           = (float)($current['relative_humidity_2m'] ?? 0);
$pres          = (float)($current['surface_pressure'] ?? 0);
$wSpeed        = (float)($current['wind_speed_10m'] ?? 0);
$wGusts        = (float)($current['wind_gusts_10m'] ?? 0);
$precipProb    = (float)($current['precipitation_probability'] ?? 0);
$rainAmt       = (float)($current['rain'] ?? 0);
$showersAmt    = (float)($current['showers'] ?? 0);
$dailyRain     = (float)($daily['precipitation_sum'][0] ?? 0);

$runoffCoeff    = calculateRunoffCoefficient($soilType, $slopeDegrees);
$infiltration   = calculateInfiltrationRate($soilType, $soilMoisture);
$saturationIdx  = calculateSoilSaturation($soilMoisture, $rainRate, $et0, $infiltration);
$surfaceFlow    = $rainRate * $runoffCoeff;

// 3. Save to Database
$sql = "INSERT INTO weather_logs (timestamp, location_name, latitude, longitude, rain_rate, daily_rain, soil_moisture, slope_angle, soil_type, temperature, humidity, pressure, wind_speed, wind_gusts, precip_probability, rain_amount, showers_amount, et0_evapotranspiration, runoff_coefficient, infiltration_rate, surface_flow, soil_saturation_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die("Prepare failed: " . mysqli_error($conn));
}

// Bind 22 parameters: timestamp, location, lat, lng, rain, daily_rain, soil_moisture, slope, soil_type, temp, hum, pres, wind, gusts, precip_prob, rain_amt, shower_amt, et0, runoff, infil, flow, sat
mysqli_stmt_bind_param($stmt, "ssdddsdddddddddddddddd", 
    $timestamp, $locationName, $latitude, $longitude, $rainRate, $dailyRain,
    $soilMoisture, $slopeDegrees, $soilType, $temp, $hum, $pres, 
    $wSpeed, $wGusts, $precipProb, $rainAmt, $showersAmt, 
    $et0, $runoffCoeff, $infiltration, $surfaceFlow, $saturationIdx
);
if (!mysqli_stmt_execute($stmt)) {
    die("Execute failed: " . mysqli_stmt_error($stmt) . " | Query: " . $sql);
}

// 4. Prepare Variables for Risk Analysis Partial
$row = [
    'location_name' => $locationName,
    'temperature' => $temp,
    'humidity' => $hum,
    'rain_rate' => $rainRate,
    'soil_saturation_index' => $saturationIdx,
    'slope_angle' => $slopeDegrees,
    'soil_type' => $soilType,
    'timestamp' => $timestamp
];

$metrics = [
    'slope_angle' => $slopeDegrees, 'soil_type' => $soilType, 'soil_moisture' => $soilMoisture,
    'soil_saturation_index' => $saturationIdx, 'infiltration_rate' => $infiltration,
    'runoff_coefficient' => $runoffCoeff, 'surface_flow' => $surfaceFlow, 'rain_rate' => $rainRate,
    'daily_rain' => $dailyRain, 'precip_probability' => $precipProb,
    'rain_amount' => $rainAmt, 'showers_amount' => $showersAmt,
    'et0_evapotranspiration' => $et0, 'temperature' => $temp,
    'humidity' => $hum, 'pressure' => $pres,
    'wind_speed' => $wSpeed, 'wind_gusts' => $wGusts
];

$score = calculateComprehensiveRisk($metrics);
$category = getLandslideRiskCategory($score);

// 5. Output both HTML components separated by a delimiter
include("partials/risk_results.php");
echo "<!--SPLIT-->";
include("partials/logs_table.php");
