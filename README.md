# propstack-sync
# 🏢 Propstack Sync

Synchronisiert Propstack-Projektdaten und Immobilienobjekte (Wohnungen & perspektivisch Stellplätze) aus der Propstack-API direkt in WordPress-Custom-Post-Types.

---

## 🚀 Features

- Holt Daten aus der Propstack REST-API (v2):
  - `/v2/properties` – Einheiten (APARTMENT, später PARKING/COMMERCIAL)
  - `/v2/projects` – Projektdaten
- Legt Custom Post Types an:
  - `project` – Propstack-Projekt
  - `apartment` – Wohnungen (rs_type = `APARTMENT`)
  - `parking_space` – vorbereitet, aktuell deaktiviert
- Erzeugt/aktualisiert Beiträge anhand von Propstack-IDs:
  - Projekt: `propstack_id`
  - Wohnung: Kombination aus `propstack_id` + `unit_id`
- Befüllt ACF-Felder mit den wichtigsten Objektdaten (Adresse, Flächen, Preise, Status, usw.)
- Lädt Titelbilder aus Propstack automatisch in die WordPress-Mediathek und setzt sie als Beitragsbild
- Bietet eine eigene Admin-Seite **„Propstack“** mit Buttons zur manuellen Synchronisation pro Projekt
- Speichert den letzten Sync-Zeitpunkt am Projekt (`letzter_sync`)
- Bereitet Cronjob-Unterstützung für automatische Synchronisation vor (Klasse `CronHandler`, noch nicht vollständig angebunden)

---

## ✅ Voraussetzungen

- WordPress 6.x (klassisch oder Bedrock)
- PHP ≥ 8.1
- Composer (für den Autoloader)
- Plugin **Advanced Custom Fields** (Free oder Pro)
- Zugriff auf die **Propstack REST API v2** inkl. API-Key

---

## 📦 Installation

