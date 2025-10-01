/**
 * GamePlan Scheduler - Enhanced Professional JavaScript Implementation
 * Advanced Gaming Schedule Management System with Complete Validation
 * Author: Harsha Kanaparthi
 * Version: 3.0 Professional Production Edition
 * Date: September 30, 2025
 * Project: K1 W3 Realisatie - Complete Working JavaScript
 */

'use strict';

// ===================== ADVANCED CONFIGURATION CONSTANTS =====================
const GAMEPLAN_CONFIG = {
    // Validation rules for comprehensive form checking
    VALIDATION: {
        TITLE_MAX_LENGTH: 100,
        TITLE_MIN_LENGTH: 3,
        USERNAME_MAX_LENGTH: 50,
        USERNAME_MIN_LENGTH: 3,
        PASSWORD_MIN_LENGTH: 6,
        DESCRIPTION_MAX_LENGTH: 500,
        EMAIL_PATTERN: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
        WHITESPACE_ONLY: /^\s*$/,
        NEGATIVE_TIME: /^-/,
        SQL_INJECTION_PATTERNS: [
            /(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER)\b)/i,
            /(UNION|OR|AND)\s+\d+\s*=\s*\d+/i,
            /['"]\s*(OR|AND)\s+['"]\w+['"]\s*=\s*['"][\w\s]*['"]/i
        ]
    },
    
    // Professional error messages in Dutch for user feedback
    MESSAGES: {
        ERRORS: {
            REQUIRED_FIELD: 'Dit veld is verplicht en mag niet leeg zijn.',
            TITLE_SPACES_ONLY: 'Titel mag niet alleen uit spaties bestaan.',
            TITLE_TOO_LONG: 'Titel mag maximaal 100 karakters bevatten.',
            TITLE_TOO_SHORT: 'Titel moet minimaal 3 karakters bevatten.',
            USERNAME_TOO_LONG: 'Gebruikersnaam mag maximaal 50 karakters bevatten.',
            USERNAME_TOO_SHORT: 'Gebruikersnaam moet minimaal 3 karakters bevatten.',
            PASSWORD_TOO_SHORT: 'Wachtwoord moet minimaal 6 karakters bevatten.',
            DESCRIPTION_TOO_LONG: 'Beschrijving mag maximaal 500 karakters bevatten.',
            FUTURE_DATE_REQUIRED: 'Datum moet in de toekomst liggen.',
            POSITIVE_TIME_REQUIRED: 'Tijd mag niet negatief zijn.',
            INVALID_EMAIL: 'Voer een geldig e-mailadres in.',
            INVALID_NUMBER: 'Voer een geldig positief getal in.',
            SUSPICIOUS_INPUT: 'Verdachte invoer gedetecteerd. Gebruik alleen normale tekens.',
            NETWORK_ERROR: 'Netwerkfout. Controleer uw internetverbinding.',
            SERVER_ERROR: 'Serverfout. Probeer het later opnieuw.',
            SESSION_EXPIRED: 'Uw sessie is verlopen. Log opnieuw in.',
            PERMISSION_DENIED: 'U heeft geen toestemming voor deze actie.'
        },
        SUCCESS: {
            FORM_SUBMITTED: 'Formulier succesvol verzonden!',
            DATA_SAVED: 'Gegevens succesvol opgeslagen!',
            FRIEND_ADDED: 'Vriend succesvol toegevoegd!',
            SCHEDULE_CREATED: 'Schema succesvol aangemaakt!',
            EVENT_CREATED: 'Evenement succesvol aangemaakt!',
            ITEM_UPDATED: 'Item succesvol bijgewerkt!',
            ITEM_DELETED: 'Item succesvol verwijderd!'
        },
        CONFIRMATIONS: {
            DELETE_CONFIRM: 'Weet je zeker dat je dit item wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.',
            UNSAVED_CHANGES: 'Je hebt niet-opgeslagen wijzigingen. Weet je zeker dat je de pagina wilt verlaten?',
            CLEAR_FORM: 'Weet je zeker dat je het formulier wilt wissen?'
        },
        REMINDERS: {
            UPCOMING_EVENT: 'Herinnering: Je hebt binnenkort een evenement!',
            SCHEDULE_REMINDER: 'Herinnering: Je hebt een geplande gaming sessie!',
            FRIEND_ONLINE: 'Je vriend is nu online en beschikbaar om te gamen!'
        }
    },
    
    // Advanced timing and performance settings
    TIMING: {
        DEBOUNCE_DELAY: 300,
        TOAST_DURATION: 5000,
        ANIMATION_DURATION: 300,
        SESSION_CHECK_INTERVAL: 30000,
        REMINDER_CHECK_INTERVAL: 60000,
        TYPING_INDICATOR_DELAY: 1000
    },
    
    // CSS classes for dynamic styling
    CLASSES: {
        ERROR: 'alert alert-danger',
        SUCCESS: 'alert alert-success',
        WARNING: 'alert alert-warning',
        INFO: 'alert alert-info',
        LOADING: 'loading',
        INVALID: 'is-invalid',
        VALID: 'is-valid',
        DISABLED: 'disabled',
        HIDDEN: 'd-none',
        VISIBLE: 'd-block'
    }
};

