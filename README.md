# GamePlan Scheduler – Professionele Gaming Planner
## 📋 Inhoudsopgave
1. [Introductie](#-introductie)
2. [SMART Doelstelling](#-smart-doelstelling)
3. [User Stories](#-user-stories)
4. [Aanvullende Eisen](#-aanvullende-eisen)
5. [Technische Architectuur](#-technische-architectuur)
6. [Installatie & Configuratie](#-installatie--configuratie)
7. [Database Schema](#-database-schema)
8. [UI/UX Richtlijnen](#-uiux-richtlijnen)
9. [Planning & Logboek](#-planning--logboek)
10. [Ontwikkelproces & Versiebeheer](#-ontwikkelproces--versiebeheer)
11. [Beveiligingsmaatregelen](#-beveiligingsmaatregelen)
12. [Testresultaten](#-testresultaten)
13. [Verbeteringsvoorstellen (K1 W5)](#-verbeteringsvoorstellen-k1-w5)
14. [Roadmap v1.1 (Gepland)](#-roadmap-v11-gepland)
15. [Ontwerp Samenvatting (K1 W2)](#-ontwerp-samenvatting-k1-w2)
16. [Test Samenvatting (K1 W4)](#-test-samenvatting-k1-w4)
17. [Security Samenvatting](#-security-samenvatting)
18. [Beoordelingscriteria (Cross-check)](#-beoordelingscriteria-cross-check)
19. [User Stories (Definitief Referentiekader)](#-user-stories-definitief-referentiekader)
20. [Audit Trail (Kernmomenten)](#-audit-trail-kernmomenten)
21. [Conclusie](#-conclusie)
22. [Contact](#-contact)
23. [Backlog](#-backlog)

---

## 🎮 Introductie
GamePlan Scheduler is een webapplicatie voor jonge gamers om profielen op te bouwen, vrienden te beheren, speelschema’s te delen en evenementen te organiseren in een centrale kalender. Het project is gerealiseerd door Harsha Kanaparthi (leerlingnummer 2195344) binnen de MBO-4 Software Development opleiding.

---

## 🎯 SMART Doelstelling
- **Specifiek:** Planner voor profielen, vrienden, schema’s, evenementen en herinneringen.
- **Meetbaar:** Positieve gebruikersfeedback bij minimaal driemaal gebruik per week.
- **Acceptabel:** Functionaliteit en design sluiten aan bij jonge gamers.
- **Realistisch:** Voltooiing binnen een maand met PHP/MySQL kennis.
- **Tijdsgebonden:** Oplevering gepland op 30-09-2025.

---

## 📚 User Stories
1. Profiel met favoriete games aanmaken.
2. Vrienden toevoegen en status bekijken.
3. Speelschema’s in een kalender delen.
4. Evenementen plannen en tonen.
5. Herinneringen configureren.
6. Content bewerken en verwijderen.

---

## ✅ Aanvullende Eisen
- Responsive interface voor desktop en mobiel.
- Veilige opslag in MySQL-tabellen *Users*, *Friends*, *Schedules*, *Events*.
- Toegankelijke UI met duidelijke knoppen en labels.
- Sessiebeveiligde login zodat gebruikers alleen eigen data zien.
- Modern donker thema met blauwe accenten.

---

## 🏗 Technische Architectuur
- **Backend:** PHP 8.1+ met PDO (prepared statements, Argon2ID hashing).
- **Database:** MySQL 8.0, UTF-8, foreign key constraints, genormaliseerd schema.
- **Frontend:** HTML5, CSS3 (donker thema), JavaScript ES6 voor validatie & meldingen.
- **Framework:** Bootstrap 5.3 voor grids en componenten.
- **Sessions:** Regeneratie, idle-timeout 30 minuten, SameSite=Lax.
- **Validatie:** Server-side (lengte, patroon, datum >= vandaag) plus client-side checks.

---

## ⚙️ Installatie & Configuratie
1. **Omgeving:** XAMPP installeren, Apache & MySQL starten.
2. **Broncode:** Plaats project in `c:\xampp\htdocs\gameplan\`.
3. **Database:**
   - phpMyAdmin → nieuwe database `gameplan_db`.
   - Importeer `database/schema.sql`.
4. **Configuratie:** Controleer verbinding in `PHP/functions.php` (PDO DSN, gebruiker, wachtwoord).
5. **Toegang:** Navigeer naar `http://localhost/gameplan/PHP/`, registreer of gebruik demo:
   - E-mail `demo@gameplan.com`
   - Wachtwoord `DemoPass123!`

---

## 🗄 Database Schema
| Tabel | Belangrijkste velden | Relaties |
| --- | --- | --- |
| **Users** | `user_id` (PK), `username`, `email`, `password_hash`, `last_activity` | 1:N naar Friends, UserGames, Schedules, Events |
| **Games** | `game_id` (PK), `title`, `description` | 1:N naar UserGames, Schedules |
| **UserGames** | `user_id` (FK), `game_id` (FK) | Favorieten per gebruiker |
| **Friends** | `friend_id` (PK), `user_id` (FK), `friend_user_id` (FK) | Bidirectionele vriendschappen |
| **Schedules** | `schedule_id` (PK), `user_id` (FK), `game_id` (FK), `date`, `time`, `friends` (tekst) | Koppeling naar vriendenlijst |
| **Events** | `event_id` (PK), `user_id` (FK), `schedule_id` (FK, optioneel), `title`, `date`, `time`, `description`, `reminder` | Evenementen per gebruiker |
| **EventUserMap** | `event_id` (FK), `friend_id` (FK) | Delen van evenementen met vrienden |

---

## 🖥 UI/UX Richtlijnen
- Donker thema (#121212) met blauwe knoppen en witte typografie.
- Header (80px) met logo, navigatie: Home, Profiel, Vrienden, Schema’s, Evenementen, Uitloggen.
- Contentbreedte 80%, afgeronde componenten, kalender met gekleurde blokken.
- Footer (50px) met © 2025, privacy- & contactlink.
- Mobiel: menu collapses naar hamburger, knoppen ≥ 40px voor touch.
- Feedbackmeldingen (groen succes, rood validatie) voor alle CRUD-acties.

---

## 🗓 Planning & Logboek
| Stap | Omschrijving | Start | Eind | Uren | Prioriteit |
| --- | --- | --- | --- | --- | --- |
| 1 | Omgeving opzetten (PHP/MySQL/editor) | 02-09-2025 | 02-09-2025 | 2 | M |
| 2 | Database ontwerpen & aanmaken | 03-09-2025 | 04-09-2025 | 3 | M |
| 3 | Login & sessiebeheer | 05-09-2025 | 07-09-2025 | 6 | M |
| 4 | Basis frontend templates | 08-09-2025 | 09-09-2025 | 4 | M |
| 5 | Profielbeheer | 10-09-2025 | 13-09-2025 | 8 | M |
| 6 | Vriendensysteem | 14-09-2025 | 16-09-2025 | 6 | S |
| 7 | Schema’s & kalender (JS) | 17-09-2025 | 18-09-2025 | 4 | S |
| 8 | Evenementen CRUD | 19-09-2025 | 21-09-2025 | 6 | M |
| 9 | Herinneringssysteem | 22-09-2025 | 23-09-2025 | 4 | C |
| 10 | Testen & bugfixes | 24-09-2025 | 25-09-2025 | 3 | M |
| 11 | Mobiel design-finetuning | 26-09-2025 | 27-09-2025 | 2 | C |
| 12 | Oplevering & live test | 28-09-2025 | 30-09-2025 | 1 | M |

- **Totaal:** 49 uur (≥40 uur eis)  
- **Overlegmomenten:** 07-09 (backend), 16-09 (vrienden), 27-09 (design)  
- **Weekindeling:**  
  - Week 1: omgeving, database, auth  
  - Week 2: frontend, profielen  
  - Week 3: vrienden, schema’s, evenementen  
  - Week 4: herinneringen, testen, livegang  

---

## 🛠 Ontwikkelproces & Versiebeheer
- **Workflow:** PSR-12 code style, functies geconcentreerd in `functions.php`, pagina’s per functionaliteit.
- **Sessiebeheer:** Regeneratie bij login, idle timeout 30 minuten.
- **Git:** Minimaal 8 commits, branches zoals `feature/events`. Voorbeelden:  
  - `abc123` Setup & schema  
  - `def456` Databasehelper & connection  
  - `ghi789` Login/Register  
  - `jkl012` Profielbeheer  
  - `mno345` Schema CRUD  
  - `pqr678` Event sharing  
  - `stu901` Dashboard kalender  
  - `vwx234` Styling & scripts

---

## 🔒 Beveiligingsmaatregelen
- **Hashing:** Argon2ID voor wachtwoorden.
- **PDO:** Prepared statements met binding voor alle queries.
- **XSS:** `htmlspecialchars` bij output, CSP ingesteld in `.htaccess`.
- **CSRF:** Tokens voor formulieracties.
- **Sessies:** `session.use_strict_mode`, `cookie_httponly`, `SameSite=Lax`.
- **Validatie:** Trim, lengtecontroles, datum ≥ vandaag, tijd positief.
- **Logging:** `activity_log` voor kritieke acties.

### Samenvatting
- Password hashing met Argon2ID conform documentatie.
- Prepared statements via PDO, output escaping en sessie-regeneratie.
- Idle timeout na 30 minuten, SameSite=Lax en `HttpOnly` cookies.
- Inputvalidatie op lengte, patroon, datum ≥ vandaag en autorisatie per `user_id`.
- Activiteitenlog voor kritieke handelingen.

## 🧪 Testresultaten
- **Periode:** 23–25-09-2025 (6 uur), platformen: Windows 10 (Chrome), Android 14 (Samsung S21).
- **Scenario’s:** 30 tests (5 per user story) → 28 geslaagd (93%).
- **Belangrijkste bevindingen:**  
  - #1001 Spaties in favoriete games → extra trim-validatie vereist.  
  - #1004 Ongeldige datum → extra edge-case check toevoegen.  
- **Performance:** Kalenderweergave < 2s (doel < 3s).  
- **Mobiel:** Responsive bevestigd.  
- **Security:** Geen gevonden SQLi/XSS tijdens tests.

---

## 🔧 Verbeteringsvoorstellen (K1 W5)
| ID | Bron | Omschrijving | Actie |
| --- | --- | --- | --- |
| #1001 | Testrapport | Trim-validatie voor favoriete games | Validatie uitbreiden in profielopslag |
| #1004 | Testrapport | Edge-case datums en lengtebeschrijvingen | Striktere validatie & extra tests |
| #1002 | Oplevering | Herinneringen uitbreiden (e-mail/push) | Notificatie-opties toevoegen |
| #1003 | Oplevering | Navigatie optimaliseren (mobiel) | Hamburger-menu + prominente CTA |
| #1005 | Reflectie | Screenshots in testrapport | Visuele documentatie toevoegen |
| #1006 | Reflectie | Sorteer-/filteropties voor lijstweergaven | Server-side ORDER BY & filters |

Uitwerking per voorstel:
- **#1001 / #1004:** Server-side `trim`, strikte datumparser en aanvullende tests voorkomen lege of ongeldige invoer.
- **#1002:** Extra dropdown voor notificatietype (in-app, e-mail, push) inclusief PHP mailer en Notification API.
- **#1003:** Mobiele navigatie herstructureren met hamburger en een opvallende CTA “Evenement toevoegen”.
- **#1005:** Screenshots (kalender, formulier, foutmelding, mobiel dashboard, filters) opnemen met toelichting.
- **#1006:** Sorteer- en filterknoppen voor schema’s en evenementen met gesanitiseerde parameters.

## 📈 Roadmap v1.1 (Gepland)
1. (#1002) E-mail- en pushnotificaties implementeren.
2. (#1003) Mobiele navigatie herschrijven met hamburger en primaire actieknop.
3. (#1006) Uitgebreide sorteer- en filteropties op datum/game/tijd.
4. (#1001/#1004) Validatie uitbreiden met strict date parsing en trim-controles.
5. (#1005) Screenshotsectie toevoegen aan testrapport.
6. Accessibility review op focus states en ARIA-roles.

## 🎨 Ontwerp Samenvatting (K1 W2)
- Donkere UI met vast raster: Header, Content, Footer.
- Navigatie-items: Home, Profiel, Vrienden, Schema’s, Evenementen, Uitloggen; icoontjes en hoverstates.
- Componenten per user story: profielformulier, vriendenlijst met status, kalenderweergave met checkboxes, evenementkaarten, herinneringsdropdown, bevestigingen bij CRUD.
- Niet-functionele eisen: privacy (minimale data), security (sessies, validatie, hashing), ethiek (geen misbruik), usability (≥40px knoppen, duidelijke feedback).
- Wireframes en diagrammen (ERD, Use Case) borgen consistentie en datamodel.

## 🧪 Test Samenvatting (K1 W4)
- Periode 23–25-09-2025, totaal 6 uur, 30 scenario’s (5 per user story) → 28 geslaagd (93%).
- Platformen: Windows 10 (Chrome) & Android 14 (Samsung S21).
- Kritieke bevindingen: #1001 (spaties in favoriete games) en #1004 (ongeldige datums).
- Performance <2s voor kalender, responsive gedrag bevestigd, geen SQL/XSS-issues aangetroffen.

## 🛡 Security Samenvatting
- Sessies regenereren na login, strenge modus en SameSite=Lax.
- Prepared statements en output escaping tegen SQLi/XSS.
- Argon2ID-hashing met passende cost-factors.
- CSRF-protectie via tokens en form-action beperkingen.
- Autorisatie op basis van `user_id` voorkomt datatoegang door derden.

## ✅ Beoordelingscriteria (Cross-check)
- 40+ uur gerealiseerd: 49 uur ✅
- Alle 6 user stories afgerond ✅
- Genormaliseerde database met Users, Games, UserGames, Friends, Schedules, Events, EventUserMap, activity_log ✅
- Security: hashing, prepared statements, XSS mitigatie ✅
- Codekwaliteit: PSR-12 en gedocumenteerde kernfuncties ✅
- Versiebeheer: ≥8 commits + branches (feature/events) ✅
- Testresultaat ≥80%: 93% ✅

## 🧩 User Stories (Definitief Referentiekader)
1. Profiel maken met favoriete games.
2. Vrienden toevoegen en status volgen.
3. Speelschema’s delen in kalender.
4. Evenementen plannen en beheren.
5. Herinneringen instellen voor schema’s en events.
6. Content bewerken en verwijderen.

## 📝 Audit Trail (Kernmomenten)
- 10-09: Validatiebug opgelost met `trim()`.
- 19-09: Prepared DELETE fix bij verwijderfunctionaliteit.
- 24-09: Edge tests toegevoegd aan regressiesuite.
- 27-09: Mobiele ontwerpconsistentie afgerond.

## 🔚 Conclusie
GamePlan Scheduler voldoet aan planning, ontwerp en testdoelen. Verbeterpunten zijn vastgelegd voor versie 1.1 met focus op notificaties, navigatie en datakwaliteit. Het project toont de volledige ontwikkelcyclus van concept tot oplevering.

## 📞 Contact
- **Ontwikkelaar:** Harsha Kanaparthi  
- **Leerlingnummer:** 2195344  
- **Begeleider:** Marius Restua  
- **Demo-account:** `demo@gameplan.com` / `DemoPass123!`

## 📌 Backlog
- [ ] Multi-language support
- [ ] Advanced analytics dashboard

---

**GamePlan Scheduler** toont de volledige cyclus van planning, ontwerp, realisatie, testen en verbetering – gebouwd met ❤️ voor de gaming community.
4. Evenementen plannen
5. Herinneringen instellen
6. Bewerken & verwijderen

## 📝 Audit Trail (Kernmomenten)
- 10-09 Validatie bug (trim opgelost)
- 19-09 Delete ID bug (prepared DELETE fix)
- 24-09 Edge tests toegevoegd
- 27-09 Design consistentie mobiel afgerond

## 🔚 Conclusie
GamePlan Scheduler levert de gespecificeerde functionaliteit volgens planning, ontwerp en testdoelen. Verbeterpunten zijn gedocumenteerd en geprioriteerd voor versie 1.1 met focus op notificaties, navigatie en datakwaliteit.

---

**Gebouwd met ❤️ voor de gaming community door Harsha Kanaparthi**

*Dit project toont de volledige cyclus van softwareontwikkeling: van planning en ontwerp tot implementatie, testing en oplevering.*
- [ ] Multi-language support
- [ ] Advanced analytics dashboard

---

**Built with ❤️ for the gaming community by Harsha Kanaparthi**
