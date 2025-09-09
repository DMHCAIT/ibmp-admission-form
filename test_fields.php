<?php
require_once 'config.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get column information
    $stmt = $pdo->query("DESCRIBE applications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Database Columns in 'applications' table:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Get sample data from most recent application
    $stmt = $pdo->query("SELECT * FROM applications ORDER BY id DESC LIMIT 1");
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($sample) {
        echo "<h2>Sample Application Data (Most Recent):</h2>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        
        foreach ($sample as $key => $value) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($key) . "</td>";
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
