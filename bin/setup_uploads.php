<?php

$projectDir = dirname(__DIR__);
$uploadsDir = $projectDir . '/public/uploads';
$ordonnancesDir = $uploadsDir . '/ordonnances';

// Create directories if they don't exist
if (!file_exists($uploadsDir)) {
    mkdir($uploadsDir, 0775, true);
    echo "Created directory: $uploadsDir\n";
}

if (!file_exists($ordonnancesDir)) {
    mkdir($ordonnancesDir, 0775, true);
    echo "Created directory: $ordonnancesDir\n";
}

echo "Upload directories are ready.\n";
