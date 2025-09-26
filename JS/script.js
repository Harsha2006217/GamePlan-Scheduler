/**
 * GamePlan Scheduler - Advanced Professional JavaScript
 * Modern ES6+ features with security and user experience enhancements
 * 
 * This comprehensive JavaScript framework provides enterprise-level functionality
 * for form validation, real-time interactions, advanced notifications, and
 * professional user experience enhancements.
 * 
 * @author Harsha Kanaparthi
 * @version 2.0
 * @since 2025-09-30
 */

// Strict mode for security and performance
'use strict';

// Advanced Global Configuration
const GamePlanConfig = {
    version: '2.0',
    environment: 'production',
    features: {
        notifications: true,
        realTimeUpdates: true,
        analytics: true,
        autoSave: true,
        offlineSupport: true
    },
    api: {
        baseUrl: '/gameplan/api/v1/',
        timeout: 30000,
        retryAttempts: 3
    },
    validation: {
        username: {
            minLength: 3,
            maxLength: 50,
            pattern: /^[a-zA-Z0-9_-]+$/,
            reserved: ['admin', 'root', 'system', 'api', 'test']
        },
        password: {
            minLength: 8,
            maxLength: 128,
            requirements: {
                lowercase: true,
                uppercase: true,
                numbers: true,
                special: true
            },
            pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/
        },
        email: {
            pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
            maxLength: 254,
            blockedDomains: ['tempmail.com', '10minutemail.com']
        }
    }
};

// Professional Error Handling System
class GamePlanErrorHandler {
    static errors = new Map();
    static listeners = new Set();
    
    static logError(error, context = {}) {
        const errorId = this.generateErrorId();
        const errorData = {
            id: errorId,
            message: error.message || error,
            stack: error.stack,
            timestamp: new Date().toISOString(),
            context: context,
            userAgent: navigator.userAgent,
            url: window.location.href
        };
        
        this.errors.set(errorId, errorData);
        console.error('GamePlan Error:', errorData);
        
        // Notify listeners
        this.listeners.forEach(listener => {
            try {
                listener(errorData);
            } catch (e) {
                console.error('Error in error listener:', e);
            }
        });
        
        return errorId;
    }
    
    static generateErrorId() {
        return 'err_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    static onError(callback) {
        this.listeners.add(callback);
    }
    
    static getRecentErrors(limit = 10) {
        return Array.from(this.errors.values()).slice(-limit);
    }
}

// Enhanced Form Validation System
class GamePlanValidator {
    static rules = new Map();
    static customRules = new Map();
    
    static addRule(name, validator, message) {
        this.customRules.set(name, { validator, message });
    }
    
    static validateForm(form) {
        if (!form || !(form instanceof HTMLFormElement)) {
            throw new Error('Invalid form element provided');
        }
        
        const errors = [];
        const warnings = [];
        const formData = new FormData(form);
        
        // Get all form controls
        const controls = form.querySelectorAll('input[required], select[required], textarea[required], [data-validate]');
        
        controls.forEach(control => {
            const fieldErrors = this.validateField(control, formData);
            errors.push(...fieldErrors);
        });
        
        // Cross-field validation
        const crossFieldErrors = this.validateCrossFields(form, formData);
        errors.push(...crossFieldErrors);
        
        // Display errors if any
        if (errors.length > 0) {
            this.displayValidationErrors(errors, form);
            return false;
        }
        
        // Clear any existing errors
        this.clearValidationErrors(form);
        return true;
    }
    
