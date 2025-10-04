// script.js: Advanced JavaScript for validation, reminders, and interactions
// Human-written: Clear functions, event listeners, no libraries beyond Bootstrap

// Form validation for login
function validateLoginForm() {
    const username = document.getElementById('username').value.trim();
    const password = document.getElementById('password').value;
    if (!username) {
        alert('Please enter your username.');
        return false;
    }
    if (!password) {
        alert('Please enter your password.');
        return false;
    }
    return true;
}

// Form validation for register
function validateRegisterForm() {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    if (!username || username.length > 50 || !/^[a-zA-Z0-9_-]+$/.test(username)) {
        alert('Username: 1-50 alphanumeric characters, hyphens, or underscores only.');
        return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    if (password.length < 8) {
        alert('Password must be at least 8 characters long.');
        return false;
    }
    return true;
}

// Show reminders as alerts on page load
document.addEventListener('DOMContentLoaded', function() {
    const reminders = JSON.parse(localStorage.getItem('reminders') || '[]');
    if (reminders.length > 0) {
        reminders.forEach(msg => alert('Reminder: ' + msg));
        localStorage.removeItem('reminders');  // Clear after showing
    }
});

// Character count for textareas/inputs (if needed in forms)
const textAreas = document.querySelectorAll('textarea[maxlength]');
textAreas.forEach(ta => {
    const max = ta.getAttribute('maxlength');
    const count = document.createElement('div');
    count.className = 'character-count';
    ta.parentNode.appendChild(count);
    ta.addEventListener('input', () => {
        const len = ta.value.length;
        count.textContent = `${len}/${max} characters`;
        count.classList.toggle('warning', len > max * 0.8);
        count.classList.toggle('danger', len > max);
    });
});

// Select all/deselect all for checkboxes (e.g., friends)
function selectAllFriends() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = true);
}

function deselectAllFriends() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);
}

// Online friends selection (based on status in label)
function selectOnlineFriends() {
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        const label = cb.nextElementSibling;
        cb.checked = label && label.textContent.includes('online');
    });
}