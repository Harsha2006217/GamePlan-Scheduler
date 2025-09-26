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

document.addEventListener('DOMContentLoaded', () => {
    // Initialize all interactive components
    initializeCharacterCounters();
    initializePasswordStrengthMeters();
    initializeFormValidation();
    initializeFriendSelection();
});

/**
 * Initializes character counters for textareas with a maxlength attribute.
 */
function initializeCharacterCounters() {
    const textareas = document.querySelectorAll('textarea[maxlength]');
    textareas.forEach(textarea => {
        const maxLength = textarea.getAttribute('maxlength');
        let counter = textarea.parentNode.querySelector('.char-counter');
        if (!counter) {
            counter = document.createElement('div');
            counter.className = 'char-counter form-text text-muted';
            textarea.parentNode.appendChild(counter);
        }

        const updateCounter = () => {
            const currentLength = textarea.value.length;
            counter.textContent = `${currentLength}/${maxLength} karakters`;
            counter.style.color = (maxLength - currentLength) < 50 ? '#dc3545' : '#6c757d';
        };

        textarea.addEventListener('input', updateCounter);
        updateCounter(); // Initial call
    });
}

/**
 * Initializes password strength indicators for password fields.
 */
function initializePasswordStrengthMeters() {
    const passwordInput = document.getElementById('new_password');
    if (passwordInput) {
        const strengthIndicator = document.getElementById('password-strength');
        if (strengthIndicator) {
            passwordInput.addEventListener('input', () => {
                const password = passwordInput.value;
                if (password.length === 0) {
                    strengthIndicator.innerHTML = '';
                    return;
                }

                let strength = 0;
                if (password.length >= 8) strength++;
                if (/[a-z]/.test(password)) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/\d/.test(password)) strength++;
                if (/[^a-zA-Z\d]/.test(password)) strength++;

                const colors = ['text-danger', 'text-warning', 'text-info', 'text-primary', 'text-success'];
                const labels = ['Zeer zwak', 'Zwak', 'Redelijk', 'Goed', 'Sterk'];
                const strengthIndex = Math.max(0, strength - 1);

                strengthIndicator.innerHTML = `
                    <span class="${colors[strengthIndex]}">
                        <i class="fas fa-key"></i> ${labels[strengthIndex]}
                    </span>
                `;
            });
        }
    }
}

/**
 * Initializes client-side form validation for all forms with 'novalidate'.
 */
function initializeFormValidation() {
    const forms = document.querySelectorAll('form[novalidate]');
    forms.forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

/**
 * Initializes 'Select All' and 'Clear All' functionality for friend selection checkboxes.
 */
function initializeFriendSelection() {
    window.selectAllFriends = () => {
        document.querySelectorAll('input[name="shared_friends[]"], input[name="friends[]"]').forEach(cb => cb.checked = true);
    };

    window.clearAllFriends = () => {
        document.querySelectorAll('input[name="shared_friends[]"], input[name="friends[]"]').forEach(cb => cb.checked = false);
    };
}

/**
 * Validates a schedule form before submission.
 * @returns {boolean} - True if valid, false otherwise.
 */
function validateScheduleForm() {
    const game = document.getElementById('game_id');
    const date = document.getElementById('date');
    const time = document.getElementById('time');
    let isValid = true;

    if (game.value === '') {
        alert('Selecteer een game.');
        isValid = false;
    }

    const selectedDate = new Date(date.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        alert('De datum moet vandaag of in de toekomst zijn.');
        isValid = false;
    }

    if (time.value === '') {
        alert('Selecteer een tijd.');
        isValid = false;
    }

    return isValid;
}

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

// JavaScript for GamePlan Scheduler

// Form validation
function validateForm(event) {
    const inputs = document.querySelectorAll('input[required], textarea[required], select[required]');
    let valid = true;
    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            valid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });
    if (!valid) {
        event.preventDefault();
        alert('Please fill in all required fields.');
    }
    return valid;
}

document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
    });

    // Reminder alerts (simulate polling)
    setInterval(() => {
        fetch('functions.php?action=checkReminders')
            .then(response => response.json())
            .then(data => {
                data.forEach(reminder => {
                    const eventTime = new Date(reminder.date + ' ' + reminder.time);
                    const now = new Date();
                    const diff = (eventTime - now) / 1000 / 60; // minutes
                    if (reminder.reminder === '1 hour before' && diff <= 60 && diff > 0) {
                        alert(`Reminder: ${reminder.title} at ${reminder.time}`);
                    } else if (reminder.reminder === '1 day before' && diff <= 1440 && diff > 0) {
                        alert(`Reminder: ${reminder.title} tomorrow at ${reminder.time}`);
                    }
                });
            });
    }, 60000); // Check every minute
});

// Debounced search for friends (if implemented)
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

// GamePlan Scheduler Scripts

// Login form validation
function validateLoginForm() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    if (!email || !password) {
        alert('Please fill in all fields.');
        return false;
    }
    return true;
}

// Register form validation
function validateRegisterForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    if (!username || !email || !password) {
        alert('Please fill in all fields.');
        return false;
    }
    if (username.length > 50) {
        alert('Username too long.');
        return false;
    }
    return true;
}

// Schedule form validation
function validateScheduleForm() {
    const game = document.getElementById('game_id').value;
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    if (!game) {
        alert('Please select a game.');
        return false;
    }
    if (!date || new Date(date) < new Date()) {
        alert('Please select a future date.');
        return false;
    }
    if (!time || !/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time)) {
        alert('Please enter a valid time.');
        return false;
    }
    return true;
}

// Event form validation
function validateEventForm() {
    const title = document.getElementById('title').value.trim();
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    if (!title || title.length > 100 || /^\s*$/.test(title)) {
        alert('Title is required, max 100 chars, not only spaces.');
        return false;
    }
    if (!date || new Date(date) < new Date()) {
        alert('Please select a future date.');
        return false;
    }
    if (!time || !/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time)) {
        alert('Please enter a valid time.');
        return false;
    }
    return true;
}

// Reminder pop-ups (simulate based on page load, in real app use server-side or WebSockets)
document.addEventListener('DOMContentLoaded', function() {
    // Example: Check for reminders (in real app, fetch from server)
    // For demo, assume no reminders on load
});