<?php
// Use absolute path based on document root to ensure it works from any location
require_once($_SERVER['DOCUMENT_ROOT'] . '/weather/config/database.php');

$result = mysqli_query($conn, "SELECT * FROM weather_logs ORDER BY id DESC LIMIT 20");
?>
<div class="container">
    
    <table class="table table-striped">
        <?php if(mysqli_num_rows($result) < 1):?>
            <h1 style="text-align: center;">No Data Found....</h1>
                    
        <?php else: ?>
            <tr>
                <p>Live Weather Logs (Stored in phpMyAdmin)</p>
                <th>Time</th>
                <th>Location</th>
                
                <th>Rain Rate</th>
                <th>Daily Rain</th>
                <th>Temp</th>
                
                <th>Soil Moisture</th>
                <th>Soil Type</th>
            </tr>
        
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['location_name'] ?? '') ?></td>
                
                <td><?= htmlspecialchars($row['rain_rate'] ?? '') ?> mm/h</td>
                <td><?= htmlspecialchars($row['daily_rain'] ?? '') ?> mm</td>
                <td><?= htmlspecialchars($row['temperature'] ?? '') ?>°C</td>
                
                <td><?= htmlspecialchars($row['soil_moisture'] ?? '') ?> m³/m³</td>
                <td><?= htmlspecialchars($row['soil_type'] ?? '') ?></td>
            </tr>
            <?php endwhile; ?>
        <?php endif ?>
    </table>
</div>
