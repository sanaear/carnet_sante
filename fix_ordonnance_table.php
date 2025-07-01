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
    
    echo "✅ Successfully connected to database '$dbName'\n";
    
    // Add each column separately with error handling
    $columns = [
        'original_name' => "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) DEFAULT NULL",
        'mime_type' => "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS mime_type VARCHAR(100) DEFAULT NULL",
        'size' => "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS size INT DEFAULT NULL"
    ];
    
    foreach ($columns as $name => $sql) {
        echo "\nAdding column '$name'... ";
        try {
            $pdo->exec($sql);
            echo "✅ Done";
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'duplicate column')) {
                echo "ℹ️ Column already exists";
            } else {
                throw $e;
            }
        }
    }
    
    // Verify the structure
    echo "\n\n🔍 Current table structure:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM ordonnance");
    $columns = $stmt->fetchAll();
    
    echo str_pad("Field", 20) . str_pad("Type", 25) . str_pad("Null", 8) . "Extra\n";
    echo str_repeat("-", 60) . "\n";
    
    foreach ($columns as $column) {
        echo str_pad($column['Field'], 20) . 
             str_pad($column['Type'], 25) . 
             str_pad($column['Null'], 8) . 
             $column['Extra'] . "\n";
    }
    
    echo "\n✅ Table structure updated successfully\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
