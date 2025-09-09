<?php
require_once 'config.php';

// Required fields based on your specification
$requiredFields = [
    'id', 'form_number', 'application_number', 'title', 'full_name', 'date_of_birth', 'gender', 'category',
    'age', 'nationality', 'marital_status', 'religion', 'correspondence_address', 'phone_number', 'mobile_number',
    'email_id', 'permanent_address', 'parent_name', 'parent_occupation', 'parent_mobile', 'parent_email',
    'course_type', 'course_name', 'session_year', 'school_10th', 'year_10th', 'subject_10th', 'marks_10th',
    'max_marks_10th', 'percentage_10th', 'school_12th', 'year_12th', 'subject_12th', 'marks_12th',
    'max_marks_12th', 'percentage_12th', 'college_ug', 'year_ug', 'subject_ug', 'marks_ug', 'max_marks_ug',
    'percentage_ug', 'college_pg', 'year_pg', 'subject_pg', 'marks_pg', 'max_marks_pg', 'percentage_pg',
    'college_other', 'year_other', 'subject_other', 'marks_other', 'max_marks_other', 'percentage_other',
    'dd_cheque_no', 'dd_date', 'payment_amount', 'bank_details', 'referral_source', 'other_referral_source',
    'passport_photo_path', 'cv_path', 'educational_certificates_path', 'marksheets_path', 'identity_proof_path',
    'terms_accepted', 'privacy_accepted', 'status', 'submitted_at', 'updated_at', 'created_at', 'photo',
    'matric_certificate', 'inter_certificate', 'bachelor_certificate', 'master_certificate',
    'preferred_start_date', 'study_mode', 'address', 'city', 'postal_code', 'matric_board', 'matric_year',
    'matric_marks', 'matric_total_marks', 'matric_percentage', 'inter_board', 'inter_year', 'inter_marks',
    'inter_total_marks', 'inter_percentage', 'bachelor_university', 'bachelor_year', 'bachelor_percentage',
    'bachelor_cgpa', 'master_university', 'master_year', 'master_percentage', 'master_cgpa', 'sponsor_name',
    'sponsor_relationship', 'sponsor_income', 'sponsor_occupation', 'payment_option', 'emergency_contact_name',
    'emergency_contact_relationship', 'emergency_contact_phone', 'emergency_contact_address'
];

