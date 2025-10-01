/**
 * GamePlan Scheduler - Verbeterde JavaScript Implementatie
 * Validatie en Interactie Systeem
 */

// Constanten voor validatie
const VALIDATION_RULES = {
    TITLE_MAX_LENGTH: 100,
    USERNAME_MAX_LENGTH: 50,
    PASSWORD_MIN_LENGTH: 6
};

// Error berichten in het Nederlands
const ERROR_MESSAGES = {
    required: 'Vul alle verplichte velden in.',
    titleSpaces: 'Titel mag niet alleen spaties zijn.',
    titleLength: 'Titel max 100 tekens.',
    futureDate: 'Datum moet in de toekomst zijn.',
    positiveTime: 'Tijd moet positief zijn.',
    usernameLength: 'Username max 50 tekens.',
    passwordLength: 'Wachtwoord min 6 tekens.',
    invalidEmail: 'Ongeldige email.'
};

/**
 * Valideert een formulier met alle vereiste velden en regels
 * @param {HTMLFormElement} form - Het te valideren formulier
 * @returns {boolean} - True als validatie succesvol is
 */
function validateForm(form) {
    let valid = true;
    let requiredInputs = form.querySelectorAll('[required]');

    requiredInputs.forEach(input => {
        let value = input.value.trim();
        
        // Validatie functies voor verschillende types
        const validators = {
            // Controleert basis required validatie
            required: () => {
                if (!value) {
                    showError(ERROR_MESSAGES.required);
                    return false;
                }
                return true;
            },
            // Valideert titel velden
            title: () => {
                if ((/^\s*$/.test(value))) {
                    showError(ERROR_MESSAGES.titleSpaces);
                    return false;
                }
                if (value.length > VALIDATION_RULES.TITLE_MAX_LENGTH) {
                    showError(ERROR_MESSAGES.titleLength);
                    return false;
                }
                return true;
            },
            // Valideert datum velden
            date: () => {
                if (value < new Date().toISOString().split('T')[0]) {
                    showError(ERROR_MESSAGES.futureDate);
                    return false;
                }
                return true;
            },
            // Valideert tijd velden
            time: () => {
                if (value.includes('-')) {
                    showError(ERROR_MESSAGES.positiveTime);
                    return false;
                }
                return true;
            },
            // Valideert username velden
            username: () => {
                if (value.length > VALIDATION_RULES.USERNAME_MAX_LENGTH) {
                    showError(ERROR_MESSAGES.usernameLength);
                    return false;
                }
                return true;
            },
            // Valideert wachtwoord velden
            password: () => {
                if (value.length < VALIDATION_RULES.PASSWORD_MIN_LENGTH) {
                    showError(ERROR_MESSAGES.passwordLength);
                    return false;
                }
                return true;
            },
            // Valideert email velden
            email: () => {
                if (!value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    showError(ERROR_MESSAGES.invalidEmail);
                    return false;
                }
                return true;
            }
        };

        // Voer basis required validatie uit
        if (!validators.required()) {
            valid = false;
            return;
        }

        // Voer specifieke validaties uit op basis van input type/naam
        if (input.name === 'title' && !validators.title()) valid = false;
        if (input.type === 'date' && !validators.date()) valid = false;
        if (input.type === 'time' && !validators.time()) valid = false;
        if (input.name === 'username' && !validators.username()) valid = false;
        if (input.type === 'password' && !validators.password()) valid = false;
        if (input.type === 'email' && !validators.email()) valid = false;
    });

    return valid;
}

/**
 * Toont een error bericht aan de gebruiker
 * @param {string} message - Het te tonen foutbericht
 */
function showError(message) {
    alert(message); // Kan later worden uitgebreid met betere UI feedback
}

/**
 * Controleert op aankomende events en toont herinneringen
 */
function checkReminders() {
    if (document.querySelector('#calendar')) {
        alert('Herinnering: Check je aankomende events!');
    }
}

// Event handlers bij document load
document.addEventListener('DOMContentLoaded', function () {
    // Form validatie setup
    let forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });

    // Reminder check
    checkReminders();
});