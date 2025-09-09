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

// Get application ID
$applicationId = $_GET['id'] ?? null;
if (!$applicationId) {
    header('Location: view_applications.php');
    exit();
}

// Handle form submission for invoice generation
if ($_POST) {
    try {
        // Update or insert invoice data
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE application_id = ?");
        $stmt->execute([$applicationId]);
        $existingInvoice = $stmt->fetch();
        
        if ($existingInvoice) {
            // Update existing invoice
            $stmt = $pdo->prepare("UPDATE invoices SET 
                invoice_number = ?, 
                invoice_date = ?, 
                due_date = ?, 
                course_name = ?, 
                course_amount = ?, 
                discount = ?, 
                tax_rate = ?, 
                notes = ?, 
                updated_at = NOW() 
                WHERE application_id = ?");
            $stmt->execute([
                $_POST['invoice_number'],
                $_POST['invoice_date'],
                $_POST['due_date'],
                $_POST['course_name'],
                $_POST['course_amount'],
                $_POST['discount'] ?? 0,
                $_POST['tax_rate'] ?? 0,
                $_POST['notes'],
                $applicationId
            ]);
        } else {
            // Insert new invoice
            $stmt = $pdo->prepare("INSERT INTO invoices (
                application_id, invoice_number, invoice_date, due_date, 
                course_name, course_amount, discount, tax_rate, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $applicationId,
                $_POST['invoice_number'],
                $_POST['invoice_date'],
                $_POST['due_date'],
                $_POST['course_name'],
                $_POST['course_amount'],
                $_POST['discount'] ?? 0,
                $_POST['tax_rate'] ?? 0,
                $_POST['notes']
            ]);
        }
        
        $success = "Invoice saved successfully!";
        
    } catch (Exception $e) {
        $error = "Error saving invoice: " . $e->getMessage();
    }
}

// Get application details
$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$applicationId]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header('Location: view_applications.php?error=not_found');
    exit();
}

// Get existing invoice if available
$stmt = $pdo->prepare("SELECT * FROM invoices WHERE application_id = ?");
$stmt->execute([$applicationId]);
$invoice = $stmt->fetch(PDO::FETCH_ASSOC);

// Generate invoice number if new
if (!$invoice) {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM invoices WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?");
    $stmt->execute([$currentMonth, $currentYear]);
    $count = $stmt->fetchColumn() + 1;
    $invoiceNumber = 'IBMP-' . $currentYear . $currentMonth . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
} else {
    $invoiceNumber = $invoice['invoice_number'];
}

// Course prices (you can modify these)
$coursePrices = [
    'Fellowship in Emergency Medicine' => 2500,
    'Fellowship in Diabetology' => 2200,
    'Fellowship in Family Medicine' => 2000,
    'Fellowship in Anesthesiology' => 2800,
    'Fellowship in Critical Care' => 3000,
    'Fellowship in Internal Medicine' => 2300,
    'Fellowship in Endocrinology' => 2400,
    'Fellowship in Obstetrics & Gynecology' => 2700,
    'Fellowship in Reproductive Medicine' => 3200,
    'Fellowship in Neonatology' => 2900,
    'Fellowship in Dermatology' => 2600,
    'Fellowship in Cosmetology & Aesthetic Medicine' => 2800,
    'Fellowship in Echocardiography' => 2500,
    'Fellowship in Clinical Cardiology' => 2700,
    'Fellowship in Interventional Radiology' => 3500,
    'Fellowship in Sleep Medicine' => 2200,
    'Fellowship in Clinical Oncology' => 3000,
    'Fellowship in Arthroscopy & Joint Replacement' => 3200,
    'Fellowship in Minimal Access Surgery' => 3500,
    'Fellowship in Robotic Surgery' => 4000,
    'Fellowship in Oral Implantology & Laser Dentistry' => 2800,
    'Fellowship in Dialysis' => 2300,
    'Fellowship in Digital Health' => 1800,
    'Fellowship in Ophthalmology' => 2500
];

$defaultPrice = $coursePrices[$application['course_name']] ?? 2000;

