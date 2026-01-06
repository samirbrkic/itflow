# ITFlow Custom HTML Email Templates - Setup & Wartung

## 📁 Update-Sichere Struktur

Alle Dateien befinden sich in `/var/www/portal.samix.one/custom/` - dieser Ordner wird bei ITFlow-Updates nicht überschrieben.

### Enthaltene Dateien:
```
custom/
├── custom_action_handler.php         # Handler für Ticket-Events
├── email_template_helper.php         # Template-Engine & Übersetzungen
├── SETUP_DUPLICATE_EMAIL_PREVENTION.sh   # Setup-Script
├── cleanup_duplicate_emails.php      # Cron-basierter Cleanup (optional)
├── cleanup_duplicate_email_background.php  # Veraltet, nicht mehr verwendet
└── email_templates/
    ├── base_template.html            # Basis-Layout mit Logo
    ├── ticket_created.html           # Neues Ticket erstellt
    ├── ticket_reply_public.html      # Öffentliche Antwort
    ├── ticket_reply_agent_public.html # Agent-Antwort (öffentlich)
    ├── ticket_assigned.html          # Ticket zugewiesen
    ├── ticket_resolved.html          # Ticket gelöst (mit Re-open Button)
    └── ticket_closed.html            # Ticket geschlossen
```

## 🔄 Nach ITFlow-Updates

### Was passiert bei Updates?

✅ **Wird NICHT überschrieben:**
- `/custom/` Ordner und alle Dateien darin
- Deine HTML-Templates
- PHP-Handler

❌ **Wird NICHT automatisch wiederhergestellt:**
- MySQL Event `cleanup_duplicate_ticket_emails`
- Cron-Jobs (falls verwendet)

### Nach jedem Update ausführen:

```bash
sudo bash /var/www/portal.samix.one/custom/SETUP_DUPLICATE_EMAIL_PREVENTION.sh
```

Dieses Script:
- Erstellt das MySQL Event neu
- Aktiviert den Event Scheduler
- Verifiziert die Installation

## 💾 Backup & Migration

### Was muss gesichert werden?

1. **Kompletter `/custom/` Ordner:**
   ```bash
   tar -czf custom_backup.tar.gz /var/www/portal.samix.one/custom/
   ```

2. **MySQL Event (optional, wird durch Setup-Script neu erstellt):**
   ```bash
   mysqldump -u root itflow --routines --triggers --events > itflow_with_events.sql
   ```

### Nach Migration auf neues System:

1. ITFlow installieren
2. `/custom/` Ordner wiederherstellen
3. Setup-Script ausführen:
   ```bash
   sudo bash /var/www/portal.samix.one/custom/SETUP_DUPLICATE_EMAIL_PREVENTION.sh
   ```

## ⚙️ Wie funktioniert das Duplikat-Prevention?

### Problem:
ITFlow sendet standardmäßig Plain-Text E-Mails bei `ticket_resolve` und `ticket_close`. Unser Custom Handler sendet gleichzeitig HTML-Versionen → Kunde bekommt 2 E-Mails.

### Lösung:
**MySQL Event** läuft alle 10 Sekunden und löscht Plain-Text E-Mails, wenn eine HTML-Version mit derselben Ticket-ID existiert.

```sql
-- Findet Plain-Text E-Mails
WHERE email_content NOT LIKE '%<!DOCTYPE html%'
-- Mit Ticket-ID im Betreff
AND email_subject REGEXP '\\[#T-[0-9]+\\]'
-- Wenn HTML-Version existiert
AND EXISTS (SELECT 1 FROM email_queue e2 WHERE ...)
```

### Event Status prüfen:
```bash
mysql -u root itflow -e "SHOW EVENTS WHERE Name = 'cleanup_duplicate_ticket_emails'\G"
```

### Event Scheduler Status:
```bash
mysql -u root itflow -e "SELECT @@event_scheduler;"
```

## 🐛 Troubleshooting

### Doppelte E-Mails trotz Setup?

1. **Event Scheduler prüfen:**
   ```bash
   mysql -u root itflow -e "SET GLOBAL event_scheduler = ON;"
   ```

2. **Event neu erstellen:**
   ```bash
   sudo bash /var/www/portal.samix.one/custom/SETUP_DUPLICATE_EMAIL_PREVENTION.sh
   ```

3. **Manuell in Queue prüfen:**
   ```sql
   SELECT email_id, email_subject, 
          email_content LIKE '%<!DOCTYPE%' as is_html,
          email_sent_at 
   FROM email_queue 
   WHERE email_queued_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
   ORDER BY email_queued_at DESC;
   ```