    static validateField(field, formData = null) {
        const errors = [];
        const value = field.value?.trim() || '';
        const fieldName = field.name || field.id || 'field';
        const fieldType = field.type || 'text';
        
        // Required field validation
        if (field.hasAttribute('required') && !value) {
            errors.push(`${this.getFieldLabel(field)} is verplicht`);
            return errors;
        }
        
        // Skip further validation if field is empty and not required
        if (!value && !field.hasAttribute('required')) {
            return errors;
        }
        
        // Type-specific validation
        switch (fieldType) {
            case 'email':
                if (!this.isValidEmail(value)) {
                    errors.push(`${this.getFieldLabel(field)} moet een geldig emailadres zijn`);
                }
                break;
                
            case 'password':
                const passwordErrors = this.validatePassword(value);
                errors.push(...passwordErrors.map(err => `${this.getFieldLabel(field)}: ${err}`));
                break;
                
            case 'text':
                if (field.name === 'username') {
                    const usernameErrors = this.validateUsername(value);
                    errors.push(...usernameErrors.map(err => `${this.getFieldLabel(field)}: ${err}`));
                }
                break;
                
            case 'date':
                if (!this.isValidDate(value)) {
                    errors.push(`${this.getFieldLabel(field)} moet een geldige datum zijn`);
                }
                break;
                
            case 'time':
                if (!this.isValidTime(value)) {
                    errors.push(`${this.getFieldLabel(field)} moet een geldige tijd zijn`);
                }
                break;
        }
        
        // Length validation
        if (field.hasAttribute('minlength')) {
            const minLength = parseInt(field.getAttribute('minlength'));
            if (value.length < minLength) {
                errors.push(`${this.getFieldLabel(field)} moet minimaal ${minLength} karakters bevatten`);
            }
        }
        
        if (field.hasAttribute('maxlength')) {
            const maxLength = parseInt(field.getAttribute('maxlength'));
            if (value.length > maxLength) {
                errors.push(`${this.getFieldLabel(field)} mag maximaal ${maxLength} karakters bevatten`);
            }
        }
        
        // Pattern validation
        if (field.hasAttribute('pattern')) {
            const pattern = new RegExp(field.getAttribute('pattern'));
            if (!pattern.test(value)) {
                const patternMessage = field.getAttribute('data-pattern-message') || 
                                     `${this.getFieldLabel(field)} heeft een ongeldig formaat`;
                errors.push(patternMessage);
            }
        }
        
        // Custom validation rules
        const customRule = field.getAttribute('data-validate');
        if (customRule && this.customRules.has(customRule)) {
            const rule = this.customRules.get(customRule);
            if (!rule.validator(value, field, formData)) {
                errors.push(rule.message);
            }
        }
        
        // Whitespace-only validation (Fix #1001)
        if (value && /^\s+$/.test(value)) {
            errors.push(`${this.getFieldLabel(field)} mag niet alleen spaties bevatten`);
        }
        
        return errors;
    }
    
    static validateCrossFields(form, formData) {
        const errors = [];
        
        // Password confirmation validation
        const password = form.querySelector('input[name="password"]');
        const confirmPassword = form.querySelector('input[name="confirm_password"]');
        
        if (password && confirmPassword && password.value !== confirmPassword.value) {
            errors.push('Wachtwoorden komen niet overeen');
        }
        
        // Date range validation
        const startDate = form.querySelector('input[name="start_date"]');
        const endDate = form.querySelector('input[name="end_date"]');
        
        if (startDate && endDate && startDate.value && endDate.value) {
            if (new Date(startDate.value) > new Date(endDate.value)) {
                errors.push('Einddatum moet na startdatum liggen');
            }
        }
        
        // Time validation for past events
        const dateField = form.querySelector('input[name="date"]');
        const timeField = form.querySelector('input[name="time"]');
        
        if (dateField && timeField && dateField.value && timeField.value) {
            const eventDateTime = new Date(`${dateField.value}T${timeField.value}`);
            const now = new Date();
            
            if (eventDateTime < now) {
                errors.push('Datum en tijd moeten in de toekomst liggen');
            }
        }
        
        return errors;
    }
    
    static isValidEmail(email) {
        if (!email || email.length > GamePlanConfig.validation.email.maxLength) {
            return false;
        }
        
        if (!GamePlanConfig.validation.email.pattern.test(email)) {
            return false;
        }
        
        // Check blocked domains
        const domain = email.split('@')[1]?.toLowerCase();
        return !GamePlanConfig.validation.email.blockedDomains.includes(domain);
    }
    
