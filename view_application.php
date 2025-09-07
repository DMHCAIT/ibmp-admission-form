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

// Get application ID
$applicationId = $_GET['id'] ?? null;
if (!$applicationId) {
    header('Location: view_applications.php');
    exit();
}

// Get database connection
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Get application details
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$applicationId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: view_applications.php?error=not_found');
    exit();
}

function displayValue($value) {
    return htmlspecialchars($value ?? 'Not provided');
}

function formatDate($date) {
    return $date ? date('F j, Y', strtotime($date)) : 'Not provided';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Details - <?= displayValue($application['full_name']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1.5rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .main-content {
            margin: 2rem 0;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .application-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .application-info h2 {
            color: #1e3c72;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }

        .application-meta {
            color: #666;
            font-size: 14px;
            line-height: 1.8;
        }

        .application-meta strong {
            color: #495057;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 600;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #218838 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #212529;
        }

        .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
            color: white;
        }

        .application-body {
            padding: 2rem;
        }

        .section {
            margin-bottom: 2.5rem;
        }

        .section-title {
            color: #1e3c72;
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .info-item {
            padding: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 12px;
            border-left: 5px solid #1e3c72;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #333;
            font-size: 1rem;
            word-wrap: break-word;
        }

        .status-badge {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            display: inline-block;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .status-under_review {
            background: linear-gradient(135deg, #d4edda 0%, #00b894 100%);
            color: #155724;
        }

        .status-approved {
            background: linear-gradient(135deg, #d1ecf1 0%, #74b9ff 100%);
            color: #0c5460;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #e17055 100%);
            color: #721c24;
        }

        .status-waitlist {
            background: linear-gradient(135deg, #e2e3e5 0%, #b2bec3 100%);
            color: #383d41;
        }

        .education-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .education-table th,
        .education-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .education-table th {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .education-table tr:nth-child(even) {
            background: #f8f9fa;
        }

        .education-table tr:hover {
            background: linear-gradient(135deg, #e9ecef 0%, #f8f9fa 100%);
        }

        .files-section {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 2rem;
            border-radius: 15px;
            margin-top: 1rem;
            border: 2px dashed #dee2e6;
        }

        .file-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .file-link:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .photo-preview {
            max-width: 200px;
            max-height: 250px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 3px solid #fff;
        }

        .payment-info {
            background: linear-gradient(135deg, #e8f5e8 0%, #f0fff0 100%);
            border: 2px solid #28a745;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .application-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .action-buttons {
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .education-table {
                font-size: 14px;
            }
            
            .education-table th,
            .education-table td {
                padding: 0.5rem;
            }
        }

        @media print {
            body {
                background: white;
            }
            
            .header,
            .action-buttons,
            .back-btn {
                display: none;
            }
            
            .main-content {
                box-shadow: none;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>👁️ Application Details</h1>
                <a href="view_applications.php" class="back-btn">← Back to Applications</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="application-header">
                <div class="application-info">
                    <h2><?= displayValue($application['full_name']) ?></h2>
                    <div class="application-meta">
                        <strong>Application ID:</strong> #<?= $application['id'] ?> | 
                        <strong>Submitted:</strong> <?= formatDate($application['created_at']) ?> |
                        <strong>Status:</strong> <span class="status-badge status-<?= strtolower($application['status'] ?? 'pending') ?>"><?= ucfirst($application['status'] ?? 'Pending') ?></span>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="edit_application.php?id=<?= $application['id'] ?>" class="btn btn-warning">✏️ Edit</a>
                    <a href="generate_invoice.php?id=<?= $application['id'] ?>" class="btn btn-success">💳 Invoice</a>
                    <a href="generate_pdf.php?id=<?= $application['id'] ?>" class="btn btn-danger">📄 PDF</a>
                    <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
                </div>
            </div>

            <div class="application-body">
                <!-- Personal Information -->
                <div class="section">
                    <h3 class="section-title">👤 Personal Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Title</div>
                            <div class="info-value"><?= displayValue($application['title']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= displayValue($application['full_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Email Address</div>
                            <div class="info-value"><?= displayValue($application['email_id']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Mobile Number</div>
                            <div class="info-value"><?= displayValue($application['mobile_number']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?= displayValue($application['phone_number']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?= formatDate($application['date_of_birth']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?= displayValue($application['gender']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nationality</div>
                            <div class="info-value"><?= displayValue($application['nationality']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= displayValue($application['address']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">City</div>
                            <div class="info-value"><?= displayValue($application['city']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Postal Code</div>
                            <div class="info-value"><?= displayValue($application['postal_code']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Course Information -->
                <div class="section">
                    <h3 class="section-title">🎓 Course Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Course Type</div>
                            <div class="info-value"><?= displayValue($application['course_type']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Course Name</div>
                            <div class="info-value"><?= displayValue($application['course_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Preferred Start Date</div>
                            <div class="info-value"><?= formatDate($application['preferred_start_date']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Study Mode</div>
                            <div class="info-value"><?= displayValue($application['study_mode']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Educational Background -->
                <div class="section">
                    <h3 class="section-title">📚 Educational Background</h3>
                    <table class="education-table">
                        <thead>
                            <tr>
                                <th>Level</th>
                                <th>Board/University</th>
                                <th>Year</th>
                                <th>Marks Obtained</th>
                                <th>Total Marks</th>
                                <th>Percentage</th>
                                <th>CGPA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Matriculation</strong></td>
                                <td><?= displayValue($application['matric_board']) ?></td>
                                <td><?= displayValue($application['matric_year']) ?></td>
                                <td><?= displayValue($application['matric_marks']) ?></td>
                                <td><?= displayValue($application['matric_total_marks']) ?></td>
                                <td><?= displayValue($application['matric_percentage']) ?>%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><strong>Intermediate</strong></td>
                                <td><?= displayValue($application['inter_board']) ?></td>
                                <td><?= displayValue($application['inter_year']) ?></td>
                                <td><?= displayValue($application['inter_marks']) ?></td>
                                <td><?= displayValue($application['inter_total_marks']) ?></td>
                                <td><?= displayValue($application['inter_percentage']) ?>%</td>
                                <td>-</td>
                            </tr>
                            <tr>
                                <td><strong>Bachelor's</strong></td>
                                <td><?= displayValue($application['bachelor_university']) ?></td>
                                <td><?= displayValue($application['bachelor_year']) ?></td>
                                <td>-</td>
                                <td>-</td>
                                <td><?= displayValue($application['bachelor_percentage']) ?>%</td>
                                <td><?= displayValue($application['bachelor_cgpa']) ?></td>
                            </tr>
                            <?php if ($application['master_university']): ?>
                            <tr>
                                <td><strong>Master's</strong></td>
                                <td><?= displayValue($application['master_university']) ?></td>
                                <td><?= displayValue($application['master_year']) ?></td>
                                <td>-</td>
                                <td>-</td>
                                <td><?= displayValue($application['master_percentage']) ?>%</td>
                                <td><?= displayValue($application['master_cgpa']) ?></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Sponsor Information -->
                <?php if ($application['sponsor_name']): ?>
                <div class="section">
                    <h3 class="section-title">💰 Sponsor Information</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Sponsor Name</div>
                            <div class="info-value"><?= displayValue($application['sponsor_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Relationship</div>
                            <div class="info-value"><?= displayValue($application['sponsor_relationship']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Income</div>
                            <div class="info-value"><?= displayValue($application['sponsor_income']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Occupation</div>
                            <div class="info-value"><?= displayValue($application['sponsor_occupation']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Payment Information -->
                <?php if ($application['payment_option']): ?>
                <div class="section">
                    <h3 class="section-title">💳 Payment Information</h3>
                    <div class="payment-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Payment Option</div>
                                <div class="info-value"><?= displayValue($application['payment_option']) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Emergency Contact -->
                <?php if ($application['emergency_contact_name']): ?>
                <div class="section">
                    <h3 class="section-title">🚨 Emergency Contact</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Contact Name</div>
                            <div class="info-value"><?= displayValue($application['emergency_contact_name']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Relationship</div>
                            <div class="info-value"><?= displayValue($application['emergency_contact_relationship']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone Number</div>
                            <div class="info-value"><?= displayValue($application['emergency_contact_phone']) ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= displayValue($application['emergency_contact_address']) ?></div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Uploaded Documents -->
                <div class="section">
                    <h3 class="section-title">📁 Uploaded Documents</h3>
                    <div class="files-section">
                        <?php 
                        $fileFields = [
                            'photo' => 'Passport Photo',
                            'matric_certificate' => 'Matriculation Certificate', 
                            'inter_certificate' => 'Intermediate Certificate',
                            'bachelor_certificate' => 'Bachelor Certificate',
                            'master_certificate' => 'Master Certificate'
                        ];
                        
                        $hasFiles = false;
                        foreach ($fileFields as $field => $label):
                            if ($application[$field]):
                                $hasFiles = true;
                                $filePath = 'uploads/' . $application[$field];
                                if (file_exists($filePath)):
                        ?>
                            <div style="margin-bottom: 1rem;">
                                <strong><?= $label ?>:</strong>
                                <a href="<?= $filePath ?>" class="file-link" target="_blank">📄 View <?= $label ?></a>
                                <?php if ($field === 'photo' && file_exists($filePath)): ?>
                                    <br><br>
                                    <img src="<?= $filePath ?>" class="photo-preview" alt="Student Photo">
                                <?php endif; ?>
                            </div>
                        <?php 
                                endif;
                            endif;
                        endforeach;
                        
                        if (!$hasFiles): 
                        ?>
                            <p style="color: #666; font-style: italic; text-align: center; padding: 2rem;">
                                📂 No documents have been uploaded yet.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