1. Repository in den Plugin-Ordner klonen / kopieren:

   ```bash
   cd wp-content/plugins
   git clone git@github.com:vhf-web/propstack-sync.git propstack-sync
(oder den Ordner propstack-sync manuell hier ablegen)

Im Plugin-Ordner Composer ausführen:

bash
Code kopieren
cd propstack-sync
composer install
Dadurch wird vendor/autoload.php erzeugt, den das Plugin im Hauptfile lädt.

Im WordPress-Backend das Plugin „Propstack Sync“ aktivieren.

🔑 Konfiguration der API-Zugänge
Das Plugin liest API-URLs und API-Key über Konstanten (z. B. in wp-config.php oder config/application.php bei Bedrock):

PROPSTACK_PROPERTIES_API_URL
→ URL für /v2/properties (z. B. https://api.propstack.de/v2/properties)

PROPSTACK_PROJECTS_API_URL
→ URL für /v2/projects (z. B. https://api.propstack.de/v2/projects)

PROPSTACK_API_TOKEN
→ geheimer API-Key / Token für den X-API-Key Header

Beispiel: klassisches WordPress (wp-config.php)
php
Code kopieren
define('PROPSTACK_PROPERTIES_API_URL', 'https://api.propstack.de/v2/properties');
define('PROPSTACK_PROJECTS_API_URL', 'https://api.propstack.de/v2/projects');

// Besser über ENV-Variable ziehen:
define('PROPSTACK_API_TOKEN', getenv('PROPSTACK_API_TOKEN'));
Beispiel: Bedrock (config/application.php)
php
Code kopieren
define('PROPSTACK_PROPERTIES_API_URL', env('PROPSTACK_PROPERTIES_API_URL', 'https://api.propstack.de/v2/properties'));
define('PROPSTACK_PROJECTS_API_URL',  env('PROPSTACK_PROJECTS_API_URL',  'https://api.propstack.de/v2/projects'));
define('PROPSTACK_API_TOKEN',         env('PROPSTACK_API_TOKEN'));
🔒 Den Token niemals ins Git-Repository committen – nur über ENV/Server-Config setzen.

🧱 Datenmodell
Custom Post Types
project
Repräsentiert ein Propstack-Projekt.

Registriert in: includes/CPT/ProjectCPT.php

Wichtige ACF-Felder (aus acf-json/projects.json):

propstack_id – externe Propstack-Projekt-ID

letzter_sync – Datum/Zeit des letzten erfolgreichen Syncs

weitere Felder für:

Adresse (Straße, PLZ/Ort, Land)

Projektbeschreibung / Lage / Ausstattung

Ansprechpartner / Betreuer

Energieausweis-Daten (Klasse, Typ, Kennwert, Energieträger, Baujahr), etc.

Die Projektdaten werden über ProjectHandler::syncProjects() aus der /v2/projects-API befüllt.

apartment
Einzelne Wohneinheit innerhalb eines Projekts (rs_type = APARTMENT).

Registriert in: includes/CPT/ApartmentCPT.php

Erzeugt/aktualisiert durch: PostHandler/ApartmentHandler.php

Identifikation:

propstack_id – externe ID aus Propstack

unit_id – Einheiten-ID

Wichtige ACF-Felder (aus acf-json/apartment-details.json):

Identität:

propstack_id

unit_id

Adresse:

address

street

house_number

zip_code

city

Flächen & Zimmer (je nach Feldgruppe):

living_space

number_of_rooms

floor

usw.

Preise:

base_rent

total_rent

hoa_fee / Hausgeld, etc.

Status:

numerischer Status aus Propstack (z. B. 142967)

wird in Klartext-Mapping übersetzt: Akquise, Verfügbar, Reserviert, Verkauft, …

Weitere Meta-Informationen (z. B. Dokument-URLs) können als JSON in Meta-Feldern wie propstack_document_urls abgelegt werden.

parking_space
Registriert in: includes/CPT/ParkingCPT.php

Aktuell (Stand dieses README) im Hauptplugin auskommentiert:

// new ParkingCPT();

Geplant für später:

Handling von rs_type = 'PARKING' mit eigenem PostHandler und ACF-Feldgruppe (acf-json/parking-space-details.json).

🖼 Medien & Titelbilder
Beim Sync von Wohnungen:

Die Methode MediaHelpers::set_featured_image_from_url() lädt das erste verfügbare Bild aus der Propstack-Galerie (Gallery-URLs).

Das Bild wird per download_url() & media_handle_sideload() in die Mediathek importiert.

Es wird als Beitragsbild (post_thumbnail) gesetzt.

Zusätzliche Metadaten:

_featured_source_url – ursprüngliche Bild-URL aus Propstack

_featured_source_hash – Hash der Quelle (zum Erkennen von Änderungen)

Weitere Bild-/Dokument-URLs können in eigenen Feldern (z. B. gallery_urls, propstack_document_urls) gespeichert werden.

🛠 Manuelle Synchronisation im Backend
Nach Aktivierung des Plugins gibt es im Backend einen neuen Menüpunkt:

Propstack
Pfad: /wp-admin/admin.php?page=propstack-dashboard
Implementiert in: includes/Admin/DashboardPage.php

Auf der Seite wird eine Tabelle aller project-Posts angezeigt:

Spalten (vereinfacht):

Projekt-Titel

Propstack-ID (propstack_id)

Letzter Sync (letzter_sync)

Aktionen

Zu jedem Projekt:

🏢 Wohnungen synchronisieren

Button-Klasse: .propstack-sync-btn

Ajax-Action: propstack_sync_project_ajax

JS: assets/propstack-sync.js

Server-Seite:

DashboardPage::ajax_sync_project()

ruft SyncService::sync_project($propstack_id, $post_id) auf

SyncService::sync_project():

ruft ApiClient::fetch() mit Parametern:

project_id – aktuelle Projekt-ID

per – Anzahl pro Seite (standardmäßig 400)

page – Pagination

iteriert über alle Objekte, filtert nach rs_type

Bei rs_type = 'APARTMENT':

ApartmentHandler::create_or_update() erstellt/aktualisiert den apartment-Post

Zählt created / updated und gibt die Zahlen zurück

Projekt-Metadaten synchronisieren

Button-Klasse: .propstack-sync-meta-btn

Ajax-Action: propstack_sync_project_meta

Server-Seite:

DashboardPage::ajax_sync_project_meta()

ruft ProjectHandler::syncProjects([$propstack_id]) auf

Aktualisiert die ACF-Felder des entsprechenden project-Posts mit /v2/projects-Daten.

Der aktuelle Status (Erfolg/Fehler) wird clientseitig im DOM in den .sync-status-Span neben den Buttons geschrieben.

🧪 Logging & Debugging
Das Plugin schreibt (bewusst) sehr viel in das PHP-Error-Log, um den Sync nachvollziehbar zu machen.

Präfixe:

[propstack]

[PropstackSync]

Typische Log-Stellen:

ApiClient::fetch():

fehlende URL/Token

WP-Errors bei wp_remote_get

MediaHelpers::set_featured_image_from_url():

Downloadfehler, ungültige URLs

Infos zu altem/neuem Bild, Hashes

ProjectHandler / ApartmentHandler:

Erstellte / aktualisierte Posts

Fälle ohne Daten

💡 Für lokale Entwicklung:
tail -f auf das PHP-Error-Log und nach propstack filtern, um den gesamten Sync-Lebenslauf zu sehen.

⏰ Cron / automatische Synchronisation (Vorbereitung)
Es existiert bereits eine Klasse Propstack\Includes\Cron\CronHandler:

registriert den Hook propstack_daily_sync_event

bietet Methoden:

run_daily_sync() – führt den täglichen Sync aus (intern SyncService)

activate() – registriert das tägliche Event

deactivate() – räumt den Hook wieder auf

Im Hauptplugin wird aktuell nur ein Objekt erstellt:

php
Code kopieren
if (class_exists(CronHandler::class)) {
    new CronHandler();
}
👉 Wichtig:
register_activation_hook() / register_deactivation_hook() sind noch nicht angebunden – der Cron läuft also noch nicht automatisch. Der Sync passiert derzeit ausschließlich über die Buttons auf der Propstack-Dashboard-Seite.

Geplante Schritte (ToDo)
Im Hauptplugin:

Bei Aktivierung: CronHandler::activate() aufrufen

Bei Deaktivierung: CronHandler::deactivate() aufrufen

Optional:

System-Cron auf dem Server (z. B. via Ploi / crontab) einrichten, der regelmäßig wp-cron.php ausführt.

Optional:

Erweiterte Logik („nur syncen, wenn letzter Sync > X Stunden her ist“, etc.)

🗂 Projektstruktur (Übersicht)
propstack-sync.php
Hauptplugin:

lädt Composer-Autoloader

registriert CPTs (ApartmentCPT, ProjectCPT, optional ParkingCPT)

setzt ACF-JSON-Pfad (acf-json im Plugin)

initialisiert CronHandler, DashboardPage usw.

composer.json

PSR-4-Autoload für Propstack\Includes\ → includes/

includes/

ApiClient.php – HTTP-Client für Propstack-API (Properties & Projects)

SyncService.php – zentrale Sync-Logik für Projekte/Wohnungen

MediaHelpers.php – Hilfsfunktionen für Bilder & Beitragsbilder

ContentTypePermalinks.php – Permalink-Anpassungen für CPTs

Cron/CronHandler.php – Cron-Vorbereitung

CPT/ApartmentCPT.php, CPT/ProjectCPT.php, CPT/ParkingCPT.php – Registrierung der CPTs

PostHandler/ApartmentHandler.php – Erzeugt & aktualisiert apartment-Posts

PostHandler/ProjectHandler.php – Sync von project-Posts aus /v2/projects

Admin/DashboardPage.php – Admin-Seite "Propstack" inkl. Sync-Buttons & Ajax

acf-json/

apartment-details.json

parking-space-details.json

projects.json

assets/propstack-sync.js

jQuery-basiertes Ajax-Skript für die Sync-Buttons.

📝 Weiterentwicklung / Ideen
PARKING & COMMERCIAL:

rs_type = 'PARKING' & rs_type = 'COMMERCIAL' aktivieren

eigene PostHandler + ACF-Gruppen nutzen

Cron:

Vollständig angebundenen daily / hourly Sync

Admin-Optionen: wie oft, welches Projekt, usw.

Backend-UI:

Filter im Dashboard (z. B. nach letztem Sync)

Detail-Anzeige, was bei letztem Sync passiert ist (Log-Übersicht)