<?php
// Start session to store the history of live status entries
session_start();

// Handle incoming POST request from AJAX (Initial Click & Automatic Interval Updates)
if (isset($_POST['lat']) && isset($_POST['lng'])) {
    
    // Store current location metrics into an associative array
    $entry = array(
        'timestamp'     => htmlspecialchars($_POST['timestamp']),
        'location_name' => htmlspecialchars($_POST['name']),
        'latitude'      => (float)$_POST['lat'],
        'longitude'     => (float)$_POST['lng'],
        'rain_rate'     => (float)$_POST['rainRate'],
        'daily_rain'    => (float)$_POST['dailyRain'],
        'soil_moisture' => (float)$_POST['soilMoisture'],
        'slope_angle'   => (int)$_POST['slope'],
        'soil_type'     => htmlspecialchars($_POST['soilType']),
        'vegetation'    => htmlspecialchars($_POST['vegetation'])
    );

    // Initialize history array if not set
    if (!isset($_SESSION['history'])) {
        $_SESSION['history'] = array();
    }

    // Add new metric entry to the top of the history list
    array_unshift($_SESSION['history'], $entry);

    // Output updated table back to JavaScript
    ?>
    <h3>Live Weather Status History <span style="font-size:0.7em; color:green; font-weight:normal;">(Auto-Updating Every 10s...)</span></h3>
    <table>
        <thead>
            <tr>
                <th>Time</th>
                <th>Location</th>
                <th>Latitude</th>
                <th>Longitude</th>
                <th>Rain Rate</th>
                <th>Daily Rain</th>
                <th>Soil Moisture</th>
                <th>Slope</th>
                <th>Soil Type</th>
                <th>Vegetation</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['history'] as $item): ?>
                <tr>
                    <td><?php echo $item['timestamp']; ?></td>
                    <td><?php echo $item['location_name']; ?></td>
                    <td><?php echo $item['latitude']; ?></td>
                    <td><?php echo $item['longitude']; ?></td>
                    <td><?php echo $item['rain_rate']; ?> mm/h</td>
                    <td><?php echo $item['daily_rain']; ?> mm/day</td>
                    <td><?php echo $item['soil_moisture']; ?> m³/m³</td>
                    <td><?php echo $item['slope_angle']; ?>°</td>
                    <td><?php echo $item['soil_type']; ?></td>
                    <td><?php echo $item['vegetation']; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
    exit; // Stop script execution after responding to AJAX
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>PMA Road Real-Time Weather Monitoring</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <style>
    body { font-family: sans-serif; margin: 20px; background: #f8f9fa; }
    #map { height: 400px; width: 100%; border-radius: 8px; }
    #info-box { margin-top: 15px; padding: 15px; background: #ffffff; border-left: 5px solid #2b8a3e; border-radius: 5px; overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 0.9em; }
    th { background-color: #2b8a3e; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
    tr:first-child { background-color: #e8f5e9; font-weight: bold; } /* Highlight latest record */
  </style>
</head>
<body>

  <h2>Fort Del Pilar (PMA Road) Auto-Updating Weather Monitor</h2>
  <div id="map"></div>
  <div id="info-box">Click anywhere on the map to start live metric tracking.</div>

  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script>
    var pmaBounds = [[16.3550, 120.6120], [16.3800, 120.6350]];
    var map = L.map('map', {
      center: [16.3675, 120.6225],
      zoom: 16,
      minZoom: 15,
      maxZoom: 19,
      maxBounds: pmaBounds,
      maxBoundsViscosity: 1.0
    });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    var activeMarker = null;
    var liveUpdateInterval = null; // Holds the timer reference

    // Function to fetch weather metrics and post to PHP
    function updateWeatherData(lat, lng, placeName) {
      var now = new Date();
      var currentTimestamp = now.toLocaleString();

      var weatherUrl = 'https://api.open-meteo.com/v1/forecast?latitude=' + lat + 
                       '&longitude=' + lng + 
                       '&current=precipitation,soil_moisture_0_to_1cm&daily=precipitation_sum&timezone=auto';

      fetch(weatherUrl)
        .then(function(r) { return r.json(); })
        .then(function(weatherData) {
          var rainRate = weatherData.current.precipitation || 0;
          var dailyRain = weatherData.daily.precipitation_sum[0] || 0;
          var soilMoisture = weatherData.current.soil_moisture_0_to_1cm || 0;

          var slope = 28; 
          var soilType = "Clay Loam";
          var vegetation = "Moderate Pine Forest";

          var postBody = 'lat=' + encodeURIComponent(lat) +
                         '&lng=' + encodeURIComponent(lng) +
                         '&name=' + encodeURIComponent(placeName) +
                         '&timestamp=' + encodeURIComponent(currentTimestamp) +
                         '&rainRate=' + encodeURIComponent(rainRate) +
                         '&dailyRain=' + encodeURIComponent(dailyRain) +
                         '&soilMoisture=' + encodeURIComponent(soilMoisture) +
                         '&slope=' + encodeURIComponent(slope) +
                         '&soilType=' + encodeURIComponent(soilType) +
                         '&vegetation=' + encodeURIComponent(vegetation);

          return fetch('<?php echo $_SERVER['PHP_SELF']?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: postBody
          });
        })
        .then(function(response) {
          if (!response.ok) { throw new Error("PHP script error"); }
          return response.text();
        })
        .then(function(phpHtml) {
          document.getElementById('info-box').innerHTML = phpHtml;
        })
        .catch(function(error) {
          console.error("Auto-update error:", error);
        });
    }

    map.on('click', function(event) {
      var lat = event.latlng.lat;
      var lng = event.latlng.lng;

      // Clear any existing automatic timer when a new location is selected
      if (liveUpdateInterval !== null) {
        clearInterval(liveUpdateInterval);
      }

      if (activeMarker !== null) {
        activeMarker.setLatLng(event.latlng);
      } else {
        activeMarker = L.marker(event.latlng).addTo(map);
      }

      document.getElementById('info-box').innerText = "Fetching initial location and weather data...";

      var geocodeUrl = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=' + lat + '&lon=' + lng;

      fetch(geocodeUrl)
        .then(function(r) { return r.json(); })
        .then(function(geoData) {
          var placeName = geoData.display_name || "PMA Road Point";

          // 1. Initial immediate update on click
          updateWeatherData(lat, lng, placeName);

          // 2. Set interval to re-fetch weather data automatically every 10 seconds (10000ms)
          liveUpdateInterval = setInterval(function() {
            updateWeatherData(lat, lng, placeName);
          }, 10000); 
        });
    });
  </script>
</body>
</html>