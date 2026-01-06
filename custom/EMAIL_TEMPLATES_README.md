# ITFlow Custom HTML Email Templates

## 📧 Übersicht

Dieses System ersetzt die Standard-Plaintext-Emails von ITFlow durch moderne, responsive HTML Email Templates mit:

- ✅ **Update-Sicherheit**: Liegt komplett im `/custom` Ordner, wird bei Updates NICHT überschrieben
- 🌍 **Multi-Language Support**: Automatische Spracherkennung (Deutsch/Englisch)
- 🎨 **Modernes Design**: Responsive HTML mit Logo, farblich getrennten Bereichen, Buttons
- 🔄 **Intelligente Integration**: Nutzt ITFlow's `customAction()` System + Fallback-Patches
- 📊 **Strukturierte Darstellung**: Ticket-Details in übersichtlichen Tabellen

## 📁 Dateien und Struktur

```
portal.samix.one/custom/
├── custom_action_handler.php          # Hauptlogik - fängt Email-Trigger ab
├── email_template_helper.php          # Helper-Funktionen für Templates
├── translation_helper.php             # Spracherkennung und Übersetzungen
└── email_templates/
    ├── base_template.html             # Basis-Template (Header, Footer, Layout)
    ├── ticket_created.html            # Neues Ticket erstellt
    ├── ticket_reply.html              # Antwort auf Ticket
    ├── ticket_resolved.html           # Ticket gelöst
    ├── ticket_closed.html             # Ticket geschlossen
    └── ticket_assigned.html           # Ticket zugewiesen
```

## 🚀 Funktionsweise

### 1. Custom Action Handler (Hauptmethode)

ITFlow ruft automatisch `customAction($trigger, $entity)` bei verschiedenen Events auf:

- `ticket_create` - Neues Ticket erstellt
- `ticket_reply_agent_public` - Öffentliche Agent-Antwort
- `ticket_assign` - Ticket zugewiesen
- `ticket_resolve` - Ticket gelöst
- `ticket_close` - Ticket geschlossen

Der `custom_action_handler.php` **aktualisiert** die bereits von ITFlow zur Email-Queue hinzugefügte Email mit dem HTML-Template.

### 2. Fallback-Patches

Falls `customAction()` in zukünftigen ITFlow-Updates nicht mehr funktioniert, gibt es Fallback-Patches in:

```
/var/www/scripts/patches/configs/patches.json
```

Diese sind **standardmäßig deaktiviert** (`"enabled": false`) und können bei Bedarf aktiviert werden.

## 🎨 Email Template Features

### Base Template (`base_template.html`)

- **Header**: Gradient-Hintergrund mit Logo (Base64-embedded) und Titel
- **Content**: Weißer Bereich mit Ticket-Details und Hauptinhalt
- **Footer**: Dunkelgrauer Bereich mit Firmen-Kontaktdaten
- **Responsive**: Optimiert für Desktop und Mobile
- **Dark Mode**: Unterstützt Dark Mode in kompatiblen Email-Clients

### Ticket-Details-Tabelle

Alle Templates zeigen strukturierte Ticket-Informationen:

| Label | Wert |
|-------|------|
| Ticket | #2025-001 |
| Betreff | Server nicht erreichbar |
| Status | Open (Badge mit Farbe) |
| Priorität | High (Badge mit Farbe) |
| Zugewiesen an | Max Mustermann |
| Erstellt | 29.12.2025 14:30 |

### View Ticket Button

Prominenter Button mit Gradient und Hover-Effekt führt direkt zum Ticket-Portal.

## 🌍 Multi-Language Support

### Automatische Spracherkennung

Die Funktion `detectLanguage()` analysiert den Ticket-Inhalt und erkennt automatisch die Sprache:

```php
$lang = detectLanguage($ticket_details . ' ' . $ticket_subject);
// Returns: 'de' or 'en'
```

### Übersetzte Email-Texte

Alle UI-Strings werden automatisch übersetzt:

**Deutsch:**
- "Neues Ticket erstellt"
- "Hallo Max,"
- "Ein neues Support-Ticket wurde für Sie erstellt."
- "Ticket anzeigen"

**Englisch:**
- "New Ticket Created"
- "Hello Max,"
- "A new support ticket has been created for you."
- "View Ticket"

## 🔧 Anpassung der Templates

### Logo ändern

