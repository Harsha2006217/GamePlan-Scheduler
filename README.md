GamePlan Scheduler
Projectoverzicht
GamePlan Scheduler is een webapplicatie ontworpen voor jonge gamers om hun game-activiteiten en sociale plannen te beheren. Gebruikers kunnen profielen maken met favoriete games, vrienden toevoegen, gameschema's delen en evenementen zoals toernooien of meetups organiseren. Alle informatie wordt weergegeven in een overzichtskalender met herinneringen. De app is gebouwd met PHP voor backend-logica, MySQL voor gegevensopslag, HTML/CSS met Bootstrap voor de frontend-lay-out, en JavaScript voor interactieve elementen zoals formuliervalidatie en pop-ups. Het ondersteunt responsief ontwerp voor desktops en mobiele apparaten.
Dit project is ontwikkeld als onderdeel van mijn MBO-4 Software Development-examen. Het legt de nadruk op veilige gegevensverwerking, efficiënte code en gebruiksvriendelijk ontwerp.
Functies

Gebruikersregistratie en Inloggen: Veilige aanmelding en inloggen met sessiebeheer en wachtwoordhashing.
Profielbeheer: Favoriete games toevoegen en bekijken uit een vooraf gedefinieerde lijst.
Vriendenbeheer: Vrienden zoeken en toevoegen, vriendenlijst bekijken met online/offline-status.
Schema's: Gameschema's maken, bewerken en verwijderen, gekoppeld aan games, met delen via checkboxes.
Evenementen: Evenementen maken, bewerken en verwijderen met optionele koppelingen aan schema's en delen met vrienden.
Kalenderweergave: Gecombineerd overzicht van schema's en evenementen, gesorteerd op datum.
Herinneringen: Herinneringen instellen met JavaScript-gebaseerde pop-up-waarschuwingen.
Beveiliging: Prepared statements om SQL-injectie te voorkomen, htmlspecialchars voor XSS-bescherming, sessie-regeneratie.
Responsief Ontwerp: Bootstrap voor mobielvriendelijke interface.

Vereisten

PHP 8.1 of hoger
MySQL 8.0 of hoger
Webserver (bijv. XAMPP voor lokale ontwikkeling)
Browser (getest op Chrome en mobiele browsers)

Geen extra bibliotheken nodig behalve Bootstrap (via CDN gelinkt).
Installatie

Database Instellen:

Open phpMyAdmin of een vergelijkbaar tool.
Maak een nieuwe database aan genaamd gameplan_db.
Importeer het bestand database.sql om tabellen te maken en voorbeeldgegevens toe te voegen.


Bestanden Configureren:

Kopieer alle projectbestanden naar een map in je webserver (bijv. htdocs/gameplan in XAMPP).
Pas db.php aan als je databasegegevens verschillen (standaard: host=localhost, user=root, pass='').


Server Starten:

Start je lokale server (bijv. Apache en MySQL in XAMPP).
Open http://localhost/gameplan/index.php in je browser.


Testaccount:

Registreer een nieuwe gebruiker of gebruik voorbeeld: email=test@example.com, wachtwoord=test (gehasht in SQL).



Gebruik

Registreren/Inloggen: Begin bij login.php om een account te maken of in te loggen.
Profiel: Ga naar profile.php om favoriete games te selecteren.
Vrienden: Vrienden toevoegen via add_friend.php; lijst bekijken bij friends.php.
Schema's: Toevoegen/bewerken/verwijderen bij add_schedule.php/edit_schedule.php.
Evenementen: Toevoegen/bewerken/verwijderen bij add_event.php/edit_event.php, met opties om schema's te koppelen en met vrienden te delen.
Kalender: Bekijk alles op het dashboard (index.php).
Uitloggen: Beëindigt de sessie veilig.

Voor gedetailleerde codestructuur, zie functions.php voor kernlogica en individuele pagina's voor weergaven.
Ontwikkelnotities

Versiebeheer: Beheerd met Git; zie repository voor commitgeschiedenis (bijv. initiële setup, toevoegen van functies).
Beveiligingsmaatregelen: Alle gebruikersinvoer gesanitiseerd en gebonden; geen directe queries.
Testen: Behandeld in testrapport; 93% slaagpercentage, met fixes voor edge cases zoals ongeldige datums.
Tijdsbesteding: 49 uur aan coderen, zoals gelogd in projectlog.

Licentie
Dit project is voor educatieve doeleinden. Geen commercieel gebruik zonder toestemming.
Contact: Harsha Kanaparthi  voor vragen.
