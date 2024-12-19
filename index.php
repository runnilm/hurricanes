<?php

require_once 'config.php';
$title = 'Maps';

?>
<!DOCTYPE html>
<html>

<head>
    <?php include('includes/head.php'); ?>

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        .place-picker-container {
            padding: 20px;
        }
    </style>
</head>

<body>
    <gmpx-api-loader
        key="<?php echo htmlspecialchars($googleMapsApiKey, ENT_QUOTES, 'UTF-8'); ?>"
        solution-channel="GMP_GE_mapsandplacesautocomplete_v2"
        v="beta">
    </gmpx-api-loader>
    <gmp-map zoom="13" map-id="d17ceafd711ab8d1 ">
        <div slot="control-block-start-inline-start" class="place-picker-container">
            <gmpx-place-picker placeholder="Enter an address"></gmpx-place-picker>
        </div>
        <gmp-advanced-marker></gmp-advanced-marker>
    </gmp-map>

    <?php include('includes/foot.php'); ?>

    <!-- Google Maps Component Library -->
    <script type="module" src="https://unpkg.com/@googlemaps/extended-component-library"></script>

    <!-- Page Script -->
    <script>
        const init = async () => {
            await customElements.whenDefined('gmp-map');

            const map = document.querySelector('gmp-map');
            const marker = document.querySelector('gmp-advanced-marker');
            const placePicker = document.querySelector('gmpx-place-picker');
            const infowindow = new google.maps.InfoWindow();

            const setUserLocation = (position) => {
                const userLocation = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                };
                map.center = userLocation; // Set the map's center
                marker.position = userLocation; // Set marker's position
            };

            const fallbackLocation = () => {
                const defaultLocation = {
                    lat: 29.7601,
                    lng: 95.3701
                }; // Houston
                map.center = defaultLocation;
                console.warn('Using fallback location: Houston');
            };

            if ('geolocation' in navigator) {
                navigator.geolocation.getCurrentPosition(setUserLocation, fallbackLocation);
            } else {
                console.error('Geolocation is not supported by this browser.');
                fallbackLocation();
            }

            placePicker.addEventListener('gmpx-placechange', () => {
                const place = placePicker.value;

                if (!place.location) {
                    alert(`No details available for input: '${place.name}'`);
                    infowindow.close();
                    marker.position = null;
                    return;
                }

                if (place.viewport) {
                    map.innerMap.fitBounds(place.viewport);
                } else {
                    map.center = place.location;
                    map.zoom = 17;
                }

                marker.position = place.location;
                infowindow.setContent(`
                    <strong>${place.displayName}</strong><br>
                    <span>${place.formattedAddress}</span>
                `);
                infowindow.open(map.innerMap, marker);
            });
        };

        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>

</html>