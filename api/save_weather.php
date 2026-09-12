<?php
/**
 * API ENDPOINT: save_weather.php
 * 
 * This script serves as the "brain" for the weather monitor.
 * When the user clicks the map, this script:
 * 1. Takes the location coordinates.
 * 2. Fetches weather, terrain, and soil data from external APIs.
 * 3. Calculates the landslide risk based on that data.
 * 4. Saves the results to the database.
 * 5. Returns a refreshed table of logs to the browser.
 */

// Include necessary files
require_once("../config/database.php");
require_once("../includes/api_helpers.php");

// --- STEP 1: VALIDATION ---
// Check if this is a valid POST request containing latitude and longitude
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['lat'], $_POST['lng'])) {
    http_response_code(400);
    exit("Invalid request: Latitude and Longitude are required.");
}

$latitude = (float)$_POST['lat'];
$longitude = (float)$_POST['lng'];

// --- STEP 2: FETCH EXTERNAL DATA ---
// Get descriptive location name (e.g., "Baguio City, Benguet")
$locationName = getLocationName($latitude, $longitude);
$timestamp = date("m/d/Y, h:i:s A"); 

// Get live weather metrics
$weatherData = getWeatherData($latitude, $longitude);
if (!$weatherData) {
    http_response_code(500);
    exit("Error: Could not fetch weather data from Open-Meteo.");
}

// --- STEP 3: PREPARE DATA FOR CALCULATION ---
// Extract needed values from the API response
$current = $weatherData['current'] ?? [];
$daily   = $weatherData['daily'] ?? [];

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

$dailyRain = (float)($daily['precipitation_sum'][0] ?? 0);
$evapotranspiration = (float)($daily['et0_fao_evapotranspiration'][0] ?? 0);

// Get Terrain/Soil info from APIs
$slopeDegrees = getSlopeAngle($latitude, $longitude) ?? 0;
$soilType = getSoilType($latitude, $longitude);
if ($soilType === 'Unknown') {
    $soilType = 'loam'; // Fallback to a neutral type
}

// --- STEP 4: CALCULATE RISK ---
$runoffCoeff    = calculateRunoffCoefficient($soilType, $slopeDegrees);
$infiltration   = calculateInfiltrationRate($soilType, $soilMoisture);
$saturationIdx  = calculateSoilSaturation($soilMoisture, $rainRate, $evapotranspiration, $infiltration);
$surfaceFlow    = $rainRate * $runoffCoeff;

// --- STEP 5: SAVE TO DATABASE ---
$sql = "INSERT INTO weather_logs (
        timestamp, location_name, latitude, longitude, rain_rate, daily_rain,
        soil_moisture, slope_angle, soil_type, temperature, humidity, pressure,
        wind_speed, wind_gusts, precip_probability, rain_amount, showers_amount,
        et0_evapotranspiration, runoff_coefficient, infiltration_rate,
        surface_flow, soil_saturation_index
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    http_response_code(500);
    exit("Database Error: Could not prepare query.");
}

mysqli_stmt_bind_param(
    $stmt,
    "ssddddddsddddddddddddd",
    $timestamp, $locationName, $latitude, $longitude, $rainRate, $dailyRain,
    $soilMoisture, $slopeDegrees, $soilType, $temperature, $humidity, $pressure,
    $windSpeed, $windGusts, $precipProbability, $rainAmount, $showersAmount,
    $evapotranspiration, $runoffCoeff, $infiltration,
    $surfaceFlow, $saturationIdx
);

if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    exit("Database Error: Could not save weather log.");
}

// --- STEP 6: RETURN UI ---
// Include the table partial to update the frontend
include("../partials/logs_table.php");
