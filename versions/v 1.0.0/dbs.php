<?php
$host = 'localhost';
$port = '1521';
$dbname = 'XEPDB1'; 
$user = 'project_admin';
$pass = 'projectpass123';

// FIX: Removed ";charset=UTF8" from the end of this string
$dsn = "oci:dbname=//{$host}:{$port}/{$dbname}";

try {
    // FIX: Set options including error tracking configurations natively
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];

    // Establish a secure PDO connection to Oracle 
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Optional flag: If you need to verify it works silently behind your app
    // echo "Connection successful!"; 

} catch (PDOException $e) {
    // If it fails, print the true Oracle error message
    die("Oracle Database connection failed: " . $e->getMessage());
}
?>
