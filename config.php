<?php
// config.php

// Function to load .env variables
function load_env($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Split at the first '='
        $parts = explode('=', $line, 2);
        if (count($parts) == 2) {
            $name = trim($parts[0]);
            $value = trim($parts[1]);
            // Remove surrounding quotes if present
            $value = trim($value, "\"'");
            // Set environment variables
            putenv("$name=$value");
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Load environment variables from .env
load_env(__DIR__ . '/.env');

// Retrieve the API key
$googleMapsApiKey = getenv('GOOGLE_MAPS_API_KEY') ?: '';

// Optionally, you can handle missing API key
if (empty($googleMapsApiKey)) {
    error_log('Google Maps API key is not set in the .env file.');
    // You can set a fallback or display an error message
}
?>
