<?php
$host = 'localhost';
$dbname = 'pms'; // Updated generic database name
$username = 'root'; // XAMPP default
$password = ''; // XAMPP default is an empty string

try {
    // Connect using the MySQL PDO driver with utf8mb4 encoding
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    
    
} catch(PDOException $e) {
    die("Database Connection failed. Please ensure XAMPP MySQL is running and the 'pms' database exists. Error: " . $e->getMessage());
}
?>

