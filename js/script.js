// Geavanceerd JavaScript voor GamePlan Scheduler
// Inclusief form validatie, confirm dialogs, en reminder checks
// Gebruik van modern ES6+ features zoals async/await voor future AJAX

// Universele validatie functie voor forms (herbruikbaar)
function validateForm(formId, rules = {}) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function(e) {
        let valid = true;
        for (const [fieldId, rule] of Object.entries(rules)) {
            const field = document.getElementById(fieldId);
            if (!field) continue;
            const value = field.value.trim();

            if (rule.required && value === '') {
                alert(rule.msg || `${fieldId.charAt(0).toUpperCase() + fieldId.slice(1)} is verplicht.`);
                valid = false;
            }
            if (rule.maxLength && value.length > rule.maxLength) {
                alert(rule.msg || `${fieldId} max ${rule.maxLength} tekens.`);
                valid = false;
            }
            if (rule.minLength && value.length < rule.minLength) {
                alert(rule.msg || `${fieldId} min ${rule.minLength} tekens.`);
                valid = false;
            }
            if (rule.pattern && !rule.pattern.test(value)) {
                alert(rule.msg || `Ongeldig formaat voor ${fieldId}.`);
                valid = false;
            }
            if (rule.custom && !rule.custom(value)) {
                alert(rule.msg || `Fout in ${fieldId}.`);
                valid = false;
            }
        }
        if (!valid) e.preventDefault();
    });
}

// Voorbeeld gebruik: Valideer alle forms op load
window.addEventListener('load', function() {
    validateForm('loginForm', {
        email: { required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Ongeldig e-mailadres.' },
        password: { required: true, minLength: 8, msg: 'Wachtwoord min 8 tekens.' }
    });

    validateForm('registerForm', {
        username: { required: true, pattern: /^[a-zA-Z0-9]+$/, minLength: 3, maxLength: 50, msg: 'Gebruikersnaam alleen letters/cijfers, 3-50 tekens.' },
        email: { required: true, pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, msg: 'Ongeldig e-mailadres.' },
        password: { required: true, minLength: 8, custom: (val) => /[A-Z]/.test(val) && /[0-9]/.test(val), msg: 'Wachtwoord min 8 tekens, hoofdletter en cijfer.' }
    });

    validateForm('addFavoriteForm', {
        game_id: { required: true, msg: 'Kies een game.' }
    });

    validateForm('addFriendForm', {
        friend_username: { required: true, pattern: /^[a-zA-Z0-9]+$/, minLength: 3, maxLength: 50, msg: 'Ongeldige gebruikersnaam, 3-50 tekens.' }
    });

    validateForm('addScheduleForm', {
        game_id: { required: true, msg: 'Kies een game.' },
        date: { required: true, custom: (val) => new Date(val) >= new Date(new Date().setHours(0,0,0,0)), msg: 'Datum in toekomst.' },
        time: { required: true, custom: (val) => !val.startsWith('-') && /^(\d{2}):(\d{2})$/.test(val), msg: 'Ongeldige tijd (HH:MM).' }
    });

    validateForm('editScheduleForm', {
        game_id: { required: true, msg: 'Kies een game.' },
        date: { required: true, custom: (val) => new Date(val) >= new Date(new Date().setHours(0,0,0,0)), msg: 'Datum in toekomst.' },
        time: { required: true, custom: (val) => !val.startsWith('-') && /^(\d{2}):(\d{2})$/.test(val), msg: 'Ongeldige tijd (HH:MM).' }
    });

    validateForm('addEventForm', {
        title: { required: true, maxLength: 100, custom: (val) => !/^\s*$/.test(val), msg: 'Titel verplicht, max 100, niet alleen spaties.' },
        date: { required: true, custom: (val) => new Date(val) >= new Date(new Date().setHours(0,0,0,0)), msg: 'Datum in toekomst.' },
        time: { required: true, custom: (val) => !val.startsWith('-') && /^(\d{2}):(\d{2})$/.test(val), msg: 'Ongeldige tijd (HH:MM).' },
        description: { maxLength: 500, msg: 'Beschrijving max 500 tekens.' }
    });

    validateForm('editEventForm', {
        title: { required: true, maxLength: 100, custom: (val) => !/^\s*$/.test(val), msg: 'Titel verplicht, max 100, niet alleen spaties.' },
        date: { required: true, custom: (val) => new Date(val) >= new Date(new Date().setHours(0,0,0,0)), msg: 'Datum in toekomst.' },
        time: { required: true, custom: (val) => !val.startsWith('-') && /^(\d{2}):(\d{2})$/.test(val), msg: 'Ongeldige tijd (HH:MM).' },
        description: { maxLength: 500, msg: 'Beschrijving max 500 tekens.' }
    });

    // Reminder check op load (gebruik data-events attribute)
    const eventsScript = document.querySelector('script[data-events]');
    if (eventsScript) {
        const events = JSON.parse(eventsScript.textContent);
        const now = new Date();
        events.forEach(event => {
            const eventTime = new Date(`${event.date}T${event.time}`);
            let reminderMs = 0;
            if (event.reminder === '1 uur ervoor') {
                reminderMs = 3600000;
            } else if (event.reminder === '1 dag ervoor') {
                reminderMs = 86400000;
            }
            if (reminderMs > 0 && (eventTime - now) <= reminderMs && (eventTime - now) > 0) {
                alert(`Herinnering: ${event.title} op ${event.date} om ${event.time}`);
            }
        });
    }
});