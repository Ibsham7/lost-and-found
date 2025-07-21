<?php
$host = 'localhost';
$dbname = 'NUSTLOSTANDFOUND';
$username = 'root';
$password = 'fb3815b8';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}
?> 