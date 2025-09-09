<?php
// Database Connection Verification Tool
// This script will test your database connection and verify table structure

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test - IBMP</title>
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
        .test-section {
            margin: 1.5rem 0;
            padding: 1rem;
            border-radius: 8px;
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
        .config-display {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            margin: 1rem 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 0.5rem;
            text-align: left;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            margin: 0.25rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: 500;
        }
        .btn-primary { background: #007bff; }
        .btn-success { background: #28a745; }
        .btn-danger { background: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Database Connection Test</h1>
            <p>Verifying IBMP Admission Form Database Configuration</p>
        </div>

<?php
$tests = [];
$overallSuccess = true;

// Test 1: Check config file
echo "<div class='test-section info'>";
echo "<h3>📋 Test 1: Configuration Check</h3>";
try {
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    require_once 'config.php';
    
    echo "<div class='config-display'>";
    echo "<strong>Current Database Configuration:</strong><br>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "Username: " . DB_USER . "<br>";
    echo "Password: " . str_repeat('*', strlen(DB_PASS)) . " (hidden)<br>";
    echo "</div>";
    
    echo "✅ Configuration file loaded successfully";
    $tests['config'] = true;
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
    $tests['config'] = false;
    $overallSuccess = false;
}
echo "</div>";

// Test 2: Database Connection
echo "<div class='test-section " . ($tests['config'] ? "info" : "error") . "'>";
echo "<h3>🔌 Test 2: Database Connection</h3>";
if ($tests['config']) {
    try {
        $pdo = getDatabaseConnection();
        echo "✅ Successfully connected to database: " . DB_NAME;
        $tests['connection'] = true;
        
        // Get server info
        $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        echo "<br>📊 MySQL Version: " . $serverVersion;
        
    } catch (Exception $e) {
        echo "❌ Connection failed: " . $e->getMessage();
        $tests['connection'] = false;
        $overallSuccess = false;
    }
} else {
    echo "⏭️ Skipped (config test failed)";
    $tests['connection'] = false;
    $overallSuccess = false;
}
echo "</div>";

// Test 3: Table Structure
echo "<div class='test-section " . ($tests['connection'] ? "info" : "error") . "'>";
echo "<h3>🗃️ Test 3: Table Structure Check</h3>";
if ($tests['connection']) {
    try {
        // Check if applications table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            echo "✅ Applications table exists<br>";
            
            // Get table structure
            $stmt = $pdo->query("DESCRIBE applications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<br><strong>Table Structure (" . count($columns) . " columns):</strong>";
            echo "<table>";
            echo "<tr><th>Column Name</th><th>Data Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            
            $essentialColumns = ['id', 'full_name', 'email_id', 'phone_number', 'status', 'created_at'];
            $foundEssential = 0;
            
            foreach ($columns as $column) {
                $isEssential = in_array($column['Field'], $essentialColumns);
                if ($isEssential) $foundEssential++;
                
                $rowClass = $isEssential ? 'style="background: #d4edda;"' : '';
                echo "<tr $rowClass>";
                echo "<td>" . $column['Field'] . ($isEssential ? ' ⭐' : '') . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            if ($foundEssential >= 5) {
                echo "✅ All essential columns found (" . $foundEssential . "/" . count($essentialColumns) . ")";
                $tests['table_structure'] = true;
            } else {
                echo "⚠️ Missing essential columns (" . $foundEssential . "/" . count($essentialColumns) . " found)";
                $tests['table_structure'] = false;
                $overallSuccess = false;
            }
            
        } else {
            echo "❌ Applications table does not exist";
            $tests['table_structure'] = false;
            $overallSuccess = false;
        }
        
    } catch (Exception $e) {
        echo "❌ Error checking table: " . $e->getMessage();
        $tests['table_structure'] = false;
        $overallSuccess = false;
    }
} else {
    echo "⏭️ Skipped (connection test failed)";
    $tests['table_structure'] = false;
}
echo "</div>";

// Test 4: Insert/Select Test
echo "<div class='test-section " . ($tests['table_structure'] ? "info" : "error") . "'>";
echo "<h3>✍️ Test 4: Database Operations Test</h3>";
if ($tests['table_structure']) {
    try {
        // Test insert
        $testEmail = 'test_' . time() . '@example.com';
        $sql = "INSERT INTO applications (full_name, email_id, phone_number, status, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $insertSuccess = $stmt->execute(['Database Test User', $testEmail, '+1234567890', 'test']);
        
        if ($insertSuccess) {
            $insertId = $pdo->lastInsertId();
            echo "✅ Test record inserted (ID: $insertId)<br>";
            
            // Test select
            $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
            $stmt->execute([$insertId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo "✅ Test record retrieved successfully<br>";
                
                // Clean up test record
                $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
                $stmt->execute([$insertId]);
                echo "✅ Test record cleaned up";
                
                $tests['operations'] = true;
            } else {
                echo "❌ Could not retrieve test record";
                $tests['operations'] = false;
                $overallSuccess = false;
            }
        } else {
            echo "❌ Could not insert test record";
            $tests['operations'] = false;
            $overallSuccess = false;
        }
        
    } catch (Exception $e) {
        echo "❌ Database operation failed: " . $e->getMessage();
        $tests['operations'] = false;
        $overallSuccess = false;
    }
} else {
    echo "⏭️ Skipped (table structure test failed)";
    $tests['operations'] = false;
}
echo "</div>";

// Overall Result
if ($overallSuccess) {
    echo "<div class='test-section success'>";
    echo "<h3>🎉 Overall Result: SUCCESS</h3>";
    echo "<p>Your database is properly configured and connected! The IBMP admission form should work correctly.</p>";
} else {
    echo "<div class='test-section error'>";
    echo "<h3>❌ Overall Result: ISSUES DETECTED</h3>";
    echo "<p>There are configuration or database issues that need to be resolved.</p>";
}

// Action buttons
echo "<div style='margin-top: 2rem; text-align: center;'>";
if ($tests['connection'] ?? false) {
    echo "<a href='database_analysis.php' class='btn btn-primary'>📊 Run Full Database Analysis</a>";
    if (!($tests['table_structure'] ?? false)) {
        echo "<a href='setup_database.php' class='btn btn-success'>🔧 Setup Database Tables</a>";
    }
    echo "<a href='admin_login.php' class='btn btn-primary'>👤 Admin Login</a>";
} else {
    echo "<a href='setup_database.php' class='btn btn-danger'>🚨 Fix Database Setup</a>";
}
echo "<a href='index.html' class='btn btn-success'>🏠 Back to Form</a>";
echo "</div>";

echo "</div>";
?>

        <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; font-size: 0.9rem; color: #6c757d;">
            <strong>Expected Database Credentials:</strong><br>
            Based on your hosting panel screenshot:<br>
            • Database: u584739810_admissionform ✓<br>
            • Username: u584739810_ibmpadmission ✓<br>
            • Host: localhost ✓<br>
            • Website: ibmpractitioner.us ✓
        </div>
    </div>
</body>
</html>
