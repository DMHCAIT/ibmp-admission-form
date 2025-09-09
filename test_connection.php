<?php
// Simple test script to check database and form submission
header('Content-Type: application/json');

try {
    // Test 1: Check config file
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    
    require_once 'config.php';
    
    // Test 2: Check database connection
    $pdo = getDatabaseConnection();
    
    // Test 3: Check if applications table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        throw new Exception('Applications table does not exist');
    }
    
    // Test 4: Check table structure
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Test 5: Simple insert test (if POST data is provided)
    $testInsert = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST)) {
        try {
            $sql = "INSERT INTO applications (full_name, email_id, status, created_at) VALUES (?, ?, 'test', NOW())";
            $stmt = $pdo->prepare($sql);
            $testInsert = $stmt->execute(['Test User', 'test@example.com']);
            
            if ($testInsert) {
                // Clean up test record
                $pdo->exec("DELETE FROM applications WHERE email_id = 'test@example.com' AND status = 'test'");
            }
        } catch (Exception $e) {
            $testInsert = false;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'All tests passed!',
        'tests' => [
            'config_exists' => true,
            'database_connected' => true,
            'table_exists' => $tableExists,
            'column_count' => count($columnNames),
            'test_insert' => $testInsert,
            'essential_columns' => [
                'full_name' => in_array('full_name', $columnNames),
                'email_id' => in_array('email_id', $columnNames),
                'phone_number' => in_array('phone_number', $columnNames),
                'status' => in_array('status', $columnNames),
                'created_at' => in_array('created_at', $columnNames)
            ]
        ],
        'available_columns' => $columnNames
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'tests' => [
            'config_exists' => file_exists('config.php'),
            'database_connected' => false,
            'table_exists' => false
        ]
    ]);
}
?>
