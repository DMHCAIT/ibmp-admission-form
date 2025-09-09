<?php
// Simple JSON API to check database connection status
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [],
    'success' => true,
    'message' => ''
];

try {
    // Test 1: Config file
    if (!file_exists('config.php')) {
        throw new Exception('Config file not found');
    }
    require_once 'config.php';
    
    $result['tests']['config'] = [
        'status' => 'success',
        'message' => 'Config file loaded',
        'database_name' => DB_NAME,
        'database_user' => DB_USER,
        'database_host' => DB_HOST
    ];
    
    // Test 2: Database connection
    try {
        $pdo = getDatabaseConnection();
        $result['tests']['connection'] = [
            'status' => 'success',
            'message' => 'Database connected successfully',
            'server_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION)
        ];
        
        // Test 3: Table check
        $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
        $tableExists = $stmt->rowCount() > 0;
        
        if ($tableExists) {
            $stmt = $pdo->query("DESCRIBE applications");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $result['tests']['table'] = [
                'status' => 'success',
                'message' => 'Applications table found',
                'column_count' => count($columns),
                'columns' => array_column($columns, 'Field')
            ];
            
            // Test 4: Quick operation test
            try {
                $testEmail = 'test_' . time() . '@example.com';
                $sql = "INSERT INTO applications (full_name, email_id, status, created_at) VALUES (?, ?, 'test', NOW())";
                $stmt = $pdo->prepare($sql);
                $insertSuccess = $stmt->execute(['Test User', $testEmail]);
                
                if ($insertSuccess) {
                    $insertId = $pdo->lastInsertId();
                    // Clean up immediately
                    $pdo->prepare("DELETE FROM applications WHERE id = ?")->execute([$insertId]);
                    
                    $result['tests']['operations'] = [
                        'status' => 'success',
                        'message' => 'Database operations working'
                    ];
                } else {
                    throw new Exception('Insert operation failed');
                }
                
            } catch (Exception $e) {
                $result['tests']['operations'] = [
                    'status' => 'error',
                    'message' => 'Database operations failed: ' . $e->getMessage()
                ];
                $result['success'] = false;
            }
            
        } else {
            $result['tests']['table'] = [
                'status' => 'error',
                'message' => 'Applications table not found'
            ];
            $result['success'] = false;
        }
        
    } catch (Exception $e) {
        $result['tests']['connection'] = [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
        $result['success'] = false;
    }
    
} catch (Exception $e) {
    $result['tests']['config'] = [
        'status' => 'error',
        'message' => 'Config error: ' . $e->getMessage()
    ];
    $result['success'] = false;
}

// Overall message
if ($result['success']) {
    $result['message'] = 'All database tests passed successfully!';
} else {
    $result['message'] = 'Database configuration issues detected.';
    http_response_code(500);
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
