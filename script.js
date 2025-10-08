// script.js - Advanced Client-Side Functionality
// Author: Harsha Kanaparthi
// Date: 30-09-2025

class GamePlanScheduler {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupFormValidations();
        this.setupRealTimeUpdates();
        this.checkReminders();
        this.setupAdvancedUI();
    }

    setupEventListeners() {
        // Auto-save functionality
        document.querySelectorAll('.auto-save').forEach(element => {
            element.addEventListener('blur', this.debounce(this.autoSaveField, 1000));
        });

        // Real-time search
        document.querySelectorAll('.real-time-search').forEach(input => {
            input.addEventListener('input', this.debounce(this.performSearch, 300));
        });

        // Advanced confirm dialogs
        document.querySelectorAll('.advanced-confirm').forEach(button => {
            button.addEventListener('click', this.showAdvancedConfirm.bind(this));
        });

        // Dynamic form field addition
        document.querySelectorAll('.add-field-btn').forEach(button => {
            button.addEventListener('click', this.addDynamicField.bind(this));
        });
    }

    setupFormValidations() {
        // Advanced form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', this.validateForm.bind(this));
        });

        // Real-time validation
        document.querySelectorAll('.real-time-validate').forEach(input => {
            input.addEventListener('input', this.realTimeValidation.bind(this));
        });
    }

    setupRealTimeUpdates() {
        // Update online status every minute
        setInterval(this.updateOnlineStatus.bind(this), 60000);
        
        // Check for new notifications
        setInterval(this.checkNotifications.bind(this), 30000);
    }

    setupAdvancedUI() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));

        // Initialize popovers
        const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl));
    }

    // === ADVANCED FORM VALIDATION ===
    validateForm(event) {
        const form = event.target;
        let isValid = true;
        const errors = [];

        // Clear previous errors
        this.clearFormErrors(form);

        // Validate required fields
        form.querySelectorAll('[required]').forEach(field => {
            if (!field.value.trim()) {
                this.showFieldError(field, `${this.getFieldLabel(field)} is required`);
                isValid = false;
                errors.push(`${this.getFieldLabel(field)} is required`);
            }
        });

        // Validate email fields
        form.querySelectorAll('input[type="email"]').forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showFieldError(field, 'Please enter a valid email address');
                isValid = false;
                errors.push('Invalid email format');
            }
        });

        // Validate date fields
        form.querySelectorAll('input[type="date"]').forEach(field => {
            if (field.value && !this.isValidDate(field.value)) {
                this.showFieldError(field, 'Please enter a valid date');
                isValid = false;
                errors.push('Invalid date');
            }
        });

        // Validate time fields
        form.querySelectorAll('input[type="time"]').forEach(field => {
            if (field.value && !this.isValidTime(field.value)) {
                this.showFieldError(field, 'Please enter a valid time');
                isValid = false;
                errors.push('Invalid time');
            }
        });

        // Validate URL fields
        form.querySelectorAll('input[type="url"]').forEach(field => {
            if (field.value && !this.isValidURL(field.value)) {
                this.showFieldError(field, 'Please enter a valid URL');
                isValid = false;
                errors.push('Invalid URL');
            }
        });

        if (!isValid) {
            event.preventDefault();
            this.showFormErrors(errors);
        }

        return isValid;
    }

    realTimeValidation(event) {
        const field = event.target;
        this.clearFieldError(field);

        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showFieldError(field, `${this.getFieldLabel(field)} is required`);
            return;
        }

        switch (field.type) {
            case 'email':
                if (field.value && !this.isValidEmail(field.value)) {
                    this.showFieldError(field, 'Please enter a valid email address');
                }
                break;
            case 'date':
                if (field.value && !this.isValidDate(field.value)) {
                    this.showFieldError(field, 'Please enter a valid date');
                }
                break;
            case 'time':
                if (field.value && !this.isValidTime(field.value)) {
                    this.showFieldError(field, 'Please enter a valid time');
                }
                break;
            case 'url':
                if (field.value && !this.isValidURL(field.value)) {
                    this.showFieldError(field, 'Please enter a valid URL');
                }
                break;
        }
    }

    // === VALIDATION HELPERS ===
    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    isValidDate(date) {
        return !isNaN(Date.parse(date));
    }

    isValidTime(time) {
        const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
        return timeRegex.test(time);
    }

    isValidURL(url) {
        try {
            new URL(url);
            return true;
        } catch {
            return false;
        }
    }

    getFieldLabel(field) {
        const label = field.labels?.[0]?.textContent || field.placeholder || field.name;
        return label.replace(':', '').trim();
    }

    // === ERROR HANDLING ===
    showFieldError(field, message) {
        field.classList.add('is-invalid');
        
        let errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            field.parentNode.appendChild(errorDiv);
        }
        errorDiv.textContent = message;
    }

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    clearFormErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(field => {
            field.classList.remove('is-invalid');
        });
        form.querySelectorAll('.invalid-feedback').forEach(div => {
            div.remove();
        });
    }

    showFormErrors(errors) {
        const errorHtml = errors.map(error => 
            `<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>${error}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>`
        ).join('');
        
        const form = document.querySelector('form');
        form.insertAdjacentHTML('afterbegin', errorHtml);
    }

    // === ADVANCED UI FEATURES ===
    showAdvancedConfirm(event) {
        event.preventDefault();
        
        const button = event.target;
        const message = button.getAttribute('data-confirm-message') || 'Are you sure you want to proceed?';
        const action = button.getAttribute('data-confirm-action') || 'proceed';
        
        const modalHtml = `
            <div class="modal fade" id="confirmModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content advanced-card">
                        <div class="modal-header border-0">
                            <h5 class="modal-title text-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>Confirm Action
                            </h5>
                        </div>
                        <div class="modal-body">
                            <p class="mb-0">${message}</p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-danger" id="confirmAction">${action}</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        const modal = new bootstrap.Modal(document.getElementById('confirmModal'));
        
        document.getElementById('confirmAction').addEventListener('click', () => {
            modal.hide();
            if (button.form) {
                button.form.submit();
            } else if (button.href) {
                window.location.href = button.href;
            }
        });
        
        modal.show();
        
        // Clean up modal after hide
        document.getElementById('confirmModal').addEventListener('hidden.bs.modal', function () {
            this.remove();
        });
    }

    addDynamicField(event) {
        event.preventDefault();
        const button = event.target;
        const container = document.querySelector(button.getAttribute('data-target'));
        const template = container.querySelector('.field-template');
        
        if (template) {
            const newField = template.cloneNode(true);
            newField.classList.remove('field-template');
            newField.classList.remove('d-none');
            
            // Update field names to avoid duplicates
            newField.querySelectorAll('[name]').forEach(field => {
                const baseName = field.getAttribute('name').replace(/\[\d+\]/, '');
                const index = container.querySelectorAll('.dynamic-field:not(.field-template)').length;
                field.setAttribute('name', `${baseName}[${index}]`);
            });
            
            // Add remove button
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'btn btn-sm btn-danger mt-2';
            removeBtn.innerHTML = '<i class="fas fa-times me-1"></i>Remove';
            removeBtn.addEventListener('click', () => newField.remove());
            
            newField.appendChild(removeBtn);
            container.appendChild(newField);
        }
    }

    // === REAL-TIME FUNCTIONALITY ===
    updateOnlineStatus() {
        // Simulate online status update
        document.querySelectorAll('.status-indicator').forEach(indicator => {
            if (Math.random() > 0.3) { // 70% chance of being online
                indicator.classList.add('status-online');
                indicator.classList.remove('status-offline');
                indicator.textContent = 'Online';
            } else {
                indicator.classList.add('status-offline');
                indicator.classList.remove('status-online');
                indicator.textContent = 'Offline';
            }
        });
    }

    checkNotifications() {
        // Simulate notification check
        if (Math.random() > 0.8) { // 20% chance of new notification
            this.showNotification('New friend request received', 'info');
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 100px; right: 20px; z-index: 1060; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-bell me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // === REMINDER SYSTEM ===
    checkReminders() {
        const reminders = JSON.parse(document.getElementById('remindersData')?.textContent || '[]');
        reminders.forEach(reminder => {
            const now = new Date();
            const reminderTime = new Date(reminder.date + ' ' + reminder.time);
            
            if (reminder.reminder !== 'none' && this.shouldShowReminder(reminderTime, reminder.reminder)) {
                this.showReminder(reminder);
            }
        });
    }

    shouldShowReminder(eventTime, reminderType) {
        const now = new Date();
        const timeDiff = eventTime - now;
        const reminderIntervals = {
            '15_minutes': 15 * 60 * 1000,
            '1_hour': 60 * 60 * 1000,
            '1_day': 24 * 60 * 60 * 1000,
            '1_week': 7 * 24 * 60 * 60 * 1000
        };
        
        const interval = reminderIntervals[reminderType] || 0;
        return timeDiff > 0 && timeDiff <= interval && timeDiff > interval - 60000; // Show within last minute of reminder
    }

    showReminder(reminder) {
        const reminderHtml = `
            <div class="modal fade" id="reminderModal" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content advanced-card">
                        <div class="modal-header border-0 bg-warning text-dark">
                            <h5 class="modal-title">
                                <i class="fas fa-bell me-2"></i>Upcoming Event Reminder
                            </h5>
                        </div>
                        <div class="modal-body">
                            <h6>${reminder.title}</h6>
                            <p class="mb-1"><strong>Time:</strong> ${reminder.date} at ${reminder.time}</p>
                            ${reminder.description ? `<p class="mb-0"><strong>Description:</strong> ${reminder.description}</p>` : ''}
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="snoozeReminder('${reminder.id}')">
                                Snooze (10 min)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', reminderHtml);
        const modal = new bootstrap.Modal(document.getElementById('reminderModal'));
        modal.show();
    }

    // === UTILITY FUNCTIONS ===
    debounce(func, wait) {
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

    autoSaveField(event) {
        const field = event.target;
        const form = field.closest('form');
        
        if (form) {
            // Simulate auto-save
            this.showNotification('Changes saved automatically', 'success');
        }
    }

    performSearch(event) {
        const searchTerm = event.target.value.toLowerCase();
        const table = event.target.closest('.table-container')?.querySelector('tbody');
        
        if (table) {
            table.querySelectorAll('tr').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
    }
}

// Global functions for HTML onclick attributes
function snoozeReminder(reminderId) {
    console.log(`Snoozing reminder ${reminderId} for 10 minutes`);
    // Implement snooze functionality
}

function quickAddFriend(username) {
    const form = document.getElementById('quickAddFriendForm');
    if (form) {
        form.querySelector('input[name="friend_username"]').value = username;
        form.submit();
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.gamePlanApp = new GamePlanScheduler();
    
    // Add fade-in animation to all cards
    document.querySelectorAll('.advanced-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    console.log('GamePlan Scheduler Advanced v2.0 initialized');
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = GamePlanScheduler;
}