    static validateUsername(username) {
        const errors = [];
        const config = GamePlanConfig.validation.username;
        
        if (username.length < config.minLength) {
            errors.push(`minimaal ${config.minLength} karakters vereist`);
        }
        
        if (username.length > config.maxLength) {
            errors.push(`maximaal ${config.maxLength} karakters toegestaan`);
        }
        
        if (!config.pattern.test(username)) {
            errors.push('alleen letters, cijfers, underscore en streepjes toegestaan');
        }
        
        if (config.reserved.includes(username.toLowerCase())) {
            errors.push('deze gebruikersnaam is gereserveerd');
        }
        
        return errors;
    }
    
    static validatePassword(password) {
        const errors = [];
        const config = GamePlanConfig.validation.password;
        
        if (password.length < config.minLength) {
            errors.push(`minimaal ${config.minLength} karakters vereist`);
        }
        
        if (password.length > config.maxLength) {
            errors.push(`maximaal ${config.maxLength} karakters toegestaan`);
        }
        
        if (config.requirements.lowercase && !/[a-z]/.test(password)) {
            errors.push('minimaal één kleine letter vereist');
        }
        
        if (config.requirements.uppercase && !/[A-Z]/.test(password)) {
            errors.push('minimaal één hoofdletter vereist');
        }
        
        if (config.requirements.numbers && !/\d/.test(password)) {
            errors.push('minimaal één cijfer vereist');
        }
        
        if (config.requirements.special && !/[@$!%*?&]/.test(password)) {
            errors.push('minimaal één speciaal teken vereist (@$!%*?&)');
        }
        
        // Check for common weak patterns
        if (/^(.)\1+$/.test(password)) {
            errors.push('mag niet uit herhalende karakters bestaan');
        }
        
        if (/123456|password|qwerty|admin/i.test(password)) {
            errors.push('mag geen veelgebruikte zwakke patronen bevatten');
        }
        
        return errors;
    }
    
    static isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date) && dateString === date.toISOString().split('T')[0];
    }
    
    static isValidTime(timeString) {
        return /^([01]?\d|2[0-3]):([0-5]?\d)$/.test(timeString);
    }
    
    static getFieldLabel(field) {
        const label = field.closest('form').querySelector(`label[for="${field.id}"]`);
        return label?.textContent?.replace('*', '').trim() || 
               field.getAttribute('data-label') || 
               field.placeholder || 
               field.name || 
               'Dit veld';
    }
    
    static displayValidationErrors(errors, form) {
        // Remove existing error alerts
        const existingAlerts = form.querySelectorAll('.validation-alert');
        existingAlerts.forEach(alert => alert.remove());
        
        if (errors.length === 0) return;
        
        // Create error alert
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show validation-alert';
        alertDiv.setAttribute('role', 'alert');
        alertDiv.innerHTML = `
            <div class="d-flex align-items-start">
                <i class="fas fa-exclamation-triangle me-3 mt-1 text-danger"></i>
                <div class="flex-grow-1">
                    <strong><i class="fas fa-clipboard-check me-2"></i>Validatiefouten gevonden:</strong>
                    <ul class="mb-0 mt-2">
                        ${errors.map(error => `<li class="small">${this.escapeHtml(error)}</li>`).join('')}
                    </ul>
                </div>
                <button type="button" class="btn-close ms-3" data-bs-dismiss="alert" aria-label="Sluiten"></button>
            </div>
        `;
        
        // Insert at the beginning of the form
        form.insertBefore(alertDiv, form.firstChild);
        
        // Scroll to show errors
        alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
        
        // Auto-dismiss after 10 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }
        }, 10000);
    }
    
    static clearValidationErrors(form) {
        const alerts = form.querySelectorAll('.validation-alert');
        alerts.forEach(alert => {
            alert.classList.remove('show');
            setTimeout(() => alert.remove(), 150);
        });
        
        // Clear field-level errors
        const errorFields = form.querySelectorAll('.is-invalid');
        errorFields.forEach(field => field.classList.remove('is-invalid'));
        
        const errorMessages = form.querySelectorAll('.invalid-feedback.d-block');
        errorMessages.forEach(msg => msg.classList.remove('d-block'));
    }
    
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Advanced Notification System
class GamePlanNotifications {
    static queue = [];
    static container = null;
    static permission = 'default';
    static settings = {
        position: 'top-end',
        duration: 5000,
        maxVisible: 5,
        sound: true,
        persistent: false
    };
    
