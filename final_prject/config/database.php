<?php
// config/database.php

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'university_tasks_db';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In a real production app, we wouldn't echo the error directly to the user
    die("Connection failed: " . $e->getMessage());
}
 