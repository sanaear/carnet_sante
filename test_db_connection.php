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
    $dsn = "mysql:host=$server;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "✅ Successfully connected to MySQL server\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE '$dbName'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Database '$dbName' exists\n";
        
        // Switch to the database
        $pdo->exec("USE `$dbName`");
        
        // List all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "\n📋 Tables in database '$dbName':\n";
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "❌ Database '$dbName' does not exist\n";
        echo "   You can create it with: CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    echo "   Please check your database configuration in the .env file\n";
}
