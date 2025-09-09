<?php
// Complete Database Setup Script for IBMP Admission Form
// Run this file once on your hosting server to set up the database

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBMP Database Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: #1e3c72;
            margin-bottom: 0.5rem;
        }
        
        .status {
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        code {
            background: #f8f9fa;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .sql-block {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            overflow-x: auto;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏥 IBMP Database Setup</h1>
            <p>Complete database initialization for your admission form</p>
        </div>

<?php
$results = [];
$errors = [];

try {
    // Check if config file exists
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found. Please ensure config.php is uploaded.');
    }
    
    require_once 'config.php';
    
    // Test database connection
    $pdo = getDatabaseConnection();
    $results[] = "✅ Database connection successful";
    
    // Check if setup should be run
    if (isset($_POST['run_setup']) || isset($_GET['setup'])) {
        
        // Create applications table with all required fields
        $createTableSQL = "
        CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            form_number VARCHAR(50),
            application_number VARCHAR(50),
            title VARCHAR(10),
            full_name VARCHAR(255) NOT NULL,
            first_name VARCHAR(100),
            last_name VARCHAR(100),
            date_of_birth DATE,
            gender VARCHAR(20),
            age INT,
            nationality VARCHAR(50),
            marital_status VARCHAR(20),
            religion VARCHAR(50),
            correspondence_address TEXT,
            phone_number VARCHAR(20),
            mobile_number VARCHAR(20),
            email_id VARCHAR(255) NOT NULL,
            permanent_address TEXT,
            address TEXT,
            city VARCHAR(100),
            postal_code VARCHAR(20),
            
            -- Parent Information
            parent_name VARCHAR(255),
            parent_occupation VARCHAR(100),
            parent_mobile VARCHAR(20),
            parent_email VARCHAR(255),
            
            -- Course Information
            course_type VARCHAR(100),
            course_name VARCHAR(255),
            session_year VARCHAR(20),
            preferred_start_date DATE,
            study_mode VARCHAR(50),
            
            -- Educational Background - 10th
            school_10th VARCHAR(255),
            matric_board VARCHAR(100),
            year_10th INT,
            matric_year INT,
            subject_10th VARCHAR(255),
            marks_10th INT,
            matric_marks INT,
            max_marks_10th INT,
            matric_total_marks INT,
            percentage_10th DECIMAL(5,2),
            matric_percentage DECIMAL(5,2),
            
            -- Educational Background - 12th
            school_12th VARCHAR(255),
            inter_board VARCHAR(100),
            year_12th INT,
            inter_year INT,
            subject_12th VARCHAR(255),
            marks_12th INT,
            inter_marks INT,
            max_marks_12th INT,
            inter_total_marks INT,
            percentage_12th DECIMAL(5,2),
            inter_percentage DECIMAL(5,2),
            
            -- Educational Background - UG
            college_ug VARCHAR(255),
            bachelor_university VARCHAR(255),
            year_ug INT,
            bachelor_year INT,
            subject_ug VARCHAR(255),
            marks_ug INT,
            max_marks_ug INT,
            percentage_ug DECIMAL(5,2),
            bachelor_percentage DECIMAL(5,2),
            bachelor_cgpa VARCHAR(10),
            
            -- Educational Background - PG
            college_pg VARCHAR(255),
            master_university VARCHAR(255),
            year_pg INT,
            master_year INT,
            subject_pg VARCHAR(255),
            marks_pg INT,
            max_marks_pg INT,
            percentage_pg DECIMAL(5,2),
            master_percentage DECIMAL(5,2),
            master_cgpa VARCHAR(10),
            
            -- Educational Background - Other
            college_other VARCHAR(255),
            year_other INT,
            subject_other VARCHAR(255),
            marks_other INT,
            max_marks_other INT,
            percentage_other DECIMAL(5,2),
            
            -- Payment Information
            dd_cheque_no VARCHAR(50),
            dd_date DATE,
            payment_amount DECIMAL(10,2),
            payment_option VARCHAR(50),
            bank_details TEXT,
            
            -- Referral
            referral_source VARCHAR(100),
            other_referral_source VARCHAR(255),
            
            -- File Paths
            passport_photo_path VARCHAR(500),
            photo VARCHAR(500),
            cv_path VARCHAR(500),
            cv VARCHAR(500),
            educational_certificates_path VARCHAR(500),
            educational_certificates VARCHAR(500),
            marksheets_path VARCHAR(500),
            marksheets VARCHAR(500),
            identity_proof_path VARCHAR(500),
            identity_proof VARCHAR(500),
            digital_signature VARCHAR(500),
            matric_certificate VARCHAR(500),
            inter_certificate VARCHAR(500),
            bachelor_certificate VARCHAR(500),
            master_certificate VARCHAR(500),
            
            -- Sponsor Information
            sponsor_name VARCHAR(255),
            sponsor_relationship VARCHAR(100),
            sponsor_income VARCHAR(100),
            sponsor_occupation VARCHAR(100),
            
            -- Emergency Contact
            emergency_contact_name VARCHAR(255),
            emergency_contact_relationship VARCHAR(100),
            emergency_contact_phone VARCHAR(20),
            emergency_contact_address TEXT,
            
            -- System Fields
            terms_accepted BOOLEAN DEFAULT FALSE,
            privacy_accepted BOOLEAN DEFAULT FALSE,
            status VARCHAR(20) DEFAULT 'pending',
            submitted_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indexes for better performance
            INDEX idx_status (status),
            INDEX idx_created (created_at),
            INDEX idx_email (email_id),
            INDEX idx_name (full_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($createTableSQL);
        $results[] = "✅ Applications table created/updated successfully";
        
        // Create uploads directory (if running on server)
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            if (mkdir($uploadDir, 0755, true)) {
                $results[] = "✅ Uploads directory created successfully";
            } else {
                $errors[] = "⚠️ Could not create uploads directory. Please create it manually with 755 permissions.";
            }
        } else {
            $results[] = "✅ Uploads directory already exists";
        }
        
        // Test insert and delete
        try {
            $testSQL = "INSERT INTO applications (full_name, email_id, status) VALUES ('Test User', 'test@example.com', 'test')";
            $pdo->exec($testSQL);
            
            $pdo->exec("DELETE FROM applications WHERE email_id = 'test@example.com' AND status = 'test'");
            $results[] = "✅ Database write/read test successful";
        } catch (Exception $e) {
            $errors[] = "❌ Database test failed: " . $e->getMessage();
        }
        
        // Get table info
        $stmt = $pdo->query("DESCRIBE applications");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $results[] = "✅ Table structure verified: " . count($columns) . " columns created";
        
    } else {
        // Just show current status
        $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("DESCRIBE applications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $results[] = "ℹ️ Applications table exists with " . count($columns) . " columns";
        } else {
            $errors[] = "⚠️ Applications table does not exist. Run setup to create it.";
        }
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Error: " . $e->getMessage();
}
?>

        <?php if (!empty($results)): ?>
            <?php foreach ($results as $result): ?>
                <div class="status success"><?= htmlspecialchars($result) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="status error"><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!isset($_POST['run_setup']) && !isset($_GET['setup'])): ?>
        <div class="status info">
            <strong>📋 Database Setup Instructions:</strong><br>
            1. Your database credentials are correctly configured<br>
            2. Click the button below to create/update the applications table<br>
            3. This will create all necessary columns for the admission form<br>
            4. Safe to run multiple times - existing data will be preserved
        </div>

        <form method="POST" style="text-align: center;">
            <button type="submit" name="run_setup" class="btn">
                🚀 Run Database Setup
            </button>
        </form>
        <?php else: ?>
        <div class="status success">
            <strong>🎉 Database setup completed!</strong><br>
            Your IBMP admission form system is now ready to use.
        </div>
        
        <div style="text-align: center; margin-top: 2rem;">
            <a href="index.html" class="btn">📝 Go to Admission Form</a>
            <a href="admin_login.php" class="btn">👤 Admin Login</a>
        </div>
        <?php endif; ?>

        <div class="status info" style="margin-top: 2rem;">
            <strong>🔧 Your Database Configuration:</strong><br>
            <code>Database:</code> <?= DB_NAME ?><br>
            <code>Username:</code> <?= DB_USER ?><br>
            <code>Host:</code> <?= DB_HOST ?><br>
            <code>Website:</code> ibmpractitioner.us
        </div>
    </div>
</body>
</html>
