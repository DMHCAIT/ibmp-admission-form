<?php
/**
 * IBMP Admission Form Submission Handler
 * Handles form submissions with proper error handling and validation
 */

// Start session and output buffering
session_start();
ob_start();

// Set error reporting (log errors but don't display them)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Send JSON response and exit
 */
function sendResponse($success, $message, $data = []) {
    // Clear any output that might have been generated
    if (ob_get_level()) {
        ob_clean();
    }
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('c')
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    // Set appropriate HTTP status code
    http_response_code($success ? 200 : 400);
    
    echo json_encode($response);
    exit();
}

/**
 * Validate required configuration
 */
try {
    if (!file_exists('config.php')) {
        sendResponse(false, 'System configuration not found. Please contact administrator.');
    }
    
    require_once 'config.php';
    
    // Verify required constants are defined
    $requiredConstants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($requiredConstants as $constant) {
        if (!defined($constant)) {
            sendResponse(false, 'System configuration incomplete. Please contact administrator.');
        }
    }
} catch (Exception $e) {
    error_log("Config Error: " . $e->getMessage());
    sendResponse(false, 'Configuration error. Please contact administrator.');
}

/**
 * Validate request method
 */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Invalid request method. Please submit the form properly.');
}

/**
 * Main submission handler
 */
