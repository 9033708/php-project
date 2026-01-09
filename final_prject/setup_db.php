<?php
// setup_db.php
// Run this script once to setup the database and tables

$host = 'localhost';
$username = 'root';
$password = '';

try {
    // 1. Connect without DB selected
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Create Database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS university_tasks_db");
    echo "Database 'university_tasks_db' created successfully.<br>";

    // 3. Select Database
    $pdo->exec("USE university_tasks_db");

    // 4. Create Tables

    // Roles Table
    $sql = "CREATE TABLE IF NOT EXISTS roles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE
    )";
    $pdo->exec($sql);
    echo "Table 'roles' created.<br>";

    // Insert Default Roles
    $pdo->exec("INSERT INTO roles (name) VALUES ('admin'), ('supervisor'), ('student') ON DUPLICATE KEY UPDATE name=name");

    // Users Table
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'users' created.<br>";

    // Groups Table (Managed by Supervisors)
    // Note: 'supervisor_id' refers to a user with supervisor role
    $sql = "CREATE TABLE IF NOT EXISTS `groups` (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        supervisor_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (supervisor_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'groups' created.<br>";

    // Group Members Table
    // 'is_leader' determines if the student can submit tasks
    $sql = "CREATE TABLE IF NOT EXISTS group_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        group_id INT NOT NULL,
        student_id INT NOT NULL,
        is_leader BOOLEAN DEFAULT FALSE,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE(group_id, student_id)
    )";
    $pdo->exec($sql);
    echo "Table 'group_members' created.<br>";

    // Tasks Table
    $sql = "CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        deadline DATETIME NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'tasks' created.<br>";

    // Task Assignments (Many-to-Many: Task can be assigned to multiple groups)
    $sql = "CREATE TABLE IF NOT EXISTS task_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        group_id INT NOT NULL,
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'task_assignments' created.<br>";

    // Submissions Table
    $sql = "CREATE TABLE IF NOT EXISTS submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        group_id INT NOT NULL,
        submitted_by INT NOT NULL, -- The student (leader) who submitted
        file_path VARCHAR(255) NOT NULL,
        status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
        feedback TEXT,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
        FOREIGN KEY (group_id) REFERENCES `groups`(id) ON DELETE CASCADE,
        FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'submissions' created.<br>";

    // Notifications Table
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $pdo->exec($sql);
    echo "Table 'notifications' created.<br>";

    // Activity Logs Table
    $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $pdo->exec($sql);
    echo "Table 'activity_logs' created.<br>";

    // 5. Create Default Super Admin Account
    // Check if admin exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['superadmin']);
    if ($stmt->rowCount() == 0) {
        // Assume Role ID 1 is Admin based on insertion order
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
        $stmt->execute(['superadmin', 'admin@university.edu', $password, 1]);
        echo "Default Super Admin created (User: superadmin, Pass: admin123)<br>";
    }

    echo "<h3>Setup Completed Successfully!</h3>";

} catch (PDOException $e) {
    die("DB Setup Error: " . $e->getMessage());
}
?>