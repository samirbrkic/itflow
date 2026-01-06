# 📧 ITFlow HTML Email Templates - Installation & Setup Guide

## ✅ Installation abgeschlossen!

Das HTML Email Template System wurde erfolgreich installiert und ist einsatzbereit.

## 📁 Installierte Dateien

### Core System
```
portal.samix.one/custom/
├── custom_action_handler.php          ✅ Hauptlogik (15 KB)
├── email_template_helper.php          ✅ Helper-Funktionen (15 KB)
├── translation_helper.php             ✅ Bereits vorhanden
└── EMAIL_TEMPLATES_README.md          ✅ Vollständige Dokumentation
```

### Email Templates
```
portal.samix.one/custom/email_templates/
├── base_template.html                 ✅ Basis-Layout (14 KB)
├── ticket_created.html                ✅ Neues Ticket (4.7 KB)
├── ticket_reply.html                  ✅ Antwort (3.1 KB)
├── ticket_resolved.html               ✅ Gelöst (3.5 KB)
├── ticket_closed.html                 ✅ Geschlossen (2.9 KB)
└── ticket_assigned.html               ✅ Zugewiesen (4.9 KB)
```

### Testing & Patches
```
portal.samix.one/custom/
├── test_email_templates.php           ✅ Test-Script
└── email_templates/test_output/       ✅ 6 Test-HTMLs generiert

scripts/patches/configs/
└── patches.json                       ✅ Fallback-Patches hinzugefügt
```

## 🚀 Schnellstart

### 1. System aktivieren

Das System ist **sofort aktiv**! ITFlow ruft automatisch `customAction()` auf und die Templates werden verwendet.

### 2. Test durchführen

```bash
# Ticket in ITFlow erstellen und Email überprüfen
# Oder: Test-Script ausführen
cd /var/www/portal.samix.one/custom
php test_email_templates.php
```

### 3. Vorschau anzeigen

```bash
# Browser öffnen mit generierten Previews
firefox /var/www/portal.samix.one/custom/email_templates/test_output/*.html

# Oder einzelne Datei
firefox /var/www/portal.samix.one/custom/email_templates/test_output/ticket_created_de.html
```

## 🎨 Template-Features

### ✅ Responsive Design
- Desktop und Mobile optimiert
- Email-Client kompatibel
- Dark Mode Support

### ✅ Branding
- Logo automatisch eingebettet (Base64)
- Firmenfarben (Gradient Header)
- Firmen-Kontaktdaten im Footer

### ✅ Strukturierte Darstellung
- Ticket-Details in Tabelle
- Status und Priorität als farbige Badges
- "View Ticket" als prominent gestylter Button

### ✅ Multi-Language
- Automatische Spracherkennung (DE/EN)
- Alle UI-Texte übersetzt
- Reply-Instruction in passender Sprache

## 🔧 Konfiguration

### Logo einstellen
```
1. In ITFlow: Admin → Settings → Company
2. Company Logo hochladen
3. Automatisch in allen Emails verwendet
```

### Farben anpassen
```php
// In: custom/email_templates/base_template.html

/* Header Gradient ändern */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                     ^Farbe1     ^Farbe2

/* Footer Farbe ändern */
background-color: #2d3748;
                  ^Farbe
```

### Neue Templates hinzufügen
```
1. Datei erstellen: custom/email_templates/my_template.html
2. Übersetzungen: email_template_helper.php → getEmailTranslations()
3. Handler: custom_action_handler.php → case 'my_trigger':
```

## 🔍 Funktionsweise

### Ablauf bei Ticket-Erstellung

```
1. User/Agent erstellt Ticket in ITFlow
   ↓
2. ITFlow erstellt Standard Email (Plaintext)
   ↓
3. Email wird zur Queue hinzugefügt (addToMailQueue)
   ↓
4. ITFlow ruft customAction('ticket_create', $ticket_id) auf
   ↓
5. custom_action_handler.php wird geladen
   ↓
6. handleTicketCreateEmail($ticket_id):
   - Lädt Ticket-Daten aus DB
   - Erkennt Sprache automatisch
   - Lädt HTML Template
   - Ersetzt alle Variablen
   - Baut vollständiges HTML zusammen
   ↓
7. Email in Queue wird AKTUALISIERT (UPDATE)
   - Neuer Subject (übersetzt)
   - Neuer Body (HTML)
   ↓
8. Cron verarbeitet Queue (mail_queue.php)
   ↓
9. PHPMailer sendet HTML Email
   ↓
10. ✅ Kunde erhält moderne HTML Email!
```