Das Logo wird automatisch aus den ITFlow-Einstellungen geladen und als Base64 eingebettet:

```php
function getCompanyLogoBase64() {
    // Lädt Logo aus: /uploads/settings/{company_logo}
    // Konvertiert zu: data:image/png;base64,iVBORw0KG...
}
```

### Farben anpassen

In `base_template.html`:

```css
/* Header Gradient */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Footer */
background-color: #2d3748;

/* Status Badge Farben */
.badge-status { background-color: #007bff; }

/* Priority Farben */
getPriorityColor('High') => '#fd7e14'
```

### Neue Templates hinzufügen

1. **Template erstellen**: `/custom/email_templates/my_template.html`

2. **Übersetzungen hinzufügen** in `email_template_helper.php`:

```php
'my_template' => [
    'en' => [
        'title' => 'My Custom Email',
        'intro' => 'This is a custom email...'
    ],
    'de' => [
        'title' => 'Meine Custom Email',
        'intro' => 'Dies ist eine Custom Email...'
    ]
]
```

3. **Handler hinzufügen** in `custom_action_handler.php`:

```php
case 'my_custom_trigger':
    handleMyCustomEmail($entity);
    break;
```

## 🔍 Template-Variablen

Alle Templates können folgende Variablen verwenden:

### Ticket-Variablen
- `{{TICKET_PREFIX}}` - z.B. "#"
- `{{TICKET_NUMBER}}` - z.B. "2025-001"
- `{{TICKET_SUBJECT}}` - Betreff
- `{{TICKET_DETAILS}}` - Details (formatiert)
- `{{TICKET_STATUS}}` - Status Name
- `{{TICKET_PRIORITY}}` - Low/Medium/High/Critical
- `{{TICKET_URL}}` - Link zum Portal
- `{{TICKET_CREATED_AT}}` - Erstellungsdatum

### Kontakt-Variablen
- `{{CONTACT_NAME}}` - Name des Kontakts
- `{{CONTACT_EMAIL}}` - Email des Kontakts

### Firmen-Variablen
- `{{COMPANY_NAME}}` - Firmenname
- `{{COMPANY_LOGO_BASE64}}` - Logo als Base64
- `{{COMPANY_PHONE}}` - Telefonnummer
- `{{COMPANY_EMAIL}}` - Email-Adresse
- `{{COMPANY_WEBSITE}}` - Website

### Übersetzungs-Variablen
- `{{GREETING}}` - "Hello" / "Hallo"
- `{{INTRO}}` - Einleitungstext
- `{{VIEW_BUTTON}}` - "View Ticket" / "Ticket anzeigen"
- `{{FOOTER_TEXT}}` - Footer-Text

### Farb-Variablen
- `{{PRIORITY_COLOR}}` - Hex-Farbe für Priorität
- `{{STATUS_COLOR}}` - Hex-Farbe für Status

## 🛠️ Wartung und Updates

### Nach ITFlow-Updates

1. **Test durchführen**: Ticket erstellen und Email überprüfen
2. **Falls Emails nicht HTML**: Custom Action Handler funktioniert nicht mehr
3. **Fallback aktivieren**:

```bash
cd /var/www/scripts/patches/configs
# Öffne patches.json
# Setze "email_template_fallback_ticket_create" auf "enabled": true
```

4. **Patches anwenden**:

```bash
cd /var/www/scripts/patches
php apply_custom_patches.php --verbose
```

### Logs überprüfen

```bash
# Custom Action Handler Fehler
tail -f /var/www/portal.samix.one/logs/app.log

# Email Queue Fehler
tail -f /var/www/portal.samix.one/logs/mail.log

# Patch-Script Logs
tail -f /var/www/scripts/patches/logs/patches.log
```

### Debugging

In `custom_action_handler.php` Logging hinzufügen:

```php
function handleTicketCreateEmail($ticket_id) {
    // Debug-Logging
    error_log("Custom Email Handler: ticket_create triggered for ticket $ticket_id");
    
    // ... rest of code
    
    if ($html_body === false) {
        error_log("Custom Email Handler: Template rendering failed");
        return;
    }
    
    error_log("Custom Email Handler: HTML email generated successfully");
}
```

## 📊 Template-Rendering-Prozess

