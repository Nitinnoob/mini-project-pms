<?php
$host = 'localhost';
$port = '1521'; //port for oracle database 11g,21c express edition 
$dbname = 'your_database_name'; //this part needs to be filled by the user
$user = 'your_db_username';
$pass = 'your_db_password';

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
