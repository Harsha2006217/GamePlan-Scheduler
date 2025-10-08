# 🎮 GamePlan Scheduler

**Responsive webapplicatie voor jonge gamers om profielen te beheren, vrienden toe te voegen, speelschema's te delen, evenementen te plannen en herinneringen in te stellen.**

---

## 📌 Overzicht

- **Auteur**: Harsha Kanaparthi  
- **Studentnummer**: 2195344  
- **Datum**: 30-09-2025  
- **Versie**: 1.0  
- **Technologieën**: PHP, MySQL, HTML/CSS/Bootstrap, JavaScript  
- **Ondersteuning**: Desktop & mobiel, donkere modus  

---

## ✨ Functionaliteiten

### Gebruikersverhalen

- Profiel aanmaken met favoriete games (CRUD)
- Vrienden toevoegen op gebruikersnaam (CRUD + status/notitie)
- Speelschema's delen via kalender (CRUD)
- Evenementen plannen met herinneringen (CRUD)
- Herinneringen instellen (JS pop-ups)
- Alles bewerken/verwijderen (soft delete)

### Geavanceerde CRUD

- Favoriete games, vrienden, schema's en evenementen volledig bewerkbaar
- Directe updates in database via gedeelde velden

### Beveiliging

- Bcrypt hashing
- PDO prepared statements
- Inputvalidatie (spaties, datum/tijd, e-mail)
- Sessie-timeout (30 min)
- Eigendom-checks

### UI/UX

- Donker/blauw thema
- Responsief ontwerp (Bootstrap)
- Sortering, bevestigingsdialogen, meldingen

### Database

- 6 tabellen: Users, Games, UserGames, Friends, Schedules, Events
- Indexen voor performance

---

## 🎯 SMART-doel

- **Specifiek**: Profielen, vrienden, schema’s en evenementen beheren  
- **Meetbaar**: Feedback bij 3x/week gebruik  
- **Acceptabel**: Gericht op gamers, eenvoudig te bouwen  
- **Realistisch**: Kennis van PHP/DB, 49 uur  
- **Tijdsgebonden**: Voltooid op 30-09-2025  

---

## ⚙️ Installatie

1. **Omgeving**: Installeer XAMPP (PHP 8.1+, MySQL 8.0+), VS Code  
2. **Database**: Voer `database.sql` uit in phpMyAdmin (maak `gameplan_db`)  
3. **Project**: Plaats bestanden in `htdocs/gameplan/`  
4. **Start**: Start Apache/MySQL, ga naar `localhost/gameplan/index.php`  
5. **Registratie/Login**: Maak een account aan en beheer via dashboard  

---

## 🧭 Gebruik

- **Dashboard**: Overzicht van vrienden, favorieten, schema’s en evenementen  
- **Profiel**: Favoriete games beheren  
- **Vrienden**: Toevoegen/bewerken/verwijderen op gebruikersnaam  
- **Schema’s**: Game, datum/tijd, gedeeld met  
- **Evenementen**: Titel, datum/tijd, beschrijving, herinnering, link  
- **Logout**: Via header  

---

## 📁 Projectstructuur

- **Core**: `db.php`, `functions.php`  
- **Pagina’s**: `login.php`, `register.php`, `index.php`, `profile.php`, `add_friend.php`, `add_schedule.php`, `add_event.php`, `edit_*.php`, `delete.php`  
- **Layout**: `header.php`, `footer.php`, `privacy.php`, `contact.php`  
- **Assets**: `style.css`, `script.js`  
- **Database**: `database.sql`  

---

## 📅 Planning & Ontwikkeling

- **Tijdlijn**: 02-09-2025 t/m 30-09-2025 (49 uur)  
- **MOSCOW**: Must = auth/DB/CRUD, Should = vrienden/schema’s, Could = herinneringen  
- **Stack**: PHP 8.1, MySQL 8.0, Bootstrap 5, CSS3, JS ES6  
- **Versiebeheer**: Git/GitHub (8+ commits, branches per feature)  
- **Testen**: 30 scenario’s (93% geslaagd)  
- **Toekomst (v1.1)**: Notificaties, betere navigatie, screenshots, sortering  

---

## 🔐 Veiligheid & Ethiek

- **Beveiliging**: Gehashte wachtwoorden, output escaping, sessie regeneratie, user_id checks  
- **Privacy**: AVG-conform, minimale data, verwijderoptie, geen verkoop  
- **Ethiek**: Inclusief, geen verslavende advertenties, eerlijke privacytekst  

---

## 🎥 Demo & Repo

- **Video**: 6 minuten walkthrough  
- **GitHub**: [link naar repo]  
  - Commits: `setup abc123`, `auth ghi789`, etc.

---

## 👥 Credits

- **Begeleider**: Marius Restua (feedback backend/navigatie/herinneringen)  
- **Testers**: 3 gamers (gebruikerservaring)

---

## 📬 Contact

Voor vragen of problemen: [support@gameplanscheduler.com](mailto:support@gameplanscheduler.com)