try {
    $pdo = getDatabaseConnection();
    
    // Get current database structure
    $stmt = $pdo->query("DESCRIBE applications");
    $currentColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingFields = [];
    foreach ($currentColumns as $column) {
        $existingFields[] = $column['Field'];
    }
    
    // Compare required vs existing
    $missingFields = array_diff($requiredFields, $existingFields);
    $extraFields = array_diff($existingFields, $requiredFields);
    
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Structure Analysis</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
            .container { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1, h2 { color: #333; }
            .missing { color: #dc3545; }
            .extra { color: #28a745; }
            .existing { color: #17a2b8; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f8f9fa; }
            .status-box { padding: 15px; margin: 10px 0; border-radius: 5px; }
            .error { background: #f8d7da; border: 1px solid #f5c6cb; }
            .success { background: #d4edda; border: 1px solid #c3e6cb; }
            .warning { background: #fff3cd; border: 1px solid #ffeeba; }
            .generate-sql { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 10px 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>📊 Database Structure Analysis</h1>
            
            <div class="status-box <?= empty($missingFields) ? 'success' : 'error' ?>">
                <h3>Overall Status</h3>
                <p><strong>Required Fields:</strong> <?= count($requiredFields) ?></p>
                <p><strong>Existing Fields:</strong> <?= count($existingFields) ?></p>
                <p><strong>Missing Fields:</strong> <?= count($missingFields) ?></p>
                <p><strong>Extra Fields:</strong> <?= count($extraFields) ?></p>
            </div>
            
            <?php if (!empty($missingFields)): ?>
            <div class="status-box error">
                <h3 class="missing">❌ Missing Fields (<?= count($missingFields) ?>)</h3>
                <table>
                    <tr><th>Field Name</th><th>Status</th></tr>
                    <?php foreach ($missingFields as $field): ?>
                    <tr>
                        <td class="missing"><?= htmlspecialchars($field) ?></td>
                        <td class="missing">MISSING</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($extraFields)): ?>
            <div class="status-box warning">
                <h3 class="extra">ℹ️ Extra Fields (<?= count($extraFields) ?>)</h3>
                <table>
                    <tr><th>Field Name</th><th>Status</th></tr>
                    <?php foreach ($extraFields as $field): ?>
                    <tr>
                        <td class="extra"><?= htmlspecialchars($field) ?></td>
                        <td class="extra">EXTRA (Not in requirements)</td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="status-box success">
                <h3 class="existing">✅ Existing Fields (<?= count(array_intersect($requiredFields, $existingFields)) ?>)</h3>
                <p>Fields that match the requirements and are present in the database.</p>
            </div>
            
            <h2>Current Database Structure</h2>
            <table>
                <tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Status</th></tr>
                <?php foreach ($currentColumns as $column): ?>
                <tr>
                    <td><?= htmlspecialchars($column['Field']) ?></td>
                    <td><?= htmlspecialchars($column['Type']) ?></td>
                    <td><?= htmlspecialchars($column['Null']) ?></td>
                    <td><?= htmlspecialchars($column['Key']) ?></td>
                    <td><?= htmlspecialchars($column['Default'] ?? '') ?></td>
                    <td class="<?= in_array($column['Field'], $requiredFields) ? 'existing' : 'extra' ?>">
                        <?= in_array($column['Field'], $requiredFields) ? '✅ Required' : '⚠️ Extra' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <?php if (!empty($missingFields)): ?>
            <h2>🔧 SQL to Add Missing Fields</h2>
            <textarea style="width: 100%; height: 300px; font-family: monospace; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
<?php
$sql = "ALTER TABLE applications\n";
$alterStatements = [];

foreach ($missingFields as $field) {
    switch ($field) {
        case 'id':
            $alterStatements[] = "ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST";
            break;
        case 'form_number':
        case 'application_number':
            $alterStatements[] = "ADD COLUMN {$field} VARCHAR(50) NULL";
            break;
        case 'title':
            $alterStatements[] = "ADD COLUMN title VARCHAR(10) NULL";
            break;
        case 'category':
        case 'marital_status':
            $alterStatements[] = "ADD COLUMN {$field} VARCHAR(20) NULL";
            break;
        case 'correspondence_address':
        case 'permanent_address':
            $alterStatements[] = "ADD COLUMN {$field} TEXT NULL";
            break;
        case 'session_year':
        case 'dd_date':
        case 'submitted_at':
            $alterStatements[] = "ADD COLUMN {$field} DATE NULL";
            break;
        case 'age':
        case 'year_10th':
        case 'marks_10th':
        case 'max_marks_10th':
        case 'year_12th':
        case 'marks_12th':
        case 'max_marks_12th':
        case 'year_ug':
        case 'marks_ug':
        case 'max_marks_ug':
        case 'year_pg':
        case 'marks_pg':
        case 'max_marks_pg':
        case 'year_other':
        case 'marks_other':
        case 'max_marks_other':
        case 'payment_amount':
            $alterStatements[] = "ADD COLUMN {$field} INT NULL";
            break;
        case 'percentage_10th':
        case 'percentage_12th':
        case 'percentage_ug':
        case 'percentage_pg':
        case 'percentage_other':
            $alterStatements[] = "ADD COLUMN {$field} DECIMAL(5,2) NULL";
            break;
        case 'terms_accepted':
        case 'privacy_accepted':
            $alterStatements[] = "ADD COLUMN {$field} BOOLEAN DEFAULT FALSE";
            break;
        case 'created_at':
            $alterStatements[] = "ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
            break;
        case 'updated_at':
            $alterStatements[] = "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
            break;
        default:
            $alterStatements[] = "ADD COLUMN {$field} VARCHAR(255) NULL";
            break;
    }
}

echo $sql . implode(",\n", $alterStatements) . ";";
?>
</textarea>
            
            <button class="generate-sql" onclick="copySQL()">📋 Copy SQL</button>
            <button class="generate-sql" onclick="window.open('fix_database.php', '_blank')" style="background: #28a745;">🔧 Auto-Fix Database</button>
            <?php endif; ?>
            
        </div>
        
        <script>
            function copySQL() {
                const textarea = document.querySelector('textarea');
                textarea.select();
                document.execCommand('copy');
                alert('SQL copied to clipboard!');
            }
        </script>
    </body>
    </html>
    
    <?php
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background: #f8d7da; border-radius: 5px;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
