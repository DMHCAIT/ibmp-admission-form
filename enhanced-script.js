// Enhanced IBMP Admission Form with Advanced Features
// VERSION: 2.7 - CUSTOM SCROLLBARS + TABLE ENHANCEMENTS
// TIMESTAMP: 2025-09-10
console.log('🚀 Loading Enhanced Script v2.7 - CUSTOM SCROLLBARS + TABLE ENHANCEMENTS');
console.log('🕒 Load time:', new Date().toISOString());

class IBMPAdmissionForm {
    constructor() {
        // Prevent multiple instances
        if (window.ibmpFormInstance) {
            console.warn('⚠️ IBMPAdmissionForm instance already exists, returning existing instance');
            return window.ibmpFormInstance;
        }
        
        this.form = document.getElementById('admissionForm');
        this.validationRules = this.initValidationRules();
        this.isSubmitting = false; // Add submission lock
        
        this.init();
        
        // Store the instance globally to prevent duplicates
        window.ibmpFormInstance = this;
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

        // Declaration checkboxes
        const checkboxes = ['termsAccepted', 'privacyAccepted', 'declarationAccepted'];
        checkboxes.forEach(checkboxId => {
            const checkbox = document.getElementById(checkboxId);
            if (checkbox) {
                checkbox.addEventListener('change', () => this.validateField(checkbox));
            }
        });

        // Referral source handler
        const referralSource = document.getElementById('referralSource');
        if (referralSource) {
            referralSource.addEventListener('change', (e) => this.handleReferralSourceChange(e));
        }

        // File upload handlers
        this.setupFileUploadHandlers();

        // Mobile optimizations
        this.setupMobileOptimizations();

        // Percentage calculation for education table
        this.setupPercentageCalculation();
    }

    setupMobileOptimizations() {
        // Check if device is mobile
        const isMobile = window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        
        if (isMobile) {
            console.log('📱 Mobile device detected - applying optimizations');
            
            // Add mobile-specific CSS class
            document.body.classList.add('mobile-device');
            
            // Optimize viewport for mobile
            this.optimizeViewport();
            
            // Add scroll hints for tables
            this.addScrollHints();
            
            // Optimize form navigation
            this.setupMobileFormNavigation();
            
            // Handle orientation changes
            this.handleOrientationChange();
            
            // Optimize touch interactions
            this.optimizeTouchInteractions();
        }
    }
    
    optimizeViewport() {
        // Ensure proper viewport settings
        let viewportMeta = document.querySelector('meta[name="viewport"]');
        if (viewportMeta) {
            viewportMeta.setAttribute('content', 
                'width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover'
            );
        }
    }
    
    addScrollHints() {
        // Add scroll hints and enhanced scrollbar functionality to tables
        const tableWrappers = document.querySelectorAll('.educational-table-wrapper, .family-details-wrapper');
        
        tableWrappers.forEach(wrapper => {
            const table = wrapper.querySelector('table');
            if (table) {
                // Add scroll indicators and functionality
                this.enhanceTableScrolling(wrapper, table);
                
                // Check if table is wider than container
                if (table.scrollWidth > wrapper.clientWidth) {
                    // Add mobile scroll hint if not already present
                    if (window.innerWidth <= 768 && !wrapper.querySelector('.mobile-scroll-hint')) {
                        const hint = document.createElement('div');
                        hint.className = 'mobile-scroll-hint';
                        hint.innerHTML = '👈 <span>Swipe left/right to see all fields</span> 👉';
                        wrapper.insertBefore(hint, wrapper.firstChild);
                    }
                }
            }
        });
    }
    
