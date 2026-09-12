<?php
require_once("config/database.php");
?>

<!DOCTYPE html>
<html lang="en">


<body class="body">
<?php include("partials/header.html") ?>  
  <div class="container text-center">
    <div class="row align-items-start">
        <div class="col-lg-6" style="margin-top: 10px;">
          <span>Offline city map · click to inspect</span>
          <div id="map"></div>
        </div>
        <div class="col-lg-6">
           <p>Risk Analysis will show here</p>
        </div>
    </div>
  </div>
  <div class="container-fluid">
    
    <div class="container">
      <div id="weather-logs" class="table-responsive " >
        <?php include("partials/logs_table.php")?>
      </div>
    </div>
  </div>
  
            

    


  <script src="/weather/assets/js/leaflet.js"></script>
 <?php include("partials/map_ui.html")?>
  
  
</body>
</html>