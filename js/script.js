function validateForm(form) {
    let valid = true;
    let requiredInputs = form.querySelectorAll('[required]');
    requiredInputs.forEach(input => {
        let value = input.value.trim();
        if (!value) {
            alert('Vul alle verplichte velden in.');
            valid = false;
        } else if (input.name === 'title' && (/^\s*$/.test(value))) {
            alert('Titel mag niet alleen spaties zijn.');
            valid = false;
        } else if (input.name === 'title' && value.length > 100) {
            alert('Titel max 100 tekens.');
            valid = false;
        } else if (input.type === 'date' && value < new Date().toISOString().split('T')[0]) {
            alert('Datum moet in de toekomst zijn.');
            valid = false;
        } else if (input.type === 'time' && value.includes('-')) {
            alert('Tijd moet positief zijn.');
            valid = false;
        } else if (input.name === 'username' && value.length > 50) {
            alert('Username max 50 tekens.');
            valid = false;
        } else if (input.type === 'password' && value.length < 6) {
            alert('Wachtwoord min 6 tekens.');
            valid = false;
        } else if (input.type === 'email' && !value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            alert('Ongeldige email.');
            valid = false;
        }
    });
    return valid;
}

// Herinnering check bij load (simpel alert voor demo)
document.addEventListener('DOMContentLoaded', function () {
    let forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
    // Simuleer herinnering alert
    if (document.querySelector('#calendar')) {
        alert('Herinnering: Check je aankomende events!');
    }
});