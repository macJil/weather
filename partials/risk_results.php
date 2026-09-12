<?php
// This partial expects $row, $score, and $category to be defined by the caller (save_weather.php)
if (!isset($row) || !isset($score) || !isset($category)) {
    echo '<div class="alert alert-warning">No analysis data available.</div>';
    return;
}
?>

<div class="card shadow-sm mb-3">
    <!-- Dynamic color header based on risk level -->
    <div class="card-header text-white" style="background-color: <?php echo $category['color']; ?>;">
        <strong>Risk Analysis: <?php echo htmlspecialchars($row['location_name']); ?></strong>
    </div>
    <div class="card-body">
        <h3 class="display-4 font-weight-bold" style="color: <?php echo $category['color']; ?>;">
            <?php echo $category['level']; ?>
        </h3>
        <p class="lead">Score: <?php echo $score; ?> / 3.0</p>
        
        <hr>
        
        <div class="row text-left" style="font-size: 0.85rem;">
            <div class="col-6">
                <strong>Conditions:</strong><br>
                Temp: <?php echo $row['temperature']; ?>°C<br>
                Humidity: <?php echo $row['humidity']; ?>%<br>
                Rain: <?php echo $row['rain_rate']; ?> mm/h
            </div>
            <div class="col-6">
                <strong>Hydrology:</strong><br>
                Saturation: <?php echo round((float)$row['soil_saturation_index'] * 100); ?>%<br>
                Slope: <?php echo $row['slope_angle']; ?>°<br>
                Soil: <?php echo ucfirst($row['soil_type']); ?>
            </div>
        </div>
    </div>
    <div class="card-footer text-muted" style="font-size: 0.75rem;">
        Last updated: <?php echo $row['timestamp']; ?>
    </div>
</div>