### Update-Sicherheit

Das System liegt komplett im `/custom` Ordner:
- ✅ Von Git ignoriert (siehe .gitignore)
- ✅ Nicht von ITFlow-Updates überschrieben
- ✅ Nutzt offizielle customAction() API
- ✅ Fallback-Patches als zusätzliche Sicherheit

## 📊 Status Check

### Überprüfen ob System aktiv ist

```bash
# 1. Custom Action Handler vorhanden?
ls -la /var/www/portal.samix.one/custom/custom_action_handler.php
# → Sollte existieren

# 2. Templates vorhanden?
ls -la /var/www/portal.samix.one/custom/email_templates/
# → Sollte 6 .html Dateien zeigen

# 3. Syntax OK?
php -l /var/www/portal.samix.one/custom/custom_action_handler.php
# → "No syntax errors detected"

# 4. Test-Emails generieren
cd /var/www/portal.samix.one/custom
php test_email_templates.php
# → "🎉 Alle Templates erfolgreich generiert!"
```

### Live-Test

```
1. In ITFlow einloggen
2. Neues Ticket erstellen
3. Email überprüfen (sollte HTML sein)
4. Falls Plaintext: Siehe Troubleshooting
```

## 🛠️ Troubleshooting

### Problem: Emails noch Plaintext

**Ursache**: Custom Action Handler greift nicht

**Lösung**:
```bash
# 1. Prüfe ob customAction() aufgerufen wird
grep -n "customAction('ticket_create'" /var/www/portal.samix.one/agent/post/ticket.php

# 2. Prüfe Logs
tail -f /var/www/portal.samix.one/logs/app.log

# 3. Aktiviere Fallback-Patch
# In: /var/www/scripts/patches/configs/patches.json
# Setze: "email_template_fallback_ticket_create" → "enabled": true

# 4. Wende Patch an
cd /var/www/scripts/patches
php apply_custom_patches.php --verbose
```

### Problem: Logo wird nicht angezeigt

**Ursache**: Logo nicht in ITFlow hochgeladen oder falscher Pfad

**Lösung**:
```bash
# 1. Prüfe Datenbank
mysql -u itflow -p itflow -e "SELECT company_logo FROM companies WHERE company_id = 1;"

# 2. Prüfe Datei
ls -la /var/www/portal.samix.one/uploads/settings/

# 3. Logo hochladen
# In ITFlow: Admin → Settings → Company → Company Logo

# 4. Test
cd /var/www/portal.samix.one/custom
php test_email_templates.php
# Prüfe ob Logo in HTML-Dateien erscheint
```

### Problem: Falsche Sprache

**Ursache**: Spracherkennung schlägt fehl

**Lösung**:
```bash
# Manuell Sprache setzen in custom_action_handler.php
# Ersetze:
$lang = detectLanguage($content);
# Mit:
$lang = 'de'; // oder 'en'
```

### Problem: Templates nicht gefunden

**Ursache**: Falscher Pfad oder Permissions

**Lösung**:
```bash
# Prüfe Permissions
chmod 644 /var/www/portal.samix.one/custom/email_templates/*.html
chmod 755 /var/www/portal.samix.one/custom/email_templates/

# Prüfe Pfade
ls -la /var/www/portal.samix.one/custom/email_templates/
```

## 📚 Dokumentation

### Vollständige Dokumentation
```
/var/www/portal.samix.one/custom/EMAIL_TEMPLATES_README.md
```

### Code-Dokumentation
Alle Funktionen sind inline dokumentiert:
- `email_template_helper.php` - Jede Funktion hat PHPDoc
- `custom_action_handler.php` - Alle Handler erklärt
- `translation_helper.php` - Übersetzungs-Arrays kommentiert

### Beispiele
```
/var/www/portal.samix.one/custom/email_templates/test_output/
```
Enthält 6 generierte HTML-Vorschauen zum Anschauen im Browser

