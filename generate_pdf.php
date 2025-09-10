<?php
// IBMP PDF Generator - Admin Panel
// Generates actual PDF files using wkhtmltopdf or browser PDF generation

// Include database configuration
if (!file_exists('config.php')) {
    die("Configuration file not found. Please contact administrator.");
}

require_once 'config.php';

// Verify required constants are defined
$requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($requiredConstants as $constant) {
    if (!defined($constant)) {
        die("System configuration incomplete. Please contact administrator.");
    }
}

// Database connection using config.php constants
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "<br><br>Please check your database configuration in config.php");
}

// Get application ID from URL parameter
$applicationId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($applicationId <= 0) {
    die("Invalid application ID");
}

// Fetch application data
try {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $stmt->execute([$applicationId]);
    $application = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$application) {
        die("Application not found");
    }
} catch(PDOException $e) {
    die("Error fetching application: " . $e->getMessage());
}

// Check if logo exists
$logoPath = 'ibmp logo.png';
$logoBase64 = '';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoBase64 = 'data:image/png;base64,' . $logoData;
}

// Generate the applicant name for filename
$applicantName = trim(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? ''));
if (empty($applicantName)) {
    $applicantName = 'Applicant';
}
$applicantName = preg_replace('/[^a-zA-Z0-9\s]/', '', $applicantName); // Remove special characters
$applicantName = preg_replace('/\s+/', ' ', $applicantName); // Clean multiple spaces

