<?php
    require_once("config/database.php");
    $result = mysqli_query($conn, "SELECT * FROM weather_logs ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">

<body>
<?php include("partials/header.html")?>  
    <div class="container-fluid">
        

        <div class="table-responsive">
            <table class="table table-striped table-hover table-sm">
                <?php if(mysqli_num_rows($result) < 1):?>
                    <h3 style="text-align: center;">No Data Found...</h3>
                <?php else: ?>
                    <h1 style="text-align: center; ">Full Log Details</h1>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Location</th>
                            <th>Lat</th>
                            <th>Lng</th>
                            <th>Temp (°C)</th>
                            <th>Hum (%)</th>
                            <th>Press (hPa)</th>
                            <th>Wind (km/h)</th>
                            <th>Gust (km/h)</th>
                            <th>Rain (mm/h)</th>
                            <th>Daily Rain (mm)</th>
                            <th>Precip Prob (%)</th>
                            <th>Rain Amt</th>
                            <th>Shower Amt</th>
                            <th>ET0 (mm)</th>
                            <th>Soil Moist</th>
                            <th>Slope (°)</th>
                            <th>Runoff Coeff</th>
                            <th>Infil Rate</th>
                            <th>Sat Index</th>
                            <th>Surface Flow</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['timestamp'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['location_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['latitude'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['longitude'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['temperature'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['humidity'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['pressure'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['wind_speed'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['wind_gusts'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['rain_rate'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['daily_rain'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['precip_probability'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['rain_amount'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['showers_amount'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['et0_evapotranspiration'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['soil_moisture'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['slope_angle'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['runoff_coefficient'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['infiltration_rate'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['soil_saturation_index'] ?? '') ?></td>
                            <td><?= htmlspecialchars($row['surface_flow'] ?? '') ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                <?php endif ?>
            </table>
        </div>
    </div>
</body>
</html>