function displayValue($value) {
    return htmlspecialchars($value ?? '');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Generator - IBMP</title>
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
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .content {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 25px;
            border-left: 5px solid #667eea;
        }

        .section-header {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .form-group input, .form-group select, .form-group textarea {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .invoice-preview {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            background: white;
            margin-top: 30px;
            page-break-inside: avoid;
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .company-info {
            flex: 1;
            min-width: 250px;
        }

        .company-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .logo-container {
            width: 120px;
            height: 120px;
            background: transparent;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
            font-weight: bold;
            font-size: 1.2rem;
            position: relative;
        }

        .logo-image {
            width: 110px;
            height: 110px;
            object-fit: contain;
            margin-right: 10px;
        }

        .logo-text {
            display: none;
        }

        .logo-image[style*="display: none"] + .logo-text {
            display: block;
        }

        .company-name {
            font-size: 1.6rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .company-subtitle {
            font-size: 0.9rem;
            color: #667eea;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .company-address {
            color: #6b7280;
            line-height: 1.6;
            margin-top: 10px;
        }

        .invoice-info {
            text-align: right;
            flex: 1;
            min-width: 250px;
        }

        .invoice-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }

        .invoice-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: left;
        }

        .invoice-details table {
            width: 100%;
        }

        .invoice-details td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .invoice-details td:first-child {
            font-weight: 600;
            color: #374151;
            width: 40%;
        }

        .bill-to {
            margin: 40px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .bill-to h3 {
            color: #333;
            margin-bottom: 15px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }

        .items-table th, .items-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 2px solid #e5e7eb;
        }

        .items-table th {
            background: #667eea;
            color: white;
            font-weight: 600;
        }

        .items-table td:last-child, .items-table th:last-child {
            text-align: right;
        }

        .totals {
            margin-top: 30px;
            text-align: right;
        }

        .totals table {
            margin-left: auto;
            width: 300px;
        }

        .totals td {
            padding: 10px 15px;
            border-bottom: 1px solid #e5e7eb;
        }

        .totals td:first-child {
            font-weight: 600;
        }

        .total-amount {
            background: #667eea;
            color: white;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .notes {
            margin-top: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .invoice-header {
                flex-direction: column;
            }
            
            .invoice-info {
                text-align: left;
            }
            
            .invoice-title {
                font-size: 2rem;
            }
        }

        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
                max-width: none;
            }
            
            .header, .content > *:not(.invoice-preview) {
                display: none !important;
            }
            
            .invoice-preview {
                border: none;
                padding: 15px;
                margin: 0;
                page-break-inside: avoid;
                height: auto;
            }
            
            .no-print {
                display: none !important;
            }
            
            /* Compact spacing for single page */
            .invoice-header {
                margin-bottom: 20px;
            }
            
            .bill-to {
                margin: 20px 0;
                padding: 15px;
            }
            
            .items-table {
                margin: 20px 0;
            }
            
            .items-table th, .items-table td {
                padding: 8px 12px;
            }
            
            .totals {
                margin-top: 20px;
            }
            
            .notes {
                margin-top: 20px;
                padding: 15px;
            }
            
            /* Ensure single page fit */
            .invoice-preview {
                font-size: 14px;
                line-height: 1.4;
            }
            
            .invoice-title {
                font-size: 2rem;
            }
            
            .company-address {
                font-size: 12px;
                line-height: 1.3;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📄 Invoice Generator</h1>
            <p>Create and manage invoices for student applications</p>
        </div>

        <div class="content">
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- Navigation -->
            <div style="margin-bottom: 30px;">
                <a href="view_applications.php" class="btn btn-secondary">← Back to Applications</a>
                <a href="view_application.php?id=<?= $applicationId ?>" class="btn btn-secondary">👁️ View Application</a>
            </div>

            <!-- Invoice Form -->
            <form method="POST">
                <div class="form-section">
                    <div class="section-header">
                        💳 Invoice Details
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="invoice_number">Invoice Number *</label>
                            <input type="text" id="invoice_number" name="invoice_number" 
                                   value="<?= displayValue($invoice['invoice_number'] ?? $invoiceNumber) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="invoice_date">Invoice Date *</label>
                            <input type="date" id="invoice_date" name="invoice_date" 
                                   value="<?= displayValue($invoice['invoice_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="due_date">Due Date *</label>
                            <input type="date" id="due_date" name="due_date" 
                                   value="<?= displayValue($invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        🎓 Course & Pricing
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="course_name">Course Name *</label>
                            <input type="text" id="course_name" name="course_name" 
                                   value="<?= displayValue($invoice['course_name'] ?? $application['course_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="course_amount">Course Amount (USD) *</label>
                            <input type="number" id="course_amount" name="course_amount" 
                                   value="<?= displayValue($invoice['course_amount'] ?? $defaultPrice) ?>" 
                                   step="0.01" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="discount">Discount (USD)</label>
                            <input type="number" id="discount" name="discount" 
                                   value="<?= displayValue($invoice['discount'] ?? 0) ?>" 
                                   step="0.01" min="0">
                        </div>
                        <div class="form-group">
                            <label for="tax_rate">Tax Rate (%)</label>
                            <input type="number" id="tax_rate" name="tax_rate" 
                                   value="<?= displayValue($invoice['tax_rate'] ?? 0) ?>" 
                                   step="0.01" min="0" max="100">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <div class="section-header">
                        📝 Additional Notes
                    </div>
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="4" 
                                  placeholder="Payment instructions, terms, or additional information..."><?= displayValue($invoice['notes'] ?? 'Payment due within 30 days of invoice date. Late payments may incur additional charges.') ?></textarea>
                    </div>
                </div>

                <div style="text-align: center; margin: 30px 0;">
                    <button type="submit" class="btn btn-primary">💾 Save Invoice</button>
                    <button type="button" onclick="window.print()" class="btn btn-success">🖨️ Print Invoice</button>
                    <button type="button" onclick="downloadInvoice()" class="btn btn-success">📥 Download PDF</button>
                    <button type="button" onclick="viewFullscreen()" class="btn btn-info">🔍 View Fullscreen</button>
                    <a href="admin_panel.php" class="btn btn-secondary" style="text-decoration: none;">← Back to Admin Panel</a>
                </div>
            </form>

            <!-- Invoice Preview -->
            <div class="invoice-preview" id="invoice-preview">
                <div class="invoice-header">
                    <div class="company-info">
                        <div class="company-logo">
                            <div class="logo-container">
                                <img src="ibmp logo.png" alt="IBMP Logo" class="logo-image" onerror="this.style.display='none';">
                                <div class="logo-text">IBMP</div>
                            </div>
                            <div>
                                <div class="company-subtitle">Professional Medical Education & Training</div>
                                <div class="company-address">
                                    600 N Broad Street Suite 5 #3695<br>
                                    Middletown, DE 19709<br>
                                    USA
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="invoice-info">
                        <div class="invoice-title">INVOICE</div>
                        <div class="invoice-details">
                            <table>
                                <tr>
                                    <td>Invoice Number:</td>
                                    <td><strong><?= displayValue($invoice['invoice_number'] ?? $invoiceNumber) ?></strong></td>
                                </tr>
                                <tr>
                                    <td>Invoice Date:</td>
                                    <td><?= displayValue($invoice['invoice_date'] ?? date('Y-m-d')) ?></td>
                                </tr>
                                <tr>
                                    <td>Due Date:</td>
                                    <td><?= displayValue($invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bill-to">
                    <h3>Bill To:</h3>
                    <strong><?= displayValue($application['full_name']) ?></strong><br>
                    <?= displayValue($application['email_id']) ?><br>
                    <?= displayValue($application['mobile_number']) ?><br>
                    <?= displayValue($application['correspondence_address']) ?><br>
                    <?= displayValue($application['city']) ?>, <?= displayValue($application['postal_code']) ?>
                </div>

                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Quantity</th>
                            <th>Rate (USD)</th>
                            <th>Amount (USD)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <strong><?= displayValue($invoice['course_name'] ?? $application['course_name']) ?></strong><br>
                                <small>Fellowship Program</small>
                            </td>
                            <td>1</td>
                            <td>$<?= number_format($invoice['course_amount'] ?? $defaultPrice, 2) ?></td>
                            <td>$<?= number_format($invoice['course_amount'] ?? $defaultPrice, 2) ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="totals">
                    <table>
                        <?php
                        $courseAmount = floatval($invoice['course_amount'] ?? $defaultPrice);
                        $discount = floatval($invoice['discount'] ?? 0);
                        $taxRate = floatval($invoice['tax_rate'] ?? 0);
                        $subtotal = $courseAmount - $discount;
                        $taxAmount = ($subtotal * $taxRate) / 100;
                        $total = $subtotal + $taxAmount;
                        ?>
                        <tr>
                            <td>Subtotal:</td>
                            <td>$<?= number_format($courseAmount, 2) ?></td>
                        </tr>
                        <?php if ($discount > 0): ?>
                        <tr>
                            <td>Discount:</td>
                            <td>-$<?= number_format($discount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($taxRate > 0): ?>
                        <tr>
                            <td>Tax (<?= $taxRate ?>%):</td>
                            <td>$<?= number_format($taxAmount, 2) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="total-amount">
                            <td>Total Amount:</td>
                            <td>$<?= number_format($total, 2) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="notes">
                    <h4>Notes:</h4>
                    <p><?= nl2br(displayValue($invoice['notes'] ?? 'Payment due within 30 days of invoice date. Late payments may incur additional charges.')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-calculate totals
        function updateTotals() {
            const courseAmount = parseFloat(document.getElementById('course_amount').value) || 0;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const taxRate = parseFloat(document.getElementById('tax_rate').value) || 0;
            
            const subtotal = courseAmount - discount;
            const taxAmount = (subtotal * taxRate) / 100;
            const total = subtotal + taxAmount;
            
            // Update preview (you can enhance this to update the preview in real-time)
        }

        document.getElementById('course_amount').addEventListener('input', updateTotals);
        document.getElementById('discount').addEventListener('input', updateTotals);
        document.getElementById('tax_rate').addEventListener('input', updateTotals);

        // Enhanced Download PDF function
        function downloadInvoice() {
            const invoiceContent = document.querySelector('.invoice-preview').outerHTML;
            const invoiceNumber = '<?= $invoice['invoice_number'] ?? $invoiceNumber ?>';
            const studentName = '<?= htmlspecialchars($application['full_name'] ?? 'Student', ENT_QUOTES) ?>';
            
            const printWindow = window.open('', '_blank', 'width=800,height=600');
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>IBMP Invoice ${invoiceNumber} - ${studentName}</title>
                    <meta charset="UTF-8">
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            background: white;
                            padding: 15px;
                            font-size: 14px;
                            line-height: 1.4;
                        }
                        
                        .invoice-preview {
                            border: none;
                            padding: 0;
                            margin: 0;
                            background: white;
                            page-break-inside: avoid;
                        }
                        
                        .invoice-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            margin-bottom: 20px;
                            flex-wrap: wrap;
                            gap: 15px;
                        }
                        
                        .company-logo {
                            display: flex;
                            align-items: center;
                            gap: 15px;
                            margin-bottom: 15px;
                        }
                        
                        .logo-container {
                            width: 120px;
                            height: 120px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        
                        .logo-image {
                            width: 110px;
                            height: 110px;
                            object-fit: contain;
                        }
                        
                        .company-subtitle {
                            font-size: 0.9rem;
                            color: #667eea;
                            font-weight: 500;
                            margin-bottom: 8px;
                        }
                        
                        .company-address {
                            color: #6b7280;
                            line-height: 1.6;
                            margin-top: 10px;
                            font-size: 12px;
                        }
                        
                        .invoice-title {
                            font-size: 2rem;
                            font-weight: bold;
                            color: #667eea;
                            margin-bottom: 15px;
                        }
                        
                        .invoice-details {
                            background: #f8f9fa;
                            padding: 15px;
                            border-radius: 8px;
                        }
                        
                        .invoice-details table {
                            width: 100%;
                        }
                        
                        .invoice-details td {
                            padding: 6px 0;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        
                        .bill-to {
                            margin: 20px 0;
                            padding: 15px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                        
                        .items-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 20px 0;
                        }
                        
                        .items-table th, .items-table td {
                            padding: 8px 12px;
                            text-align: left;
                            border-bottom: 2px solid #e5e7eb;
                        }
                        
                        .items-table th {
                            background: #667eea;
                            color: white;
                            font-weight: 600;
                        }
                        
                        .items-table td:last-child, .items-table th:last-child {
                            text-align: right;
                        }
                        
                        .totals {
                            margin-top: 20px;
                            text-align: right;
                        }
                        
                        .totals table {
                            margin-left: auto;
                            width: 300px;
                        }
                        
                        .totals td {
                            padding: 8px 12px;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        
                        .total-amount {
                            background: #667eea;
                            color: white;
                            font-weight: bold;
                            font-size: 1.1rem;
                        }
                        
                        .notes {
                            margin-top: 20px;
                            padding: 15px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                        
                        @media print {
                            body {
                                padding: 0;
                                margin: 0;
                                font-size: 12px;
                            }
                            
                            .invoice-title {
                                font-size: 1.8rem;
                            }
                        }
                        
                        @page {
                            size: A4;
                            margin: 15mm;
                        }
                    </style>
                </head>
                <body onload="window.print(); window.close();">
                    ${invoiceContent}
                </body>
                </html>
            `);
            
            printWindow.document.close();
        }
        
        // View fullscreen function
        function viewFullscreen() {
            const invoiceContent = document.querySelector('.invoice-preview').outerHTML;
            const invoiceNumber = '<?= $invoice['invoice_number'] ?? $invoiceNumber ?>';
            const studentName = '<?= htmlspecialchars($application['full_name'] ?? 'Student', ENT_QUOTES) ?>';
            
            const fullscreenWindow = window.open('', '_blank', 'width=1000,height=800,scrollbars=yes,resizable=yes');
            
            fullscreenWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>IBMP Invoice ${invoiceNumber} - ${studentName}</title>
                    <meta charset="UTF-8">
                    <style>
                        * {
                            margin: 0;
                            padding: 0;
                            box-sizing: border-box;
                        }
                        
                        body {
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                            background: #f5f5f5;
                            padding: 20px;
                        }
                        
                        .invoice-preview {
                            background: white;
                            padding: 30px;
                            border-radius: 12px;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                            max-width: 800px;
                            margin: 0 auto;
                        }
                        
                        .action-buttons {
                            position: fixed;
                            top: 20px;
                            right: 20px;
                            display: flex;
                            gap: 10px;
                            z-index: 1000;
                        }
                        
                        .btn {
                            padding: 10px 20px;
                            border: none;
                            border-radius: 6px;
                            cursor: pointer;
                            font-weight: 600;
                            text-decoration: none;
                            color: white;
                        }
                        
                        .btn-print {
                            background: #10b981;
                        }
                        
                        .btn-close {
                            background: #ef4444;
                        }
                        
                        .invoice-header {
                            display: flex;
                            justify-content: space-between;
                            align-items: flex-start;
                            margin-bottom: 30px;
                            flex-wrap: wrap;
                            gap: 20px;
                        }
                        
                        .company-logo {
                            display: flex;
                            align-items: center;
                            gap: 15px;
                            margin-bottom: 20px;
                        }
                        
                        .logo-container {
                            width: 120px;
                            height: 120px;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        }
                        
                        .logo-image {
                            width: 110px;
                            height: 110px;
                            object-fit: contain;
                        }
                        
                        .company-subtitle {
                            font-size: 0.9rem;
                            color: #667eea;
                            font-weight: 500;
                            margin-bottom: 8px;
                        }
                        
                        .company-address {
                            color: #6b7280;
                            line-height: 1.6;
                            margin-top: 10px;
                        }
                        
                        .invoice-title {
                            font-size: 2.5rem;
                            font-weight: bold;
                            color: #667eea;
                            margin-bottom: 20px;
                        }
                        
                        .invoice-details {
                            background: #f8f9fa;
                            padding: 20px;
                            border-radius: 8px;
                        }
                        
                        .invoice-details table {
                            width: 100%;
                        }
                        
                        .invoice-details td {
                            padding: 8px 0;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        
                        .bill-to {
                            margin: 30px 0;
                            padding: 20px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                        
                        .items-table {
                            width: 100%;
                            border-collapse: collapse;
                            margin: 30px 0;
                        }
                        
                        .items-table th, .items-table td {
                            padding: 15px;
                            text-align: left;
                            border-bottom: 2px solid #e5e7eb;
                        }
                        
                        .items-table th {
                            background: #667eea;
                            color: white;
                            font-weight: 600;
                        }
                        
                        .items-table td:last-child, .items-table th:last-child {
                            text-align: right;
                        }
                        
                        .totals {
                            margin-top: 30px;
                            text-align: right;
                        }
                        
                        .totals table {
                            margin-left: auto;
                            width: 300px;
                        }
                        
                        .totals td {
                            padding: 10px 15px;
                            border-bottom: 1px solid #e5e7eb;
                        }
                        
                        .total-amount {
                            background: #667eea;
                            color: white;
                            font-weight: bold;
                            font-size: 1.1rem;
                        }
                        
                        .notes {
                            margin-top: 30px;
                            padding: 20px;
                            background: #f8f9fa;
                            border-radius: 8px;
                        }
                    </style>
                </head>
                <body>
                    <div class="action-buttons">
                        <button onclick="window.print()" class="btn btn-print">🖨️ Print/Download</button>
                        <button onclick="window.close()" class="btn btn-close">✕ Close</button>
                    </div>
                    ${invoiceContent}
                </body>
                </html>
            `);
            
            fullscreenWindow.document.close();
        }
    </script>
</body>
</html>
