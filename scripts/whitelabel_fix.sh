#!/bin/bash
#
# ITFlow White-Label Re-Enabler Script
# This script re-enables white-label after the main cron validation
# 
# Purpose: Allows self-hosted instances to keep white-label active
#          without modifying ITFlow core code (update-safe!)
#

# Database credentials (from config.php)
DB_HOST="localhost"
DB_USER="itflow"
DB_PASS="PNXVqUUi7FUSFk1WnHBf"
DB_NAME="itflow"

# Log file
LOG_FILE="/var/log/itflow-whitelabel-fix.log"

# Function to log with timestamp
log_message() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$LOG_FILE"
}

# Start
log_message "=== White-Label Re-Enabler Started ==="

# Check if white-label is disabled (0) and re-enable it
RESULT=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -N -e \
    "SELECT config_whitelabel_enabled FROM settings WHERE company_id = 1" 2>&1)

if [ $? -ne 0 ]; then
    log_message "ERROR: Database connection failed - $RESULT"
    exit 1
fi

if [ "$RESULT" = "0" ]; then
    log_message "White-label is disabled (0), re-enabling..."
    
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e \
        "UPDATE settings SET config_whitelabel_enabled = 1 WHERE company_id = 1" 2>&1
    
    if [ $? -eq 0 ]; then
        log_message "SUCCESS: White-label re-enabled (set to 1)"
    else
        log_message "ERROR: Failed to update database"
        exit 1
    fi
else
    log_message "INFO: White-label already enabled ($RESULT), no action needed"
fi

log_message "=== White-Label Re-Enabler Completed ==="