    enhanceTableScrolling(wrapper, table) {
        // Add scroll position indicators
        const addScrollIndicators = () => {
            // Remove existing indicators
            const existing = wrapper.querySelectorAll('.scroll-indicator');
            existing.forEach(el => el.remove());
            
            if (table.scrollWidth > wrapper.clientWidth) {
                // Left scroll indicator
                const leftIndicator = document.createElement('div');
                leftIndicator.className = 'scroll-indicator scroll-left';
                leftIndicator.innerHTML = '◀';
                leftIndicator.style.cssText = `
                    position: absolute;
                    left: 5px;
                    top: 50%;
                    transform: translateY(-50%);
                    background: rgba(59, 130, 246, 0.9);
                    color: white;
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 12px;
                    z-index: 15;
                    cursor: pointer;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                    pointer-events: none;
                `;
                
                // Right scroll indicator
                const rightIndicator = document.createElement('div');
                rightIndicator.className = 'scroll-indicator scroll-right';
                rightIndicator.innerHTML = '▶';
                rightIndicator.style.cssText = leftIndicator.style.cssText.replace('left: 5px', 'right: 5px');
                
                wrapper.appendChild(leftIndicator);
                wrapper.appendChild(rightIndicator);
                
                // Update indicator visibility based on scroll position
                const updateIndicators = () => {
                    const scrollLeft = wrapper.scrollLeft;
                    const maxScroll = wrapper.scrollWidth - wrapper.clientWidth;
                    
                    leftIndicator.style.opacity = scrollLeft > 10 ? '1' : '0';
                    leftIndicator.style.pointerEvents = scrollLeft > 10 ? 'auto' : 'none';
                    
                    rightIndicator.style.opacity = scrollLeft < maxScroll - 10 ? '1' : '0';
                    rightIndicator.style.pointerEvents = scrollLeft < maxScroll - 10 ? 'auto' : 'none';
                };
                
                // Scroll functionality
                leftIndicator.addEventListener('click', () => {
                    wrapper.scrollBy({ left: -200, behavior: 'smooth' });
                });
                
                rightIndicator.addEventListener('click', () => {
                    wrapper.scrollBy({ left: 200, behavior: 'smooth' });
                });
                
                // Update indicators on scroll
                wrapper.addEventListener('scroll', updateIndicators);
                
                // Initial update
                updateIndicators();
                
                // Show right indicator initially if table is scrollable
                if (table.scrollWidth > wrapper.clientWidth) {
                    rightIndicator.style.opacity = '1';
                    rightIndicator.style.pointerEvents = 'auto';
                }
            }
        };
        
        // Add indicators after a short delay to ensure proper measurements
        setTimeout(addScrollIndicators, 100);
        
        // Re-add indicators on window resize
        window.addEventListener('resize', () => {
            setTimeout(addScrollIndicators, 100);
        });
    }
    
