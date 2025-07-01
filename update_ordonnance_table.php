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
    
    // Add missing columns if they don't exist
    $alterSql = [
        "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS original_name VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS mime_type VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE ordonnance ADD COLUMN IF NOT EXISTS size INT DEFAULT NULL"
    ];
    
    $pdo->beginTransaction();
    
    try {
        foreach ($alterSql as $sql) {
            echo "\nExecuting: $sql\n";
            $pdo->exec($sql);
            echo "✅ Success\n";
        }
        
        $pdo->commit();
        echo "\n🎉 Successfully updated ordonnance table structure\n";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    // Verify the structure
    echo "\n🔍 Verifying table structure...\n";
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
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