### HTTP 500 Fehler beim Ticket-Resolve?

Das passiert wenn ein MySQL Trigger aktiv ist (alte Konfiguration). Trigger entfernen:

```bash
mysql -u root itflow -e "DROP TRIGGER IF EXISTS prevent_duplicate_ticket_emails;"
```

Dann Setup-Script ausführen (verwendet Event statt Trigger).

### Keine E-Mails kommen an?

1. **SMTP-Konfiguration in ITFlow prüfen**
2. **Email Queue prüfen:**
   ```sql
   SELECT * FROM email_queue WHERE email_sent_at IS NULL ORDER BY email_id DESC LIMIT 10;
   ```

3. **ITFlow Cron läuft?**
   ```bash
   crontab -l | grep cron.php
   ```

## 📝 Technische Details

### Multi-Language Support

Automatische Sprach-Erkennung anhand:
- 60+ deutschen Keywords ("Anfrage", "Problem", "Ticket", etc.)
- Umlaute (ä, ö, ü, ß)
- Threshold: 2 Treffer = Deutsch, sonst Englisch

### Email Parser Integration

Alle Templates verwenden das korrekte Reply-Separator Format:

```html
<i style="color: #808080">##- Please type your reply above this line -##</i>
```

ITFlow erkennt dieses Format und schneidet alles darunter ab.

### Outlook-Kompatibilität

Alle Buttons verwenden VML (Vector Markup Language) für Outlook:

```html
<!--[if mso]>
<v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" ... >
<![endif]-->
<a href="..." style="...">Button Text</a>
<!--[if mso]></v:roundrect><![endif]-->
```

## 🔐 Sicherheit

- **SQL Injection Prevention:** Alle Variablen werden mit `mysqli_real_escape_string()` escaped
- **XSS Prevention:** `formatTicketDetailsForEmail()` entfernt gefährlichen HTML-Code
- **Email Validation:** `filter_var($email, FILTER_VALIDATE_EMAIL)` vor jedem Versand

## 📊 Logging

Fehler werden in `/var/log/apache2/error.log` geloggt:

```bash
tail -f /var/log/apache2/error.log | grep -i "email\|ticket"
```

## 🎨 Templates anpassen

1. HTML-Datei in `/custom/email_templates/` bearbeiten
2. Variablen im Format `{{VARIABLE_NAME}}` verwenden
3. Verfügbare Variablen siehe `email_template_helper.php`
4. Kein Neustart nötig - Änderungen sind sofort aktiv

### Verfügbare Variablen (Beispiel ticket_resolved):

- `{{LOGO_BASE64}}` - Logo embedded
- `{{CUSTOMER_NAME}}` - Kundenname
- `{{TICKET_NUMBER}}` - z.B. T-100045
- `{{TICKET_SUBJECT}}` - Betreff
- `{{TICKET_DETAILS}}` - Details (HTML)
- `{{TICKET_URL}}` - Link zum Guest Portal
- `{{REOPEN_URL}}` - Link zum Re-open
- `{{COMPANY_NAME}}` - Firmenname
- `{{SUPPORT_EMAIL}}` - Support E-Mail
- Translations: `{{title}}`, `{{greeting}}`, `{{closing}}`, etc.

## ⚠️ Bekannte Einschränkungen

### 1. Duplikat-Prevention nicht 100% garantiert

- Event läuft alle 10 Sekunden
- Wenn E-Mails in <10 Sekunden versendet werden, können beide ankommen
- In der Praxis sehr selten, da ITFlow's mail_queue Cron nur 1x/Minute läuft

### 2. Update-Sicherheit

- MySQL Events sind **nicht** in Standard-ITFlow-Backups
- Setup-Script muss nach Migration manuell ausgeführt werden
- Alternative: Duplikate akzeptieren (Kunde bekommt 2 E-Mails)

### 3. Keine Integration für andere E-Mail-Types

Derzeit nur implementiert für:
- ticket_created
- ticket_reply_public  
- ticket_reply_agent_public (mit Typo `reply_reply_agent_public`)
- ticket_assigned
- ticket_resolved
- ticket_closed

Andere ITFlow-E-Mails (z.B. Invoices, Quotes) verwenden weiterhin Standard-Templates.

## 📞 Support

Bei Problemen:
1. Error Log prüfen: `tail -f /var/log/apache2/error.log`
2. Setup-Script erneut ausführen
3. Event Status prüfen (siehe oben)

Entwickelt für ITFlow mit PHP 7.4+, MySQL/MariaDB
