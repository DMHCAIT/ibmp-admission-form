<?php
// Enable error reporting for debugging but hide from user
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Start output buffering to catch any unexpected output
ob_start();

// Include config file
require_once 'config.php';

try {
    // Get database connection
    $pdo = getDatabaseConnection();
    
    // Check if it's a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests are allowed');
    }
    
    // Handle file uploads
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // File upload function
    function uploadFile($fileKey, $uploadDir) {
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
    
    // Helper function to get field value with fallback names
    function getField($primary, $fallback = null) {
        return $_POST[$primary] ?? ($_POST[$fallback] ?? null);
    }
    
    // Handle file uploads with multiple possible field names
    $photoFile = uploadFile('photo', $uploadDir) ?? uploadFile('passportPhoto', $uploadDir);
    $cvFile = uploadFile('cv', $uploadDir);
    $educationalCertificatesFile = uploadFile('educationalCertificates', $uploadDir);
    $marksheetsFile = uploadFile('marksheets', $uploadDir);
    $identityProofFile = uploadFile('identityProof', $uploadDir);
    $digitalSignatureFile = uploadFile('digitalSignature', $uploadDir);
    $matricCertFile = uploadFile('matricCertificate', $uploadDir);
    $interCertFile = uploadFile('interCertificate', $uploadDir);
    $bachelorCertFile = uploadFile('bachelorCertificate', $uploadDir);
    $masterCertFile = uploadFile('masterCertificate', $uploadDir);
    
    // Split fullName into firstName and lastName if needed
    $fullName = getField('fullName', 'full_name');
    $firstName = getField('firstName', 'first_name');
    $lastName = getField('lastName', 'last_name');
    
    if ($fullName && !$lastName) {
        $nameParts = explode(' ', trim($fullName), 2);
        $firstName = $nameParts[0];
        $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
    }
    
    // Prepare SQL with correct field names that match admin panel
    $sql = "INSERT INTO applications (
        title, first_name, last_name, full_name, email_id, phone_number, mobile_number, date_of_birth, gender, age,
        nationality, religion, referral_source, address, city, postal_code,
        parent_name, parent_occupation, parent_mobile, parent_email,
        course_type, course_name, preferred_start_date, study_mode,
        matric_board, matric_year, matric_marks, matric_total_marks, matric_percentage,
        inter_board, inter_year, inter_marks, inter_total_marks, inter_percentage,
        bachelor_university, bachelor_year, bachelor_percentage, bachelor_cgpa,
        master_university, master_year, master_percentage, master_cgpa,
        sponsor_name, sponsor_relationship, sponsor_income, sponsor_occupation,
        payment_option,
        emergency_contact_name, emergency_contact_relationship, emergency_contact_phone, emergency_contact_address,
        photo, cv, educational_certificates, marksheets, identity_proof, digital_signature, matric_certificate, inter_certificate, bachelor_certificate, master_certificate,
        status, created_at, updated_at
    ) VALUES (
        :title, :first_name, :last_name, :full_name, :email_id, :phone_number, :mobile_number, :date_of_birth, :gender, :age,
        :nationality, :religion, :referral_source, :address, :city, :postal_code,
        :parent_name, :parent_occupation, :parent_mobile, :parent_email,
        :course_type, :course_name, :preferred_start_date, :study_mode,
        :matric_board, :matric_year, :matric_marks, :matric_total_marks, :matric_percentage,
        :inter_board, :inter_year, :inter_marks, :inter_total_marks, :inter_percentage,
        :bachelor_university, :bachelor_year, :bachelor_percentage, :bachelor_cgpa,
        :master_university, :master_year, :master_percentage, :master_cgpa,
        :sponsor_name, :sponsor_relationship, :sponsor_income, :sponsor_occupation,
        :payment_option,
        :emergency_contact_name, :emergency_contact_relationship, :emergency_contact_phone, :emergency_contact_address,
        :photo, :cv, :educational_certificates, :marksheets, :identity_proof, :digital_signature, :matric_certificate, :inter_certificate, :bachelor_certificate, :master_certificate,
        'pending', NOW(), NOW()
    )";
    
    $stmt = $pdo->prepare($sql);
    
    // Bind parameters with multiple field name support
    $title = getField('title');
    $fullName = $firstName . ' ' . $lastName;
    $emailId = getField('email', 'emailId') ?: getField('email_id');
    $phoneNumber = getField('phone', 'phoneNumber');
    $mobileNumber = getField('mobileNumber', 'mobile_number');
    
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':first_name', $firstName);
    $stmt->bindParam(':last_name', $lastName);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->bindParam(':email_id', $emailId);
    $stmt->bindParam(':phone_number', $phoneNumber);
    $stmt->bindParam(':mobile_number', $mobileNumber);
    $stmt->bindParam(':date_of_birth', getField('dateOfBirth', 'date_of_birth'));
    $stmt->bindParam(':gender', getField('gender'));
    $stmt->bindParam(':age', getField('age'));
    $stmt->bindParam(':nationality', getField('nationality'));
    $stmt->bindParam(':religion', getField('religion'));
    $stmt->bindParam(':referral_source', getField('referralSource', 'otherReferralSource'));
    $stmt->bindParam(':address', getField('address', 'correspondenceAddress'));
    $stmt->bindParam(':city', getField('city'));
    $stmt->bindParam(':postal_code', getField('postalCode', 'postal_code'));
    
    // Parent information
    $stmt->bindParam(':parent_name', getField('parentName', 'parent_name'));
    $stmt->bindParam(':parent_occupation', getField('parentOccupation', 'parent_occupation'));
    $stmt->bindParam(':parent_mobile', getField('parentMobile', 'parent_mobile'));
    $stmt->bindParam(':parent_email', getField('parentEmail', 'parent_email'));
    
    // Course information with field mapping
    $stmt->bindParam(':course_type', getField('courseType', 'course_type'));
    $stmt->bindParam(':course_name', getField('program', 'courseName') ?: getField('course_name'));
    $stmt->bindParam(':preferred_start_date', getField('preferredStartDate', 'sessionYear'));
    $stmt->bindParam(':study_mode', getField('studyMode', 'study_mode'));
    
    // Educational background - Matriculation
    $stmt->bindParam(':matric_board', getField('matricBoard', 'school10th') ?: getField('school_10th'));
    $stmt->bindParam(':matric_year', getField('matricYear', 'year10th') ?: getField('year_10th'));
    $stmt->bindParam(':matric_marks', getField('matricMarks', 'marks10th') ?: getField('marks_10th'));
    $stmt->bindParam(':matric_total_marks', getField('matricTotalMarks', 'maxMarks10th') ?: getField('max_marks_10th'));
    $stmt->bindParam(':matric_percentage', getField('matricPercentage', 'percentage10th') ?: getField('percentage_10th'));
    
    // Educational background - Intermediate
    $stmt->bindParam(':inter_board', getField('interBoard', 'school12th') ?: getField('school_12th'));
    $stmt->bindParam(':inter_year', getField('interYear', 'year12th') ?: getField('year_12th'));
    $stmt->bindParam(':inter_marks', getField('interMarks', 'marks12th') ?: getField('marks_12th'));
    $stmt->bindParam(':inter_total_marks', getField('interTotalMarks', 'maxMarks12th') ?: getField('max_marks_12th'));
    $stmt->bindParam(':inter_percentage', getField('interPercentage', 'percentage12th') ?: getField('percentage_12th'));
    
    // Educational background - Bachelor
    $stmt->bindParam(':bachelor_university', getField('bachelorUniversity', 'collegeUG') ?: getField('college_ug'));
    $stmt->bindParam(':bachelor_year', getField('bachelorYear', 'yearUG') ?: getField('year_ug'));
    $stmt->bindParam(':bachelor_percentage', getField('bachelorPercentage', 'percentageUG') ?: getField('percentage_ug'));
    $stmt->bindParam(':bachelor_cgpa', getField('bachelorCGPA'));
    
    // Educational background - Master (optional)
    $masterUniversity = getField('masterUniversity', 'collegePG') ?: getField('college_pg');
    $masterYear = getField('masterYear', 'yearPG') ?: getField('year_pg');
    $masterPercentage = getField('masterPercentage', 'percentagePG') ?: getField('percentage_pg');
    $masterCGPA = getField('masterCGPA');
    
    $stmt->bindParam(':master_university', $masterUniversity);
    $stmt->bindParam(':master_year', $masterYear);
    $stmt->bindParam(':master_percentage', $masterPercentage);
    $stmt->bindParam(':master_cgpa', $masterCGPA);
    
    // Sponsor information (optional)
    $sponsorName = getField('sponsorName', 'parentName') ?: getField('parent_name');
    $sponsorRelationship = getField('sponsorRelationship');
    $sponsorIncome = getField('sponsorIncome');
    $sponsorOccupation = getField('sponsorOccupation', 'parentOccupation') ?: getField('parent_occupation');
    
    $stmt->bindParam(':sponsor_name', $sponsorName);
    $stmt->bindParam(':sponsor_relationship', $sponsorRelationship);
    $stmt->bindParam(':sponsor_income', $sponsorIncome);
    $stmt->bindParam(':sponsor_occupation', $sponsorOccupation);
    
    // Payment option
    $stmt->bindParam(':payment_option', getField('paymentOption', 'paymentMethod'));
    
    // Emergency contact (optional)
    $emergencyName = getField('emergencyContactName');
    $emergencyRelationship = getField('emergencyContactRelationship');
    $emergencyPhone = getField('emergencyContactPhone');
    $emergencyAddress = getField('emergencyContactAddress');
    
    $stmt->bindParam(':emergency_contact_name', $emergencyName);
    $stmt->bindParam(':emergency_contact_relationship', $emergencyRelationship);
    $stmt->bindParam(':emergency_contact_phone', $emergencyPhone);
    $stmt->bindParam(':emergency_contact_address', $emergencyAddress);
    
    // File uploads
    $stmt->bindParam(':photo', $photoFile);
    $stmt->bindParam(':cv', $cvFile);
    $stmt->bindParam(':educational_certificates', $educationalCertificatesFile);
    $stmt->bindParam(':marksheets', $marksheetsFile);
    $stmt->bindParam(':identity_proof', $identityProofFile);
    $stmt->bindParam(':digital_signature', $digitalSignatureFile);
    $stmt->bindParam(':matric_certificate', $matricCertFile);
    $stmt->bindParam(':inter_certificate', $interCertFile);
    $stmt->bindParam(':bachelor_certificate', $bachelorCertFile);
    $stmt->bindParam(':master_certificate', $masterCertFile);
    
    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Failed to save application to database');
    }
    
    $applicationId = $pdo->lastInsertId();
    
    // Clear any output buffer
    ob_clean();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully to International Board of Medical Practitioners!',
        'applicationId' => $applicationId,
        'redirect' => 'success.html'
    ]);
    
} catch (Exception $e) {
    // Clear any output buffer
    ob_clean();
    
    // Log the error
    error_log("Submission Error: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Sorry, there was an error submitting your application. Please try again.',
        'error' => $e->getMessage(),
        'debug_info' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'has_config' => file_exists('config.php'),
            'post_fields' => isset($_POST) ? array_keys($_POST) : [],
            'file_uploads' => isset($_FILES) ? array_keys($_FILES) : []
        ]
    ]);
} finally {
    // End output buffering
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
}
?>
