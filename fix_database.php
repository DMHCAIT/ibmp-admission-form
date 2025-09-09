<?php
require_once 'config.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

$results = [];
$errors = [];

try {
    $pdo = getDatabaseConnection();
    
    // Get current columns
    $stmt = $pdo->query("DESCRIBE applications");
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existingFields = array_column($currentColumns, 'Field');
    
    // Required fields with their proper types
    $requiredFields = [
        'id' => 'INT AUTO_INCREMENT PRIMARY KEY',
        'form_number' => 'VARCHAR(50)',
        'application_number' => 'VARCHAR(50)',
        'title' => 'VARCHAR(10)',
        'full_name' => 'VARCHAR(255)',
        'first_name' => 'VARCHAR(100)',
        'last_name' => 'VARCHAR(100)',
        'date_of_birth' => 'DATE',
        'gender' => 'VARCHAR(20)',
        'category' => 'VARCHAR(20)',
        'age' => 'INT',
        'nationality' => 'VARCHAR(50)',
        'marital_status' => 'VARCHAR(20)',
        'religion' => 'VARCHAR(50)',
        'correspondence_address' => 'TEXT',
        'phone_number' => 'VARCHAR(20)',
        'mobile_number' => 'VARCHAR(20)',
        'email_id' => 'VARCHAR(255)',
        'permanent_address' => 'TEXT',
        'parent_name' => 'VARCHAR(255)',
        'parent_occupation' => 'VARCHAR(100)',
        'parent_mobile' => 'VARCHAR(20)',
        'parent_email' => 'VARCHAR(255)',
        'course_type' => 'VARCHAR(100)',
        'course_name' => 'VARCHAR(255)',
        'session_year' => 'VARCHAR(20)',
        'preferred_start_date' => 'DATE',
        'study_mode' => 'VARCHAR(50)',
        'address' => 'TEXT',
        'city' => 'VARCHAR(100)',
        'postal_code' => 'VARCHAR(20)',
        
        // Educational fields - 10th
        'school_10th' => 'VARCHAR(255)',
        'matric_board' => 'VARCHAR(100)',
        'year_10th' => 'INT',
        'matric_year' => 'INT',
        'subject_10th' => 'VARCHAR(255)',
        'marks_10th' => 'INT',
        'matric_marks' => 'INT',
        'max_marks_10th' => 'INT',
        'matric_total_marks' => 'INT',
        'percentage_10th' => 'DECIMAL(5,2)',
        'matric_percentage' => 'DECIMAL(5,2)',
        
        // Educational fields - 12th
        'school_12th' => 'VARCHAR(255)',
        'inter_board' => 'VARCHAR(100)',
        'year_12th' => 'INT',
        'inter_year' => 'INT',
        'subject_12th' => 'VARCHAR(255)',
        'marks_12th' => 'INT',
        'inter_marks' => 'INT',
        'max_marks_12th' => 'INT',
        'inter_total_marks' => 'INT',
        'percentage_12th' => 'DECIMAL(5,2)',
        'inter_percentage' => 'DECIMAL(5,2)',
        
        // Educational fields - UG
        'college_ug' => 'VARCHAR(255)',
        'bachelor_university' => 'VARCHAR(255)',
        'year_ug' => 'INT',
        'bachelor_year' => 'INT',
        'subject_ug' => 'VARCHAR(255)',
        'marks_ug' => 'INT',
        'max_marks_ug' => 'INT',
        'percentage_ug' => 'DECIMAL(5,2)',
        'bachelor_percentage' => 'DECIMAL(5,2)',
        'bachelor_cgpa' => 'VARCHAR(10)',
        
        // Educational fields - PG
        'college_pg' => 'VARCHAR(255)',
        'master_university' => 'VARCHAR(255)',
        'year_pg' => 'INT',
        'master_year' => 'INT',
        'subject_pg' => 'VARCHAR(255)',
        'marks_pg' => 'INT',
        'max_marks_pg' => 'INT',
        'percentage_pg' => 'DECIMAL(5,2)',
        'master_percentage' => 'DECIMAL(5,2)',
        'master_cgpa' => 'VARCHAR(10)',
        
        // Educational fields - Other
        'college_other' => 'VARCHAR(255)',
        'year_other' => 'INT',
        'subject_other' => 'VARCHAR(255)',
        'marks_other' => 'INT',
        'max_marks_other' => 'INT',
        'percentage_other' => 'DECIMAL(5,2)',
        
        // Payment fields
        'dd_cheque_no' => 'VARCHAR(50)',
        'dd_date' => 'DATE',
        'payment_amount' => 'DECIMAL(10,2)',
        'payment_option' => 'VARCHAR(50)',
        'bank_details' => 'TEXT',
        
        // Referral
        'referral_source' => 'VARCHAR(100)',
        'other_referral_source' => 'VARCHAR(255)',
        
        // File paths
        'passport_photo_path' => 'VARCHAR(500)',
        'photo' => 'VARCHAR(500)',
        'cv_path' => 'VARCHAR(500)',
        'cv' => 'VARCHAR(500)',
        'educational_certificates_path' => 'VARCHAR(500)',
        'educational_certificates' => 'VARCHAR(500)',
        'marksheets_path' => 'VARCHAR(500)',
        'marksheets' => 'VARCHAR(500)',
        'identity_proof_path' => 'VARCHAR(500)',
        'identity_proof' => 'VARCHAR(500)',
        'digital_signature' => 'VARCHAR(500)',
        'matric_certificate' => 'VARCHAR(500)',
        'inter_certificate' => 'VARCHAR(500)',
        'bachelor_certificate' => 'VARCHAR(500)',
        'master_certificate' => 'VARCHAR(500)',
        
        // Sponsor information
        'sponsor_name' => 'VARCHAR(255)',
        'sponsor_relationship' => 'VARCHAR(100)',
        'sponsor_income' => 'VARCHAR(100)',
        'sponsor_occupation' => 'VARCHAR(100)',
        
        // Emergency contact
        'emergency_contact_name' => 'VARCHAR(255)',
        'emergency_contact_relationship' => 'VARCHAR(100)',
        'emergency_contact_phone' => 'VARCHAR(20)',
        'emergency_contact_address' => 'TEXT',
        
        // System fields
        'terms_accepted' => 'BOOLEAN DEFAULT FALSE',
        'privacy_accepted' => 'BOOLEAN DEFAULT FALSE',
        'status' => 'VARCHAR(20) DEFAULT "pending"',
        'submitted_at' => 'TIMESTAMP NULL',
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
    ];
    
    // Find missing fields
    $missingFields = [];
    foreach ($requiredFields as $field => $type) {
        if (!in_array($field, $existingFields)) {
            $missingFields[$field] = $type;
        }
    }
    
    if ($_POST['action'] === 'fix_database') {
        $pdo->beginTransaction();
        
        try {
            foreach ($missingFields as $field => $type) {
                $sql = "ALTER TABLE applications ADD COLUMN `{$field}` {$type}";
                $pdo->exec($sql);
                $results[] = "✅ Added field: {$field} ({$type})";
            }
            
            $pdo->commit();
            $results[] = "🎉 Database structure updated successfully!";
            
        } catch (Exception $e) {
            $pdo->rollback();
            $errors[] = "❌ Error updating database: " . $e->getMessage();
        }
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Database connection error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Fix Tool - IBMP Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 900px;
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
        
        .status-box {
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 8px;
            border: 1px solid;
        }
        
        .success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .warning {
            background: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .info {
            background: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
        }
        
        .field-list {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            margin: 1rem 0;
        }
        
        .field-item {
            padding: 0.25rem 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        th, td {
            border: 1px solid #dee2e6;
            padding: 0.75rem;
            text-align: left;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Database Structure Fix Tool</h1>
            <p>Automatically add missing database fields</p>
        </div>
        
        <?php if (!empty($results)): ?>
        <div class="status-box success">
            <h3>✅ Success Results</h3>
            <?php foreach ($results as $result): ?>
                <p><?= htmlspecialchars($result) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
        <div class="status-box error">
            <h3>❌ Errors</h3>
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($missingFields) && !empty($missingFields)): ?>
        <div class="status-box warning">
            <h3>⚠️ Missing Fields Detected</h3>
            <p><strong><?= count($missingFields) ?></strong> fields are missing from your database.</p>
            
            <div class="field-list">
                <?php foreach ($missingFields as $field => $type): ?>
                    <div class="field-item">• <?= htmlspecialchars($field) ?> → <?= htmlspecialchars($type) ?></div>
                <?php endforeach; ?>
            </div>
            
            <form method="POST" style="text-align: center; margin-top: 1.5rem;">
                <input type="hidden" name="action" value="fix_database">
                <button type="submit" class="btn btn-danger" onclick="return confirm('This will modify your database structure. Are you sure?')">
                    🔧 Fix Database Structure (Add <?= count($missingFields) ?> Fields)
                </button>
            </form>
        </div>
        <?php elseif (isset($missingFields)): ?>
        <div class="status-box success">
            <h3>✅ Database Structure Complete</h3>
            <p>All required fields are present in the database!</p>
        </div>
        <?php endif; ?>
        
        <div class="status-box info">
            <h3>📊 Database Information</h3>
            <table>
                <tr>
                    <th>Metric</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td>Current Fields</td>
                    <td><?= isset($existingFields) ? count($existingFields) : 'Unknown' ?></td>
                </tr>
                <tr>
                    <td>Required Fields</td>
                    <td><?= isset($requiredFields) ? count($requiredFields) : 'Unknown' ?></td>
                </tr>
                <tr>
                    <td>Missing Fields</td>
                    <td><?= isset($missingFields) ? count($missingFields) : 'Unknown' ?></td>
                </tr>
                <tr>
                    <td>Database Status</td>
                    <td><?= isset($missingFields) && empty($missingFields) ? '✅ Complete' : '⚠️ Needs Update' ?></td>
                </tr>
            </table>
        </div>
        
        <div class="back-link">
            <a href="database_analysis.php" class="btn btn-primary">📊 View Detailed Analysis</a>
            <a href="admin_panel.php" class="btn btn-success">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>
