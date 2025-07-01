<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__.'/.env');

// Get database URL
$databaseUrl = $_ENV['DATABASE_URL'];

// Parse database URL
$params = parse_url($databaseUrl);

// Extract connection details
$dbName = trim($params['path'], '/');
$server = $params['host'];
$username = $params['user'] ?? '';
$password = $params['pass'] ?? '';
$port = $params['port'] ?? 3306;

// Try to connect to the database
try {
    $dsn = "mysql:host=$server;port=$port;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Successfully connected to database '$dbName'\n\n";
    
    // Check ordonnance table structure
    $stmt = $pdo->query("SHOW COLUMNS FROM ordonnance");
    $columns = $stmt->fetchAll();
    
    echo "📋 ordonnance table structure:\n";
    echo str_pad("Field", 20) . str_pad("Type", 25) . str_pad("Null", 8) . "Extra\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 25) . 
             str_pad($column['Null'], 8) . 
             $column['Extra'] . "\n";
    }
    
    // Check if our new columns exist
    $newColumns = ['original_name', 'mime_type', 'size'];
    $existingColumns = array_column($columns, 'Field');
    
    echo "\n🔍 Checking for new columns:\n";
    foreach ($newColumns as $col) {
        if (in_array($col, $existingColumns)) {
            echo "✅ Column '$col' exists\n";
        } else {
            echo "❌ Column '$col' is missing\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
