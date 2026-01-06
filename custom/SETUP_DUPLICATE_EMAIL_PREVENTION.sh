#!/bin/bash
###############################################################################
# Setup Script für Duplicate Email Prevention
# 
# WICHTIG: Dieses Script muss nach folgenden Ereignissen ausgeführt werden:
# - Nach ITFlow Updates
# - Nach System-Migration
# - Nach Datenbank-Wiederherstellung
#
# Verwendung: sudo bash SETUP_DUPLICATE_EMAIL_PREVENTION.sh
###############################################################################

echo "======================================"
echo "Duplicate Email Prevention - Setup"
echo "======================================"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "ERROR: Bitte als root ausführen (sudo)"
    exit 1
fi

# Database credentials
DB_NAME="itflow"
DB_USER="root"

echo "[1/3] Erstelle MySQL Event für Duplikat-Cleanup..."

mysql -u "$DB_USER" "$DB_NAME" << 'SQLEOF'
-- Enable event scheduler
SET GLOBAL event_scheduler = ON;

-- Drop existing event if present
DROP EVENT IF EXISTS cleanup_duplicate_ticket_emails;

DELIMITER $$

-- Create event to cleanup duplicates every 10 seconds
CREATE EVENT cleanup_duplicate_ticket_emails
ON SCHEDULE EVERY 10 SECOND
DO
BEGIN
    DELETE e1 FROM email_queue e1
    WHERE e1.email_content NOT LIKE '%<!DOCTYPE html%'
    AND e1.email_content NOT LIKE '%<html%'
    AND e1.email_sent_at IS NULL
    AND e1.email_queued_at >= DATE_SUB(NOW(), INTERVAL 2 MINUTE)
    AND e1.email_subject REGEXP '\\[#T-[0-9]+\\]'
    AND EXISTS (
        SELECT 1 FROM email_queue e2
        WHERE e2.email_recipient = e1.email_recipient
        AND e2.email_subject REGEXP CONCAT('\\[#', SUBSTRING_INDEX(SUBSTRING_INDEX(e1.email_subject, '[#', -1), ']', 1), '\\]')
        AND (e2.email_content LIKE '%<!DOCTYPE html%' OR e2.email_content LIKE '%<html%')
        AND e2.email_sent_at IS NULL
        AND e2.email_id != e1.email_id
    );
END$$

DELIMITER ;
SQLEOF

if [ $? -eq 0 ]; then
    echo "✓ MySQL Event erfolgreich erstellt"
else
    echo "✗ Fehler beim Erstellen des MySQL Events"
    exit 1
fi

echo ""
echo "[2/3] Verifiziere Event..."
mysql -u "$DB_USER" "$DB_NAME" -e "SHOW EVENTS WHERE Name = 'cleanup_duplicate_ticket_emails'\G" | grep -q "cleanup_duplicate_ticket_emails"

if [ $? -eq 0 ]; then
    echo "✓ Event ist aktiv"
else
    echo "✗ Event nicht gefunden"
    exit 1
fi

echo ""
echo "[3/3] Prüfe Event Scheduler Status..."
SCHEDULER_STATUS=$(mysql -u "$DB_USER" "$DB_NAME" -sN -e "SELECT @@event_scheduler;")

if [ "$SCHEDULER_STATUS" = "ON" ]; then
    echo "✓ Event Scheduler ist aktiviert"
else
    echo "⚠ Event Scheduler ist deaktiviert, aktiviere..."
    mysql -u "$DB_USER" "$DB_NAME" -e "SET GLOBAL event_scheduler = ON;"
fi

echo ""
echo "======================================"
echo "✓ Setup erfolgreich abgeschlossen!"
echo "======================================"
echo ""
echo "INFO: Das MySQL Event läuft jetzt alle 10 Sekunden und löscht"
echo "      Plain-Text Duplikate automatisch aus der E-Mail Queue."
echo ""
echo "HINWEIS: Dieses Script muss nach ITFlow-Updates oder System-Migration"
echo "         erneut ausgeführt werden, da MySQL Events nicht automatisch"
echo "         in Standard-Backups enthalten sind."
echo ""
echo "Backup-Tipp: Füge diese Zeile zu deinem Backup-Script hinzu:"
echo "  mysqldump -u root itflow --routines --triggers --events > backup.sql"
echo ""

exit 0