try {
    // Connect to database
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
    
    // Verify table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
    if ($stmt->rowCount() === 0) {
        sendResponse(false, 'Database not properly initialized. Please contact administrator.');
    }
    
    // Get and validate form data
    $formData = $_POST;
    
    // Enhanced debug logging
    error_log("IBMP Form Data Keys: " . json_encode(array_keys($formData)));
    error_log("IBMP Form Data Count: " . count($formData));
    error_log("IBMP Required Fields Check - fullName: " . ($formData['fullName'] ?? 'MISSING') . ", emailId: " . ($formData['emailId'] ?? 'MISSING'));
    
    // Check if we have any data at all
    if (empty($formData)) {
        sendResponse(false, 'No form data received. Please try submitting the form again.');
    }
    
    // Check for critical required fields that should always be present
    $criticalFields = ['fullName', 'emailId'];
    $missingCritical = [];
    foreach ($criticalFields as $field) {
        if (empty($formData[$field])) {
            $missingCritical[] = $field;
        }
    }
    
    if (!empty($missingCritical)) {
        error_log("IBMP Critical fields missing: " . implode(', ', $missingCritical));
        sendResponse(false, 'Required form fields are missing. Please fill in all required fields and try again.');
    }
    
    // Extract and validate required fields based on actual form field names
    $fullName = $formData['fullName'] ?? '';
    $email = $formData['emailId'] ?? $formData['email'] ?? '';
    
    // Validate required fields
    if (empty($fullName)) {
        sendResponse(false, 'Full name is required.');
    }
    
    if (empty($email)) {
        sendResponse(false, 'Email address is required.');
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please provide a valid email address.');
    }
    
    // Sanitize and prepare data
    $fullName = trim($fullName);
    $email = trim($email);
    
    // Split name if needed
    $nameParts = explode(' ', $fullName, 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Handle file uploads
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            sendResponse(false, 'File upload system not available. Please try again later.');
        }
    }
    
    /**
     * Safe file upload handler
     */
    function handleFileUpload($fileKey, $uploadDir) {
        if (!isset($_FILES[$fileKey]) || $_FILES[$fileKey]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $file = $_FILES[$fileKey];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            return null; // Skip invalid files instead of throwing error
        }
        
        $fileName = uniqid() . '_' . $fileKey . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $fileName; // Return just filename, not full path
        }
        return null;
    }
    
    // Helper function to get field value with comprehensive fallback mapping
    function getField($formData, $primary, $fallback = null) {
        // Direct match
        if (isset($formData[$primary]) && $formData[$primary] !== '') {
            return $formData[$primary];
        }
        
        // Fallback match
        if ($fallback && isset($formData[$fallback]) && $formData[$fallback] !== '') {
            return $formData[$fallback];
        }
        
        // Try camelCase to snake_case conversion
        $snakeCase = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $primary));
        if (isset($formData[$snakeCase]) && $formData[$snakeCase] !== '') {
            return $formData[$snakeCase];
        }
        
        // Try snake_case to camelCase conversion
        $camelCase = lcfirst(str_replace('_', '', ucwords($primary, '_')));
        if (isset($formData[$camelCase]) && $formData[$camelCase] !== '') {
            return $formData[$camelCase];
        }
        
        return '';
    }
    
    // Create comprehensive field mapping
    function mapFormFields($formData) {
        $mapping = [
            // Basic info mapping
            'full_name' => getField($formData, 'fullName', 'full_name'),
            'email_id' => getField($formData, 'emailId', 'email_id'),
            'phone_number' => getField($formData, 'phoneNumber', 'phone_number'),
            'mobile_number' => getField($formData, 'mobileNumber', 'mobile_number'),
            'date_of_birth' => getField($formData, 'dateOfBirth', 'date_of_birth'),
            'gender' => getField($formData, 'gender'),
            'age' => getField($formData, 'age'),
            'nationality' => getField($formData, 'nationality'),
            'religion' => getField($formData, 'religion'),
            'referral_source' => getField($formData, 'referralSource', 'referral_source'),
            
            // Address mapping
            'address' => getField($formData, 'correspondenceAddress', 'address'),
            'city' => getField($formData, 'city'),
            'postal_code' => getField($formData, 'postalCode', 'postal_code'),
            
            // Parent info mapping
            'parent_name' => getField($formData, 'parentName', 'parent_name'),
            'parent_occupation' => getField($formData, 'parentOccupation', 'parent_occupation'),
            'parent_mobile' => getField($formData, 'parentMobile', 'parent_mobile'),
            'parent_email' => getField($formData, 'parentEmail', 'parent_email'),
            
            // Course info mapping
            'course_type' => getField($formData, 'courseType', 'course_type'),
            'course_name' => getField($formData, 'courseName', 'course_name'),
            'preferred_start_date' => getField($formData, 'sessionYear', 'preferred_start_date'),
            'study_mode' => getField($formData, 'studyMode', 'study_mode'),
            
            // Educational background mapping (10th)
            'matric_board' => getField($formData, 'school10th', 'matric_board'),
            'matric_year' => getField($formData, 'year10th', 'matric_year'),
            'matric_marks' => getField($formData, 'marks10th', 'matric_marks'),
            'matric_total_marks' => getField($formData, 'maxMarks10th', 'matric_total_marks'),
            'matric_percentage' => getField($formData, 'percentage10th', 'matric_percentage'),
            
            // Educational background mapping (12th)
            'inter_board' => getField($formData, 'school12th', 'inter_board'),
            'inter_year' => getField($formData, 'year12th', 'inter_year'),
            'inter_marks' => getField($formData, 'marks12th', 'inter_marks'),
            'inter_total_marks' => getField($formData, 'maxMarks12th', 'inter_total_marks'),
            'inter_percentage' => getField($formData, 'percentage12th', 'inter_percentage'),
            
            // Bachelor degree mapping
            'bachelor_university' => getField($formData, 'collegeUG', 'bachelor_university'),
            'bachelor_year' => getField($formData, 'yearUG', 'bachelor_year'),
            'bachelor_percentage' => getField($formData, 'percentageUG', 'bachelor_percentage'),
            'bachelor_cgpa' => getField($formData, 'cgpaUG', 'bachelor_cgpa'),
            
            // Master degree mapping
            'master_university' => getField($formData, 'collegePG', 'master_university'),
            'master_year' => getField($formData, 'yearPG', 'master_year'),
            'master_percentage' => getField($formData, 'percentagePG', 'master_percentage'),
            'master_cgpa' => getField($formData, 'cgpaPG', 'master_cgpa'),
            
            // Payment info
            'payment_option' => getField($formData, 'paymentOption', 'payment_option')
        ];
        
        return $mapping;
    }
    
    // Handle file uploads based on actual form field names
    $photoFile = handleFileUpload('passportPhoto', $uploadDir);
    $cvFile = handleFileUpload('cv', $uploadDir);
    $educationalCertificatesFile = handleFileUpload('educationalCertificates', $uploadDir);
    $marksheetsFile = handleFileUpload('marksheets', $uploadDir);
    $identityProofFile = handleFileUpload('identityProof', $uploadDir);
    $digitalSignatureFile = handleFileUpload('digitalSignature', $uploadDir);
    
    // Generate unique application number
    $applicationNumber = 'IBMP-' . date('Y') . '-' . uniqid();
    
    // Prepare SQL with essential fields including application_number
    $sql = "INSERT INTO applications (
        application_number, first_name, last_name, full_name, email_id, phone_number, mobile_number, 
        date_of_birth, gender, age, nationality, religion, referral_source, 
        address, city, postal_code,
        parent_name, parent_occupation, parent_mobile, parent_email,
        course_type, course_name, preferred_start_date, study_mode,
        matric_board, matric_year, matric_marks, matric_total_marks, matric_percentage,
        inter_board, inter_year, inter_marks, inter_total_marks, inter_percentage,
        bachelor_university, bachelor_year, bachelor_percentage, bachelor_cgpa,
        master_university, master_year, master_percentage, master_cgpa,
        payment_option,
        photo, cv, educational_certificates, marksheets, identity_proof, digital_signature,
        status, created_at
    ) VALUES (
        :application_number, :first_name, :last_name, :full_name, :email_id, :phone_number, :mobile_number, 
        :date_of_birth, :gender, :age, :nationality, :religion, :referral_source, 
        :address, :city, :postal_code,
        :parent_name, :parent_occupation, :parent_mobile, :parent_email,
        :course_type, :course_name, :preferred_start_date, :study_mode,
        :matric_board, :matric_year, :matric_marks, :matric_total_marks, :matric_percentage,
        :inter_board, :inter_year, :inter_marks, :inter_total_marks, :inter_percentage,
        :bachelor_university, :bachelor_year, :bachelor_percentage, :bachelor_cgpa,
        :master_university, :master_year, :master_percentage, :master_cgpa,
        :payment_option,
        :photo, :cv, :educational_certificates, :marksheets, :identity_proof, :digital_signature,
        'pending', NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    
    // Use comprehensive field mapping
    $mappedFields = mapFormFields($formData);
    
    // Validate essential mapped fields
    $requiredFields = ['full_name', 'email_id', 'course_type', 'course_name'];
    $missingFields = [];
    
    foreach ($requiredFields as $field) {
        if (empty($mappedFields[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        error_log("IBMP Missing required fields after mapping: " . implode(', ', $missingFields));
        sendResponse(false, 'Required form fields are missing. Please fill in all required fields and try again.');
    }
    
    // Validate email format
    if (!filter_var($mappedFields['email_id'], FILTER_VALIDATE_EMAIL)) {
        sendResponse(false, 'Please provide a valid email address.');
    }
    
    // Log successful mapping for debugging
    error_log("IBMP Mapping successful - Name: " . $mappedFields['full_name'] . ", Email: " . $mappedFields['email_id'] . ", Course: " . $mappedFields['course_name']);
    
    // Split full name into first and last name
    $nameParts = explode(' ', trim($mappedFields['full_name']), 2);
    $firstName = $nameParts[0];
    $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    
    // Bind core fields with mapped data including application_number
    $stmt->bindParam(':application_number', $applicationNumber);
    $stmt->bindParam(':first_name', $firstName);
    $stmt->bindParam(':last_name', $lastName);  
    $stmt->bindParam(':full_name', $mappedFields['full_name']);
    $stmt->bindParam(':email_id', $mappedFields['email_id']);
    $stmt->bindParam(':phone_number', $mappedFields['phone_number']);
    $stmt->bindParam(':mobile_number', $mappedFields['mobile_number']);
    
    // Bind all personal information using mapped fields
    $stmt->bindParam(':date_of_birth', $mappedFields['date_of_birth']);
    $stmt->bindParam(':gender', $mappedFields['gender']);
    $stmt->bindParam(':age', $mappedFields['age']);
    $stmt->bindParam(':nationality', $mappedFields['nationality']);
    $stmt->bindParam(':religion', $mappedFields['religion']);
    $stmt->bindParam(':referral_source', $mappedFields['referral_source']);
    $stmt->bindParam(':address', $mappedFields['address']);
    $stmt->bindParam(':city', $mappedFields['city']);
    $stmt->bindParam(':postal_code', $mappedFields['postal_code']);
    
    // Bind parent information using mapped fields
    $stmt->bindParam(':parent_name', $mappedFields['parent_name']);
    $stmt->bindParam(':parent_occupation', $mappedFields['parent_occupation']);
    $stmt->bindParam(':parent_mobile', $mappedFields['parent_mobile']);
    $stmt->bindParam(':parent_email', $mappedFields['parent_email']);
    
    // Bind course information using mapped fields
    $stmt->bindParam(':course_type', $mappedFields['course_type']);
    $stmt->bindParam(':course_name', $mappedFields['course_name']);
    $stmt->bindParam(':preferred_start_date', $mappedFields['preferred_start_date']);
    $stmt->bindParam(':study_mode', $mappedFields['study_mode']);
    
    // Bind educational background using mapped fields - Matriculation
    $stmt->bindParam(':matric_board', $mappedFields['matric_board']);
    $stmt->bindParam(':matric_year', $mappedFields['matric_year']);
    $stmt->bindParam(':matric_marks', $mappedFields['matric_marks']);
    $stmt->bindParam(':matric_total_marks', $mappedFields['matric_total_marks']);
    $stmt->bindParam(':matric_percentage', $mappedFields['matric_percentage']);
    
    // Bind educational background using mapped fields - Intermediate
    $stmt->bindParam(':inter_board', $mappedFields['inter_board']);
    $stmt->bindParam(':inter_year', $mappedFields['inter_year']);
    $stmt->bindParam(':inter_marks', $mappedFields['inter_marks']);
    $stmt->bindParam(':inter_total_marks', $mappedFields['inter_total_marks']);
    $stmt->bindParam(':inter_percentage', $mappedFields['inter_percentage']);
    
    // Bind educational background using mapped fields - Bachelor
    $stmt->bindParam(':bachelor_university', $mappedFields['bachelor_university']);
    $stmt->bindParam(':bachelor_year', $mappedFields['bachelor_year']);
    $stmt->bindParam(':bachelor_percentage', $mappedFields['bachelor_percentage']);
    $stmt->bindParam(':bachelor_cgpa', $mappedFields['bachelor_cgpa']);
    
    // Bind educational background using mapped fields - Master
    $stmt->bindParam(':master_university', $mappedFields['master_university']);
    $stmt->bindParam(':master_year', $mappedFields['master_year']);
    $stmt->bindParam(':master_percentage', $mappedFields['master_percentage']);
    $stmt->bindParam(':master_cgpa', $mappedFields['master_cgpa']);
    
    // Bind payment option using mapped fields
    $stmt->bindParam(':payment_option', $mappedFields['payment_option']);
    
    // File uploads
    $stmt->bindParam(':photo', $photoFile);
    $stmt->bindParam(':cv', $cvFile);
    $stmt->bindParam(':educational_certificates', $educationalCertificatesFile);
    $stmt->bindParam(':marksheets', $marksheetsFile);
    $stmt->bindParam(':identity_proof', $identityProofFile);
    $stmt->bindParam(':digital_signature', $digitalSignatureFile);
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Failed to save application to database');
    }
    
    $applicationId = $pdo->lastInsertId();
    
    // Return success response
    sendResponse(true, 'Application submitted successfully to International Board of Medical Practitioners!', [
        'applicationId' => $applicationId,
        'application_number' => $applicationNumber,
        'redirect' => 'success.html'
    ]);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("IBMP Form Submission Error: " . $e->getMessage() . " | File: " . __FILE__ . " | Line: " . __LINE__);
    
    // Send user-friendly error response
    sendResponse(false, 'Sorry, there was an error submitting your application. Please try again.', [
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'error_type' => get_class($e),
            'has_config' => file_exists('config.php'),
            'post_count' => isset($_POST) ? count($_POST) : 0,
            'files_count' => isset($_FILES) ? count($_FILES) : 0
        ]
    ]);
}
?>
