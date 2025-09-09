<?php
// File Management and Cleanup Tool for IBMP Admission Form
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IBMP File Management & Cleanup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            color: #1e3c72;
        }
        
        .section {
            margin: 2rem 0;
            padding: 1.5rem;
            border: 1px solid #e9ecef;
            border-radius: 10px;
        }
        
        .essential { border-left: 5px solid #28a745; background: #f8fff9; }
        .empty { border-left: 5px solid #dc3545; background: #fff8f8; }
        .duplicate { border-left: 5px solid #ffc107; background: #fffdf7; }
        .utility { border-left: 5px solid #17a2b8; background: #f7fcfd; }
        
        .file-list {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 0.5rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .file-item {
            display: contents;
        }
        
        .filename {
            padding: 0.25rem;
        }
        
        .filesize {
            color: #6c757d;
            text-align: right;
        }
        
        .status {
            font-weight: bold;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            margin: 0.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        h3 {
            margin-top: 0;
            color: #495057;
        }
        
        .recommendation {
            background: #e7f3ff;
            border: 1px solid #b8daff;
            border-radius: 5px;
            padding: 1rem;
            margin: 1rem 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🗂️ IBMP File Management & Cleanup</h1>
            <p>Analyze and optimize your admission form files</p>
        </div>

<?php
// File analysis
$currentDir = __DIR__;
$files = scandir($currentDir);

// Categories
$essential_files = [];
$empty_files = [];
$duplicate_files = [];
$utility_files = [];
$unnecessary_files = [];

// Essential files that should always be present
$required_files = [
    'index.html' => 'Main admission form',
    'styles.css' => 'Form styling',
    'enhanced-script.js' => 'Form JavaScript',
    'config.php' => 'Database configuration',
    'submit_application_new.php' => 'Form submission handler',
    'admin_login.php' => 'Admin login',
    'admin_logout.php' => 'Admin logout',
    'view_applications.php' => 'Applications dashboard',
    'view_application.php' => 'View individual application',
    'edit_application.php' => 'Edit applications',
    'generate_invoice.php' => 'Invoice generator',
    'success.html' => 'Success page',
    'ibmp logo.png' => 'Logo image',
    'database_analysis.php' => 'Database analyzer',
    'fix_database.php' => 'Database repair',
    'check_database.php' => 'Database connection test',
];

foreach ($files as $file) {
    if ($file == '.' || $file == '..' || is_dir($file)) continue;
    
    $filepath = $currentDir . DIRECTORY_SEPARATOR . $file;
    $filesize = filesize($filepath);
    
    // Categorize files
    if (array_key_exists($file, $required_files)) {
        $essential_files[$file] = ['size' => $filesize, 'description' => $required_files[$file]];
    }
    elseif ($filesize == 0) {
        $empty_files[$file] = $filesize;
    }
    elseif (strpos($file, 'enhanced') !== false || strpos($file, 'fixed') !== false || 
            strpos($file, 'simple') !== false || strpos($file, '_old') !== false) {
        $duplicate_files[$file] = $filesize;
    }
    elseif (strpos($file, 'test') !== false || strpos($file, 'debug') !== false || 
            strpos($file, 'backup') !== false || $file == 'DEPLOYMENT_GUIDE.md') {
        $utility_files[$file] = $filesize;
    }
    else {
        // Check if it might be unnecessary
        if (strpos($file, '.sql') !== false || strpos($file, 'setup') !== false) {
            $utility_files[$file] = $filesize;
        } else {
            $essential_files[$file] = ['size' => $filesize, 'description' => 'Additional file'];
        }
    }
}

function formatFileSize($size) {
    if ($size == 0) return '0 B';
    $units = ['B', 'KB', 'MB'];
    $unitIndex = 0;
    while ($size >= 1024 && $unitIndex < count($units) - 1) {
        $size /= 1024;
        $unitIndex++;
    }
    return round($size, 1) . ' ' . $units[$unitIndex];
}
?>

        <!-- Essential Files -->
        <div class="section essential">
            <h3>✅ Essential Files (Keep These)</h3>
            <div class="file-list">
                <?php foreach ($essential_files as $file => $info): ?>
                <div class="file-item">
                    <div class="filename"><?= htmlspecialchars($file) ?></div>
                    <div class="filesize"><?= formatFileSize($info['size']) ?></div>
                    <div class="status" style="color: #28a745;">KEEP</div>
                </div>
                <?php endforeach; ?>
            </div>
            <p><strong>Total: <?= count($essential_files) ?> files</strong> - These are required for the system to function.</p>
        </div>

        <!-- Empty Files -->
        <?php if (!empty($empty_files)): ?>
        <div class="section empty">
            <h3>❌ Empty Files (Safe to Delete)</h3>
            <div class="file-list">
                <?php foreach ($empty_files as $file => $size): ?>
                <div class="file-item">
                    <div class="filename"><?= htmlspecialchars($file) ?></div>
                    <div class="filesize">0 B</div>
                    <div class="status" style="color: #dc3545;">DELETE</div>
                </div>
                <?php endforeach; ?>
            </div>
            <p><strong>Total: <?= count($empty_files) ?> files</strong> - These files are empty and can be safely deleted.</p>
            
            <?php if (isset($_POST['delete_empty'])): ?>
                <?php
                $deleted_count = 0;
                foreach ($empty_files as $file => $size) {
                    $filepath = $currentDir . DIRECTORY_SEPARATOR . $file;
                    if (unlink($filepath)) {
                        $deleted_count++;
                    }
                }
                ?>
                <div class="recommendation">
                    <strong>✅ Deleted <?= $deleted_count ?> empty files!</strong>
                </div>
            <?php else: ?>
                <form method="POST" style="margin-top: 1rem;">
                    <button type="submit" name="delete_empty" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete all empty files?')">
                        🗑️ Delete All Empty Files
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Duplicate/Version Files -->
        <?php if (!empty($duplicate_files)): ?>
        <div class="section duplicate">
            <h3>⚠️ Duplicate/Version Files (Review Before Deleting)</h3>
            <div class="file-list">
                <?php foreach ($duplicate_files as $file => $size): ?>
                <div class="file-item">
                    <div class="filename"><?= htmlspecialchars($file) ?></div>
                    <div class="filesize"><?= formatFileSize($size) ?></div>
                    <div class="status" style="color: #ffc107;">REVIEW</div>
                </div>
                <?php endforeach; ?>
            </div>
            <p><strong>Total: <?= count($duplicate_files) ?> files</strong> - These appear to be alternative versions. Review before deleting.</p>
        </div>
        <?php endif; ?>

        <!-- Utility Files -->
        <?php if (!empty($utility_files)): ?>
        <div class="section utility">
            <h3>🔧 Utility Files (Keep for Maintenance)</h3>
            <div class="file-list">
                <?php foreach ($utility_files as $file => $size): ?>
                <div class="file-item">
                    <div class="filename"><?= htmlspecialchars($file) ?></div>
                    <div class="filesize"><?= formatFileSize($size) ?></div>
                    <div class="status" style="color: #17a2b8;">UTILITY</div>
                </div>
                <?php endforeach; ?>
            </div>
            <p><strong>Total: <?= count($utility_files) ?> files</strong> - Useful for testing and maintenance.</p>
        </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <div class="recommendation">
            <h3>💡 Cleanup Recommendations:</h3>
            <ul style="margin: 0.5rem 0;">
                <li><strong>Delete empty files</strong> - They serve no purpose</li>
                <li><strong>Keep one version</strong> of duplicate files (usually the newest)</li>
                <li><strong>Keep utility files</strong> for troubleshooting and maintenance</li>
                <li><strong>Archive test files</strong> after deployment</li>
            </ul>
        </div>

        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 2rem;">
            <a href="check_database.php" class="btn">🔍 Test Database Connection</a>
            <a href="database_analysis.php" class="btn">📊 Analyze Database</a>
            <a href="index.html" class="btn">📝 View Admission Form</a>
        </div>

        <!-- File Statistics -->
        <div class="section" style="background: #f8f9fa;">
            <h3>📊 File Statistics</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div><strong>Essential Files:</strong> <?= count($essential_files) ?></div>
                <div><strong>Empty Files:</strong> <?= count($empty_files) ?></div>
                <div><strong>Duplicate Files:</strong> <?= count($duplicate_files) ?></div>
                <div><strong>Utility Files:</strong> <?= count($utility_files) ?></div>
            </div>
        </div>
    </div>
</body>
</html>
