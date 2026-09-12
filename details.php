<?php
    require_once("config/database.php");
    $result = mysqli_query($conn, "SELECT * FROM weather_logs ORDER BY id DESC");

?>
<!DOCTYPE html>
<html lang="en">

<body>
<?php include("partials/header.html")?>  
    <div class="container">
        
        <table class="table table-striped">
            <?php if(mysqli_num_rows($result) < 1):?>
                <h1 style="text-align: center;">No Data Found...</h1>
                        
            <?php else: ?>
               
                <tr>
                    <h1 style="text-align: center;">Full Locations Details</h1>
                    <th>Time</th>
                    <th>Location</th>
                    
                    <th>Rain Rate</th>
                    <th>Daily Rain</th>
                    <th>Temp</th>
                    
                    <th>Soil Moisture</th>
                
                </tr>
                
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
            <?php endif ?>
        </table>
    </div>
</body>
</html>