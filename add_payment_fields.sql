-- Add payment tracking fields to existing invoices table
-- Run this script if you already have an invoices table

-- Add paid_amount column if it doesn't exist
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 
AFTER tax_rate;

-- Add due_amount column if it doesn't exist
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS due_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00 
AFTER paid_amount;

-- Add index for payment tracking queries
ALTER TABLE invoices 
ADD INDEX IF NOT EXISTS idx_payment_status (paid_amount, due_amount);

-- Update existing records to calculate due_amount based on existing data
UPDATE invoices 
SET due_amount = GREATEST(0, 
    (course_amount - discount + ((course_amount - discount) * tax_rate / 100)) - paid_amount
) 
WHERE due_amount = 0;
