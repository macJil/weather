# Baguio Weather Monitor

A real-time landslide risk analysis and weather monitoring system tailored for Baguio City, Philippines. The application combines live meteorological data with local terrain and soil characteristics to provide dynamic risk assessments.

## Project Overview

- **Purpose:** Monitor rainfall, soil moisture, and local terrain conditions to estimate landslide susceptibility.
- **Target Area:** Baguio City (bounded by 16.30, 120.50 to 16.50, 120.72).
- **Core Stack:** PHP (Vanilla), MySQL, JavaScript (Leaflet.js), Bootstrap 5.
- **External APIs:**
  - **Open-Meteo:** Current weather and daily evapotranspiration (ET0).
  - **OpenTopoData:** SRTM 90m elevation data for slope calculation.
  - **SoilGrids:** Global soil information for soil type classification.
  - **Nominatim (OSM):** Reverse geocoding for location naming.

## Architecture

### Backend (PHP)
- `config.php`: Central database connection (`pma_weather` database).
- `components/weather-save.php`: Main logic for processing weather logs. It calculates:
  - **Runoff Coefficient:** Based on soil type and slope.
  - **Infiltration Rate:** Adjusted for soil saturation.
  - **Soil Saturation Index:** A water balance model (Rain - ET0 - Infiltration).
  - **Surface Flow:** Estimated runoff volume.
- `components/risk_analyze.php`: Risk threshold definitions for soil types, slope angles, and moisture levels.
- `components/weather_logs.php`: Partial view for displaying recent data entries.

### Frontend (JavaScript/CSS)
- `index.php`: Main dashboard layout.
- `components/map.html`: Interactive Leaflet map with Baguio city boundaries and auto-refresh (30s).
- **Offline Support:** Includes local map tiles (`map-tiles/`) for zoom levels 12-18 and local CSS/JS assets to function in low-connectivity environments.

### Data & GIS
- `data/raster/`: Local DEM (baguio_slope.tif) for terrain analysis.
- `data/vector/`: GeoJSON layers for barangay boundaries and historical landslide data.
- `assets/hazard-images/`: Static hazard map tiles derived from official KML/KMZ sources.

## Building and Running

1.  **Environment:** Requires XAMPP or a similar LAMP stack.
2.  **Database:**
    - Create a database named `pma_weather`.
    - Expected table: `weather_logs` (schema inferred from `weather-save.php` and `weather_logs.php`).
3.  **Deployment:** Place the project folder in `htdocs/weather/`.
4.  **Access:** Navigate to `http://localhost/weather/index.php`.

## Development Conventions

- **Database:** Always use `mysqli_prepare` and `mysqli_stmt_bind_param` for data insertion to prevent SQL injection (as seen in `weather-save.php`).
- **GIS Integration:** Prefer EPSG:4326 (WGS84) for all spatial data.
- **UI:** Follow Bootstrap 5 utility classes for layout and responsiveness.

## TODO / Future Improvements
- [ ] **Localize Risk Analysis:** Replace external API calls (OpenTopoData/SoilGrids) with local raster processing using the `.tif` files in `data/raster/`.
- [ ] **Vulnerability Mitigation:** Update `show_detail.php` and `weather_logs.php` to use prepared statements for queries.
- [ ] **Visual Feedback:** Implement a heatmap or color-coded markers on the map based on the calculated `soil_saturation_index`.
- [ ] **Offline Logic:** Ensure the water balance model can run entirely offline if cached weather data is available.
