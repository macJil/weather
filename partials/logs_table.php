<?php
// Use absolute path based on document root to ensure it works from any location
require_once($_SERVER['DOCUMENT_ROOT'] . '/weather/config/database.php');

$result = mysqli_query($conn, "SELECT * FROM weather_logs ORDER BY id DESC LIMIT 20");
?>
<div class="container">
    <p style="margin-bottom: 10px;">Live Weather Logs (Stored in phpMyAdmin)</p>
    <table class="table table-striped">
        <?php if(mysqli_num_rows($result) < 1):?>
            <tr><td colspan="6" style="text-align: center;">No Data Found...</td></tr>
        <?php else: ?>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Location</th>
                    <th>Rain Rate</th>
                    <th>Daily Rain</th>
                    <th>Temp</th>
                    <th>Soil Moisture</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['location_name'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['rain_rate'] ?? '') ?> mm/h</td>
                    <td><?= htmlspecialchars($row['daily_rain'] ?? '') ?> mm</td>
                    <td><?= htmlspecialchars($row['temperature'] ?? '') ?>°C</td>
                    <td><?= htmlspecialchars($row['soil_moisture'] ?? '') ?> m³/m³</td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        <?php endif ?>
    </table>
</div>
