<?php
// Database configuration for IBMP Admission Form
// Check your Hostinger control panel for the exact database host
// Most Hostinger plans use 'localhost', but some may use a specific hostname

define('DB_HOST', 'localhost'); // Change this if your Hostinger panel shows a different host
define('DB_NAME', 'u584739810_admissionform'); // Your database name - CORRECTED
define('DB_USER', 'u584739810_ibmpadmission'); // Your database username - CORRECTED
define('DB_PASS', 'Dmhca@321'); // Your database password - CORRECTED

// Admin configuration
define('ADMIN_PASSWORD', 'IBMP_Admin_2025!'); // Secure admin password for application management

// File upload configuration
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB max file size
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);

// Create database connection
function getDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>
