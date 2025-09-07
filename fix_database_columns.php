<?php
// Comprehensive database fix for missing columns
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    echo "<h1>IBMP Database Column Fix</h1>";
    echo "<p><strong>International Board of Medical Practitioners</strong></p>";
    echo "<p>Adding missing file upload and other columns...</p>";
    
    // Check current table structure
    $stmt = $pdo->query("DESCRIBE applications");
    $currentColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Current Columns:</h2>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    foreach ($currentColumns as $col) {
        echo "<span style='background: #e9ecef; padding: 3px 8px; margin: 2px; border-radius: 3px; display: inline-block;'>$col</span> ";
    }
    echo "</div>";
    
    // Complete list of required columns with their definitions
    $requiredColumns = [
        // File upload columns
        'photo' => 'VARCHAR(255) NULL',
        'matric_certificate' => 'VARCHAR(255) NULL',
        'inter_certificate' => 'VARCHAR(255) NULL',
        'bachelor_certificate' => 'VARCHAR(255) NULL',
        'master_certificate' => 'VARCHAR(255) NULL',
        
        // Timestamp columns
        'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        
        // Status column
        'status' => "VARCHAR(50) DEFAULT 'pending'",
        
        // Additional missing columns that might be needed
        'course_type' => 'VARCHAR(100) NULL',
        'course_name' => 'VARCHAR(255) NULL',
        'preferred_start_date' => 'DATE NULL',
        'study_mode' => 'VARCHAR(50) NULL',
        
        // Personal info that might be missing
        'nationality' => 'VARCHAR(100) NULL',
        'address' => 'TEXT NULL',
        'city' => 'VARCHAR(100) NULL',
        'postal_code' => 'VARCHAR(20) NULL',
        'gender' => 'VARCHAR(10) NULL',
        'date_of_birth' => 'DATE NULL',
        
        // Educational background
        'matric_board' => 'VARCHAR(255) NULL',
        'matric_year' => 'INT NULL',
        'matric_marks' => 'INT NULL',
        'matric_total_marks' => 'INT NULL',
        'matric_percentage' => 'DECIMAL(5,2) NULL',
        
        'inter_board' => 'VARCHAR(255) NULL',
        'inter_year' => 'INT NULL',
        'inter_marks' => 'INT NULL',
        'inter_total_marks' => 'INT NULL',
        'inter_percentage' => 'DECIMAL(5,2) NULL',
        
        'bachelor_university' => 'VARCHAR(255) NULL',
        'bachelor_year' => 'INT NULL',
        'bachelor_percentage' => 'DECIMAL(5,2) NULL',
        'bachelor_cgpa' => 'DECIMAL(3,2) NULL',
        
        'master_university' => 'VARCHAR(255) NULL',
        'master_year' => 'INT NULL',
        'master_percentage' => 'DECIMAL(5,2) NULL',
        'master_cgpa' => 'DECIMAL(3,2) NULL',
        
        // Sponsor information
        'sponsor_name' => 'VARCHAR(255) NULL',
        'sponsor_relationship' => 'VARCHAR(100) NULL',
        'sponsor_income' => 'VARCHAR(100) NULL',
        'sponsor_occupation' => 'VARCHAR(255) NULL',
        
        // Payment and emergency contact
        'payment_option' => 'VARCHAR(100) NULL',
        'emergency_contact_name' => 'VARCHAR(255) NULL',
        'emergency_contact_relationship' => 'VARCHAR(100) NULL',
        'emergency_contact_phone' => 'VARCHAR(20) NULL',
        'emergency_contact_address' => 'TEXT NULL'
    ];
    
    echo "<h2>Adding Missing Columns:</h2>";
    $addedCount = 0;
    $existingCount = 0;
    
    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $currentColumns)) {
            try {
                $sql = "ALTER TABLE applications ADD COLUMN $columnName $columnDefinition";
                $pdo->exec($sql);
                echo "✅ <strong>Added:</strong> $columnName ($columnDefinition)<br>";
                $addedCount++;
            } catch (Exception $e) {
                echo "❌ <strong>Failed to add:</strong> $columnName - " . $e->getMessage() . "<br>";
            }
        } else {
            echo "✅ <strong>Exists:</strong> $columnName<br>";
            $existingCount++;
        }
    }
    
    echo "<br><div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px;'>";
    echo "<h3>Summary:</h3>";
    echo "<p>✅ <strong>$existingCount</strong> columns already existed</p>";
    echo "<p>➕ <strong>$addedCount</strong> columns added successfully</p>";
    echo "</div>";
    
    // Update existing records with default values if needed
    if ($addedCount > 0) {
        try {
            $pdo->exec("UPDATE applications SET created_at = COALESCE(created_at, NOW()) WHERE created_at IS NULL");
            $pdo->exec("UPDATE applications SET status = COALESCE(status, 'pending') WHERE status IS NULL OR status = ''");
            echo "<br>✅ Updated existing records with default values<br>";
        } catch (Exception $e) {
            echo "<br>❌ Failed to update existing records: " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<br><h2>✅ IBMP Database Fix Completed!</h2>";
    echo "<p>Your International Board of Medical Practitioners admin panel should now work perfectly!</p>";
    echo "<div style='margin: 20px 0;'>";
    echo "<a href='view_applications.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Go to Admin Dashboard</a> ";
    echo "<a href='debug.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔍 Run Debug Again</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Database fix failed!</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
