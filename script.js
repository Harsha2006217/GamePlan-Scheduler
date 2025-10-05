// Global script for GamePlan Scheduler
// Handles common JS functionalities across pages

// Smooth scrolling for anchor links
document.addEventListener('DOMContentLoaded', function() {
    const links = document.querySelectorAll('a[href^="#"]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});

// Generic form validation helper
function validateForm(formId, validators) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    let isValid = true;
    
    Object.entries(validators).forEach(([fieldId, rules]) => {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        const value = field.value.trim();
        
        if (rules.required && !value) {
            alert(`${rules.label} is required.`);
            isValid = false;
            return;
        }
        
        if (rules.minLength && value.length < rules.minLength) {
            alert(`${rules.label} must be at least ${rules.minLength} characters.`);
            isValid = false;
            return;
        }
        
        if (rules.maxLength && value.length > rules.maxLength) {
            alert(`${rules.label} cannot exceed ${rules.maxLength} characters.`);
            isValid = false;
            return;
        }
        
        if (rules.pattern && !rules.pattern.test(value)) {
            alert(rules.patternMessage || `Invalid format for ${rules.label}.`);
            isValid = false;
            return;
        }
    });
    
    return isValid;
}

// Auto-refresh online status (for pages with friends list)
function refreshOnlineStatus() {
    if (document.querySelector('.online-status')) {
        setInterval(() => {
            window.location.reload();
        }, 30000);
    }
}
refreshOnlineStatus();

// Character counter helper
function addCharacterCounter(inputId, countId, max) {
    const input = document.getElementById(inputId);
    const count = document.getElementById(countId);
    if (!input || !count) return;
    
    function update() {
        const length = input.value.length;
        count.textContent = `${length}/${max} characters`;
        
        count.className = 'character-count';
        if (length > max * 0.8) count.classList.add('warning');
        if (length > max) count.classList.add('danger');
    }
    
    input.addEventListener('input', update);
    update();
}

// Initialize counters on pages that have them
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('title')) {
        addCharacterCounter('title', 'titleCount', 100);
    }
    if (document.getElementById('description')) {
        addCharacterCounter('description', 'descriptionCount', 500);
    }
});

// Date and time validation helper
function setupDateTimeValidation(dateId, timeId) {
    const dateInput = document.getElementById(dateId);
    const timeInput = document.getElementById(timeId);
    if (!dateInput || !timeInput) return;
    
    dateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        
        if (selectedDate.toDateString() === today.toDateString()) {
            const now = new Date();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            timeInput.min = `${hours}:${minutes}`;
        } else {
            timeInput.removeAttribute('min');
        }
    });
}

// Initialize date-time on relevant pages
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('date') && document.getElementById('time')) {
        setupDateTimeValidation('date', 'time');
    }
});

// Friend selection helpers
function selectAllFriends() {
    document.querySelectorAll('input[name="friends[]"], input[name="shared_friends[]"]').forEach(checkbox => checkbox.checked = true);
}

function deselectAllFriends() {
    document.querySelectorAll('input[name="friends[]"], input[name="shared_friends[]"]').forEach(checkbox => checkbox.checked = false);
}

function selectOnlineFriends() {
    document.querySelectorAll('input[name="friends[]"], input[name="shared_friends[]"]').forEach(checkbox => {
        const label = checkbox.closest('.form-check-label');
        if (label && label.textContent.includes('Online')) {
            checkbox.checked = true;
        }
    });
}