## 🔄 Updates

### Nach ITFlow-Updates

1. **Testen**: Ticket erstellen, Email prüfen
2. **Falls Problem**: Fallback-Patches aktivieren (siehe Troubleshooting)
3. **Logs prüfen**: `tail -f /var/www/portal.samix.one/logs/app.log`

### Template-Updates

Templates im `/custom` Ordner können jederzeit editiert werden:
```bash
# Template bearbeiten
nano /var/www/portal.samix.one/custom/email_templates/ticket_created.html

# Test
cd /var/www/portal.samix.one/custom
php test_email_templates.php

# Preview im Browser
firefox custom/email_templates/test_output/ticket_created_de.html
```

Änderungen sind **sofort aktiv** (kein Restart nötig)!

## ✨ Features im Überblick

| Feature | Status | Beschreibung |
|---------|--------|--------------|
| HTML Emails | ✅ | Moderne responsive Emails statt Plaintext |
| Logo Embedding | ✅ | Firmenlogo als Base64 inline |
| Multi-Language | ✅ | Auto-Erkennung Deutsch/Englisch |
| Update-Sicher | ✅ | /custom Ordner, von Updates geschützt |
| Responsive Design | ✅ | Desktop & Mobile optimiert |
| Color Coding | ✅ | Status/Priorität mit Farb-Badges |
| Structured Tables | ✅ | Ticket-Details übersichtlich |
| View Button | ✅ | Prominenter "Ticket anzeigen" Button |
| Custom Actions | ✅ | ITFlow's offizielle API |
| Fallback Patches | ✅ | Zusätzliche Update-Sicherheit |
| Test Suite | ✅ | Standalone Test-Script |
| Documentation | ✅ | Vollständig dokumentiert |

## 📞 Support

### Logs
```bash
# Application Log
tail -f /var/www/portal.samix.one/logs/app.log

# Email Queue Log
tail -f /var/www/portal.samix.one/logs/mail.log

# Patch Log
tail -f /var/www/scripts/patches/logs/patches.log
```

### Debug Mode
```php
// In custom_action_handler.php aktivieren:
error_log("DEBUG: Ticket Create Handler triggered for ticket $ticket_id");
```

### Hilfreiche Befehle
```bash
# Email Queue prüfen
mysql -u itflow -p itflow -e "SELECT * FROM email_queue WHERE email_status = 0 LIMIT 5;"

# Templates validieren
php -l /var/www/portal.samix.one/custom/custom_action_handler.php
php -l /var/www/portal.samix.one/custom/email_template_helper.php

# Preview generieren
cd /var/www/portal.samix.one/custom
php test_email_templates.php

# Permissions checken
ls -la /var/www/portal.samix.one/custom/
```

## 🎯 Next Steps

### Optional: Weitere Templates

Weitere Email-Typen können hinzugefügt werden:
- Ticket Watcher hinzugefügt
- Ticket Merged
- Invoice erstellt
- Quote erstellt
- Payment erhalten

Siehe: `EMAIL_TEMPLATES_README.md` → "Beispiel: Eigenes Template erstellen"

### Optional: Branding anpassen

Farben, Fonts, Layout können angepasst werden in:
```
custom/email_templates/base_template.html
```

### Optional: Mehr Sprachen

Weitere Sprachen hinzufügen in:
```php
// email_template_helper.php → getEmailTranslations()
'ticket_created' => [
    'en' => [...],
    'de' => [...],
    'fr' => [...],  // Französisch hinzufügen
    'it' => [...]   // Italienisch hinzufügen
]
```

## ✅ Installation erfolgreich!

Das HTML Email Template System ist vollständig installiert und funktionsfähig.

**Nächste Schritte:**
1. ✅ Live-Test: Ticket erstellen und Email prüfen
2. ✅ Preview anzeigen: `firefox custom/email_templates/test_output/*.html`
3. ✅ Dokumentation lesen: `custom/EMAIL_TEMPLATES_README.md`
4. ✅ Optional: Farben/Branding anpassen

---

**Version**: 1.0  
**Datum**: 29.12.2025  
**Status**: ✅ Production Ready  
**Kompatibilität**: ITFlow Current Version