```
1. Trigger Event (z.B. Ticket erstellt)
   ↓
2. ITFlow erstellt Standard Email und fügt zur Queue hinzu
   ↓
3. ITFlow ruft customAction('ticket_create', $ticket_id) auf
   ↓
4. custom_action_handler.php wird geladen
   ↓
5. handleTicketCreateEmail($ticket_id) wird ausgeführt
   ↓
6. Ticket-Daten aus DB laden
   ↓
7. Sprache erkennen mit detectLanguage()
   ↓
8. Template laden: ticket_created.html
   ↓
9. Variablen ersetzen
   ↓
10. Content in base_template.html einbetten
   ↓
11. Email in Queue aktualisieren (UPDATE)
   ↓
12. Cron sendet HTML Email via PHPMailer
```

## 🎯 Template-Typen

### ticket_created.html
- **Zweck**: Bestätigung für neues Ticket
- **Empfänger**: Ticket-Kontakt
- **Features**: Vollständige Ticket-Details, Reply-Instruction-Line
- **Farbe**: Blau (Standard)

### ticket_reply.html
- **Zweck**: Neue Antwort auf Ticket
- **Empfänger**: Ticket-Kontakt
- **Features**: Antwort-Box (gelb hervorgehoben), Von-Name
- **Farbe**: Gelb/Gold für Reply-Box

### ticket_resolved.html
- **Zweck**: Ticket wurde gelöst
- **Empfänger**: Ticket-Kontakt
- **Features**: Grüner Hintergrund, Resolution-Details, Autoclose-Hinweis
- **Farbe**: Grün (Erfolg)

### ticket_closed.html
- **Zweck**: Ticket wurde geschlossen
- **Empfänger**: Ticket-Kontakt
- **Features**: Grauer Hintergrund, Dankes-Nachricht
- **Farbe**: Grau (Neutral)

### ticket_assigned.html
- **Zweck**: Ticket wurde zugewiesen
- **Empfänger**: Zugewiesener Agent
- **Features**: Blaue Info-Box, Kontakt-Details
- **Farbe**: Blau (Info)

## 🔒 Sicherheit

### XSS-Schutz

Alle Variablen werden escaped:

```php
$ticket_subject = sanitizeInput($row['ticket_subject']);
$contact_name = sanitizeInput($row['contact_name']);
```

### SQL Injection Schutz

Alle Queries verwenden prepared statements oder escaped inputs:

```php
mysqli_real_escape_string($mysqli, $subject)
```

### Email Injection Schutz

PHPMailer validiert automatisch Email-Adressen und Header.

## 📝 Beispiel: Eigenes Template erstellen

Erstelle ein Template für Ticket-Eskalation:

### 1. Template-Datei erstellen

`/var/www/portal.samix.one/custom/email_templates/ticket_escalated.html`:

```html
<!DOCTYPE html>
<html lang="{{LANG}}">
<body>
    <div style="background-color: #fee; border-left: 4px solid #f00; padding: 20px;">
        <h3>⚠️ Ticket Eskaliert</h3>
        <p>Dieses Ticket wurde eskaliert und benötigt dringende Aufmerksamkeit!</p>
    </div>
    
    <!-- Ticket Details -->
    <table>
        <tr><td>Ticket:</td><td>{{TICKET_PREFIX}}{{TICKET_NUMBER}}</td></tr>
        <tr><td>Betreff:</td><td>{{TICKET_SUBJECT}}</td></tr>
        <tr><td>Priorität:</td><td>{{TICKET_PRIORITY}}</td></tr>
    </table>
</body>
</html>
```

### 2. Übersetzungen hinzufügen

In `email_template_helper.php`, Funktion `getEmailTranslations()`:

```php
'ticket_escalated' => [
    'en' => [
        'title' => 'Ticket Escalated',
        'intro' => 'This ticket has been escalated and requires urgent attention.'
    ],
    'de' => [
        'title' => 'Ticket eskaliert',
        'intro' => 'Dieses Ticket wurde eskaliert und benötigt dringende Aufmerksamkeit.'
    ]
]
```

### 3. Handler erstellen

In `custom_action_handler.php`:

