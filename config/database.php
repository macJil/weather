<?php
// --- PHP BACKEND LOGIC ---
// 1. Connect to MySQL database
$conn = mysqli_connect("localhost", "root", "", "pma_weather");

// Check database connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

// 2. Ensure the location_cache table exists
$sql_cache = "CREATE TABLE IF NOT EXISTS location_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lat DECIMAL(10, 6),
    lng DECIMAL(10, 6),
    slope FLOAT,
    soil_type VARCHAR(50),
    UNIQUE KEY (lat, lng)
)";
mysqli_query($conn, $sql_cache);
?>