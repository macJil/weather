<?php
/**
 * RISK MODELS: risk_models.php
 * 
 * Provides functions to analyze 18 environmental, meteorological, and 
 * hydrological factors to calculate a comprehensive, weighted landslide 
 * risk score for Baguio City.
 * 
 * Individual Risk levels: 0 (Low), 1 (Normal), 2 (Moderate), 3 (High)
 */

// ==========================================
// 1. TERRAIN ANALYSIS
// ==========================================

/**
 * Returns soil type vulnerability (0 to 3).
 */
function soil_type_analysis(string $soil_type): int {
    $risk_levels = [
        'andosols' => 3, 'vertisols' => 3, 'planosols' => 3, 'stagnosols' => 3,
        'alisols' => 3, 'acrisols' => 3, 'plinthosols' => 3, 'leptosols' => 3,
        'cambisols' => 2, 'luvisols' => 2, 'lixisols' => 2, 'umbrisols' => 2,
        'podzols' => 2, 'regosols' => 2, 'nitisols' => 2, 'gleysols' => 2, 'histosols' => 2,
        'chernozems' => 1, 'phaeozems' => 1, 'kastanozems' => 1, 'anthrosols' => 1,
        'technosols' => 1, 'fluvisols' => 1, 'ferralsols' => 1,
        'arenosols' => 0, 'calcisols' => 0, 'gypsisols' => 0, 'durisols' => 0,
        'solonchaks' => 0, 'solonetz' => 0, 'cryosols' => 0
    ];
    $soil_type = strtolower(trim($soil_type));
    return $risk_levels[$soil_type] ?? 1; // Default to Normal (1)
}

/**
 * Returns terrain slope risk (0 to 3).
 */
function slope_analyze(float $slope): int {
    if ($slope <= 15) return 0;
    if ($slope <= 25) return 1;
    if ($slope <= 35) return 2;
    return 3;
}

// ==========================================
// 2. HYDROLOGICAL SATURATION ANALYSIS
// ==========================================

/**
 * Returns soil moisture risk (0 to 3).
 */
function analyze_soil_moisture(float $soil): int {
    if ($soil <= 0.15) return 0;
    if ($soil <= 0.30) return 1;
    if ($soil <= 0.40) return 2;
    return 3;
}

/**
 * Returns infiltration rate risk (0 to 3).
 * Slow infiltration on saturated soil means high surface flow, 
 * whereas fast infiltration on clay means high internal pore pressure.
 */
function analyze_infiltration_rate(float $rate): int {
    if ($rate >= 15.0) return 0;
    if ($rate >= 6.0 && $rate < 15.0) return 1;
    if ($rate >= 1.0 && $rate < 6.0) return 2;
    return 3;
}

/**
 * Returns runoff coefficient risk (0 to 3).
 */
function analyze_runoff(float $runoff): int {
    if ($runoff < 0.25) return 0;
    if ($runoff >= 0.25 && $runoff <= 0.40) return 1;
    if ($runoff > 0.40 && $runoff <= 0.60) return 2;
    return 3;
}

/**
 * Returns saturation index risk (0 to 3).
 */
function analyze_saturation_rate(float $saturation): int {
    if ($saturation >= 0.30 && $saturation <= 0.55) return 0;
    if ($saturation > 0.55 && $saturation <= 0.75) return 1;
    if ($saturation > 0.75 && $saturation <= 0.90) return 2;
    return 3;
}

/**
 * Returns surface flow risk (0 to 3).
 */
function analyze_surface_flow(float $surface): int {
    if ($surface < 0.15) return 0;
    if ($surface < 0.35) return 1;
    if ($surface < 0.66) return 2;
    return 3;
}

// ==========================================
// 3. METEOROLOGICAL RAIN TRIGGERS
// ==========================================

/**
 * Returns rain rate risk (0 to 3).
 */
function analyze_rain_rate(float $rain_rate): int {
    if ($rain_rate <= 2.5) return 0;
    if ($rain_rate <= 7.5) return 1;
    if ($rain_rate <= 15.0) return 2;
    return 3;
}

/**
 * Returns daily rain risk (0 to 3).
 * Uses millimeter values for daily precipitation.
 */
function analyze_daily_rain(float $daily_rain): int {
    if ($daily_rain <= 5.0) return 0;
    if ($daily_rain <= 15.0) return 1;
    if ($daily_rain <= 30.0) return 2;
    return 3;
}

/**
 * Returns precipitation probability risk (0 to 3).
 */
function analyze_precipitation_prob(float $precipitation): int {
    if ($precipitation < 21) return 0;
    if ($precipitation < 51) return 1;
    if ($precipitation < 81) return 2;
    return 3;
}

/**
 * Returns rain amount risk (0 to 3).
 */
function analyze_rain_amount(float $amount): int {
    if ($amount < 2) return 0;
    if ($amount < 10) return 1;
    if ($amount < 31) return 2;
    return 3;
}

/**
 * Returns shower amount risk (0 to 3).
 */
function analyze_shower_amount(float $shower): int {
    if ($shower < 4) return 0;
    if ($shower < 10) return 1;
    if ($shower < 33) return 2;
    return 3;
}

/**
 * Returns evapotranspiration (ET0) risk (0 to 3).
 * Higher ET0 dries soil faster (lower risk); low ET0 retains water (higher risk).
 */
