#!/usr/bin/php
<?php
/**
 * Cleanup Duplicate Emails
 * 
 * Removes plain text duplicates from email_queue when HTML version exists
 * Run via cron: * * * * * /usr/bin/php /var/www/portal.samix.one/custom/cleanup_duplicate_emails.php
 */

// Load ITFlow config
require_once dirname(__DIR__) . '/config.php';

// Find all plain text emails that have HTML duplicates (match by ticket ID in subject)
$result = mysqli_query($mysqli, "
    SELECT e1.email_id, e1.email_subject, e1.email_recipient
    FROM email_queue e1
    WHERE e1.email_content NOT LIKE '%<!DOCTYPE html%'
    AND e1.email_content NOT LIKE '%<html%'
    AND e1.email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    AND e1.email_sent_at IS NULL
    AND e1.email_subject REGEXP '\\[#T-[0-9]+\\]'
    AND EXISTS (
        SELECT 1 
        FROM email_queue e2 
        WHERE e2.email_recipient = e1.email_recipient
        AND e2.email_subject REGEXP CONCAT('\\[#', SUBSTRING_INDEX(SUBSTRING_INDEX(e1.email_subject, '[#', -1), ']', 1), '\\]')
        AND (e2.email_content LIKE '%<!DOCTYPE html%' OR e2.email_content LIKE '%<html%')
        AND e2.email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        AND e2.email_sent_at IS NULL
        AND e2.email_id != e1.email_id
    )
");

$deleted_count = 0;
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        mysqli_query($mysqli, "DELETE FROM email_queue WHERE email_id = " . intval($row['email_id']));
        $deleted_count++;
        error_log("🗑️  [Cleanup] Removed duplicate plain text email ID " . $row['email_id'] . ": " . $row['email_subject']);
    }
}

if ($deleted_count > 0) {
    error_log("✓ [Cleanup] Removed $deleted_count duplicate plain text email(s)");
} else {
    // Silent when no duplicates found
    // error_log("[Cleanup] No duplicate emails found");
}

mysqli_close($mysqli);
?>
