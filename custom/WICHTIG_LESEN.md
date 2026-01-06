# ITFlow Custom HTML Email Templates

## ✅ Was funktioniert (100% update-sicher):

- **Schöne HTML-E-Mails** mit Logo und professionellem Design
- **Multi-Language Support** (Deutsch/Englisch automatisch)
- **6 Email-Templates**: create, reply, assign, resolved, closed
- **Re-open Button** in resolved-Template
- **Email Parser Integration** (Antworten funktionieren)
- **Alle Dateien in /custom/** (überleben ITFlow-Updates)

## ⚠️ Bekanntes Problem: Doppelte E-Mails

### Das Problem:
Bei `ticket_resolve` und `ticket_close` bekommt der Kunde **2 E-Mails**:
1. **ITFlow Standard** (Plain Text, Englisch)
2. **Custom Template** (HTML, Deutsch/Englisch)

### Warum keine Lösung?

ITFlow sendet seine Standard-E-Mails **im gleichen Request** wie unser Custom Handler:

```
agent/post/ticket.php:
  Zeile 1166: customAction('ticket_resolve')  → Unser Handler läuft
  Zeile 1220: addToMailQueue()                → ITFlow fügt Plain-Text hinzu
  
  Beide E-Mails sind GLEICHZEITIG in der Queue!
```

### Getestete Lösungen (alle gescheitert):

❌ **DELETE Query nach INSERT**: Zu spät, ITFlow fügt E-Mail danach ein
❌ **register_shutdown_function()**: Läuft nicht in ITFlow's Request-Context
❌ **Background-Prozess (exec)**: Apache-Context hat keine Rechte
❌ **MySQL Trigger**: "Can't update table in stored function/trigger"
❌ **MySQL Event**: Läuft zu langsam (alle 10 Sek), E-Mails bereits versendet
❌ **Cron-Job**: Auch zu langsam (ITFlow versendet innerhalb 30 Sekunden)

### Die EINZIGE funktionierende Lösung (NICHT update-sicher):

ITFlow's E-Mail Code in `agent/post/ticket.php` auskommentieren:

```php
// Zeile ~1193 und ~2048 in agent/post/ticket.php
// addToMailQueue($data);  // <-- auskommentieren
```

**ABER**: Bei ITFlow-Updates wird diese Datei überschrieben → Duplikate kommen zurück!

## 💡 Empfehlung:

**Akzeptiere die doppelten E-Mails** für resolved/closed:

### Vorteile:
- ✅ 100% update-sicher
- ✅ Keine Wartung nötig
- ✅ Keine Fehleranfälligkeit

### Für Kunden:
Die meisten Kunden sind es gewohnt, mehrere Benachrichtigungen zu bekommen (siehe: Amazon, DHL, etc.). Die **HTML-Version ist eindeutig die Haupt-E-Mail** (professionell, auf Deutsch, mit Re-open Button).

## 🎯 Alternative: Nur eine E-Mail-Art verwenden

### Option A: Nur Custom HTML-E-Mails (NICHT update-sicher)
1. `agent/post/ticket.php` editieren
2. ITFlow's `addToMailQueue()` auskommentieren
3. **NACHTEIL**: Muss nach jedem ITFlow-Update wiederholt werden

### Option B: Nur ITFlow Standard-E-Mails
1. Custom Handler deaktivieren: `/custom/custom_action_handler.php` umbenennen
2. **NACHTEIL**: Verlust von HTML-Design, Multi-Language, Re-open Button

## 📊 Aktueller Status:

✅ **Alle Duplikat-Prevention Mechanismen entfernt**
✅ **System ist stabil und update-sicher**
✅ **HTML-E-Mails funktionieren perfekt**

⚠️ **Kunde bekommt 2 E-Mails** bei resolved/closed (bekanntes ITFlow-Design-Problem)

## 🔧 Dateien:

```
/var/www/portal.samix.one/custom/
├── custom_action_handler.php          ✅ Bereinigt, keine Cleanup-Versuche mehr
├── email_template_helper.php          ✅ Funktioniert perfekt
├── email_templates/
│   ├── base_template.html            ✅ Mit Base64 Logo
│   ├── ticket_created.html           ✅ Funktioniert (keine Duplikate)
│   ├── ticket_reply_public.html      ✅ Funktioniert (keine Duplikate)
│   ├── ticket_assigned.html          ✅ Funktioniert (keine Duplikate)
│   ├── ticket_resolved.html          ⚠️ Funktioniert, aber ITFlow sendet auch
│   └── ticket_closed.html            ⚠️ Funktioniert, aber ITFlow sendet auch
├── SETUP_DUPLICATE_EMAIL_PREVENTION.sh  ❌ Nicht mehr verwenden (funktioniert nicht)
├── cleanup_duplicate_emails.php         ❌ Nicht mehr verwenden (zu langsam)
└── README.md                              ⚠️ Veraltet

Dieser File (WICHTIG_LESEN.md) ist aktuell! ✅
```

## 🚀 Was jetzt?

**Empfehlung:** Einfach so lassen wie es ist!

- Kunden bekommen professionelle HTML-E-Mails ✅
- System ist 100% stabil und update-sicher ✅
- Die zusätzliche Plain-Text E-Mail ist unkritisch (Kunden können sie ignorieren)

Falls du die Duplikate **unbedingt** eliminieren willst, ist die einzige Option:
**Manuelles Editieren von `agent/post/ticket.php` nach jedem ITFlow-Update** (siehe oben).