function analyze_et0(float $et0): int {
    if ($et0 >= 3.0) return 0;
    if ($et0 >= 1.5 && $et0 < 3.0) return 1;
    if ($et0 >= 0.5 && $et0 < 1.5) return 2;
    return 3;
}

// ==========================================
// 4. ATMOSPHERIC CONTEXT
// ==========================================

/**
 * Returns temperature risk (0 to 3).
 */
function analyze_temperature(float $temperature): int {
    if ($temperature <= 20) return 0;
    if ($temperature <= 28) return 1;
    if ($temperature <= 36) return 2;
    return 3;
}

/**
 * Returns humidity risk (0 to 3).
 */
function analyze_humidity(int $humidity): int {
    if ($humidity >= 45 && $humidity <= 65) return 0;
    if ($humidity > 65 && $humidity <= 75) return 1;
    if ($humidity > 75 && $humidity <= 85) return 2;
    return 3;
}

/**
 * Returns barometric pressure risk (0 to 3).
 * Low pressure indicates storm systems (higher risk).
 */
function analyze_pressure(float $pressure): int {
    if ($pressure > 1015) return 0;
    if ($pressure >= 1009 && $pressure <= 1015) return 1;
    if ($pressure >= 1000 && $pressure <= 1008) return 2;
    return 3;
}

/**
 * Returns wind speed risk (0 to 3).
 */
function analyze_wind(float $wind): int {
    if ($wind < 20) return 0;
    if ($wind < 39) return 1;
    if ($wind < 51) return 2;
    return 3;
}

/**
 * Returns wind gusts risk (0 to 3).
 */
function analyze_gust(float $gust): int {
    if ($gust < 30) return 0;
    if ($gust < 50) return 1;
    if ($gust < 76) return 2;
    return 3;
}

// ==========================================
// 5. THE HIERARCHICAL WEIGHTED RISK ENGINE
// ==========================================

/**
 * Calculates a comprehensive landslide risk score (0.0 to 3.0) using 
 * all 18 individual environmental analyzer functions.
 */
function calculateComprehensiveRisk(array $metrics): float {
    // --- CATEGORY 1: TERRAIN VULNERABILITY (Weight: 35%) ---
    $slopeScore = slope_analyze($metrics['slope_angle']);
    $soilScore  = soil_type_analysis($metrics['soil_type']);
    $terrainIndex = ($slopeScore * 0.7) + ($soilScore * 0.3);

    // --- CATEGORY 2: HYDROLOGICAL SATURATION (Weight: 30%) ---
    $moistureScore   = analyze_soil_moisture($metrics['soil_moisture']);
    $satScore        = analyze_saturation_rate($metrics['soil_saturation_index']);
    $infiltration    = analyze_infiltration_rate($metrics['infiltration_rate']);
    $runoff          = analyze_runoff($metrics['runoff_coefficient']);
    $surfaceFlow     = analyze_surface_flow($metrics['surface_flow']);
    $hydrologyIndex = ($moistureScore + $satScore + $infiltration + $runoff + $surfaceFlow) / 5;

    // --- CATEGORY 3: RAIN TRIGGER (Weight: 25%) ---
    $rainRateScore   = analyze_rain_rate($metrics['rain_rate']);
    $dailyRainScore  = analyze_daily_rain($metrics['daily_rain']);
    $precipProbScore = analyze_precipitation_prob($metrics['precip_probability']);
    $rainAmtScore    = analyze_rain_amount($metrics['rain_amount']);
    $showerScore     = analyze_shower_amount($metrics['showers_amount']);
    $et0Score        = analyze_et0($metrics['et0_evapotranspiration']);
    $triggerIndex = ($rainRateScore + $dailyRainScore + $precipProbScore + $rainAmtScore + $showerScore + $et0Score) / 6;

    // --- CATEGORY 4: ATMOSPHERIC CONTEXT (Weight: 10%) ---
    $tempScore     = analyze_temperature($metrics['temperature']);
    $humidityScore = analyze_humidity($metrics['humidity']);
    $pressureScore = analyze_pressure($metrics['pressure']);
    $windScore     = analyze_wind($metrics['wind_speed']);
    $gustScore     = analyze_gust($metrics['wind_gusts']);
    $atmosphereIndex = ($tempScore + $humidityScore + $pressureScore + $windScore + $gustScore) / 5;

    // === WEIGHTED RISK COMBINATION ===
    $finalScore = ($terrainIndex * 0.35) + 
                  ($hydrologyIndex * 0.30) + 
                  ($triggerIndex * 0.25) + 
                  ($atmosphereIndex * 0.10);

    return round($finalScore, 2);
}

/**
 * Translates the final weighted score (0.0 - 3.0) into 4 human-readable categories.
 */
function getLandslideRiskCategory(float $finalScore): array {
    if ($finalScore <= 0.75) {
        return [
            'level' => 'Low',
            'color' => 'green',       // Safe Green
            'badge' => 'bg-success'
        ];
    } elseif ($finalScore <= 1.50) {
        return [
            'level' => 'Normal',
            'color' => 'blue',       // Moderate Blue
            'badge' => 'bg-info'
        ];
    } elseif ($finalScore <= 2.25) {
        return [
            'level' => 'Moderate',
            'color' => 'orange',       // Warning Orange
            'badge' => 'bg-warning'
        ];
    } else {
        return [
            'level' => 'High',
            'color' => 'red',       // Danger Red
            'badge' => 'bg-danger'
        ];
    }
}
?>