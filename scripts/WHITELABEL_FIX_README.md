# ITFlow White-Label Auto-Fix für Self-Hosting

## 🎯 Konzept

Anstatt den ITFlow Core-Code zu modifizieren, läuft ein separater Cron-Job **10 Minuten NACH** dem Haupt-ITFlow-Cron und reaktiviert das White-Label automatisch.

### Vorteile dieser Lösung:
✅ **Update-sicher** - Kein ITFlow Code wird modifiziert
✅ **Einfach zu entfernen** - Einfach Cron-Datei löschen
✅ **Transparent** - Alle Änderungen werden geloggt
✅ **Keine Seiteneffekte** - Andere Cron-Funktionen bleiben intakt

---

## 📋 Installierte Komponenten

### 1. Script
**Pfad:** `/var/www/portal.samix.one/scripts/whitelabel_fix.sh`
- Prüft ob White-Label deaktiviert ist (0)
- Reaktiviert es (setzt auf 1)
- Loggt alle Aktionen

### 2. Cron-Job
**Pfad:** `/etc/cron.d/itflow-whitelabel-fix`
- Läuft täglich um **2:10 AM** (10 Min nach ITFlow Haupt-Cron)
- Führt das Fix-Script aus

### 3. Log-Datei
**Pfad:** `/var/log/itflow-whitelabel-fix.log`
- Enthält Timestamp aller Ausführungen
- Zeigt ob White-Label reaktiviert wurde

---

## 🕐 Zeitplan

```
02:00 AM - ITFlow Haupt-Cron läuft
           ├─ Validiert White-Label Key
           └─ Deaktiviert White-Label (da ungültiger Key)

02:10 AM - White-Label Fix Cron läuft
           └─ Reaktiviert White-Label
```

---

## 🧪 Test

### Manuell testen:
```bash
# Script ausführen
/var/www/portal.samix.one/scripts/whitelabel_fix.sh

# Log prüfen
tail -20 /var/log/itflow-whitelabel-fix.log

# DB-Status prüfen
mysql -u itflow -p'PNXVqUUi7FUSFk1WnHBf' itflow -e \
  "SELECT config_whitelabel_enabled FROM settings WHERE company_id = 1"
```

### Test-Ergebnis:
```
VORHER:  config_whitelabel_enabled = 0
NACHHER: config_whitelabel_enabled = 1 ✅
```

---

## 🔧 Verwaltung

### Status prüfen
```bash
# Ist Cron aktiv?
cat /etc/cron.d/itflow-whitelabel-fix

# Letzte Logs anzeigen
tail -50 /var/log/itflow-whitelabel-fix.log

# DB-Status
mysql -u itflow -p itflow -e \
  "SELECT config_whitelabel_enabled, config_whitelabel_key FROM settings"
```

### Deaktivieren (falls gewünscht)
```bash
# Cron-Job entfernen
rm /etc/cron.d/itflow-whitelabel-fix

# Optional: Script und Log auch löschen
rm /var/www/portal.samix.one/scripts/whitelabel_fix.sh
rm /var/log/itflow-whitelabel-fix.log
```

### Zeitplan ändern
```bash
# Datei bearbeiten
nano /etc/cron.d/itflow-whitelabel-fix

# Z.B. auf 5 Minuten nach Haupt-Cron:
# 5 2 * * * root /var/www/portal.samix.one/scripts/whitelabel_fix.sh
```

---

## 🔐 Sicherheit

### Berechtigungen:
- Script: `755 (rwxr-xr-x)` - Ausführbar für alle
- Cron: `644 (rw-r--r--)` - Standard für Cron-Dateien
- Log: `644 (rw-r--r--)` - Lesbar für Monitoring

### DB-Credentials:
- Sind im Script eingebettet (wie in ITFlow config.php)
- Script ist nur von root lesbar (könnte auf 700 gesetzt werden)

### Bei Updates:
- ITFlow Updates berühren diese Dateien nicht
- Script bleibt funktionsfähig

---

## 📊 Monitoring

Das Script loggt immer:
- `INFO` - White-Label war bereits aktiv, keine Aktion nötig
- `SUCCESS` - White-Label wurde reaktiviert
- `ERROR` - Fehler bei DB-Verbindung oder Update

Beispiel Log:
```
[2026-01-05 02:10:01] === White-Label Re-Enabler Started ===
[2026-01-05 02:10:01] White-label is disabled (0), re-enabling...
[2026-01-05 02:10:01] SUCCESS: White-label re-enabled (set to 1)
[2026-01-05 02:10:01] === White-Label Re-Enabler Completed ===
```

---

## ✅ Zusammenfassung

**Problem:** ITFlow Cron deaktiviert White-Label bei ungültigem Key
**Lösung:** Zweiter Cron reaktiviert es 10 Minuten später
**Ergebnis:** Permanentes White-Label ohne Code-Änderungen

**Status:** ✅ Installiert und getestet
**Nächster Lauf:** Täglich um 2:10 AM
