<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Landslide GeoJSON Test</title>
	<link rel="stylesheet" href="css/leaflet.css">
	<style>
		body { margin: 20px; font-family: sans-serif; }
		#map { height: 600px; width: 100%; }
		#result { margin: 12px 0; padding: 10px; border: 1px solid #ccc; }
	</style>
</head>
<body>
	<h1>Landslide GeoJSON Test</h1>
	<div id="result">Click the map to test a location.</div>
	<div id="map"></div>

	<script src="js/leaflet.js"></script>
	<script>
		var mapBounds = [[16.3000, 120.5000], [16.5000, 120.7200]];
		var map = L.map('map', {
			center: [16.4023, 120.5960],
			zoom: 13,
			minZoom: 12,
			maxZoom: 18,
			maxBounds: mapBounds,
			maxBoundsViscosity: 1.0
		});

		L.tileLayer('map-tiles/{z}/{x}/{y}.png', {
			minZoom: 12,
			maxZoom: 18,
			bounds: mapBounds,
			errorTileUrl: 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=',
			attribution: '© OpenStreetMap contributors'
		}).addTo(map);

		var hazardFeatures = [];
		var hazardLayer;
		var selectedMarker;

		  fetch('data/vector/landslide.geojson')
			.then(function(response) {
				if (!response.ok) {
								  throw new Error('Could not load data/vector/landslide.geojson');
				}
				return response.json();
			})
			.then(function(data) {
				hazardFeatures = data.features || [];
				hazardLayer = L.layerGroup().addTo(map);

				hazardFeatures.forEach(function(feature) {
					var coordinates = feature.geometry.coordinates[0];
					var bounds = coordinates.map(function(point) {
						return [point[1], point[0]];
					});
					var icon = feature.properties && feature.properties.icon;

					if (icon) {
										  L.imageOverlay('assets/hazard-images/' + icon, bounds, {
							opacity: 0.65,
							interactive: false
						}).addTo(hazardLayer);
					}
				});

				if (hazardLayer.getBounds().isValid()) {
					map.fitBounds(hazardLayer.getBounds());
				}
			})
			.catch(function(error) {
				document.getElementById('result').textContent = error.message;
			});

		function pointInRing(latitude, longitude, ring) {
			var inside = false;

			for (var index = 0, previous = ring.length - 1; index < ring.length; previous = index++) {
				var currentPoint = ring[index];
				var previousPoint = ring[previous];
				var intersects = ((currentPoint[1] > latitude) !== (previousPoint[1] > latitude)) &&
					(longitude < (previousPoint[0] - currentPoint[0]) *
					(latitude - currentPoint[1]) / (previousPoint[1] - currentPoint[1]) + currentPoint[0]);

				if (intersects) {
					inside = !inside;
				}
			}

			return inside;
		}

		function pointInFeature(latitude, longitude, feature) {
			var geometry = feature.geometry;

			if (!geometry || geometry.type !== 'Polygon') {
				return false;
			}

			var rings = geometry.coordinates;
			if (!pointInRing(latitude, longitude, rings[0])) {
				return false;
			}

			for (var holeIndex = 1; holeIndex < rings.length; holeIndex++) {
				if (pointInRing(latitude, longitude, rings[holeIndex])) {
					return false;
				}
			}

			return true;
		}

		map.on('click', function(event) {
			var latitude = event.latlng.lat;
			var longitude = event.latlng.lng;
			var matches = hazardFeatures.filter(function(feature) {
				return pointInFeature(latitude, longitude, feature);
			});

			if (selectedMarker) {
				selectedMarker.setLatLng(event.latlng);
			} else {
				selectedMarker = L.marker(event.latlng).addTo(map);
			}

			var result = document.getElementById('result');
			if (matches.length === 0) {
				result.innerHTML = '<strong>No matching landslide polygon</strong><br>' +
					'Latitude: ' + latitude.toFixed(6) + '<br>' +
					'Longitude: ' + longitude.toFixed(6);
				return;
			}

			var sources = matches.map(function(feature) {
				return feature.properties && feature.properties.icon
					? feature.properties.icon
					: 'Unnamed polygon';
			});

			result.innerHTML = '<strong>Inside landslide polygon</strong><br>' +
				'Latitude: ' + latitude.toFixed(6) + '<br>' +
				'Longitude: ' + longitude.toFixed(6) + '<br>' +
				'Matching polygons: ' + matches.length + '<br>' +
				'Source overlay(s): ' + sources.join(', ');
		});
	</script>
</body>
</html>