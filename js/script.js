 // Client-side form validatie voor alle forms
function validateForm(form) {
    let inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    for (let input of inputs) {
        let value = input.value.trim();
        if (!value) {
            alert('Vul alle verplichte velden in.');
            return false;
        }
        if (input.type === 'text' && input.name === 'title' && value.length > 100) {
            alert('Titel mag maximaal 100 tekens zijn.');
            return false;
        }
        if (input.type === 'text' && input.name === 'username' && value.length > 50) {
            alert('Username mag maximaal 50 tekens zijn.');
            return false;
        }
        if (input.type === 'password' && value.length < 6) {
            alert('Wachtwoord moet minimaal 6 tekens zijn.');
            return false;
        }
        if (input.type === 'time' && value.match(/^-/) ) {
            alert('Tijd moet positief zijn.');
            return false;
        }
        if (input.type === 'date') {
            let today = new Date().toISOString().split('T')[0];
            if (value < today) {
                alert('Datum moet in de toekomst liggen.');
                return false;
            }
        }
        if (input.name === 'title' && /^\s*$/.test(value)) {
            alert('Titel mag niet alleen spaties bevatten.');
            return false;
        }
    }
    return true;
}

// Herinnering simulatie op load (voor demo, in productie poll or websocket)
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#calendar')) {
        // Simuleer: check events met reminder < nu + reminder tijd
        // Hier simpel alert voor demo
        alert('Herinnering: Je hebt aankomende events! Check de kalender.');
    }
    // Voeg onsubmit toe aan alle forms
    let forms = document.querySelectorAll('form');
    for (let form of forms) {
        form.onsubmit = function() { return validateForm(this); };
    }
});