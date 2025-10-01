// GamePlan Scheduler - Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    initializeGamePlanApp();
});

function initializeGamePlanApp() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    const popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-dismiss alerts after 5 seconds
    autoDismissAlerts();

    // Initialize date pickers
    initializeDatePickers();

    // Initialize time inputs
    initializeTimeInputs();

    // Initialize form validations
    initializeFormValidations();

    // Initialize real-time updates
    initializeRealTimeUpdates();

    // Initialize notification system
    initializeNotificationSystem();
}

// Auto-dismiss alerts
function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

// Date picker initialization
function initializeDatePickers() {
    const dateInputs = document.querySelectorAll('.date-picker');
    dateInputs.forEach(input => {
        // Set min date to today
        input.min = new Date().toISOString().split('T')[0];
        
        // Add date validation
        input.addEventListener('change', function() {
            validateDate(this);
        });
    });
}

// Time input initialization
function initializeTimeInputs() {
    const timeInputs = document.querySelectorAll('.time-picker');
    timeInputs.forEach(input => {
        input.addEventListener('change', function() {
            validateTime(this);
        });
    });
}

// Date validation
function validateDate(dateInput) {
    const selectedDate = new Date(dateInput.value);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        showValidationError(dateInput, 'Please select a future date');
        return false;
    }

    clearValidationError(dateInput);
    return true;
}

// Time validation
function validateTime(timeInput) {
    const timeRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
    if (!timeRegex.test(timeInput.value)) {
        showValidationError(timeInput, 'Please enter a valid time (HH:MM)');
        return false;
    }

    clearValidationError(timeInput);
    return true;
}

// Show validation error
function showValidationError(input, message) {
    clearValidationError(input);
    
    input.classList.add('is-invalid');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    input.parentNode.appendChild(errorDiv);
}

// Clear validation error
function clearValidationError(input) {
    input.classList.remove('is-invalid');
    
    const existingError = input.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

// Form validation initialization
function initializeFormValidations() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
}

// Real-time updates
function initializeRealTimeUpdates() {
    // Update time remaining for upcoming events
    updateTimeRemaining();
    setInterval(updateTimeRemaining, 60000); // Update every minute
    
    // Check for new notifications
    checkNewNotifications();
    setInterval(checkNewNotifications, 30000); // Check every 30 seconds
}

// Update time remaining for events and schedules
function updateTimeRemaining() {
    const timeElements = document.querySelectorAll('.time-remaining');
    
    timeElements.forEach(element => {
        const datetime = element.getAttribute('data-datetime');
        if (datetime) {
            const remaining = calculateTimeRemaining(datetime);
            element.textContent = remaining;
            
            // Update styling based on time remaining
            if (remaining.includes('Past')) {
                element.classList.add('text-muted');
                element.classList.remove('text-warning', 'text-success');
            } else if (remaining.includes('minute')) {
                element.classList.add('text-success');
                element.classList.remove('text-warning', 'text-muted');
            } else {
                element.classList.add('text-warning');
                element.classList.remove('text-success', 'text-muted');
            }
        }
    });
}

// Calculate time remaining
function calculateTimeRemaining(datetime) {
    const now = new Date();
    const target = new Date(datetime);
    const diff = target - now;
    
    if (diff < 0) {
        return 'Past';
    }
    
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    
    if (days > 0) {
        return `${days} day${days > 1 ? 's' : ''}`;
    } else if (hours > 0) {
        return `${hours} hour${hours > 1 ? 's' : ''}`;
    } else {
        return `${minutes} minute${minutes > 1 ? 's' : ''}`;
    }
}

// Notification system
function initializeNotificationSystem() {
    // Check for browser notification support
    if ('Notification' in window) {
        if (Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    // Initialize notification bell
    const notificationBell = document.getElementById('notificationBell');
    if (notificationBell) {
        notificationBell.addEventListener('click', function() {
            markNotificationsAsRead();
        });
    }
}

// Check for new notifications
function checkNewNotifications() {
    // In a real application, this would make an API call
    // For now, we'll just update the UI periodically
    const notificationCount = document.querySelector('.notification-count');
    if (notificationCount) {
        // Simulate new notification (remove in production)
        // const currentCount = parseInt(notificationCount.textContent);
        // if (Math.random() > 0.8) { // 20% chance of new notification
        //     notificationCount.textContent = currentCount + 1;
        //     showBrowserNotification('New Notification', 'You have new activity in GamePlan!');
        // }
    }
}

// Show browser notification
function showBrowserNotification(title, message) {
    if ('Notification' in window && Notification.permission === 'granted') {
        new Notification(title, {
            body: message,
            icon: '/assets/images/logo.png'
        });
    }
}

// Mark notifications as read
function markNotificationsAsRead() {
    // This would typically make an API call to mark notifications as read
    const notificationCount = document.querySelector('.notification-count');
    if (notificationCount) {
        notificationCount.style.display = 'none';
    }
}

// Game search functionality
function initializeGameSearch() {
    const gameSearchInput = document.getElementById('gameSearch');
    if (gameSearchInput) {
        let searchTimeout;
        
        gameSearchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchGames(this.value);
            }, 300);
        });
    }
}

