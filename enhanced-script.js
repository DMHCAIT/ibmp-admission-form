// Enhanced IBMP Admission Form with Advanced Features
// VERSION: 2.6 - FIXED FORM SUBMISSION ENDPOINT + CACHE BUST
// TIMESTAMP: 2025-09-07
console.log('🚀 Loading Enhanced Script v2.6 - FIXED FORM SUBMISSION ENDPOINT');
console.log('🕒 Load time:', new Date().toISOString());

class IBMPAdmissionForm {
    constructor() {
        this.form = document.getElementById('admissionForm');
        this.validationRules = this.initValidationRules();
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadDraftData();
        this.initializeValidation();
        this.setupFileHandling();
    }

    setupEventListeners() {
        // Form submission
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        }

        // Input field listeners for validation
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('focus', () => this.handleFieldFocus(input));
        });

        // Declaration checkbox
        const declarationCheckbox = document.getElementById('declaration');
        if (declarationCheckbox) {
            declarationCheckbox.addEventListener('change', () => this.validateField(declarationCheckbox));
        }

        // Referral source handler
        const referralSource = document.getElementById('referralSource');
        if (referralSource) {
            referralSource.addEventListener('change', (e) => this.handleReferralSourceChange(e));
        }

        // File upload handlers
        this.setupFileUploadHandlers();

        // Percentage calculation for education table
        this.setupPercentageCalculation();
    }

    setupPercentageCalculation() {
        // Education levels that need percentage calculation
        const levels = ['10th', '12th', 'UG', 'PG', 'Other'];
        
        levels.forEach(level => {
            const marksField = document.querySelector(`input[name="marks${level}"]`);
            const maxMarksField = document.querySelector(`input[name="maxMarks${level}"]`);
            const percentageField = document.querySelector(`input[name="percentage${level}"]`);
            
            if (marksField && maxMarksField && percentageField) {
                [marksField, maxMarksField].forEach(field => {
                    field.addEventListener('input', () => {
                        this.calculatePercentage(marksField, maxMarksField, percentageField);
                    });
                });
            }
        });
    }

    calculatePercentage(marksField, maxMarksField, percentageField) {
        const marks = parseFloat(marksField.value) || 0;
        const maxMarks = parseFloat(maxMarksField.value) || 0;
        
        if (marks > 0 && maxMarks > 0) {
            const percentage = ((marks / maxMarks) * 100).toFixed(2);
            percentageField.value = percentage;
        } else {
            percentageField.value = '';
        }
    }

    initValidationRules() {
        return {
            title: { required: true, message: 'Please select your title' },
            firstName: { required: true, pattern: /^[a-zA-Z\s]+$/, message: 'First name must contain only letters' },
            lastName: { required: true, pattern: /^[a-zA-Z\s]+$/, message: 'Last name must contain only letters' },
            dateOfBirth: { required: true, message: 'Please enter your date of birth' },
            gender: { required: true, message: 'Please select your gender' },
            nationality: { required: true, message: 'Please enter your nationality' },
            email: { required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, message: 'Please enter a valid email address' },
            phone: { required: true, pattern: /^\+?[\d\s\-\(\)]+$/, message: 'Please enter a valid phone number' },
            address: { required: true, message: 'Please enter your address' },
            city: { required: true, message: 'Please enter your city' },
            state: { required: true, message: 'Please enter your state' },
            country: { required: true, message: 'Please enter your country' },
            postalCode: { required: true, message: 'Please enter your postal code' },
            program: { required: true, message: 'Please select a program' },
            paymentOption: { required: true, message: 'Please select a payment option' },
            referralSource: { required: true, message: 'Please tell us how you heard about IBMP' },
            declaration: { required: true, message: 'You must accept the declaration to proceed' }
        };
    }

    // Progress tracking functionality removed

    loadDraftData() {
        // Draft functionality removed - no longer loading draft data
        console.log('Draft functionality disabled');
    }

    populateForm(data) {
        try {
            Object.keys(data).forEach(key => {
                try {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (!field) return;
                    
                    // Skip file inputs completely - they cannot be programmatically set
                    if (field.type === 'file') {
                        console.log(`Skipping file input: ${key}`);
                        return;
                    }
                    
                    if (field.type === 'checkbox') {
                        field.checked = data[key] === 'on' || data[key] === true;
                    } else if (field.type === 'radio') {
                        if (field.value === data[key]) {
                            field.checked = true;
                        }
                    } else {
                        field.value = data[key] || '';
                    }
                } catch (fieldError) {
                    console.warn(`Error setting field ${key}:`, fieldError);
                }
            });
        } catch (error) {
            console.error('Error in populateForm:', error);
        }
    }

    getFormData() {
        const formData = new FormData(this.form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            // Skip file inputs - they contain File objects that can't be stored in localStorage
            const field = document.querySelector(`[name="${key}"]`);
            if (field && field.type === 'file') {
                continue; // Skip file inputs for localStorage storage
            }
            data[key] = value;
        }
        
        return data;
    }

    validateField(field) {
        const rules = this.validationRules[field.name];
        if (!rules) return true;
        
        const value = field.value.trim();
        const errorElement = field.parentNode.querySelector('.error-message');
        
        // Clear previous errors
        this.clearFieldError(field);
        
        // Required validation
        if (rules.required && value === '') {
            this.showFieldError(field, rules.message);
            return false;
        }
        
        // Pattern validation
        if (rules.pattern && value !== '' && !rules.pattern.test(value)) {
            this.showFieldError(field, rules.message);
            return false;
        }
        
        // Length validation
        if (rules.minLength && value.length < rules.minLength) {
            this.showFieldError(field, rules.message);
            return false;
        }
        
        return true;
    }

    showFieldError(field, message) {
        field.classList.add('error');
        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    clearFieldError(field) {
        field.classList.remove('error');
        const errorElement = field.parentNode.querySelector('.error-message');
        if (errorElement) {
            errorElement.textContent = '';
            errorElement.style.display = 'none';
        }
    }

    handleFieldFocus(field) {
        this.clearFieldError(field);
    }

    handleReferralSourceChange(event) {
        const selectedValue = event.target.value;
        const otherSourceGroup = document.getElementById('otherSourceGroup');
        const otherInput = document.getElementById('otherReferralSource');

        if (selectedValue === 'Other') {
            otherSourceGroup.style.display = 'block';
            otherInput.required = true;
        } else {
            otherSourceGroup.style.display = 'none';
            otherInput.required = false;
            otherInput.value = '';
            this.clearFieldError(otherInput);
        }
    }

    setupFileUploadHandlers() {
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', (e) => this.handleFileUpload(e));
        });
    }

    handleFileUpload(event) {
        const file = event.target.files[0];
        const input = event.target;
        const maxSize = 2 * 1024 * 1024; // 2MB
        
        if (file) {
            if (file.size > maxSize) {
                this.showFieldError(input, 'File size must be less than 2MB');
                input.value = '';
                return;
            }
            
            // Show file name
            const fileNote = input.parentNode.querySelector('.file-note');
            if (fileNote) {
                fileNote.textContent = `Selected: ${file.name}`;
                fileNote.style.color = '#059669';
            }
            
            this.clearFieldError(input);
        }
    }

    setupAutoSave() {
        // Auto-save functionality disabled
        console.log('Auto-save functionality disabled');
    }

    // Progress tracking functionality removed

    setupFileHandling() {
        // Add drag and drop functionality
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            const container = input.parentNode;
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, this.preventDefaults, false);
            });
            
            ['dragenter', 'dragover'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.add('drag-over'), false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.remove('drag-over'), false);
            });
            
            container.addEventListener('drop', (e) => this.handleDrop(e, input), false);
        });
    }

    preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    handleDrop(e, input) {
        const dt = e.dataTransfer;
        const files = dt.files;
        
        if (files.length > 0) {
            input.files = files;
            this.handleFileUpload({ target: input });
        }
    }

    initializeValidation() {
        // Real-time validation setup is already handled in setupEventListeners
    }

    async handleSubmit(event) {
        event.preventDefault();

        try {
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Validate all fields
            const isValid = this.validateForm();

            if (!isValid) {
                this.showNotification('Please fix all errors before submitting.', 'error');
                return;
            }

            // Create FormData for file uploads
            const formData = new FormData(this.form);

            // Submit to PHP backend
            const response = await fetch('submit_application_new.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                this.showSuccessModal({
                    applicationNumber: result.application_id,
                    message: result.message
                });
                // Draft functionality removed
            } else {
                this.showNotification(result.message, 'error');
            }

        } catch (error) {
            console.error('Submission error:', error);
            this.showNotification('Submission failed. Please try again.', 'error');
        } finally {
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-text">Submit Application</span><i class="fas fa-arrow-right"></i>';
        }
    }

    validateForm() {
        const requiredFields = this.form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }

    showSuccessModal(result) {
        const modal = document.getElementById('successModal');
        if (modal) {
            const messageElement = modal.querySelector('.modal-message');
            if (messageElement) {
                messageElement.innerHTML = `
                    <h3>Application Submitted Successfully!</h3>
                    <p>Your application number is: <strong>${result.applicationNumber}</strong></p>
                    <p>You will receive a confirmation email shortly.</p>
                `;
            }
            modal.style.display = 'block';
            
            // Auto-close modal after 5 seconds
            setTimeout(() => {
                modal.style.display = 'none';
            }, 5000);
        }
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
        
        // Close button functionality
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }
}

// Initialize the form when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing form...');
    
    // Initialize form
    try {
        new IBMPAdmissionForm();
        console.log('✅ Form initialized successfully with v2.6 - FIXED FORM SUBMISSION ENDPOINT');
    } catch (error) {
        console.error('❌ Error initializing form:', error);
    }
});

// Add notification styles
const notificationStyles = `
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    gap: 0.75rem;
    z-index: 1000;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 350px;
    border-left: 4px solid #3b82f6;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    border-left-color: #10b981;
    color: #059669;
}

.notification-error {
    border-left-color: #ef4444;
    color: #dc2626;
}

.notification-info {
    border-left-color: #3b82f6;
    color: #1d4ed8;
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
    color: #64748b;
    margin-left: auto;
}

.auto-save-indicator {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 6px;
    font-size: 0.875rem;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.auto-save-indicator.show {
    opacity: 1;
    transform: translateY(0);
}

.drag-over {
    border: 2px dashed #3b82f6 !important;
    background: rgba(59, 130, 246, 0.05) !important;
}
`;

// Add styles to head
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);
