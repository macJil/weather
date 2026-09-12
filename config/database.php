<?php
// --- PHP BACKEND LOGIC ---
// 1. Connect to MySQL database
$conn = mysqli_connect("localhost", "root", "", "pma_weather");

// Check database connection
if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}




?>