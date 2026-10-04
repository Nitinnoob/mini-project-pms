<?php
$host = 'localhost';
$port = '1521';
$dbname = 'XEPDB1'; 
$user = 'project_admin';
$pass = 'projectpass123';

// Oracle-specific connection string (DSN)
$dsn = "oci:dbname=//{$host}:{$port}/{$dbname};charset=UTF8";

try {
    // Establish a secure PDO connection to Oracle
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // If it fails, print the Oracle error message
    die("Oracle Database connection failed: " . $e->getMessage());
}
?>
