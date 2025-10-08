// script.js - Client-Side JavaScript
// Author: Harsha Kanaparthi
// Date: 30-09-2025
// Description: Form validations and reminder pop-ups.
// Login form validation
function validateLoginForm() {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    
    if (!email || !password) {
        alert('Email and password are required.');
        return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Invalid email format.');
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
        alert('All fields are required.');
        return false;
    }
    if (username.length > 50) {
        alert('Username too long (max 50 characters).');
        return false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        alert('Invalid email format.');
        return false;
    }
    if (password.length < 8) {
        alert('Password must be at least 8 characters.');
        return false;
    }
    return true;
}
// Schedule form validation
function validateScheduleForm() {
    const gameTitle = document.getElementById('game_title').value.trim();
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const friendsStr = document.getElementById('friends_str').value.trim();
    const sharedWithStr = document.getElementById('shared_with_str').value.trim();
    
    if (!gameTitle || /^\s*$/.test(gameTitle)) {
        alert('Game title is required and cannot be only spaces.');
        return false;
    }
    if (!date || new Date(date) < new Date()) {
        alert('Date must be in the future.');
        return false;
    }
    if (!/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time)) {
        alert('Invalid time format.');
        return false;
    }
    if (friendsStr && !/^[a-zA-Z0-9,\s]*$/.test(friendsStr)) {
        alert('Invalid friends format.');
        return false;
    }
    if (sharedWithStr && !/^[a-zA-Z0-9,\s]*$/.test(sharedWithStr)) {
        alert('Invalid shared with format.');
        return false;
    }
    return true;
}
// Event form validation
function validateEventForm() {
    const title = document.getElementById('title').value.trim();
    const date = document.getElementById('date').value;
    const time = document.getElementById('time').value;
    const description = document.getElementById('description').value;
    const externalLink = document.getElementById('external_link').value;
    const sharedWithStr = document.getElementById('shared_with_str').value.trim();
    
    if (!title || /^\s*$/.test(title)) {
        alert('Title is required and cannot be only spaces.');
        return false;
    }
    if (title.length > 100) {
        alert('Title too long (max 100 characters).');
        return false;
    }
    if (!date || new Date(date) < new Date()) {
        alert('Date must be in the future.');
        return false;
    }
    if (!/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/.test(time)) {
        alert('Invalid time format.');
        return false;
    }
    if (description.length > 500) {
        alert('Description too long (max 500 characters).');
        return false;
    }
    if (externalLink && !/^(https?:\/\/)?[\w\-]+(\.[\w\-]+)+[\/#?]?.*$/.test(externalLink)) {
        alert('Invalid external link format.');
        return false;
    }
    if (sharedWithStr && !/^[a-zA-Z0-9,\s]*$/.test(sharedWithStr)) {
        alert('Invalid shared with format.');
        return false;
    }
    return true;
}
// Reminder pop-ups (simulated)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Checking for reminders...');
});