// ===================== ADVANCED VALIDATION ENGINE =====================
/**
 * Professional form validation system with comprehensive security checks
 * Handles all user input validation with advanced pattern matching
 */
class GamePlanValidator {
    constructor() {
        this.errors = [];
        this.warnings = [];
    }
    
    /**
     * Validates a complete form with all fields and security checks
     * @param {HTMLFormElement} form - The form element to validate
     * @returns {Object} Validation result with status and messages
     */
    validateForm(form) {
        this.errors = [];
        this.warnings = [];
        
        if (!form) {
            this.addError('Formulier niet gevonden.');
            return this.getResult();
        }
        
        // Get all required fields for validation
        const requiredFields = form.querySelectorAll('[required]');
        const textFields = form.querySelectorAll('input[type="text"], input[type="email"], textarea');
        const dateFields = form.querySelectorAll('input[type="date"]');
        const timeFields = form.querySelectorAll('input[type="time"]');
        const numberFields = form.querySelectorAll('input[type="number"]');
        
        // Validate required fields first
        requiredFields.forEach(field => this.validateRequired(field));
        
        // Validate specific field types with advanced checks
        textFields.forEach(field => this.validateText(field));
        dateFields.forEach(field => this.validateDate(field));
        timeFields.forEach(field => this.validateTime(field));
        numberFields.forEach(field => this.validateNumber(field));
        
        // Security validation for all inputs
        textFields.forEach(field => this.validateSecurity(field));
        
        return this.getResult();
    }
    
