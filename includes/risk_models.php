<?php
// high = 3, moderate = 2, normal = 1, low = 0

//analyze soil type
function soil_type_analysis(string $soil_type): int|string
{
    $risk_levels = [
        'andosols' => 3,
        'vertisols' => 3,
        'planosols' => 3,
        'stagnosols' => 3,
        'alisols' => 3,
        'acrisols' => 3,
        'plinthosols' => 3,
        'leptosols' => 3,
        'cambisols' => 2,
        'luvisols' => 2,
        'lixisols' => 2,
        'umbrisols' => 2,
        'podzols' => 2,
        'regosols' => 2,
        'nitisols' => 2,
        'gleysols' => 2,
        'histosols' => 2,
        'chernozems' => 1,
        'phaeozems' => 1,
        'kastanozems' => 1,
        'anthrosols' => 1,
        'technosols' => 1,
        'fluvisols' => 1,
        'ferralsols' => 1,
        'arenosols' => 0,
        'calcisols' => 0,
        'gypsisols' => 0,
        'durisols' => 0,
        'solonchaks' => 0,
        'solonetz' => 0,
        'cryosols' => 0
    ];

    $soil_type = strtolower(trim($soil_type));

    return $risk_levels[$soil_type] ?? 'Unknown';
}

//Analze slope angle
function slope_analyze(float $slope): int|string 
{
    if($slope <= 15){
        return 0;
    } elseif ($slope <=25){
        return 1;
    } elseif($slope <= 35){
        return 2;
    } elseif($slope > 35){
        return 3;
    } else {
        return "Uknown";
    }
}

//analyze soil moisture
function analyze_soil_moisture(float $soil): int|string{
    if($soil <= 0.15){
        return 0;
    } elseif ($soil <=0.30){
        return 1;
    } elseif($soil <= 0.40){
        return 2;
    } elseif($soil > 0.40){
        return 3;
    } else {
        return "Uknown";
    }
}
//analyze Daily rain
function analyze_daily_rain(float $daily_rain): int|string {
    if($daily_rain <= 0.15){
        return 0;
    } elseif ($daily_rain <=0.30){
        return 1;
    } elseif($daily_rain <= 0.40){
        return 2;
    } elseif($daily_rain > 0.40){
        return 3;
    } else {
        return "Uknown";
    }
}
//analyze rain rate
function analyze_rain_rate(float $rain_rate): int|string{
    if($rain_rate <= 2.5){
        return 0;
    } elseif ($rain_rate <=7.5){
        return 1;
    } elseif($rain_rate <= 15.0){
        return 2;
    } elseif($rain_rate > 15.0){
        return 3;
    } else {
        return "Uknown";
    }
}
?>