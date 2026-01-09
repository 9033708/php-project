<?php
// test_connection.php
$ports = [3306, 3307, 3308, 8889];
$host = '127.0.0.1';
$username = 'root';
$password = '';

echo "Diagnostic: Starting Connection Tests...\n";

foreach ($ports as $port) {
    echo "Testing Port $port... ";
    try {
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "SUCCESS! MySQL is running on port $port.\n";

        // Check if DB exists
        try {
            $pdo->exec("USE university_tasks_db");
            echo "SUCCESS! Database 'university_tasks_db' found.\n";
        } catch (Exception $e) {
            echo "WARNING: Database 'university_tasks_db' NOT found (but server is up).\n";
        }
        exit(0); // Found it
    } catch (PDOException $e) {
        echo "FAILED. (" . $e->getMessage() . ")\n";
    }
}

echo "CONCLUSION: Could not connect to MySQL on any standard port. Please ensure XAMPP MySQL service is running.\n";
?>