<?php
// Base path configuration
define('BASE_PATH', dirname(__DIR__));
define('BASE_URL', '/PLSPcart');

// Function to get the correct asset URL
function asset_url($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

// Function to get the correct URL for pages
function url($path) {
    return BASE_URL . '/' . ltrim($path, '/');
}
?> 