// Search games (simulated)
function searchGames(query) {
    if (query.length < 2) {
        hideSearchResults();
        return;
    }
    
    // In a real application, this would be an API call
    console.log('Searching for games:', query);
    
    // Simulate API response
    const mockResults = [
        { id: 1, title: 'Cyberpunk 2077', genre: 'RPG' },
        { id: 2, title: 'Cyberpunk 2077: Phantom Liberty', genre: 'RPG' },
        { id: 3, title: 'Cyberpunk 2077 - Complete Edition', genre: 'RPG' }
    ];
    
    showSearchResults(mockResults);
}

// Show search results
function showSearchResults(results) {
    hideSearchResults();
    
    const searchResults = document.createElement('div');
    searchResults.className = 'search-results dropdown-menu show';
    searchResults.style.position = 'absolute';
    searchResults.style.width = '100%';
    
    results.forEach(result => {
        const resultItem = document.createElement('a');
        resultItem.className = 'dropdown-item';
        resultItem.href = '#';
        resultItem.innerHTML = `
            <strong>${result.title}</strong>
            <small class="text-muted d-block">${result.genre}</small>
        `;
        resultItem.addEventListener('click', function(e) {
            e.preventDefault();
            selectGame(result);
        });
        
        searchResults.appendChild(resultItem);
    });
    
    const searchInput = document.getElementById('gameSearch');
    searchInput.parentNode.appendChild(searchResults);
}

// Hide search results
function hideSearchResults() {
    const existingResults = document.querySelector('.search-results');
    if (existingResults) {
        existingResults.remove();
    }
}

// Select game from search results
function selectGame(game) {
    const searchInput = document.getElementById('gameSearch');
    searchInput.value = game.title;
    hideSearchResults();
    
    // You might want to set a hidden input with the game ID
    const gameIdInput = document.getElementById('gameId');
    if (gameIdInput) {
        gameIdInput.value = game.id;
    }
}

// Schedule duration calculation
function calculateScheduleDuration() {
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const durationDisplay = document.getElementById('duration_display');
    
    if (startTimeInput && endTimeInput && durationDisplay) {
        startTimeInput.addEventListener('change', updateDuration);
        endTimeInput.addEventListener('change', updateDuration);
    }
}

// Update duration display
function updateDuration() {
    const startTime = document.getElementById('start_time').value;
    const endTime = document.getElementById('end_time').value;
    const durationDisplay = document.getElementById('duration_display');
    
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        const duration = (end - start) / (1000 * 60 * 60); // Convert to hours
        
        if (duration > 0) {
            durationDisplay.textContent = `${duration.toFixed(1)} hours`;
            durationDisplay.className = 'text-success';
        } else {
            durationDisplay.textContent = 'Invalid time range';
            durationDisplay.className = 'text-danger';
        }
    }
}

// Export data functionality
function exportUserData() {
    if (confirm('This will export all your GamePlan data. Continue?')) {
        // In a real application, this would generate and download a file
        showToast('Export started...', 'info');
        
        setTimeout(() => {
            showToast('Data exported successfully!', 'success');
        }, 2000);
    }
}

// Toast notifications
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Create toast container
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    
    document.body.appendChild(container);
    return container;
}

// Responsive table handling
function initializeResponsiveTables() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        if (table.parentElement.classList.contains('table-responsive')) {
            return; // Already responsive
        }
        
        if (table.offsetWidth > table.parentElement.offsetWidth) {
            table.parentElement.classList.add('table-responsive');
        }
    });
}

// Initialize when page loads
window.addEventListener('load', function() {
    initializeGameSearch();
    calculateScheduleDuration();
    initializeResponsiveTables();
});

// Utility function for confirmation dialogs
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to continue?');
}

// Utility function for loading states
function setLoadingState(element, isLoading) {
    if (isLoading) {
        element.disabled = true;
        element.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Loading...';
    } else {
        element.disabled = false;
        element.innerHTML = element.getAttribute('data-original-text') || element.textContent;
    }
}

// Add loading state to buttons
document.addEventListener('submit', function(e) {
    const submitButton = e.target.querySelector('button[type="submit"]');
    if (submitButton) {
        submitButton.setAttribute('data-original-text', submitButton.innerHTML);
        setLoadingState(submitButton, true);
    }
});

// Error handling for AJAX requests (example)
function handleApiError(error) {
    console.error('API Error:', error);
    showToast('An error occurred. Please try again.', 'danger');
}

// Format date for display
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString(undefined, options);
}

// Format time for display
function formatTime(timeString) {
    return new Date(`2000-01-01T${timeString}`).toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
}