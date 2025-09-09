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

try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get application count
$stmt = $pdo->query("SELECT COUNT(*) as total FROM applications");
$totalApps = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get sample application
$stmt = $pdo->query("SELECT * FROM applications ORDER BY created_at DESC LIMIT 1");
$sampleApp = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Test - IBMP Admin</title>
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
        
        .stats {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .export-section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            margin: 0.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-info {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }
        
        .sample-data {
            background: #e8f4f8;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #17a2b8;
            margin-top: 1rem;
        }
        
        .sample-data pre {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Export System Test</h1>
            <p>Test the CSV and Excel export functionality</p>
        </div>
        
        <div class="stats">
            <h2>📈 Current Statistics</h2>
            <p><strong>Total Applications:</strong> <?= $totalApps ?></p>
            <p><strong>Database Status:</strong> ✅ Connected</p>
            <p><strong>Export Fields:</strong> 46+ comprehensive fields</p>
        </div>
        
        <div class="export-section">
            <h3>🚀 Export All Applications</h3>
            <p>Click the buttons below to test the export functionality:</p>
            
            <div style="text-align: center; margin: 1.5rem 0;">
                <a href="view_applications.php?export=csv" class="btn btn-success">
                    📊 Test CSV Export (<?= $totalApps ?> records)
                </a>
                <a href="view_applications.php?export=excel" class="btn btn-info">
                    📈 Test Excel Export (<?= $totalApps ?> records)
                </a>
            </div>
            
            <div style="text-align: center; margin-top: 1rem;">
                <a href="view_applications.php" class="btn btn-primary">
                    ← Back to Applications
                </a>
            </div>
        </div>
        
        <?php if ($sampleApp): ?>
        <div class="sample-data">
            <h3>📋 Sample Export Data Structure</h3>
            <p>Preview of fields that will be exported:</p>
            <pre><?= htmlspecialchars(json_encode([
                'ID' => $sampleApp['id'] ?? 'N/A',
                'Full Name' => $sampleApp['full_name'] ?? 'N/A',
                'Email' => $sampleApp['email_id'] ?? 'N/A',
                'Mobile' => $sampleApp['mobile_number'] ?? 'N/A',
                'Course' => $sampleApp['course_name'] ?? 'N/A',
                'Status' => $sampleApp['status'] ?? 'N/A',
                'Submitted' => $sampleApp['created_at'] ?? 'N/A'
            ], JSON_PRETTY_PRINT)) ?></pre>
        </div>
        <?php endif; ?>
        
        <div style="background: #d4edda; padding: 1rem; border-radius: 8px; border-left: 4px solid #28a745; margin-top: 2rem;">
            <h4>✅ Export Features</h4>
            <ul>
                <li><strong>Complete Data:</strong> All 46+ fields exported</li>
                <li><strong>UTF-8 Support:</strong> Proper encoding for international characters</li>
                <li><strong>Filter Support:</strong> Respects current search and filter settings</li>
                <li><strong>Professional Format:</strong> Clean, organized column structure</li>
                <li><strong>File Naming:</strong> Timestamped filenames</li>
                <li><strong>Cross-Platform:</strong> Works with Excel, Google Sheets, LibreOffice</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Add click feedback for export buttons
        document.querySelectorAll('.btn').forEach(button => {
            if (button.href && (button.href.includes('export=csv') || button.href.includes('export=excel'))) {
                button.addEventListener('click', function() {
                    const originalText = this.innerHTML;
                    const exportType = this.href.includes('csv') ? 'CSV' : 'Excel';
                    
                    this.innerHTML = `⏳ Generating ${exportType}...`;
                    this.style.opacity = '0.7';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.style.opacity = '1';
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>