    setupMobileFormNavigation() {
        // Add smooth scrolling to form sections
        const sections = document.querySelectorAll('.form-section');
        sections.forEach((section, index) => {
            section.style.scrollMarginTop = '20px';
        });
        
        // Focus management for better UX
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                // Scroll element into view with some padding
                setTimeout(() => {
                    input.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest' 
                    });
                }, 300);
            });
        });
    }
    
    handleOrientationChange() {
        window.addEventListener('orientationchange', () => {
            // Refresh scroll hints after orientation change
            setTimeout(() => {
                this.addScrollHints();
            }, 500);
        });
        
        window.addEventListener('resize', () => {
            // Debounce resize events
            clearTimeout(this.resizeTimeout);
            this.resizeTimeout = setTimeout(() => {
                this.addScrollHints();
            }, 250);
        });
    }
    
    optimizeTouchInteractions() {
        // Improve touch target sizes
        const touchTargets = document.querySelectorAll('input, button, select, textarea, a');
        touchTargets.forEach(target => {
            const computedStyle = window.getComputedStyle(target);
            const minHeight = parseInt(computedStyle.minHeight);
            
            // Ensure minimum touch target of 44px
            if (minHeight < 44) {
                target.style.minHeight = '44px';
            }
        });
        
        // Add visual feedback for touch
        document.addEventListener('touchstart', (e) => {
            const target = e.target.closest('button, input, select, textarea');
            if (target) {
                target.classList.add('touch-active');
            }
        });
        
        document.addEventListener('touchend', (e) => {
            const target = e.target.closest('button, input, select, textarea');
            if (target) {
                setTimeout(() => {
                    target.classList.remove('touch-active');
                }, 150);
            }
        });
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
            termsAccepted: { required: true, message: 'You must accept the terms and conditions to proceed' },
            privacyAccepted: { required: true, message: 'You must accept the privacy policy to proceed' },
            declarationAccepted: { required: true, message: 'You must confirm that all information is true and accurate' }
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
        
        // Handle checkbox validation differently
        let value;
        let isEmpty;
        
        if (field.type === 'checkbox') {
            value = field.checked;
            isEmpty = !field.checked;
        } else {
            value = field.value.trim();
            isEmpty = value === '';
        }
        
        const errorElement = field.parentNode.querySelector('.error-message');
        
        // Clear previous errors
        this.clearFieldError(field);
        
        // Required validation
        if (rules.required && isEmpty) {
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

        // Prevent multiple simultaneous submissions
        if (this.isSubmitting) {
            console.warn('⚠️ Submission already in progress, ignoring duplicate submission attempt');
            return;
        }

        try {
            this.isSubmitting = true; // Lock submissions
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

            // Debug: Log form data before validation
            console.log('📋 Form submission started');
            
            // Validate all fields
            const isValid = this.validateForm();
            console.log('✅ Form validation result:', isValid);

            if (!isValid) {
                console.log('❌ Form validation failed');
                this.showNotification('Please fix all errors before submitting.', 'error');
                return;
            }

            // Create FormData for file uploads
            const formData = new FormData(this.form);
            
            // Clean up empty educational fields to avoid server validation issues
            const fieldsToCleanup = [];
            for (let [key, value] of formData.entries()) {
                // Remove completely empty text fields (but keep files and required fields)
                if (typeof value === 'string' && value.trim() === '' && 
                    (key.includes('year') || key.includes('marks') || key.includes('percentage') || 
                     key.includes('subject')) && !key.includes('Other')) {
                    fieldsToCleanup.push(key);
                }
            }
            
            // Remove the empty fields
            fieldsToCleanup.forEach(key => {
                formData.delete(key);
                console.log(`🧹 Removed empty field: ${key}`);
            });
            
            // Debug: Log FormData contents
            console.log('📦 FormData created with entries:');
            for (let [key, value] of formData.entries()) {
                if (value instanceof File) {
                    console.log(`  ${key}: [File] ${value.name} (${value.size} bytes)`);
                } else {
                    console.log(`  ${key}: ${value}`);
                }
            }

            // Submit to PHP backend with retry logic
            console.log('🚀 Starting submission with retry logic...');
            const result = await this.submitWithRetry(formData);
            console.log('📨 Submission result:', result);

            if (result.success) {
                this.showSuccessModal({
                    applicationNumber: result.application_id || 'N/A',
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
            this.isSubmitting = false; // Reset submission lock
            const submitBtn = document.querySelector('.btn-submit');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<span class="btn-text">Submit Application</span><i class="fas fa-arrow-right"></i>';
        }
    }

    async submitWithRetry(formData, maxRetries = 3) {
        for (let attempt = 1; attempt <= maxRetries; attempt++) {
            try {
                console.log(`🔄 Submission attempt ${attempt}/${maxRetries}`);
                
                // Use the fixed main submission endpoint
                const endpoint = 'https://admission.ibmpractitioner.us/submit_application_new.php';
                
                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    // Try to get more detailed error information
                    let errorText = response.statusText;
                    try {
                        const errorBody = await response.text();
                        if (errorBody) {
                            console.log('🔍 Server error response:', errorBody);
                            errorText = errorBody.substring(0, 200); // First 200 chars
                        }
                    } catch (e) {
                        console.log('Could not read error response body');
                    }
                    throw new Error(`HTTP ${response.status}: ${errorText}`);
                }

                // Try to parse JSON response
                let result;
                try {
                    result = await response.json();
                } catch (jsonError) {
                    // If response isn't JSON, treat as text
                    const textResult = await response.text();
                    console.log('📄 Non-JSON response received:', textResult);
                    result = { 
                        success: response.ok, 
                        message: textResult || 'Form submitted successfully' 
                    };
                }
                
                console.log(`✅ Submission successful on attempt ${attempt}`);
                return result;

            } catch (error) {
                console.warn(`❌ Attempt ${attempt} failed:`, error.message);
                
                if (attempt === maxRetries) {
                    // Last attempt failed - check if it's a network issue
                    if (error.message.includes('Failed to fetch') || 
                        error.message.includes('ERR_NETWORK_CHANGED') ||
                        error.message.includes('network')) {
                        
                        throw new Error('Network connection issue. Please check your internet connection and try again.');
                    }
                    throw error;
                }
                
                // Wait before retry (exponential backoff)
                const waitTime = Math.pow(2, attempt) * 1000; // 2s, 4s, 8s
                console.log(`⏳ Waiting ${waitTime}ms before retry...`);
                await new Promise(resolve => setTimeout(resolve, waitTime));
            }
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

// Auto-initialize the form when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Prevent multiple initializations
    if (window.ibmpFormInstance) {
        console.log('✅ IBMPAdmissionForm already initialized, skipping');
        return;
    }
    
    console.log('🎯 Auto-initializing IBMPAdmissionForm...');
    try {
        window.ibmpForm = new IBMPAdmissionForm();
        console.log('✅ IBMPAdmissionForm initialized successfully');
    } catch (error) {
        console.error('❌ Error initializing IBMPAdmissionForm:', error);
    }
});