// Set headers to suggest PDF download with proper filename
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="Application Details - ' . $applicantName . '.html"');

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>IBMP Application - <?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></title>
    <style>
        @page {
            margin: 15mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            background: white;
            padding: 20px;
        }
        
        .pdf-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }
        
        .header-section {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #1e40af;
        }
        
        .logo {
            max-height: 100px;
            margin-bottom: 15px;
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
            text-transform: uppercase;
        }
        
        .header-subtitle {
            font-size: 16px;
            color: #64748b;
            margin-bottom: 15px;
        }
        
        .application-info {
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .app-number {
            font-size: 16px;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .generation-date {
            font-size: 11px;
            color: #64748b;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: bold;
            background: #1e40af;
            color: white;
            padding: 12px 15px;
            margin: 25px 0 15px 0;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .field-row {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            border-bottom: 1px dotted #e5e7eb;
            padding-bottom: 8px;
        }
        
        .field-label {
            font-weight: 600;
            width: 200px;
            color: #374151;
            flex-shrink: 0;
        }
        
        .field-value {
            flex: 1;
            padding-left: 15px;
            color: #1f2937;
        }
        
        .section-content {
            padding: 0 15px 15px 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .status-approved { 
            background: #d1fae5; 
            color: #065f46; 
            border: 2px solid #10b981;
        }
        .status-pending { 
            background: #fef3c7; 
            color: #92400e; 
            border: 2px solid #f59e0b;
        }
        .status-rejected { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 2px solid #ef4444;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #64748b;
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .print-btn {
            background: #1e40af;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .print-btn:hover {
            background: #1d4ed8;
        }
        
        .pdf-instruction {
            background: #eff6ff;
            border: 2px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 14px;
            text-align: center;
        }
        
        .pdf-instruction strong {
            color: #1e40af;
        }
        
        @media print {
            body { -webkit-print-color-adjust: exact; }
            .print-controls { display: none; }
            .pdf-instruction { display: none; }
            .pdf-container { margin: 0; padding: 0; }
            .header-section { page-break-after: avoid; }
            .section-title { page-break-after: avoid; }
        }
    </style>
    <script>
        // Set document title for PDF filename
        document.title = 'Application Details - <?php echo $applicantName; ?>';
        
        function printPDF() {
            window.print();
        }
        
        function downloadPDF() {
            // Trigger browser's save as PDF
            window.print();
        }
        
        function autoDownloadPDF() {
            // Auto-trigger print dialog for PDF save
            setTimeout(function() {
                window.print();
            }, 1000);
        }
        
        // Auto-trigger PDF download when page loads (optional)
        // window.addEventListener('load', autoDownloadPDF);
    </script>
</head>
<body>

<div class="print-controls">
    <button class="print-btn" onclick="printPDF()">📄 Save as PDF</button>
    <button class="print-btn" onclick="autoDownloadPDF()">⬇️ Auto Download PDF</button>
</div>

<div class="pdf-instruction">
    <strong>💡 To save as PDF:</strong> Click "📄 Save as PDF" button above, then in the print dialog select "Save as PDF" as destination. 
    The file will be saved as "Application Details - <?php echo $applicantName; ?>.pdf"
</div>

<div class="pdf-container">
    <div class="header-section">
        <?php if ($logoBase64): ?>
            <img src="<?php echo $logoBase64; ?>" alt="IBMP Logo" class="logo">
        <?php endif; ?>
        <div class="header-title">International Board of Medical Practitioners</div>
        <div class="header-subtitle">Admission Application Form</div>
    </div>

    <div class="application-info">
        <div class="app-number">Application Number: <?php echo htmlspecialchars($application['application_number'] ?? 'N/A'); ?></div>
        <div class="generation-date">Generated on: <?php echo date('F j, Y \a\t g:i A'); ?></div>
        <?php if (isset($application['status'])): ?>
            <div class="status-badge status-<?php echo strtolower($application['status'] ?? 'pending'); ?>">
                Status: <?php echo htmlspecialchars($application['status'] ?? 'Pending'); ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="section-title">Personal Information</div>
    <div class="section-content">
        <div class="field-row">
            <span class="field-label">Title:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['title'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Full Name:</span>
            <span class="field-value"><?php echo htmlspecialchars(($application['first_name'] ?? '') . ' ' . ($application['last_name'] ?? '')); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Date of Birth:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['date_of_birth'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Gender:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['gender'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Nationality:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['nationality'] ?? ''); ?></span>
        </div>
    </div>

    <div class="section-title">Contact Information</div>
    <div class="section-content">
        <div class="field-row">
            <span class="field-label">Email Address:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['email'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Phone Number:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['phone'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Address:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['address'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">City:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['city'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">State/Province:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['state'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Country:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['country'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Postal Code:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['postal_code'] ?? ''); ?></span>
        </div>
    </div>

    <div class="section-title">Program Information</div>
    <div class="section-content">
        <div class="field-row">
            <span class="field-label">Selected Program:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['program'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Payment Option:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['payment_option'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">How did you hear about us:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['referral_source'] ?? ''); ?></span>
        </div>
    </div>

    <?php if (!empty($application['father_name']) || !empty($application['mother_name'])): ?>
    <div class="section-title">Family Information</div>
    <div class="section-content">
        <?php if (!empty($application['father_name'])): ?>
        <div class="field-row">
            <span class="field-label">Father's Name:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['father_name']); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Father's Occupation:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['father_occupation'] ?? ''); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($application['mother_name'])): ?>
        <div class="field-row">
            <span class="field-label">Mother's Name:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['mother_name']); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Mother's Occupation:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['mother_occupation'] ?? ''); ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="section-title">Declaration & Acceptance</div>
    <div class="section-content">
        <?php if (!empty($application['declaration_date'])): ?>
        <div class="field-row">
            <span class="field-label">Declaration Date:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['declaration_date']); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="field-row">
            <span class="field-label">Terms & Conditions:</span>
            <span class="field-value"><?php echo (isset($application['terms_accepted']) && $application['terms_accepted'] ? '✅ Accepted' : '❌ Not Accepted'); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Privacy Policy:</span>
            <span class="field-value"><?php echo (isset($application['privacy_accepted']) && $application['privacy_accepted'] ? '✅ Accepted' : '❌ Not Accepted'); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Declaration Confirmed:</span>
            <span class="field-value"><?php echo (isset($application['declaration_accepted']) && $application['declaration_accepted'] ? '✅ Confirmed' : '❌ Not Confirmed'); ?></span>
        </div>
    </div>

    <div class="section-title">Submission Details</div>
    <div class="section-content">
        <div class="field-row">
            <span class="field-label">Application ID:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['id'] ?? ''); ?></span>
        </div>
        <div class="field-row">
            <span class="field-label">Submitted On:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['created_at'] ?? ''); ?></span>
        </div>
        <?php if (!empty($application['updated_at']) && $application['updated_at'] != $application['created_at']): ?>
        <div class="field-row">
            <span class="field-label">Last Updated:</span>
            <span class="field-value"><?php echo htmlspecialchars($application['updated_at']); ?></span>
        </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p><strong>International Board of Medical Practitioners (IBMP)</strong></p>
        <p>This document was automatically generated from the IBMP admission system.</p>
        <p>For any queries, please contact: admission@ibmpractitioner.us</p>
        <p style="margin-top: 10px; font-size: 9px;">
            Generated at: <?php echo date('Y-m-d H:i:s T'); ?> | 
            Document ID: <?php echo $application['application_number'] ?? $application['id']; ?>
        </p>
    </div>
</div>

</body>
</html>
<?php
// Close database connection
$pdo = null;
?>
