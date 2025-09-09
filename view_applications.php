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

// Get database connection
try {
    $pdo = getDatabaseConnection();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle status updates
if ($_POST && isset($_POST['update_status'])) {
    $applicationId = $_POST['application_id'];
    $newStatus = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$newStatus, $applicationId]);
    
    header('Location: view_applications.php?updated=1');
    exit();
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    try {
        $applicationId = $_GET['id'];
        
        // Get file paths before deletion
        $stmt = $pdo->prepare("SELECT photo, matric_certificate, inter_certificate, bachelor_certificate, master_certificate FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        $files = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the application record
        $stmt = $pdo->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$applicationId]);
        
        // Delete associated files
        if ($files) {
            $uploadDir = 'uploads/';
            foreach ($files as $file) {
                if ($file && file_exists($uploadDir . $file)) {
                    unlink($uploadDir . $file);
                }
            }
        }
        
        header('Location: view_applications.php?deleted=1');
        exit();
        
    } catch (Exception $e) {
        $deleteError = "Delete failed: " . $e->getMessage();
    }
}

// Handle export functionality
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    
    // Get filter parameters for export
    $statusFilter = $_GET['status'] ?? '';
    $courseFilter = $_GET['course'] ?? '';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';
    
    // Build query for export
    $whereConditions = [];
    $params = [];
    
    if ($statusFilter) {
        $whereConditions[] = "status = ?";
        $params[] = $statusFilter;
    }
    
    if ($courseFilter) {
        $whereConditions[] = "course_name LIKE ?";
        $params[] = "%$courseFilter%";
    }
    
    if ($dateFrom) {
        $whereConditions[] = "DATE(created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "DATE(created_at) <= ?";
        $params[] = $dateTo;
    }
    
    if ($search) {
        $whereConditions[] = "(full_name LIKE ? OR email_id LIKE ? OR mobile_number LIKE ? OR phone_number LIKE ? OR id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Select all fields for export
    $exportQuery = "SELECT 
        id, title, full_name, first_name, last_name, email_id, phone_number, mobile_number,
        date_of_birth, gender, age, nationality, religion, referral_source, address, city, postal_code,
        parent_name, parent_occupation, parent_mobile, parent_email,
        course_type, course_name, preferred_start_date, study_mode,
        matric_board, matric_year, matric_marks, matric_total_marks, matric_percentage,
        inter_board, inter_year, inter_marks, inter_total_marks, inter_percentage,
        bachelor_university, bachelor_year, bachelor_percentage, bachelor_cgpa,
        master_university, master_year, master_percentage, master_cgpa,
        sponsor_name, sponsor_relationship, sponsor_income, sponsor_occupation,
        payment_option, emergency_contact_name, emergency_contact_relationship, 
        emergency_contact_phone, emergency_contact_address, status, created_at, updated_at
        FROM applications $whereClause ORDER BY created_at DESC";
    
    $stmt = $pdo->prepare($exportQuery);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($exportType === 'csv') {
        // CSV Export
        $filename = 'IBMP_Applications_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for proper Excel display
        fwrite($output, "\xEF\xBB\xBF");
        
        // CSV Headers
        $headers = [
            'ID', 'Title', 'Full Name', 'First Name', 'Last Name', 'Email', 'Phone', 'Mobile',
            'Date of Birth', 'Gender', 'Age', 'Nationality', 'Religion', 'How Found Us', 'Address', 'City', 'Postal Code',
            'Parent Name', 'Parent Occupation', 'Parent Mobile', 'Parent Email',
            'Course Type', 'Course Name', 'Preferred Start Date', 'Study Mode',
            'Matric Board', 'Matric Year', 'Matric Marks', 'Matric Total', 'Matric %',
            'Inter Board', 'Inter Year', 'Inter Marks', 'Inter Total', 'Inter %',
            'Bachelor University', 'Bachelor Year', 'Bachelor %', 'Bachelor CGPA',
            'Master University', 'Master Year', 'Master %', 'Master CGPA',
            'Sponsor Name', 'Sponsor Relationship', 'Sponsor Income', 'Sponsor Occupation',
            'Payment Option', 'Emergency Name', 'Emergency Relationship', 'Emergency Phone', 'Emergency Address',
            'Status', 'Submitted Date', 'Last Updated'
        ];
        
        fputcsv($output, $headers);
        
        foreach ($applications as $app) {
            $row = [
                $app['id'], $app['title'], $app['full_name'], $app['first_name'], $app['last_name'],
                $app['email_id'], $app['phone_number'], $app['mobile_number'],
                $app['date_of_birth'], $app['gender'], $app['age'], $app['nationality'], $app['religion'], 
                $app['referral_source'], $app['address'], $app['city'], $app['postal_code'],
                $app['parent_name'], $app['parent_occupation'], $app['parent_mobile'], $app['parent_email'],
                $app['course_type'], $app['course_name'], $app['preferred_start_date'], $app['study_mode'],
                $app['matric_board'], $app['matric_year'], $app['matric_marks'], $app['matric_total_marks'], $app['matric_percentage'],
                $app['inter_board'], $app['inter_year'], $app['inter_marks'], $app['inter_total_marks'], $app['inter_percentage'],
                $app['bachelor_university'], $app['bachelor_year'], $app['bachelor_percentage'], $app['bachelor_cgpa'],
                $app['master_university'], $app['master_year'], $app['master_percentage'], $app['master_cgpa'],
                $app['sponsor_name'], $app['sponsor_relationship'], $app['sponsor_income'], $app['sponsor_occupation'],
                $app['payment_option'], $app['emergency_contact_name'], $app['emergency_contact_relationship'],
                $app['emergency_contact_phone'], $app['emergency_contact_address'],
                $app['status'], $app['created_at'], $app['updated_at']
            ];
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit();
    }
    
    if ($exportType === 'excel') {
        // Excel Export (HTML table format that Excel can open)
        $filename = 'IBMP_Applications_' . date('Y-m-d_H-i-s') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        echo '<html>';
        echo '<head><meta charset="UTF-8"></head>';
        echo '<body>';
        echo '<table border="1">';
        
        // Excel Headers
        echo '<tr>';
        $headers = [
            'ID', 'Title', 'Full Name', 'First Name', 'Last Name', 'Email', 'Phone', 'Mobile',
            'Date of Birth', 'Gender', 'Age', 'Nationality', 'Religion', 'How Found Us', 'Address', 'City', 'Postal Code',
            'Parent Name', 'Parent Occupation', 'Parent Mobile', 'Parent Email',
            'Course Type', 'Course Name', 'Preferred Start Date', 'Study Mode',
            'Matric Board', 'Matric Year', 'Matric Marks', 'Matric Total', 'Matric %',
            'Inter Board', 'Inter Year', 'Inter Marks', 'Inter Total', 'Inter %',
            'Bachelor University', 'Bachelor Year', 'Bachelor %', 'Bachelor CGPA',
            'Master University', 'Master Year', 'Master %', 'Master CGPA',
            'Sponsor Name', 'Sponsor Relationship', 'Sponsor Income', 'Sponsor Occupation',
            'Payment Option', 'Emergency Name', 'Emergency Relationship', 'Emergency Phone', 'Emergency Address',
            'Status', 'Submitted Date', 'Last Updated'
        ];
        
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
        
        foreach ($applications as $app) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($app['id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['title'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['full_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['first_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['last_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['email_id'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['phone_number'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['mobile_number'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['date_of_birth'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['gender'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['age'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['nationality'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['religion'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['referral_source'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['address'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['city'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['postal_code'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['parent_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['parent_occupation'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['parent_mobile'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['parent_email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['course_type'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['course_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['preferred_start_date'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['study_mode'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['matric_board'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['matric_year'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['matric_marks'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['matric_total_marks'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['matric_percentage'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['inter_board'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['inter_year'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['inter_marks'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['inter_total_marks'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['inter_percentage'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['bachelor_university'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['bachelor_year'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['bachelor_percentage'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['bachelor_cgpa'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['master_university'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['master_year'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['master_percentage'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['master_cgpa'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['sponsor_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['sponsor_relationship'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['sponsor_income'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['sponsor_occupation'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['payment_option'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['emergency_contact_name'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['emergency_contact_relationship'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['emergency_contact_phone'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['emergency_contact_address'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['status'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['created_at'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($app['updated_at'] ?? '') . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
        echo '</body>';
        echo '</html>';
        exit();
    }
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$courseFilter = $_GET['course'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$whereConditions = [];
$params = [];

if ($statusFilter) {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if ($courseFilter) {
    $whereConditions[] = "course_name LIKE ?";
    $params[] = "%$courseFilter%";
}

if ($dateFrom) {
    $whereConditions[] = "DATE(created_at) >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $whereConditions[] = "DATE(created_at) <= ?";
    $params[] = $dateTo;
}

if ($search) {
    $whereConditions[] = "(full_name LIKE ? OR email_id LIKE ? OR mobile_number LIKE ? OR phone_number LIKE ? OR id LIKE ? OR application_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Get applications with pagination
$page = $_GET['page'] ?? 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$countSql = "SELECT COUNT(*) FROM applications $whereClause";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalApplications = $countStmt->fetchColumn();
$totalPages = ceil($totalApplications / $limit);

$sql = "SELECT * FROM applications $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN status = 'under_review' THEN 1 ELSE 0 END) as under_review,
    SUM(CASE WHEN status = 'waitlist' THEN 1 ELSE 0 END) as waitlist
FROM applications $whereClause";
$statsStmt = $pdo->prepare($statsQuery);
$statsStmt->execute(array_slice($params, 0, -2)); // Remove limit and offset for stats
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

function displayValue($value) {
    return htmlspecialchars($value ?? 'N/A');
}

function formatDate($date) {
    return $date ? date('M d, Y', strtotime($date)) : 'N/A';
}
?>
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin Dashboard - International Board of Medical Practitioners</title>
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
        }

        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.2);
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
            font-size: 2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .main-content {
            margin: 2rem 0;
            padding-bottom: 2rem;
        }

        .stats-section {
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: all 0.3s ease;
            border-left: 5px solid #667eea;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .section-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h2 {
            color: #495057;
            font-size: 1.4rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-content {
            padding: 2rem;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #495057;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            justify-content: center;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
        }

        .applications-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .applications-table th,
        .applications-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }

        .applications-table th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-weight: 600;
            color: #495057;
            position: sticky;
            top: 0;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .applications-table tr:hover {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            color: #856404;
        }

        .status-approved {
            background: linear-gradient(135deg, #d4edda 0%, #00b894 100%);
            color: #155724;
        }

        .status-rejected {
            background: linear-gradient(135deg, #f8d7da 0%, #e17055 100%);
            color: #721c24;
        }

        .status-under_review {
            background: linear-gradient(135deg, #d1ecf1 0%, #74b9ff 100%);
            color: #0c5460;
        }

        .status-waitlist {
            background: linear-gradient(135deg, #e2e3e5 0%, #b2bec3 100%);
            color: #383d41;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
        }

        .action-buttons .btn {
            padding: 8px 12px;
            font-size: 12px;
            min-width: 70px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 2rem;
        }

        .pagination a, .pagination span {
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            text-decoration: none;
            color: #495057;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .pagination .current {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 5px solid #28a745;
        }

        .alert-danger {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 5px solid #dc3545;
        }

        .no-applications {
            text-align: center;
            padding: 4rem 2rem;
            color: #6c757d;
        }

        .no-applications h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .export-buttons .btn {
            position: relative;
            overflow: hidden;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .export-buttons .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .export-buttons .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .export-buttons .btn-success:hover {
            background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
        }
        
        .export-buttons .btn-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }
        
        .export-buttons .btn-info:hover {
            background: linear-gradient(135deg, #6f42c1 0%, #17a2b8 100%);
        }
        
        .export-buttons .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: #212529;
        }
        
        .export-buttons .btn-warning:hover {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
        }
        
        .export-buttons .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e83e8c 100%);
            color: white;
        }
        
        .export-buttons .btn-danger:hover {
            background: linear-gradient(135deg, #e83e8c 0%, #dc3545 100%);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close:hover {
            color: #dc3545;
        }

        .admin-utilities {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 10px;
            }
            
            .header h1 {
                font-size: 1.5rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .applications-table {
                font-size: 14px;
            }
            
            .applications-table th,
            .applications-table td {
                padding: 0.5rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 5px;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="header-content">
                <h1>🎓 IBMP Admin Panel</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 0; font-size: 14px;">International Board of Medical Practitioners</p>
                <a href="admin_logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['updated'])): ?>
                <div class="alert alert-success">
                    ✅ Application status updated successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">
                    ✅ Application deleted successfully!
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    ❌ Error: <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($deleteError)): ?>
                <div class="alert alert-danger">
                    ❌ <?= htmlspecialchars($deleteError) ?>
                </div>
            <?php endif; ?>

            <!-- Statistics Section -->
            <div class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['total'] ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['pending'] ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['under_review'] ?></div>
                        <div class="stat-label">Under Review</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['approved'] ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['rejected'] ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?= $stats['waitlist'] ?></div>
                        <div class="stat-label">Waitlist</div>
                    </div>
                </div>
            </div>

            <!-- Admin Utilities Section -->
            <div class="section">
                <div class="section-header">
                    <h2>🛠️ Admin Utilities</h2>
                </div>
                <div class="section-content">
                    <div class="admin-utilities">
                        <a href="index.html" class="btn btn-secondary">🏠 Back to Form</a>
                        <a href="file_management.php" class="btn btn-info">📁 File Management</a>
                        <a href="system_status.php" class="btn btn-success">� System Status</a>
                        <a href="config.php" class="btn btn-warning">⚙️ System Config</a>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters Section -->
            <div class="section">
                <div class="section-header">
                    <h2>🔍 Search & Filter Applications</h2>
                </div>
                <div class="section-content">
                    <form method="GET" action="">
                        <div class="filters-grid">
                            <div class="form-group">
                                <label for="search">🔍 Search</label>
                                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                                       placeholder="Name, Email, Phone, Application ID...">
                            </div>
                            <div class="form-group">
                                <label for="status">📊 Status</label>
                                <select id="status" name="status">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="under_review" <?= $statusFilter === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
                                    <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="waitlist" <?= $statusFilter === 'waitlist' ? 'selected' : '' ?>>Waitlist</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="course">🎓 Course</label>
                                <input type="text" id="course" name="course" value="<?= htmlspecialchars($courseFilter) ?>" 
                                       placeholder="Fellowship program...">
                            </div>
                            <div class="form-group">
                                <label for="date_from">📅 From Date</label>
                                <input type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                            </div>
                            <div class="form-group">
                                <label for="date_to">📅 To Date</label>
                                <input type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">🔍 Apply Filters</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Applications Section -->
            <div class="section">
                <div class="section-header">
                    <h2>📋 Applications (<?= count($applications) ?> found)</h2>
                    <div class="export-buttons">
                        <a href="database_analysis.php" class="btn btn-warning" title="Analyze database structure">
                            🔍 Database Analysis
                        </a>
                        <a href="fix_database.php" class="btn btn-danger" title="Fix missing database fields">
                            🔧 Fix Database
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-success" title="Export <?= count($applications) ?> records as CSV">
                            📊 Export CSV (<?= count($applications) ?> records)
                        </a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'excel'])) ?>" class="btn btn-info" title="Export <?= count($applications) ?> records as Excel">
                            📈 Export Excel (<?= count($applications) ?> records)
                        </a>
                    </div>
                </div>

                <?php if (empty($applications)): ?>
                    <div class="no-applications">
                        <h3>📋 No Applications Found</h3>
                        <p>No admission applications match your current filters.</p>
                        <a href="view_applications.php" class="btn btn-primary">🔄 Reset Filters</a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="applications-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Course</th>
                                    <th>Course Type</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td><strong>#<?= $app['id'] ?></strong></td>
                                        <td><?= displayValue($app['full_name']) ?></td>
                                        <td><?= displayValue($app['email_id']) ?></td>
                                        <td><?= displayValue($app['course_name']) ?></td>
                                        <td><?= displayValue($app['course_type']) ?></td>
                                        <td>
                                            <span class="status status-<?= strtolower($app['status'] ?? 'pending') ?>">
                                                <?= ucfirst($app['status'] ?? 'Pending') ?>
                                            </span>
                                        </td>
                                        <td><?= formatDate($app['created_at']) ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="view_application.php?id=<?= $app['id'] ?>" class="btn btn-info">👁️ View</a>
                                                <a href="edit_application.php?id=<?= $app['id'] ?>" class="btn btn-warning">✏️ Edit</a>
                                                <a href="generate_invoice.php?id=<?= $app['id'] ?>" class="btn btn-success">💳 Invoice</a>
                                                <a href="generate_application_pdf.php?id=<?= $app['id'] ?>" class="btn btn-success">📄 PDF</a>
                                                <button onclick="openStatusModal(<?= $app['id'] ?>, '<?= $app['status'] ?? 'pending' ?>')" class="btn btn-warning">📋 Status</button>
                                                <a href="?action=delete&id=<?= $app['id'] ?>" 
                                                   class="btn btn-danger"
                                                   onclick="return confirm('Are you sure you want to delete this application? This action cannot be undone.')">🗑️ Delete</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Previous</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next »</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeStatusModal()">&times;</span>
            <h3>📋 Update Application Status</h3>
            <form method="POST" action="">
                <input type="hidden" id="modalApplicationId" name="application_id">
                <div class="form-group" style="margin: 1.5rem 0;">
                    <label for="modalStatus">New Status:</label>
                    <select id="modalStatus" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="waitlist">Waitlist</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" onclick="closeStatusModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">✅ Update Status</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Status Modal Functions
        function openStatusModal(applicationId, currentStatus) {
            document.getElementById('modalApplicationId').value = applicationId;
            document.getElementById('modalStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Auto-refresh every 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);

        // Success message auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });
        });

        // Real-time search (debounced)
        let searchTimeout;
        document.getElementById('search')?.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // You can implement AJAX search here
                console.log('Search for:', this.value);
            }, 500);
        });

        // Enhanced Export Functionality
        document.querySelectorAll('.export-buttons .btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const originalText = this.innerHTML;
                const exportType = this.href.includes('csv') ? 'CSV' : 'Excel';
                
                // Show loading state
                this.innerHTML = `⏳ Generating ${exportType}...`;
                this.style.pointerEvents = 'none';
                this.style.opacity = '0.7';
                
                // Create a temporary link to trigger download
                const tempLink = document.createElement('a');
                tempLink.href = this.href;
                tempLink.style.display = 'none';
                document.body.appendChild(tempLink);
                
                // Prevent default and use our custom handler
                e.preventDefault();
                
                // Trigger download
                window.location.href = this.href;
                
                // Reset button after a short delay
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                    this.style.opacity = '1';
                    
                    // Show success message
                    showExportSuccess(exportType);
                }, 2000);
                
                document.body.removeChild(tempLink);
            });
        });
        
        // Show export success message
        function showExportSuccess(type) {
            const message = document.createElement('div');
            message.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 8px;
                box-shadow: 0 8px 25px rgba(0,0,0,0.2);
                z-index: 1001;
                font-weight: 600;
                animation: slideInRight 0.3s ease;
            `;
            message.innerHTML = `✅ ${type} export completed successfully!`;
            
            document.body.appendChild(message);
            
            setTimeout(() => {
                message.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(message)) {
                        document.body.removeChild(message);
                    }
                }, 300);
            }, 3000);
        }
        
        // Add CSS animations for notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOutRight {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
