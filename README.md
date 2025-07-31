# propstack-sync
# 🏢 Propstack Sync

**Synchronisiert Immobilienobjekte (Wohnungen & Stellplätze) aus der Propstack-API direkt in WordPress.**

---

## 🚀 Funktionen

- Lädt Daten aus der Propstack REST API (`/v2/properties`)
- Unterstützt mehrere Custom Post Types: `apartment`, `parking_space`
- Erkennt vorhandene Objekte anhand von `unit_id` und aktualisiert sie
- Erstellt neue Objekte automatisch mit ACF-Feldern
- Lädt Titelbilder aus Propstack automatisch in die WordPress-Mediathek
- Unterstützt manuelle Synchronisation im Adminbereich
- Alle Einstellungen (API-URL, Project-ID, Token) direkt in WordPress verwaltbar

---

## 🛠️ Voraussetzungen

- WordPress 6.x
- Advanced Custom Fields (ACF)
- Composer (für Autoloading)

---

## 📦 Installation

```bash
git clone git@github.com:vhf-web/propstack-sync.git wp-content/plugins/propstack-sync
cd wp-content/plugins/propstack-sync
composer install
Dann aktiviere das Plugin in WordPress.

⚙️ Einrichtung
Gehe zu Einstellungen → Propstack Sync

Gib ein:

API URL: https://api.propstack.de/v2/properties

Project ID: z. B. 123432

(Optional) API Token für geschützte APIs

Klicke auf „Jetzt synchronisieren“
→ Die Daten werden geladen, verarbeitet und gespeichert.

🧠 Wie es funktioniert
Dashboard.php: Admin-Seite mit Einstellungen und Sync-Button

SyncService.php: Steuert den gesamten Synchronisationsprozess

ApiClient.php: Holt Daten aus der Propstack-API

ApartmentHandler.php: Legt apartment-Posts an oder aktualisiert sie

(Bald) ParkingHandler.php: Für Stellplätze

🖼️ Medien-Import
Erstes Bild aus images[] wird automatisch heruntergeladen

Wird als „Beitragsbild“ (Featured Image) gesetzt

Zusätzlich als image_url in ACF gespeichert

📁 Struktur
pgsql
Kopieren
Bearbeiten
propstack-sync/
├── includes/
│   ├── Admin/
│   │   ├── Dashboard.php
│   ├── CPT/
│   │   ├── ApartmentCPT.php
│   ├── PostHandler/
│   │   ├── ApartmentHandler.php
│   ├── ApiClient.php
│   ├── SyncService.php
├── acf-json/
├── vendor/
├── composer.json
└── propstack-sync.php
📚 Roadmap
 Apartments synchronisieren

 Bilder importieren

 Stellplätze synchronisieren

 Mehrsprachige Inhalte (via translations)

 Cronjob-Unterstützung

 Logging & Fehleranzeige


API TEST:
https://api.propstack.de/v2/properties?project_id=404126