```php
case 'ticket_escalate':
    handleTicketEscalatedEmail($entity);
    break;

function handleTicketEscalatedEmail($ticket_id) {
    global $mysqli, $config_base_url;
    
    // Get ticket data
    $sql = mysqli_query($mysqli, "SELECT * FROM tickets WHERE ticket_id = $ticket_id");
    $ticket = mysqli_fetch_assoc($sql);
    
    // Detect language
    $lang = detectLanguage($ticket['ticket_details']);
    
    // Prepare data
    $data = [
        'ticket_prefix' => $ticket['ticket_prefix'],
        'ticket_number' => $ticket['ticket_number'],
        'ticket_subject' => $ticket['ticket_subject'],
        'ticket_priority' => $ticket['ticket_priority'],
        // ... mehr Variablen
    ];
    
    // Render template
    $html = renderTicketEmail('ticket_escalated', $data, $lang);
    
    // Send email or update queue
    // ...
}
```

### 4. Trigger aufrufen

In der Datei wo Eskalation stattfindet:

```php
customAction('ticket_escalate', $ticket_id);
```

## 🆘 Troubleshooting

### Emails werden nicht als HTML versendet

**Check 1**: Ist `custom_action_handler.php` vorhanden?
```bash
ls -la /var/www/portal.samix.one/custom/custom_action_handler.php
```

**Check 2**: Wird `customAction()` aufgerufen?
```bash
grep -n "customAction" /var/www/portal.samix.one/agent/post/ticket.php
```

**Check 3**: Syntax-Fehler in custom_action_handler.php?
```bash
php -l /var/www/portal.samix.one/custom/custom_action_handler.php
```

### Template wird nicht geladen

**Check**: Template-Datei vorhanden?
```bash
ls -la /var/www/portal.samix.one/custom/email_templates/
```

**Fix**: Permissions setzen
```bash
chmod 644 /var/www/portal.samix.one/custom/email_templates/*.html
```

### Logo wird nicht angezeigt

**Check 1**: Logo in Datenbank vorhanden?
```sql
SELECT company_logo FROM companies WHERE company_id = 1;
```

**Check 2**: Logo-Datei existiert?
```bash
ls -la /var/www/portal.samix.one/uploads/settings/
```

**Fix**: Logo in ITFlow hochladen unter Admin → Settings → Company

### Emails bleiben in Queue stecken

**Check**: Email Queue
```sql
SELECT * FROM email_queue WHERE email_status = 0 ORDER BY email_id DESC LIMIT 10;
```

**Check**: Cron läuft?
```bash
ps aux | grep mail_queue
```

**Manual**: Email Queue manuell verarbeiten
```bash
cd /var/www/portal.samix.one/cron
php mail_queue.php
```

## 📚 Weiterführende Dokumentation

- **ITFlow Custom Actions**: `/portal.samix.one/custom/README.md`
- **Patch System**: `/scripts/patches/README.md`
- **Translation Helper**: `/portal.samix.one/custom/translation_helper.php` (Inline-Dokumentation)
- **Email Template Helper**: `/portal.samix.one/custom/email_template_helper.php` (Inline-Dokumentation)

## ✅ Vorteile dieses Systems

1. **Update-Sicher**: Alle Dateien im `/custom` Ordner, von Git ignoriert
2. **Keine Code-Änderungen**: Nutzt ITFlow's offizielle `customAction()` API
3. **Fallback-System**: Patches als Backup falls API sich ändert
4. **Multi-Language**: Automatische Spracherkennung ohne Config
5. **Wartbar**: Klare Struktur, gut dokumentiert
6. **Erweiterbar**: Neue Templates einfach hinzufügbar
7. **Responsive**: Funktioniert auf allen Geräten
8. **Modern**: Gradient-Design, Buttons, strukturierte Tabellen

## 📅 Version

- **Version**: 1.0
- **Erstellt**: 29.12.2025
- **ITFlow Kompatibilität**: Getestet mit ITFlow aktueller Version
- **Autor**: Custom Development für Samix

## 🔄 Changelog

### Version 1.0 (29.12.2025)
- ✅ Initial Release
- ✅ Base Template mit responsive Design
- ✅ 5 Ticket Email Templates (created, reply, resolved, closed, assigned)
- ✅ Custom Action Handler Integration
- ✅ Multi-Language Support (DE/EN)
- ✅ Fallback-Patch System
- ✅ Base64 Logo-Embedding
- ✅ Vollständige Dokumentation

---

**Support**: Bei Fragen oder Problemen, siehe Troubleshooting-Sektion oder ITFlow-Logs überprüfen.
