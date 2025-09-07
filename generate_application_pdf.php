<?php
require_once 'config.php';

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}

// Get application ID
$applicationId = $_GET['id'] ?? null;
if (!$applicationId) {
    die('Application ID not provided');
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
    die('Application not found');
}

// Function to safely display values
function displayValue($value) {
    return htmlspecialchars($value ?? 'Not provided');
}

function formatDate($date) {
    return $date ? date('F j, Y', strtotime($date)) : 'Not provided';
}

// Set headers for PDF download
header('Content-Type: text/html; charset=utf-8');
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
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
            color: #333;
            background: white;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border: 2px solid #333;
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            margin-bottom: 20px;
        }

        .logo img {
            height: 80px;
            width: auto;
        }

        .title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .subtitle {
            font-size: 18px;
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            background: #f0f0f0;
            padding: 8px 12px;
            border-left: 4px solid #333;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px 30px;
            margin-bottom: 15px;
        }

        .info-item {
            border-bottom: 1px dotted #ccc;
            padding-bottom: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 2px;
        }

        .info-value {
            color: #333;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .table th,
        .table td {
            border: 1px solid #333;
            padding: 8px 12px;
            text-align: left;
        }

        .table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-under_review {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }

        @media print {
            body {
                padding: 0;
            }
            .container {
                border: none;
                padding: 20px;
            }
            .no-print {
                display: none !important;
            }
        }

        .print-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
            font-size: 14px;
        }

        .btn:hover {
            background: #0056b3;
        }

        .btn-print {
            background: #28a745;
        }

        .btn-print:hover {
            background: #1e7e34;
        }
    </style>