    static async init() {
        // Request notification permission
        if ('Notification' in window) {
            this.permission = await Notification.requestPermission();
        }
        
        // Create toast container
        this.createToastContainer();
        
        // Load settings from localStorage
        this.loadSettings();
    }
    
    static createToastContainer() {
        if (this.container) return;
        
        this.container = document.createElement('div');
        this.container.className = `toast-container position-fixed ${this.settings.position} p-3`;
        this.container.style.zIndex = '9999';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-atomic', 'true');
        document.body.appendChild(this.container);
    }
    
    static show(title, message, type = 'info', options = {}) {
        const notification = {
            id: this.generateId(),
            title: title,
            message: message,
            type: type,
            timestamp: Date.now(),
            options: { ...this.settings, ...options }
        };
        
        this.queue.push(notification);
        
        // Show browser notification if supported and allowed
        if (this.permission === 'granted' && options.browser !== false) {
            this.showBrowserNotification(notification);
        }
        
        // Show toast notification
        this.showToast(notification);
        
        // Play sound if enabled
        if (this.settings.sound && options.sound !== false) {
            this.playNotificationSound(type);
        }
        
        return notification.id;
    }
    
    static showToast(notification) {
        const { id, title, message, type, options } = notification;
        
        // Limit visible toasts
        const visibleToasts = this.container.querySelectorAll('.toast.show');
        if (visibleToasts.length >= this.settings.maxVisible) {
            const oldestToast = visibleToasts[0];
            this.dismissToast(oldestToast);
        }
        
        // Create toast element
        const toastElement = document.createElement('div');
        toastElement.className = `toast align-items-center text-bg-${this.getBootstrapType(type)}`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        toastElement.setAttribute('data-notification-id', id);
        
        const iconClass = this.getIconClass(type);
        toastElement.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <div class="d-flex align-items-start">
                        <i class="${iconClass} me-3 mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-1">${this.escapeHtml(title)}</strong>
                            <div class="small">${this.escapeHtml(message)}</div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Sluiten"></button>
            </div>
            ${options.actions ? this.createToastActions(options.actions) : ''}
        `;
        
        this.container.appendChild(toastElement);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toastElement, {
            delay: options.persistent ? false : options.duration,
            autohide: !options.persistent
        });
        
        // Add event listeners
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
            this.removeFromQueue(id);
        });
        
        if (options.onClick) {
            toastElement.style.cursor = 'pointer';
            toastElement.addEventListener('click', options.onClick);
        }
        
        bsToast.show();
        
        return toastElement;
    }
    
    static showBrowserNotification(notification) {
        if (this.permission !== 'granted') return;
        
        const { title, message, options } = notification;
        const browserNotification = new Notification(title, {
            body: message,
            icon: '/gameplan/assets/img/icon.png',
            badge: '/gameplan/assets/img/badge.png',
            tag: notification.id,
            requireInteraction: options.persistent,
            silent: !options.sound
        });
        
        if (options.onClick) {
            browserNotification.onclick = options.onClick;
        }
        
        // Auto-close after duration
        if (!options.persistent) {
            setTimeout(() => browserNotification.close(), options.duration);
        }
        
        return browserNotification;
    }
    
    static success(title, message, options = {}) {
        return this.show(title, message, 'success', options);
    }
    
    static error(title, message, options = {}) {
        return this.show(title, message, 'error', { ...options, persistent: true });
    }
    
    static warning(title, message, options = {}) {
        return this.show(title, message, 'warning', options);
    }
    
    static info(title, message, options = {}) {
        return this.show(title, message, 'info', options);
    }
    
    static dismiss(notificationId) {
        const toastElement = this.container.querySelector(`[data-notification-id="${notificationId}"]`);
        if (toastElement) {
            this.dismissToast(toastElement);
        }
    }
    
    static dismissToast(toastElement) {
        const bsToast = bootstrap.Toast.getInstance(toastElement);
        if (bsToast) {
            bsToast.hide();
        } else {
            toastElement.remove();
        }
    }
    
    static dismissAll() {
        const toasts = this.container.querySelectorAll('.toast');
        toasts.forEach(toast => this.dismissToast(toast));
    }
    
    static generateId() {
        return 'notification_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }
    
    static getBootstrapType(type) {
        const typeMap = {
            success: 'success',
            error: 'danger',
            warning: 'warning',
            info: 'info',
            primary: 'primary',
            secondary: 'secondary'
        };
        return typeMap[type] || 'info';
    }
    
    static getIconClass(type) {
        const iconMap = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle',
            primary: 'fas fa-star',
            secondary: 'fas fa-bell'
        };
        return iconMap[type] || 'fas fa-info-circle';
    }
    
    static createToastActions(actions) {
        const actionsHtml = actions.map(action => {
            const btnClass = action.class || 'btn-outline-light btn-sm';
            return `<button type="button" class="btn ${btnClass} me-2" onclick="${action.handler}">${action.label}</button>`;
        }).join('');
        
        return `<div class="toast-actions p-2 border-top">${actionsHtml}</div>`;
    }
    
    static playNotificationSound(type) {
        try {
            const soundMap = {
                success: 'success.mp3',
                error: 'error.mp3',
                warning: 'warning.mp3',
                info: 'info.mp3'
            };
            
            const soundFile = soundMap[type] || 'info.mp3';
            const audio = new Audio(`/gameplan/assets/sounds/${soundFile}`);
            audio.volume = 0.3;
            audio.play().catch(e => console.log('Sound play failed:', e));
        } catch (e) {
            // Silent fail for sound
        }
    }
    
    static loadSettings() {
        try {
            const saved = localStorage.getItem('gameplan_notification_settings');
            if (saved) {
                this.settings = { ...this.settings, ...JSON.parse(saved) };
            }
        } catch (e) {
            console.warn('Failed to load notification settings:', e);
        }
    }
    
    static saveSettings(newSettings) {
        this.settings = { ...this.settings, ...newSettings };
        try {
            localStorage.setItem('gameplan_notification_settings', JSON.stringify(this.settings));
        } catch (e) {
            console.warn('Failed to save notification settings:', e);
        }
    }
    
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    static removeFromQueue(id) {
        const index = this.queue.findIndex(n => n.id === id);
        if (index > -1) {
            this.queue.splice(index, 1);
        }
    }
}

// Validation and interactivity for GamePlan Scheduler

document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const title = form.querySelector('[name="title"]');
            if (title && title.value.trim() === '') {
                alert('Title is required.');
                e.preventDefault();
                return;
            }
            const date = form.querySelector('[name="date"]');
            if (date && new Date(date.value) < new Date()) {
                alert('Date must be in the future.');
                e.preventDefault();
                return;
            }
            const time = form.querySelector('[name="time"]');
            if (time && !/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time.value)) {
                alert('Invalid time format.');
                e.preventDefault();
                return;
            }
        });
    });

    // Reminder pop-ups (simulate)
    const reminders = document.querySelectorAll('[data-reminder]');
    reminders.forEach(reminder => {
        const time = new Date(reminder.dataset.reminder);
        if (time > new Date()) {
            setTimeout(() => {
                alert('Reminder: ' + reminder.textContent);
            }, time - new Date());
        }
    });

    // Debounced search (for future)
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});

// Advanced JavaScript for GamePlan Scheduler - Professional Gaming Features

// DOM Content Loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Initialize Application
function initializeApp() {
    setupEventListeners();
    setupFormValidations();
    setupRealTimeFeatures();
    setupAccessibility();
    setupPerformanceOptimizations();
}

// Event Listeners Setup
function setupEventListeners() {
    // Delete confirmations
    document.querySelectorAll('.btn-delete').forEach(btn => {
        btn.addEventListener('click', confirmDelete);
    });

    // Form submissions with loading states
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', handleFormSubmit);
    });

    // Dynamic content loading
    setupDynamicContent();

    // Keyboard navigation
    setupKeyboardNavigation();

    // Real-time updates
    setupRealTimeUpdates();
}

// Form Validation Setup
function setupFormValidations() {
    // Real-time validation for all forms
    document.querySelectorAll('input, select, textarea').forEach(field => {
        field.addEventListener('blur', validateField);
        field.addEventListener('input', validateFieldRealTime);
    });

    // Password strength indicator
    setupPasswordStrength();

    // Date/time validation
    setupDateTimeValidation();
}

// Real-time Features Setup
function setupRealTimeFeatures() {
    // Auto-save drafts (for long forms)
    setupAutoSave();

    // Real-time search
    setupLiveSearch();

    // Notification system
    setupNotifications();

    // Activity status updates
    setupActivityTracking();
}

// Accessibility Setup
function setupAccessibility() {
    // ARIA labels and roles
    setupAriaLabels();

    // Keyboard navigation
    setupKeyboardSupport();

    // Screen reader support
    setupScreenReaderSupport();

    // Focus management
    setupFocusManagement();
}

// Performance Optimizations
function setupPerformanceOptimizations() {
    // Lazy loading for images
    setupLazyLoading();

    // Debounced search
    setupDebouncedSearch();

    // Virtual scrolling for large lists
    setupVirtualScrolling();

    // Memory management
    setupMemoryManagement();
}

// ====================
// Core Functionality
// ====================

// Confirm Delete Action
function confirmDelete(e) {
    e.preventDefault();

    const itemType = this.dataset.type || 'item';
    const itemName = this.dataset.name || 'this item';

    if (confirm(`Are you sure you want to delete ${itemName}? This action cannot be undone.`)) {
        // Add loading state
        this.classList.add('loading');
        this.disabled = true;

        // Proceed with deletion
        window.location.href = this.href;
    }
}

// Handle Form Submissions
function handleFormSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('button[type="submit"]');

    if (submitBtn) {
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    }
}

// Validate Individual Fields
function validateField(e) {
    const field = e.target;
    const value = field.value.trim();
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';

    // Field-specific validation
    switch (fieldName) {
        case 'username':
            if (value.length < 3) {
                isValid = false;
                errorMessage = 'Username must be at least 3 characters';
            } else if (!/^[a-zA-Z0-9_]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Username can only contain letters, numbers, and underscores';
            }
            break;

        case 'email':
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            }
            break;

        case 'password':
            if (value.length < 8) {
                isValid = false;
                errorMessage = 'Password must be at least 8 characters';
            } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
                isValid = false;
                errorMessage = 'Password must contain uppercase, lowercase, and number';
            }
            break;

        case 'title':
            if (!value) {
                isValid = false;
                errorMessage = 'Title is required';
            } else if (value.length > 100) {
                isValid = false;
                errorMessage = 'Title cannot exceed 100 characters';
            } else if (/^\s*$/.test(value)) {
                isValid = false;
                errorMessage = 'Title cannot be only spaces';
            }
            break;

        case 'date':
            if (!value) {
                isValid = false;
                errorMessage = 'Date is required';
            } else {
                const selectedDate = new Date(value);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (selectedDate < today) {
                    isValid = false;
                    errorMessage = 'Date must be today or in the future';
                }
            }
            break;

        case 'time':
            if (!value) {
                isValid = false;
                errorMessage = 'Time is required';
            }
            break;
    }

    // Update field appearance
    updateFieldValidation(field, isValid, errorMessage);

    return isValid;
}

// Real-time Field Validation
function validateFieldRealTime(e) {
    const field = e.target;

    // Debounced validation for better performance
    clearTimeout(field.validationTimeout);
    field.validationTimeout = setTimeout(() => {
        validateField({ target: field });
    }, 300);
}

// Update Field Validation Appearance
function updateFieldValidation(field, isValid, errorMessage) {
    const formGroup = field.closest('.mb-3') || field.parentNode;
    const feedback = formGroup.querySelector('.invalid-feedback') ||
                    formGroup.querySelector('.valid-feedback');

    // Remove existing classes
    field.classList.remove('is-valid', 'is-invalid');

    if (isValid && field.value.trim()) {
        field.classList.add('is-valid');
        if (feedback) feedback.style.display = 'none';
    } else if (!isValid) {
        field.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = errorMessage;
            feedback.style.display = 'block';
        }
    }
}

// ====================
// Advanced Features
// ====================

// Password Strength Indicator
function setupPasswordStrength() {
    const passwordFields = document.querySelectorAll('input[type="password"]');

    passwordFields.forEach(field => {
        field.addEventListener('input', function() {
            const strength = calculatePasswordStrength(this.value);
            updatePasswordStrengthIndicator(this, strength);
        });
    });
}

function calculatePasswordStrength(password) {
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z\d]/.test(password)) strength++;

    return strength;
}

function updatePasswordStrengthIndicator(field, strength) {
    const colors = ['#dc3545', '#ffc107', '#fd7e14', '#20c997', '#28a745'];
    const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];

    let indicator = field.parentNode.querySelector('.password-strength');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'password-strength form-text';
        field.parentNode.appendChild(indicator);
    }

    if (strength > 0) {
        indicator.innerHTML = `
            <div class="progress" style="height: 6px;">
                <div class="progress-bar" style="width: ${(strength / 5) * 100}%; background-color: ${colors[strength - 1]};"></div>
            </div>
            <small style="color: ${colors[strength - 1]};">${labels[strength - 1]}</small>
        `;
    } else {
        indicator.innerHTML = '';
    }
}

// Date/Time Validation
function setupDateTimeValidation() {
    const dateFields = document.querySelectorAll('input[type="date"]');

    dateFields.forEach(field => {
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        field.min = today;

        field.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const now = new Date();

            if (selectedDate < now.setHours(0, 0, 0, 0)) {
                this.setCustomValidity('Date must be today or in the future');
            } else {
                this.setCustomValidity('');
            }
        });
    });
}

// Auto-save Functionality
function setupAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');

    forms.forEach(form => {
        let autoSaveTimeout;

        form.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(() => {
                const formData = new FormData(form);
                const data = {};

                for (let [key, value] of formData.entries()) {
                    data[key] = value;
                }

                // Store in localStorage
                localStorage.setItem(`autosave_${form.id || 'form'}`, JSON.stringify(data));

                // Show save indicator
                showAutoSaveIndicator(form);
            }, 2000);
        });

        // Load auto-saved data
        const savedData = localStorage.getItem(`autosave_${form.id || 'form'}`);
        if (savedData) {
            const data = JSON.parse(savedData);
            Object.keys(data).forEach(key => {
                const field = form.querySelector(`[name="${key}"]`);
                if (field) {
                    field.value = data[key];
                }
            });
        }
    });
}

function showAutoSaveIndicator(form) {
    let indicator = form.querySelector('.autosave-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.className = 'autosave-indicator alert alert-info mt-2';
        indicator.innerHTML = '<i class="fas fa-save"></i> Draft saved automatically';
        form.appendChild(indicator);
    }

    indicator.style.display = 'block';
    setTimeout(() => {
        indicator.style.display = 'none';
    }, 3000);
}

// Live Search Functionality
function setupLiveSearch() {
    const searchInputs = document.querySelectorAll('input[data-live-search]');

    searchInputs.forEach(input => {
        let searchTimeout;

        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performLiveSearch(this);
            }, 300);
        });
    });
}

function performLiveSearch(input) {
    const query = input.value.trim();
    const target = input.dataset.liveSearch;
    const resultsContainer = document.querySelector(target);

    if (!resultsContainer) return;

    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }

    // Show loading state
    resultsContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

    // Perform search (this would typically be an AJAX call)
    // For demo purposes, we'll simulate a search
    setTimeout(() => {
        const mockResults = [
            { name: 'Fortnite Player', type: 'user' },
            { name: 'CS2 Tournament', type: 'event' },
            { name: 'Minecraft Builders', type: 'group' }
        ].filter(item => item.name.toLowerCase().includes(query.toLowerCase()));

        if (mockResults.length > 0) {
            resultsContainer.innerHTML = mockResults.map(result =>
                `<div class="search-result p-2 border-bottom">
                    <i class="fas fa-${result.type}"></i> ${result.name}
                </div>`
            ).join('');
        } else {
            resultsContainer.innerHTML = '<div class="text-muted p-2">No results found</div>';
        }
    }, 500);
}

// Notification System
function setupNotifications() {
    // Check for browser notification permission
    if ('Notification' in window) {
        document.addEventListener('click', requestNotificationPermission);
    }

    // Show in-app notifications
    showPendingNotifications();
}

function requestNotificationPermission() {
    if (Notification.permission === 'default') {
        Notification.requestPermission();
    }
}

function showPendingNotifications() {
    // Check for reminder notifications
    const reminders = document.querySelectorAll('[data-reminder]');

    reminders.forEach(reminder => {
        const reminderTime = new Date(reminder.dataset.reminder).getTime();
        const now = Date.now();

        if (reminderTime <= now && reminderTime > now - 60000) { // Within last minute
            showNotification('Reminder', reminder.textContent, 'reminder');
        }
    });
}

function showNotification(title, body, type = 'info') {
    // Browser notification
    if (Notification.permission === 'granted') {
        new Notification(title, {
            body: body,
            icon: '/favicon.ico',
            tag: type
        });
    }

    // In-app notification
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'reminder' ? 'warning' : 'info'} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-bell"></i> <strong>${title}</strong> ${body}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    document.body.appendChild(notification);

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// ====================
// Accessibility Features
// ====================

function setupAriaLabels() {
    // Add ARIA labels to interactive elements
    document.querySelectorAll('button, a, input, select, textarea').forEach(element => {
        if (!element.getAttribute('aria-label') && !element.getAttribute('aria-labelledby')) {
            const label = element.textContent.trim() || element.placeholder || element.name;
            if (label) {
                element.setAttribute('aria-label', label);
            }
        }
    });
}

function setupKeyboardSupport() {
    // Keyboard navigation for custom components
    document.addEventListener('keydown', function(e) {
        // ESC to close modals
        if (e.key === 'Escape') {
            const modal = document.querySelector('.modal.show');
            if (modal) {
                const closeBtn = modal.querySelector('.btn-close');
                if (closeBtn) closeBtn.click();
            }
        }

        // Enter to submit forms
        if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
            const form = e.target.closest('form');
            if (form) {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.click();
            }
        }
    });
}

function setupScreenReaderSupport() {
    // Announce dynamic content changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                announceToScreenReader('Content updated');
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

function announceToScreenReader(message) {
    const announcement = document.createElement('div');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    announcement.style.position = 'absolute';
    announcement.style.left = '-10000px';
    announcement.style.width = '1px';
    announcement.style.height = '1px';
    announcement.style.overflow = 'hidden';

    announcement.textContent = message;
    document.body.appendChild(announcement);

    setTimeout(() => {
        document.body.removeChild(announcement);
    }, 1000);
}

// ====================
// Performance Features
// ====================

function setupLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.remove('lazy');
                observer.unobserve(img);
            }
        });
    });

    images.forEach(img => imageObserver.observe(img));
}

function setupDebouncedSearch() {
    // Already implemented in live search
}

function setupVirtualScrolling() {
    // For large lists, implement virtual scrolling
    const largeLists = document.querySelectorAll('.virtual-list');

    largeLists.forEach(list => {
        // Implementation would go here for very large datasets
        // This is a placeholder for the concept
    });
}

function setupMemoryManagement() {
    // Clean up event listeners on page unload
    window.addEventListener('beforeunload', function() {
        // Clear timeouts, intervals, and observers
        const timeouts = window.timeouts || [];
        timeouts.forEach(clearTimeout);

        const intervals = window.intervals || [];
        intervals.forEach(clearInterval);
    });
}

// ====================
// Utility Functions
// ====================

// Debounce function for performance
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Throttle function for performance
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Safe console logging (for production)
function safeLog(message, level = 'log') {
    if (window.console && window.console[level]) {
        window.console[level](message);
    }
}

// Export functions for potential module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        validateField,
        showNotification,
        debounce,
        throttle
    };
}