<?php
require_once 'config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Get database connection
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle file deletion
if ($_POST && isset($_POST['delete_file'])) {
    $filePath = $_POST['file_path'];
    $applicationId = $_POST['application_id'];
    $fileType = $_POST['file_type'];
    
    // Delete physical file
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Update database to remove file path
    $sql = "UPDATE applications SET {$fileType}_path = NULL WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$applicationId]);
    
    header("Location: view_application_enhanced.php?id=$applicationId&file_deleted=1");
    exit();
}

// Get all applications with files
$sql = "SELECT id, application_number, full_name, passport_photo_path, cv_path, 
               educational_certificates_path, marksheets_path, identity_proof_path, 
               submitted_at FROM applications 
        WHERE passport_photo_path IS NOT NULL 
           OR cv_path IS NOT NULL 
           OR educational_certificates_path IS NOT NULL 
           OR marksheets_path IS NOT NULL 
           OR identity_proof_path IS NOT NULL
        ORDER BY submitted_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate storage usage
$uploadDir = 'uploads/';
$totalSize = 0;
$fileCount = 0;

if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadDir . $file;
            if (is_file($filePath)) {
                $totalSize += filesize($filePath);
                $fileCount++;
            }
        }
    }
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Management - IBMP Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .stats-section {
            margin: 2rem 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #1e3c72;
        }

        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }

        .applications-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .section-header {
            background: #f8f9fa;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #dee2e6;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
        }

        .applications-table th,
        .applications-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .applications-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }

        .applications-table tr:hover {
            background: #f8f9fa;
        }

        .file-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 5px;
        }

        .file-uploaded {
            background: #28a745;
        }

        .file-missing {
            background: #dc3545;
        }

        .file-links {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .file-link {
            padding: 0.25rem 0.5rem;
            background: #1e3c72;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .file-link:hover {
            background: #2a5298;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>File Management - IBMP Admin</h1>
                <a href="view_applications_enhanced.php" class="back-btn">← Back to Applications</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_GET['file_deleted'])): ?>
            <div class="alert alert-success">
                File deleted successfully!
            </div>
        <?php endif; ?>

        <!-- Storage Statistics -->
        <div class="stats-section">
            <div class="stat-card">
                <div class="stat-number"><?= $fileCount ?></div>
                <div class="stat-label">Total Files</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= formatBytes($totalSize) ?></div>
                <div class="stat-label">Storage Used</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($applications) ?></div>
                <div class="stat-label">Applications with Files</div>
            </div>
        </div>

        <!-- Applications with Files -->
        <div class="applications-section">
            <div class="section-header">
                <h2>Applications with Uploaded Files</h2>
            </div>
            
            <?php if (empty($applications)): ?>
                <div style="padding: 2rem; text-align: center; color: #666;">
                    No applications with uploaded files found.
                </div>
            <?php else: ?>
                <table class="applications-table">
                    <thead>
                        <tr>
                            <th>Application #</th>
                            <th>Student Name</th>
                            <th>Files Status</th>
                            <th>Uploaded Files</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($app['application_number']) ?></strong></td>
                            <td><?= htmlspecialchars($app['full_name']) ?></td>
                            <td>
                                <span class="file-indicator <?= $app['passport_photo_path'] ? 'file-uploaded' : 'file-missing' ?>" title="Passport Photo"></span>
                                <span class="file-indicator <?= $app['cv_path'] ? 'file-uploaded' : 'file-missing' ?>" title="CV"></span>
                                <span class="file-indicator <?= $app['educational_certificates_path'] ? 'file-uploaded' : 'file-missing' ?>" title="Educational Certificates"></span>
                                <span class="file-indicator <?= $app['marksheets_path'] ? 'file-uploaded' : 'file-missing' ?>" title="Marksheets"></span>
                                <span class="file-indicator <?= $app['identity_proof_path'] ? 'file-uploaded' : 'file-missing' ?>" title="Identity Proof"></span>
                            </td>
                            <td>
                                <div class="file-links">
                                    <?php if ($app['passport_photo_path']): ?>
                                        <a href="<?= htmlspecialchars($app['passport_photo_path']) ?>" target="_blank" class="file-link">Photo</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['cv_path']): ?>
                                        <a href="<?= htmlspecialchars($app['cv_path']) ?>" target="_blank" class="file-link">CV</a>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['educational_certificates_path']): ?>
                                        <?php 
                                        $certificates = explode(',', $app['educational_certificates_path']);
                                        foreach ($certificates as $index => $cert): 
                                        ?>
                                            <a href="<?= htmlspecialchars(trim($cert)) ?>" target="_blank" class="file-link">Cert <?= $index + 1 ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['marksheets_path']): ?>
                                        <?php 
                                        $marksheets = explode(',', $app['marksheets_path']);
                                        foreach ($marksheets as $index => $marksheet): 
                                        ?>
                                            <a href="<?= htmlspecialchars(trim($marksheet)) ?>" target="_blank" class="file-link">Mark <?= $index + 1 ?></a>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <?php if ($app['identity_proof_path']): ?>
                                        <a href="<?= htmlspecialchars($app['identity_proof_path']) ?>" target="_blank" class="file-link">ID</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= date('M j, Y', strtotime($app['submitted_at'])) ?></td>
                            <td>
                                <div class="actions">
                                    <a href="view_application_enhanced.php?id=<?= $app['id'] ?>" class="btn btn-info">View Details</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