    /**
     * Validates required fields with comprehensive empty checking
     * @param {HTMLElement} field - The field to validate
     */
    validateRequired(field) {
        const value = field.value.trim();
        
        if (!value) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.REQUIRED_FIELD);
            return;
        }
        
        // Check for whitespace-only input (security issue #1001)
        if (GAMEPLAN_CONFIG.VALIDATION.WHITESPACE_ONLY.test(value)) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.TITLE_SPACES_ONLY);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Advanced text field validation with length and content checks
     * @param {HTMLElement} field - The text field to validate
     */
    validateText(field) {
        const value = field.value.trim();
        
        if (!value) return; // Skip if empty (handled by required validation)
        
        // Validate based on field type and name
        switch (field.name) {
            case 'title':
                this.validateTitle(field, value);
                break;
            case 'username':
                this.validateUsername(field, value);
                break;
            case 'description':
                this.validateDescription(field, value);
                break;
            default:
                // Generic text validation
                if (value.length > 255) {
                    this.addFieldError(field, 'Tekst mag maximaal 255 karakters bevatten.');
                }
        }
    }
    
    /**
     * Professional title validation with multiple checks
     * @param {HTMLElement} field - The title field
     * @param {string} value - The title value
     */
    validateTitle(field, value) {
        if (value.length < GAMEPLAN_CONFIG.VALIDATION.TITLE_MIN_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.TITLE_TOO_SHORT);
            return;
        }
        
        if (value.length > GAMEPLAN_CONFIG.VALIDATION.TITLE_MAX_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.TITLE_TOO_LONG);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Username validation with length and character checks
     * @param {HTMLElement} field - The username field
     * @param {string} value - The username value
     */
    validateUsername(field, value) {
        if (value.length < GAMEPLAN_CONFIG.VALIDATION.USERNAME_MIN_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.USERNAME_TOO_SHORT);
            return;
        }
        
        if (value.length > GAMEPLAN_CONFIG.VALIDATION.USERNAME_MAX_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.USERNAME_TOO_LONG);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Description validation with length limits
     * @param {HTMLElement} field - The description field
     * @param {string} value - The description value
     */
    validateDescription(field, value) {
        if (value.length > GAMEPLAN_CONFIG.VALIDATION.DESCRIPTION_MAX_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.DESCRIPTION_TOO_LONG);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Advanced date validation ensuring future dates only
     * @param {HTMLElement} field - The date field to validate
     */
    validateDate(field) {
        const value = field.value;
        
        if (!value) return; // Skip if empty
        
        const inputDate = new Date(value);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time for accurate comparison
        
        if (inputDate < today) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.FUTURE_DATE_REQUIRED);
            return;
        }
        
        // Validate date format and existence (edge case #1004)
        if (isNaN(inputDate.getTime())) {
            this.addFieldError(field, 'Voer een geldige datum in.');
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Time validation preventing negative values
     * @param {HTMLElement} field - The time field to validate
     */
    validateTime(field) {
        const value = field.value;
        
        if (!value) return; // Skip if empty
        
        if (GAMEPLAN_CONFIG.VALIDATION.NEGATIVE_TIME.test(value)) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.POSITIVE_TIME_REQUIRED);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Number validation ensuring positive values
     * @param {HTMLElement} field - The number field to validate
     */
    validateNumber(field) {
        const value = field.value;
        
        if (!value) return; // Skip if empty
        
        const numberValue = parseFloat(value);
        
        if (isNaN(numberValue)) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.INVALID_NUMBER);
            return;
        }
        
        if (numberValue < 0) {
            this.addFieldError(field, 'Getal moet positief zijn.');
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Advanced security validation to prevent injection attacks
     * @param {HTMLElement} field - The field to check for security issues
     */
    validateSecurity(field) {
        const value = field.value;
        
        if (!value) return;
        
        // Check for SQL injection patterns
        const hasSqlInjection = GAMEPLAN_CONFIG.VALIDATION.SQL_INJECTION_PATTERNS.some(pattern => 
            pattern.test(value)
        );
        
        if (hasSqlInjection) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.SUSPICIOUS_INPUT);
            return;
        }
        
        // Check for script injection attempts
        if (value.includes('<script') || value.includes('javascript:')) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.SUSPICIOUS_INPUT);
            return;
        }
    }
    
    /**
     * Email validation with professional pattern matching
     * @param {HTMLElement} field - The email field to validate
     */
    validateEmail(field) {
        const value = field.value.trim();
        
        if (!value) return;
        
        if (!GAMEPLAN_CONFIG.VALIDATION.EMAIL_PATTERN.test(value)) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.INVALID_EMAIL);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Password validation with security requirements
     * @param {HTMLElement} field - The password field to validate
     */
    validatePassword(field) {
        const value = field.value;
        
        if (!value) return;
        
        if (value.length < GAMEPLAN_CONFIG.VALIDATION.PASSWORD_MIN_LENGTH) {
            this.addFieldError(field, GAMEPLAN_CONFIG.MESSAGES.ERRORS.PASSWORD_TOO_SHORT);
            return;
        }
        
        this.markFieldValid(field);
    }
    
    /**
     * Adds an error to the validation results
     * @param {string} message - Error message to add
     */
    addError(message) {
        this.errors.push(message);
    }
    
    /**
     * Adds a field-specific error and marks field as invalid
     * @param {HTMLElement} field - The field with the error
     * @param {string} message - Error message
     */
    addFieldError(field, message) {
        this.errors.push(message);
        this.markFieldInvalid(field);
    }
    
    /**
     * Marks a field as valid with visual feedback
     * @param {HTMLElement} field - The field to mark as valid
     */
    markFieldValid(field) {
        field.classList.remove(GAMEPLAN_CONFIG.CLASSES.INVALID);
        field.classList.add(GAMEPLAN_CONFIG.CLASSES.VALID);
    }
    
    /**
     * Marks a field as invalid with visual feedback
     * @param {HTMLElement} field - The field to mark as invalid
     */
    markFieldInvalid(field) {
        field.classList.remove(GAMEPLAN_CONFIG.CLASSES.VALID);
        field.classList.add(GAMEPLAN_CONFIG.CLASSES.INVALID);
    }
    
    /**
     * Returns the complete validation result
     * @returns {Object} Validation result with status and messages
     */
    getResult() {
        return {
            isValid: this.errors.length === 0,
            errors: this.errors,
            warnings: this.warnings,
            errorCount: this.errors.length,
            warningCount: this.warnings.length
        };
    }
}

// ===================== ADVANCED UI FEEDBACK SYSTEM =====================
/**
 * Professional user interface feedback system with toast notifications
 * Provides consistent user feedback across the application
 */
class GamePlanUI {
    constructor() {
        this.toastContainer = this.createToastContainer();
        this.loadingOverlay = this.createLoadingOverlay();
    }
    
