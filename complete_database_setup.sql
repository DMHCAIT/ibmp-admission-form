-- Complete Database Setup Script for IBMP Admission System
-- This script creates all necessary tables with payment tracking

-- Use the correct database
USE u584739810_admissionform;

-- Create applications table (if it doesn't exist)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) NOT NULL,
    country VARCHAR(100) NOT NULL DEFAULT 'India',
    course VARCHAR(255) NOT NULL,
    preferred_batch VARCHAR(100),
    previous_education TEXT,
    work_experience TEXT,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(100),
    medical_conditions TEXT,
    dietary_requirements TEXT,
    accommodation_needed BOOLEAN DEFAULT FALSE,
    transportation_needed BOOLEAN DEFAULT FALSE,
    heard_about_us VARCHAR(255),
    additional_comments TEXT,
    terms_accepted BOOLEAN DEFAULT FALSE,
    privacy_accepted BOOLEAN DEFAULT FALSE,
    marketing_accepted BOOLEAN DEFAULT FALSE,
    application_status ENUM('pending', 'approved', 'rejected', 'under_review') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_application_number (application_number),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_course (course),
    INDEX idx_status (application_status),
    INDEX idx_created_date (created_at)
);

-- Create invoices table with payment tracking
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    course_name VARCHAR(255) NOT NULL,
    course_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    due_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    INDEX idx_application_id (application_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_invoice_date (invoice_date),
    INDEX idx_payment_status (paid_amount, due_amount),
    INDEX idx_due_date (due_date)
);

-- Create payment_history table for detailed payment tracking (optional)
CREATE TABLE IF NOT EXISTS payment_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    payment_amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'cheque', 'online', 'other') DEFAULT 'cash',
    transaction_reference VARCHAR(255),
    payment_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method)
);

-- Insert sample course data (optional)
INSERT IGNORE INTO applications (application_number, full_name, email, phone, date_of_birth, gender, address, city, state, postal_code, course, application_status, terms_accepted, privacy_accepted) 
VALUES 
('IBMP-2025-SAMPLE001', 'Sample Student', 'sample@example.com', '+91-9876543210', '1995-01-15', 'Male', '123 Sample Street', 'Mumbai', 'Maharashtra', '400001', 'Digital Marketing Certification', 'approved', TRUE, TRUE);

-- Show tables to confirm creation
SHOW TABLES;

-- Show invoice table structure to confirm payment fields
DESCRIBE invoices;
