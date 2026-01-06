#!/usr/bin/php
<?php
/**
 * Background Cleanup Script for Duplicate Emails
 * 
 * This script runs as a background process to delete ITFlow's default plain text emails
 * after our custom HTML email has been queued. It waits 3 seconds to ensure ITFlow's
 * email is in the queue before attempting to delete it.
 * 
 * Usage: php cleanup_duplicate_email_background.php "[#T-12345]" "customer@email.com"
 */

// Prevent direct browser access
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Check arguments
if ($argc < 3) {
    error_log('[Cleanup Background] Error: Missing arguments. Usage: php ' . basename(__FILE__) . ' "[#T-12345]" "email@example.com"');
    exit(1);
}

$ticket_pattern = $argv[1];
$contact_email = $argv[2];

// Wait 3 seconds for ITFlow to add its email to the queue
sleep(3);

// Load ITFlow config
require_once dirname(__DIR__) . '/config.php';

if (!isset($mysqli) || !$mysqli) {
    error_log('[Cleanup Background] Error: Database connection failed');
    exit(1);
}

// Escape parameters
$ticket_pattern_escaped = mysqli_real_escape_string($mysqli, $ticket_pattern);
$contact_email_escaped = mysqli_real_escape_string($mysqli, $contact_email);

// Delete ITFlow's plain text email
$result = mysqli_query($mysqli, "DELETE FROM email_queue 
    WHERE email_recipient = '$contact_email_escaped'
    AND email_subject LIKE '%$ticket_pattern_escaped%'
    AND email_content NOT LIKE '%<!DOCTYPE html%'
    AND email_content NOT LIKE '%<html%'
    AND email_sent_at IS NULL
    AND email_queued_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
");

if ($result) {
    $deleted_count = mysqli_affected_rows($mysqli);
    if ($deleted_count > 0) {
        error_log("[Cleanup Background] ✓ Deleted $deleted_count duplicate plain text email(s) for $ticket_pattern");
    }
} else {
    error_log('[Cleanup Background] Error: ' . mysqli_error($mysqli));
}

mysqli_close($mysqli);
exit(0);
?>