</head>
<body>
    <div class="print-buttons no-print">
        <a href="view_applications.php" class="btn">← Back</a>
        <a href="javascript:window.print()" class="btn btn-print">🖨️ Print PDF</a>
    </div>

    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="ibmp logo.png" alt="IBMP Logo" style="height: 60px;">
            </div>
            <div class="title">International Board of Medical Practitioners</div>
            <div class="subtitle">Application for Admission</div>
            <div style="margin-top: 15px;">
                <strong>Application Number:</strong> <?= displayValue($application['application_number']) ?><br>
                <strong>Form Number:</strong> <?= displayValue($application['form_number']) ?><br>
                <strong>Status:</strong> 
                <span class="status-badge status-<?= $application['status'] ?? 'pending' ?>">
                    <?= ucfirst(str_replace('_', ' ', $application['status'] ?? 'pending')) ?>
                </span>
            </div>
        </div>

        <!-- Personal Information -->
        <div class="section">
            <div class="section-title">Personal Information</div>
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
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?= formatDate($application['date_of_birth']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Age</div>
                    <div class="info-value"><?= displayValue($application['age']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?= displayValue($application['gender']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Category</div>
                    <div class="info-value"><?= displayValue($application['category']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Nationality</div>
                    <div class="info-value"><?= displayValue($application['nationality']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Marital Status</div>
                    <div class="info-value"><?= displayValue($application['marital_status']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Religion</div>
                    <div class="info-value"><?= displayValue($application['religion']) ?></div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="section">
            <div class="section-title">Contact Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Email ID</div>
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
                    <div class="info-label">City</div>
                    <div class="info-value"><?= displayValue($application['city']) ?></div>
                </div>
                <div class="info-item full-width">
                    <div class="info-label">Correspondence Address</div>
                    <div class="info-value"><?= displayValue($application['correspondence_address']) ?></div>
                </div>
                <div class="info-item full-width">
                    <div class="info-label">Permanent Address</div>
                    <div class="info-value"><?= displayValue($application['permanent_address']) ?></div>
                </div>
            </div>
        </div>

        <!-- Course Information -->
        <div class="section">
            <div class="section-title">Course Information</div>
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
                    <div class="info-label">Session Year</div>
                    <div class="info-value"><?= displayValue($application['session_year']) ?></div>
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
            <div class="section-title">Educational Background</div>
            
            <!-- 10th Standard -->
            <h4 style="margin-bottom: 10px;">10th Standard (Matriculation)</h4>
            <table class="table">
                <tr>
                    <th>School/Board</th>
                    <th>Year</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                </tr>
                <tr>
                    <td><?= displayValue($application['school_10th'] ?? $application['matric_board']) ?></td>
                    <td><?= displayValue($application['year_10th'] ?? $application['matric_year']) ?></td>
                    <td><?= displayValue($application['marks_10th'] ?? $application['matric_marks']) ?></td>
                    <td><?= displayValue($application['max_marks_10th'] ?? $application['matric_total_marks']) ?></td>
                    <td><?= displayValue($application['percentage_10th'] ?? $application['matric_percentage']) ?>%</td>
                </tr>
            </table>

            <!-- 12th Standard -->
            <h4 style="margin-bottom: 10px;">12th Standard (Intermediate)</h4>
            <table class="table">
                <tr>
                    <th>School/Board</th>
                    <th>Year</th>
                    <th>Marks Obtained</th>
                    <th>Total Marks</th>
                    <th>Percentage</th>
                </tr>
                <tr>
                    <td><?= displayValue($application['school_12th'] ?? $application['inter_board']) ?></td>
                    <td><?= displayValue($application['year_12th'] ?? $application['inter_year']) ?></td>
                    <td><?= displayValue($application['marks_12th'] ?? $application['inter_marks']) ?></td>
                    <td><?= displayValue($application['max_marks_12th'] ?? $application['inter_total_marks']) ?></td>
                    <td><?= displayValue($application['percentage_12th'] ?? $application['inter_percentage']) ?>%</td>
                </tr>
            </table>

            <!-- Bachelor's Degree -->
            <?php if ($application['college_ug'] || $application['bachelor_university']): ?>
            <h4 style="margin-bottom: 10px;">Bachelor's Degree</h4>
            <table class="table">
                <tr>
                    <th>University/College</th>
                    <th>Year</th>
                    <th>Subject/Course</th>
                    <th>Percentage/CGPA</th>
                </tr>
                <tr>
                    <td><?= displayValue($application['college_ug'] ?? $application['bachelor_university']) ?></td>
                    <td><?= displayValue($application['year_ug'] ?? $application['bachelor_year']) ?></td>
                    <td><?= displayValue($application['subject_ug']) ?></td>
                    <td>
                        <?= displayValue($application['percentage_ug'] ?? $application['bachelor_percentage']) ?>%
                        <?php if ($application['bachelor_cgpa']): ?>
                            / <?= displayValue($application['bachelor_cgpa']) ?> CGPA
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php endif; ?>

            <!-- Master's Degree -->
            <?php if ($application['college_pg'] || $application['master_university']): ?>
            <h4 style="margin-bottom: 10px;">Master's Degree</h4>
            <table class="table">
                <tr>
                    <th>University/College</th>
                    <th>Year</th>
                    <th>Subject/Course</th>
                    <th>Percentage/CGPA</th>
                </tr>
                <tr>
                    <td><?= displayValue($application['college_pg'] ?? $application['master_university']) ?></td>
                    <td><?= displayValue($application['year_pg'] ?? $application['master_year']) ?></td>
                    <td><?= displayValue($application['subject_pg']) ?></td>
                    <td>
                        <?= displayValue($application['percentage_pg'] ?? $application['master_percentage']) ?>%
                        <?php if ($application['master_cgpa']): ?>
                            / <?= displayValue($application['master_cgpa']) ?> CGPA
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php endif; ?>
        </div>

        <!-- Parent/Guardian Information -->
        <?php if ($application['parent_name']): ?>
        <div class="section">
            <div class="section-title">Parent/Guardian Information</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Parent/Guardian Name</div>
                    <div class="info-value"><?= displayValue($application['parent_name']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Occupation</div>
                    <div class="info-value"><?= displayValue($application['parent_occupation']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Mobile Number</div>
                    <div class="info-value"><?= displayValue($application['parent_mobile']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?= displayValue($application['parent_email']) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Emergency Contact -->
        <?php if ($application['emergency_contact_name']): ?>
        <div class="section">
            <div class="section-title">Emergency Contact</div>
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
                <div class="info-item full-width">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?= displayValue($application['emergency_contact_address']) ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Application Status -->
        <div class="section">
            <div class="section-title">Application Status</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Current Status</div>
                    <div class="info-value">
                        <span class="status-badge status-<?= $application['status'] ?? 'pending' ?>">
                            <?= ucfirst(str_replace('_', ' ', $application['status'] ?? 'pending')) ?>
                        </span>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Submitted At</div>
                    <div class="info-value"><?= formatDate($application['submitted_at']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Last Updated</div>
                    <div class="info-value"><?= formatDate($application['updated_at']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Referral Source</div>
                    <div class="info-value"><?= displayValue($application['referral_source']) ?></div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p><strong>International Board of Medical Practitioners (IBMP)</strong></p>
            <p>This is a computer-generated document. Generated on <?= date('F j, Y \a\t g:i A') ?></p>
            <p>For queries, please contact: admin@ibmpractitioner.us</p>
        </div>
    </div>

    <script>
        // Auto-print functionality
        if (window.location.search.includes('auto_print=1')) {
            window.onload = function() {
                window.print();
            }
        }
    </script>
</body>
</html>