    /**
     * Creates a toast container for notifications
     * @returns {HTMLElement} Toast container element
     */
    createToastContainer() {
        let container = document.getElementById('toast-container');
        
        if (!container) {
            container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        return container;
    }
    
    /**
     * Creates a loading overlay for form submissions
     * @returns {HTMLElement} Loading overlay element
     */
    createLoadingOverlay() {
        let overlay = document.getElementById('loading-overlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.className = 'position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-flex justify-content-center align-items-center d-none';
            overlay.style.zIndex = '10000';
            overlay.innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Laden...</span>
                </div>
            `;
            document.body.appendChild(overlay);
        }
        
        return overlay;
    }
    
    /**
     * Shows a professional toast notification
     * @param {string} message - Message to display
     * @param {string} type - Type of notification (success, error, warning, info)
     * @param {number} duration - Display duration in milliseconds
     */
    showToast(message, type = 'info', duration = GAMEPLAN_CONFIG.TIMING.TOAST_DURATION) {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${this.getBootstrapType(type)} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas ${this.getIconClass(type)} me-2"></i>
                    ${this.escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        this.toastContainer.appendChild(toast);
        
        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toast, {
            autohide: true,
            delay: duration
        });
        
        bsToast.show();
        
        // Remove from DOM after hiding
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    /**
     * Shows a success notification
     * @param {string} message - Success message
     */
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    /**
     * Shows an error notification
     * @param {string} message - Error message
     */
    showError(message) {
        this.showToast(message, 'error', 8000); // Longer duration for errors
    }
    
    /**
     * Shows a warning notification
     * @param {string} message - Warning message
     */
    showWarning(message) {
        this.showToast(message, 'warning');
    }
    
    /**
     * Shows an info notification
     * @param {string} message - Info message
     */
    showInfo(message) {
        this.showToast(message, 'info');
    }
    
    /**
     * Shows loading overlay during form submission
     */
    showLoading() {
        this.loadingOverlay.classList.remove(GAMEPLAN_CONFIG.CLASSES.HIDDEN);
        this.loadingOverlay.classList.add(GAMEPLAN_CONFIG.CLASSES.VISIBLE);
    }
    
    /**
     * Hides loading overlay
     */
    hideLoading() {
        this.loadingOverlay.classList.remove(GAMEPLAN_CONFIG.CLASSES.VISIBLE);
        this.loadingOverlay.classList.add(GAMEPLAN_CONFIG.CLASSES.HIDDEN);
    }
    
    /**
     * Gets Bootstrap color type for notifications
     * @param {string} type - Internal type
     * @returns {string} Bootstrap color class
     */
    getBootstrapType(type) {
        const typeMap = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return typeMap[type] || 'info';
    }
    
    /**
     * Gets Font Awesome icon class for notification type
     * @param {string} type - Notification type
     * @returns {string} Icon class
     */
    getIconClass(type) {
        const iconMap = {
            'success': 'fa-check-circle',
            'error': 'fa-exclamation-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };
        return iconMap[type] || 'fa-info-circle';
    }
    
    /**
     * Escapes HTML to prevent XSS attacks
     * @param {string} text - Text to escape
     * @returns {string} Escaped text
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Shows a professional confirmation dialog
     * @param {string} message - Confirmation message
     * @param {Function} onConfirm - Callback for confirmation
     * @param {Function} onCancel - Callback for cancellation
     */
    showConfirmation(message, onConfirm, onCancel = null) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content bg-dark text-white">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-question-circle me-2"></i>
                            Bevestiging
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        ${this.escapeHtml(message)}
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuleren</button>
                        <button type="button" class="btn btn-danger" id="confirm-action">Bevestigen</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        const bsModal = new bootstrap.Modal(modal);
        const confirmBtn = modal.querySelector('#confirm-action');
        
        confirmBtn.addEventListener('click', () => {
            bsModal.hide();
            if (onConfirm) onConfirm();
        });
        
        modal.addEventListener('hidden.bs.modal', () => {
            modal.remove();
            if (onCancel) onCancel();
        });
        
        bsModal.show();
    }
}

// ===================== ADVANCED REMINDER SYSTEM =====================
/**
 * Professional reminder system with multiple notification types
 * Handles all scheduled reminders and notifications
 */
class GamePlanReminders {
    constructor(ui) {
        this.ui = ui;
        this.checkInterval = null;
        this.lastCheck = Date.now();
    }
    
    /**
     * Initializes the reminder system
     */
    initialize() {
        this.checkReminders();
        this.startPeriodicCheck();
        this.setupVisibilityChangeHandler();
    }
    
    /**
     * Checks for due reminders and displays notifications
     */
    checkReminders() {
        const now = new Date();
        const reminderElements = document.querySelectorAll('[data-reminder-time]');
        
        reminderElements.forEach(element => {
            const reminderTime = new Date(element.getAttribute('data-reminder-time'));
            const reminderType = element.getAttribute('data-reminder-type') || 'event';
            const reminderTitle = element.getAttribute('data-reminder-title') || 'Herinnering';
            const alreadyShown = element.getAttribute('data-reminder-shown') === 'true';
            
            if (reminderTime <= now && !alreadyShown) {
                this.showReminder(reminderTitle, reminderType);
                element.setAttribute('data-reminder-shown', 'true');
            }
        });
    }
    
    /**
     * Shows a reminder notification with appropriate styling
     * @param {string} title - Reminder title
     * @param {string} type - Reminder type (event, schedule, friend)
     */
    showReminder(title, type) {
        let message = '';
        
        switch (type) {
            case 'schedule':
                message = `${GAMEPLAN_CONFIG.MESSAGES.REMINDERS.SCHEDULE_REMINDER}\n"${title}"`;
                break;
            case 'friend':
                message = `${GAMEPLAN_CONFIG.MESSAGES.REMINDERS.FRIEND_ONLINE}\n"${title}"`;
                break;
            default:
                message = `${GAMEPLAN_CONFIG.MESSAGES.REMINDERS.UPCOMING_EVENT}\n"${title}"`;
        }
        
        // Show browser notification if permission granted
        this.showBrowserNotification(title, message);
        
        // Always show in-app notification
        this.ui.showInfo(message);
        
        // Play notification sound if available
        this.playNotificationSound();
    }
    
    /**
     * Shows browser notification with permission check
     * @param {string} title - Notification title
     * @param {string} message - Notification message
     */
    showBrowserNotification(title, message) {
        if ('Notification' in window) {
            if (Notification.permission === 'granted') {
                new Notification(`GamePlan - ${title}`, {
                    body: message,
                    icon: '/gameplan/favicon.ico',
                    badge: '/gameplan/favicon.ico'
                });
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        new Notification(`GamePlan - ${title}`, {
                            body: message,
                            icon: '/gameplan/favicon.ico',
                            badge: '/gameplan/favicon.ico'
                        });
                    }
                });
            }
        }
    }
    
    /**
     * Plays notification sound if audio is available
     */
    playNotificationSound() {
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmMeCSCI2O/EdikHKHzJ8NiKOQgZZ7zs3ZdNDwxOqOLwtWMcBjiS2O/WeiMEKnvH8N2QQAoUXrTp66hVFApGneDyvmMeSCGI1-7EdikHKHzJ8NiKOQgZZ7zs3ZdNDwxOqOLwtWMcBjiS2O_WeiMEKnvH8N2QQAoUXrTp66hVFApGneDyvmMeSCGI1-7EdikHKHzJ8NiKOQgZZ7zs3ZdNDwxOqOLwtWMcBTiS2O_WeiMEKnvH8N2QQAoUXrTp66hVFApGneDyvmMeSCGI1-7EdikHKHzJ8NiKOQgZZ7zs3ZdNDwxOqOLwtWQ=');
            audio.volume = 0.3;
            audio.play().catch(() => {
                // Ignore audio play errors (user interaction required)
            });
        } catch (error) {
            // Audio not supported or failed, continue silently
        }
    }
    
    /**
     * Starts periodic reminder checking
     */
    startPeriodicCheck() {
        this.checkInterval = setInterval(() => {
            this.checkReminders();
        }, GAMEPLAN_CONFIG.TIMING.REMINDER_CHECK_INTERVAL);
    }
    
    /**
     * Stops periodic reminder checking
     */
    stopPeriodicCheck() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    }
    
    /**
     * Handles page visibility changes to optimize performance
     */
    setupVisibilityChangeHandler() {
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.stopPeriodicCheck();
            } else {
                this.checkReminders();
                this.startPeriodicCheck();
            }
        });
    }
}

// ===================== ADVANCED FORM ENHANCEMENT SYSTEM =====================
/**
 * Professional form enhancement with real-time validation and UX improvements
 */
class GamePlanFormEnhancer {
    constructor(validator, ui) {
        this.validator = validator;
        this.ui = ui;
        this.forms = new Map();
        this.debounceTimers = new Map();
    }
    
    /**
     * Enhances a form with advanced validation and UX features
     * @param {HTMLFormElement} form - Form to enhance
     */
    enhanceForm(form) {
        if (!form || this.forms.has(form)) return;
        
        this.forms.set(form, {
            hasUnsavedChanges: false,
            originalData: new FormData(form)
        });
        
        this.setupFormValidation(form);
        this.setupRealTimeValidation(form);
        this.setupUnsavedChangesWarning(form);
        this.setupFormSubmission(form);
        this.setupAccessibilityFeatures(form);
    }
    
    /**
     * Sets up form submission with validation and loading states
     * @param {HTMLFormElement} form - Form to set up
     */
    setupFormValidation(form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            
            const result = this.validator.validateForm(form);
            
            if (!result.isValid) {
                this.ui.showError(`Formulier bevat ${result.errorCount} fout(en). Controleer je invoer.`);
                
                // Focus first invalid field
                const firstInvalid = form.querySelector('.is-invalid');
                if (firstInvalid) {
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                
                return false;
            }
            
            this.submitForm(form);
        });
    }
    
    /**
     * Sets up real-time validation with debouncing
     * @param {HTMLFormElement} form - Form to enhance
     */
    setupRealTimeValidation(form) {
        const fields = form.querySelectorAll('input, textarea, select');
        
        fields.forEach(field => {
            // Real-time validation on input
            field.addEventListener('input', () => {
                this.markFormChanged(form);
                this.debounceValidation(field);
            });
            
            // Validation on blur for immediate feedback
            field.addEventListener('blur', () => {
                this.validateSingleField(field);
            });
            
            // Clear validation state on focus
            field.addEventListener('focus', () => {
                field.classList.remove('is-invalid', 'is-valid');
            });
        });
    }
    
    /**
     * Validates a single field with appropriate method
     * @param {HTMLElement} field - Field to validate
     */
    validateSingleField(field) {
        const value = field.value.trim();
        
        // Skip empty optional fields
        if (!value && !field.hasAttribute('required')) {
            field.classList.remove('is-invalid', 'is-valid');
            return;
        }
        
        // Create temporary validator for single field
        const tempValidator = new GamePlanValidator();
        
        // Validate based on field type
        switch (field.type) {
            case 'email':
                tempValidator.validateEmail(field);
                break;
            case 'password':
                tempValidator.validatePassword(field);
                break;
            case 'date':
                tempValidator.validateDate(field);
                break;
            case 'time':
                tempValidator.validateTime(field);
                break;
            case 'number':
                tempValidator.validateNumber(field);
                break;
            default:
                if (field.hasAttribute('required')) {
                    tempValidator.validateRequired(field);
                }
                tempValidator.validateText(field);
                tempValidator.validateSecurity(field);
        }
    }
    
    /**
     * Debounces validation to avoid excessive checking
     * @param {HTMLElement} field - Field to validate
     */
    debounceValidation(field) {
        const fieldId = field.id || field.name || 'anonymous';
        
        if (this.debounceTimers.has(fieldId)) {
            clearTimeout(this.debounceTimers.get(fieldId));
        }
        
        const timer = setTimeout(() => {
            this.validateSingleField(field);
            this.debounceTimers.delete(fieldId);
        }, GAMEPLAN_CONFIG.TIMING.DEBOUNCE_DELAY);
        
        this.debounceTimers.set(fieldId, timer);
    }
    
    /**
     * Sets up unsaved changes warning
     * @param {HTMLFormElement} form - Form to monitor
     */
    setupUnsavedChangesWarning(form) {
        window.addEventListener('beforeunload', (event) => {
            const formData = this.forms.get(form);
            if (formData && formData.hasUnsavedChanges) {
                event.preventDefault();
                event.returnValue = GAMEPLAN_CONFIG.MESSAGES.CONFIRMATIONS.UNSAVED_CHANGES;
                return event.returnValue;
            }
        });
    }
    
    /**
     * Marks form as having unsaved changes
     * @param {HTMLFormElement} form - Form with changes
     */
    markFormChanged(form) {
        const formData = this.forms.get(form);
        if (formData) {
            formData.hasUnsavedChanges = true;
        }
    }
    
    /**
     * Submits form with loading state and error handling
     * @param {HTMLFormElement} form - Form to submit
     */
    submitForm(form) {
        this.ui.showLoading();
        
        // Mark as saved to prevent unsaved changes warning
        const formData = this.forms.get(form);
        if (formData) {
            formData.hasUnsavedChanges = false;
        }
        
        // Submit form normally (PHP will handle processing)
        setTimeout(() => {
            this.ui.hideLoading();
            form.submit();
        }, 500); // Brief delay to show loading state
    }
    
    /**
     * Sets up accessibility features for better usability
     * @param {HTMLFormElement} form - Form to enhance
     */
    setupAccessibilityFeatures(form) {
        const fields = form.querySelectorAll('input, textarea, select');
        
        fields.forEach(field => {
            // Add ARIA labels if missing
            if (!field.getAttribute('aria-label') && !field.getAttribute('aria-labelledby')) {
                const label = form.querySelector(`label[for="${field.id}"]`);
                if (label) {
                    field.setAttribute('aria-labelledby', label.id || `label-${field.id}`);
                    if (!label.id) {
                        label.id = `label-${field.id}`;
                    }
                }
            }
            
            // Add error message containers
            this.setupErrorContainer(field);
        });
    }
    
    /**
     * Creates error message container for field
     * @param {HTMLElement} field - Field to add error container for
     */
    setupErrorContainer(field) {
        let errorContainer = document.getElementById(`${field.id}-error`);
        
        if (!errorContainer) {
            errorContainer = document.createElement('div');
            errorContainer.id = `${field.id}-error`;
            errorContainer.className = 'invalid-feedback';
            errorContainer.setAttribute('role', 'alert');
            
            field.parentNode.insertBefore(errorContainer, field.nextSibling);
            field.setAttribute('aria-describedby', errorContainer.id);
        }
    }
}

// ===================== ADVANCED DELETE CONFIRMATION SYSTEM =====================
/**
 * Professional delete confirmation with enhanced security and UX
 */
class GamePlanDeleteHandler {
    constructor(ui) {
        this.ui = ui;
    }
    
    /**
     * Sets up delete confirmation for all delete links
     */
    setupDeleteConfirmations() {
        document.addEventListener('click', (event) => {
            const deleteLink = event.target.closest('[data-delete-confirm]');
            
            if (deleteLink) {
                event.preventDefault();
                this.showDeleteConfirmation(deleteLink);
            }
        });
    }
    
    /**
     * Shows advanced delete confirmation dialog
     * @param {HTMLElement} deleteLink - Delete link element
     */
    showDeleteConfirmation(deleteLink) {
        const itemType = deleteLink.getAttribute('data-item-type') || 'item';
        const itemName = deleteLink.getAttribute('data-item-name') || '';
        const deleteUrl = deleteLink.href;
        
        let message = GAMEPLAN_CONFIG.MESSAGES.CONFIRMATIONS.DELETE_CONFIRM;
        
        if (itemName) {
            message = `Weet je zeker dat je "${itemName}" wilt verwijderen? Deze actie kan niet ongedaan worden gemaakt.`;
        }
        
        this.ui.showConfirmation(
            message,
            () => {
                this.executeDelete(deleteUrl, itemType);
            }
        );
    }
    
    /**
     * Executes the delete action with loading state
     * @param {string} deleteUrl - URL to delete item
     * @param {string} itemType - Type of item being deleted
     */
    executeDelete(deleteUrl, itemType) {
        this.ui.showLoading();
        
        // Navigate to delete URL (PHP will handle the deletion)
        setTimeout(() => {
            window.location.href = deleteUrl;
        }, 300);
    }
}

// ===================== GLOBAL APPLICATION INITIALIZATION =====================
/**
 * Main application class that coordinates all systems
 */
class GamePlanApp {
    constructor() {
        this.validator = new GamePlanValidator();
        this.ui = new GamePlanUI();
        this.reminders = new GamePlanReminders(this.ui);
        this.formEnhancer = new GamePlanFormEnhancer(this.validator, this.ui);
        this.deleteHandler = new GamePlanDeleteHandler(this.ui);
        
        this.initialized = false;
    }
    
    /**
     * Initializes the complete application
     */
    initialize() {
        if (this.initialized) return;
        
        try {
            // Set up all systems
            this.setupFormEnhancements();
            this.setupDeleteConfirmations();
            this.setupReminders();
            this.setupGlobalEventHandlers();
            this.setupPerformanceOptimizations();
            
            this.initialized = true;
            
            console.log('GamePlan Scheduler initialized successfully');
            
        } catch (error) {
            console.error('Failed to initialize GamePlan Scheduler:', error);
            this.ui.showError('Er is een fout opgetreden bij het laden van de applicatie.');
        }
    }
    
    /**
     * Sets up form enhancements for all forms on the page
     */
    setupFormEnhancements() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            this.formEnhancer.enhanceForm(form);
        });
    }
    
    /**
     * Sets up delete confirmations
     */
    setupDeleteConfirmations() {
        this.deleteHandler.setupDeleteConfirmations();
    }
    
    /**
     * Sets up reminder system
     */
    setupReminders() {
        this.reminders.initialize();
    }
    
    /**
     * Sets up global event handlers
     */
    setupGlobalEventHandlers() {
        // Handle session timeout
        this.setupSessionMonitoring();
        
        // Handle network errors
        this.setupNetworkErrorHandling();
        
        // Handle keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        // Handle responsive behavior
        this.setupResponsiveHandlers();
    }
    
    /**
     * Sets up session monitoring for security
     */
    setupSessionMonitoring() {
        setInterval(() => {
            // Check if user is still logged in by making a lightweight request
            fetch('check_session.php', { method: 'POST' })
                .then(response => {
                    if (!response.ok) {
                        this.ui.showWarning(GAMEPLAN_CONFIG.MESSAGES.ERRORS.SESSION_EXPIRED);
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 3000);
                    }
                })
                .catch(() => {
                    // Network error, ignore for now
                });
        }, GAMEPLAN_CONFIG.TIMING.SESSION_CHECK_INTERVAL);
    }
    
    /**
     * Sets up network error handling
     */
    setupNetworkErrorHandling() {
        window.addEventListener('online', () => {
            this.ui.showSuccess('Internetverbinding hersteld.');
        });
        
        window.addEventListener('offline', () => {
            this.ui.showWarning('Geen internetverbinding. Sommige functies werken mogelijk niet.');
        });
    }
    
    /**
     * Sets up keyboard shortcuts for power users
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (event) => {
            // Ctrl/Cmd + S to save forms
            if ((event.ctrlKey || event.metaKey) && event.key === 's') {
                const activeForm = document.activeElement.closest('form');
                if (activeForm) {
                    event.preventDefault();
                    activeForm.requestSubmit();
                }
            }
            
            // Escape to close modals
            if (event.key === 'Escape') {
                const activeModal = document.querySelector('.modal.show');
                if (activeModal) {
                    const modal = bootstrap.Modal.getInstance(activeModal);
                    if (modal) modal.hide();
                }
            }
        });
    }
    
    /**
     * Sets up responsive behavior handlers
     */
    setupResponsiveHandlers() {
        // Handle orientation changes on mobile
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Recalculate layouts if needed
                window.dispatchEvent(new Event('resize'));
            }, 100);
        });
        
        // Handle window resize for dynamic layouts
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                // Trigger layout recalculations
                this.handleResponsiveChanges();
            }, 150);
        });
    }
    
    /**
     * Handles responsive layout changes
     */
    handleResponsiveChanges() {
        // Adjust tables for mobile if needed
        const tables = document.querySelectorAll('table');
        tables.forEach(table => {
            if (window.innerWidth < 768) {
                table.classList.add('table-responsive');
            } else {
                table.classList.remove('table-responsive');
            }
        });
    }
    
    /**
     * Sets up performance optimizations
     */
    setupPerformanceOptimizations() {
        // Lazy load images
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                            imageObserver.unobserve(img);
                        }
                    }
                });
            });
            
            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
        
        // Optimize scroll performance
        let ticking = false;
        document.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    // Handle scroll-based optimizations
                    ticking = false;
                });
                ticking = true;
            }
        });
    }
}

// ===================== APPLICATION STARTUP =====================
/**
 * Global application instance
 */
let gamePlanApp;

/**
 * Initialize application when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create and initialize the main application
    gamePlanApp = new GamePlanApp();
    gamePlanApp.initialize();
    
    // Backward compatibility: legacy form validation function
    window.validateForm = function(form) {
        if (!gamePlanApp || !gamePlanApp.validator) {
            console.warn('GamePlan app not initialized, falling back to basic validation');
            return basicFormValidation(form);
        }
        
        const result = gamePlanApp.validator.validateForm(form);
        
        if (!result.isValid && result.errors.length > 0) {
            alert(result.errors.join('\n'));
            return false;
        }
        
        return true;
    };
    
    // Legacy reminder checking function
    window.checkReminders = function() {
        if (gamePlanApp && gamePlanApp.reminders) {
            gamePlanApp.reminders.checkReminders();
        }
    };
});

/**
 * Basic fallback validation for legacy support
 * @param {HTMLFormElement} form - Form to validate
 * @returns {boolean} Validation result
 */
function basicFormValidation(form) {
    let valid = true;
    const requiredInputs = form.querySelectorAll('[required]');
    
    requiredInputs.forEach(input => {
        const value = input.value.trim();
        
        if (!value) {
            alert(GAMEPLAN_CONFIG.MESSAGES.ERRORS.REQUIRED_FIELD);
            valid = false;
            return;
        }
        
        if (/^\s*$/.test(value)) {
            alert(GAMEPLAN_CONFIG.MESSAGES.ERRORS.TITLE_SPACES_ONLY);
            valid = false;
            return;
        }
        
        // Additional basic checks
        if (input.type === 'date' && value < new Date().toISOString().split('T')[0]) {
            alert(GAMEPLAN_CONFIG.MESSAGES.ERRORS.FUTURE_DATE_REQUIRED);
            valid = false;
            return;
        }
        
        if (input.type === 'time' && value.includes('-')) {
            alert(GAMEPLAN_CONFIG.MESSAGES.ERRORS.POSITIVE_TIME_REQUIRED);
            valid = false;
            return;
        }
    });
    
    return valid;
}

// ===================== EXPORT FOR MODULE SYSTEMS =====================
// Support for module systems if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        GamePlanValidator,
        GamePlanUI,
        GamePlanReminders,
        GamePlanFormEnhancer,
        GamePlanDeleteHandler,
        GamePlanApp,
        GAMEPLAN_CONFIG
    };
}

// Support for AMD/RequireJS
if (typeof define === 'function' && define.amd) {
    define(function() {
        return {
            GamePlanValidator,
            GamePlanUI,
            GamePlanReminders,
            GamePlanFormEnhancer,
            GamePlanDeleteHandler,
            GamePlanApp,
            GAMEPLAN_CONFIG
        };
    });
}

/**
 * GamePlan Scheduler JavaScript Implementation Complete
 * 
 * Features implemented:
 * - Advanced form validation with real-time feedback
 * - Professional UI feedback system with toast notifications
 * - Comprehensive reminder system with browser notifications
 * - Enhanced delete confirmations with security
 * - Session monitoring and network error handling
 * - Accessibility features and keyboard shortcuts
 * - Performance optimizations and responsive handling
 * - Backward compatibility with legacy code
 * 
 * Security measures:
 * - SQL injection pattern detection
 * - XSS prevention with HTML escaping
 * - Input sanitization and validation
 * - Session timeout monitoring
 * 
 * This implementation addresses all test report issues:
 * - #1001: Enhanced validation for whitespace-only input
 * - #1004: Comprehensive edge case handling
 * - Professional error messaging throughout
 * 
 * Browser compatibility: Modern browsers with ES6+ support
 * Dependencies: Bootstrap 5 for UI components
 * File size: Optimized for production use
 */