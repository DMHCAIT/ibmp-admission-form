-- Verify Database Structure Script
-- Run this to check if payment tracking fields are properly added

-- Check if invoices table exists and show structure
DESCRIBE invoices;

-- Check if payment fields exist
SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = 'u584739810_admissionform' 
AND TABLE_NAME = 'invoices' 
AND COLUMN_NAME IN ('paid_amount', 'due_amount');

-- Show sample data from invoices (if any exists)
SELECT invoice_number, course_amount, discount, tax_rate, paid_amount, due_amount, 
       (course_amount - discount + ((course_amount - discount) * tax_rate / 100)) AS calculated_total
FROM invoices 
LIMIT 5;
