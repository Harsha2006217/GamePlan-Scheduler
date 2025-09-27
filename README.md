# GamePlan Scheduler

## Overzicht
GamePlan Scheduler is een webapplicatie speciaal ontworpen voor jonge gamers om hun game-activiteiten en sociale plannen te beheren. Met deze app kun je profielen aanmaken, favoriete games toevoegen, vrienden uitnodigen, speelschema's delen in een kalender, evenementen zoals toernooien organiseren, herinneringen instellen en alles eenvoudig bewerken of verwijderen. De app is responsive en werkt perfect op desktops en mobiele apparaten, met een modern donker ontwerp dat een game-achtig gevoel geeft. Het project is ontwikkeld als examenopdracht voor een MBO-4 Software Development opleiding en richt zich op gebruiksvriendelijkheid, veiligheid en efficiëntie.

## Functies
- **Profiel Aanmaken en Beheren**: Voeg favoriete games toe uit een lijst en bekijk je profiel.
- **Vrienden Toevoegen en Lijst Bekijken**: Zoek gebruikers met AJAX-suggesties, voeg vrienden toe en zie hun online/offline status.
- **Speelschema's Delen**: Plan games met datum, tijd en deel met vrienden via checkboxes.
- **Evenementen Organiseren**: Voeg evenementen toe met titel, beschrijving, reminder en optionele link naar schema's; deel met geselecteerde vrienden.
- **Kalender Overzicht**: Gecombineerde weergave van schema's en evenementen in cards, gesorteerd op datum/tijd.
- **Herinneringen Instellen**: Kies reminders (1 uur of 1 dag ervoor) met pop-up meldingen via JavaScript.
- **Bewerken en Verwijderen**: Update of verwijder items met bevestigingsdialogs voor veiligheid.
- **Security Maatregelen**: Gehashte wachtwoorden (bcrypt), prepared statements tegen SQL-injectie, XSS-preventie met htmlspecialchars, sessie regeneratie en timeout.
- **Responsive Design**: Geoptimaliseerd voor mobiel met Bootstrap media queries en grote knoppen.

## Installatie
### Vereisten
- PHP 8.1 of hoger
- MySQL 8.0 of hoger
- Webserver (bijv. XAMPP, Apache)
- Browser met JavaScript ingeschakeld

### Stappen
1. **Database Instellen**:
   - Creëer een database genaamd `gameplan_db`.
   - Importeer `database.sql` via phpMyAdmin of MySQL command line om tabellen en voorbeeldgegevens aan te maken.

2. **Bestanden Plaatsen**:
   - Kopieer alle projectbestanden naar je webserver map (bijv. `htdocs/gameplan-scheduler`).

3. **Configuratie**:
   - Open `db.php` en pas database credentials aan (host, username, password, dbname).
   - Zorg voor schrijfrechten op logbestanden (`app_log.log`, `errors.log`) voor auditing.

4. **Starten**:
   - Open in je browser: `http://localhost/gameplan-scheduler/index.php`.
   - Registreer een nieuw account via `register.php` of gebruik testaccounts uit de database.

## Voorbeeldgebruik
1. Registreer en log in.
2. Voeg favoriete games toe op de profielpagina.
3. Zoek en voeg vrienden toe.
4. Plan een schema of evenement en deel met vrienden.
5. Bekijk alles in het dashboard kalenderoverzicht.

## Bestandsstructuur
- `database.sql`: SQL-script voor database creatie, tabellen, relaties en voorbeeldgegevens.
- `db.php`: Databaseverbinding met PDO, inclusief error handling en performance attributes.
- `functions.php`: Centrale functies voor validatie, logging, CRUD-operaties (add/edit/delete) voor users, games, friends, schedules en events.
- `index.php`: Dashboard met overzichten van favorieten, vrienden, schema's, evenementen en kalender.
- `login.php`: Inlogpagina met form validatie.
- `register.php`: Registratiepagina met password strength checks.
- `profile.php`: Profielbeheer met favoriete games toevoegen.
- `add_friend.php`: Vriend toevoegen met AJAX-zoekfunctie.
- `search_users.php`: AJAX-endpoint voor gebruikerszoeken.
- `friends.php`: Vriendenlijst met status en verwijderoptie.
- `add_schedule.php`: Schema toevoegen form.
- `edit_schedule.php`: Schema bewerken form.
- `add_event.php`: Evenement toevoegen form.
- `edit_event.php`: Evenement bewerken form.
- `delete.php`: Universele delete handler voor types (schedule, event, friend).
- `logout.php`: Logout script.
- `style.css`: Custom CSS voor dark theme, responsive design en interacties.
- `script.js`: JavaScript voor form validaties, reminders en AJAX.

## Ontwikkeling en Testen
- **Versiebeheer**: Gebruik Git met branches (bijv. `feature/events`) en commits voor elke stap.
- **Testen**: 30 testscenario's per user story, 93% succesrate. Edge cases zoals spaties en ongeldige datums getest.
- **Verbeteringen**: Suggesties uit testrapport en feedback, zoals extra validatie en screenshots.
- **Tijdsbesteding**: 49 uur, volgens planning met wekelijkse checks.

## Licentie
Dit project is gelicenseerd onder de MIT License. Zie `LICENSE` bestand voor details.

## Contact
Voor vragen of feedback: Harsha Kanaparthi - harsha.kanaparthi20062@gmail.com 
