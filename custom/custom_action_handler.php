<?php
/**
 * Custom Action Handler für ITFlow
 * 
 * Dieses File liegt im /custom/ Verzeichnis und wird NICHT von Updates überschrieben.
 * Es wird von der customAction() Funktion aufgerufen bei verschiedenen Trigger-Events.
 * 
 * Verfügbare Trigger:
 * - ticket_create: Neues Ticket erstellt
 * - ticket_reply_agent_public: Öffentliche Agent-Antwort
 * - ticket_reply_agent_internal: Interne Agent-Antwort
 * - ticket_update: Ticket aktualisiert
 * - ticket_assign: Ticket zugewiesen
 * - ticket_status_change: Status geändert
 * - ticket_close: Ticket geschlossen
 * - ticket_resolve: Ticket gelöst
 * 
 * Usage: Wird automatisch von functions.php aufgerufen via customAction($trigger, $entity)
 */

// Lade Helper Functions
require_once __DIR__ . '/email_template_helper.php';
require_once __DIR__ . '/translation_helper.php';

// Global database connection
global $mysqli;

/**
 * Main Handler Switch
 */

// Debug logging
error_log("========================================");
error_log("✓ custom_action_handler.php: customAction() triggered");
error_log("  Trigger: $trigger");
error_log("  Entity ID: $entity");
error_log("========================================");

switch($trigger) {
    
    case 'ticket_create':
        handleTicketCreateEmail($entity);
        break;
        
    case 'ticket_reply_agent_public':
        handleTicketReplyEmail($entity);
        break;
        
    // ITFlow hat einen Tippfehler in agent/post/ticket.php Zeile 1730
    // Es heißt dort "reply_reply_agent_public" statt "ticket_reply_agent_public"
    case 'reply_reply_agent_public':
        handleTicketReplyEmail($entity);
        break;
        
    case 'ticket_assign':
        handleTicketAssignedEmail($entity);
        break;
        
    case 'ticket_resolve':
        handleTicketResolvedEmail($entity);
        break;
        
    case 'ticket_close':
        handleTicketClosedEmail($entity);
        break;
        
    // Weitere Trigger können hier hinzugefügt werden
    default:
        // Kein Custom Handler für diesen Trigger
        break;
}

/**
 * Handler für ticket_create Trigger
 * 
 * WICHTIG: Dieser Handler überschreibt die Standard-Email-Logik!
 * Die Original-Email wird bereits in agent/post/ticket.php versendet.
 * Wir müssen die Email VORHER abfangen oder die Queue modifizieren.
 * 
 * Da customAction() NACH addToMailQueue() aufgerufen wird,
 * müssen wir die Queue-Einträge aktualisieren statt neue zu erstellen.
 */
function handleTicketCreateEmail($ticket_id) {
    global $mysqli, $config_ticket_from_email, $config_ticket_from_name, $config_base_url, $config_ticket_client_general_notifications, $config_smtp_host;
    
    // Check if emails are enabled
    if (empty($config_smtp_host) || $config_ticket_client_general_notifications != 1) {
        return;
    }
    
    // Get ticket details
    $sql = mysqli_query($mysqli, "
        SELECT 
            t.ticket_id,
            t.ticket_prefix,
            t.ticket_number,
            t.ticket_subject,
            t.ticket_details,
            t.ticket_priority,
            t.ticket_status,
            t.ticket_url_key,
            t.ticket_created_at,
            t.ticket_assigned_to,
            t.ticket_client_id,
            ts.ticket_status_name,
            c.contact_name,
            c.contact_email,
            cl.client_name,
            u.user_name as assigned_to_name
        FROM tickets t
        LEFT JOIN contacts c ON t.ticket_contact_id = c.contact_id
        LEFT JOIN clients cl ON t.ticket_client_id = cl.client_id
        LEFT JOIN ticket_statuses ts ON t.ticket_status = ts.ticket_status_id
        LEFT JOIN users u ON t.ticket_assigned_to = u.user_id
        WHERE t.ticket_id = $ticket_id
    ");
    
    if (!$sql || mysqli_num_rows($sql) == 0) {
        return;
    }
    
    $ticket = mysqli_fetch_assoc($sql);
    
    // Auto-detect language
    $content_for_detection = $ticket['ticket_details'] . ' ' . $ticket['ticket_subject'];
    $lang = detectLanguage($content_for_detection);
    
    // Get translations
    $trans = getEmailTranslations('ticket_created', $lang);
    
    // Get assigned user name (already in query result)
    $assigned_to_name = sanitizeInput($ticket['assigned_to_name'] ?? '');
    if (empty($assigned_to_name) && $ticket['ticket_assigned_to'] > 0) {
        $assigned_to_name = 'Assigned';
    }
    
    // Prepare template variables
    $template_data = [
        'ticket_id' => $ticket['ticket_id'],
        'ticket_prefix' => sanitizeInput($ticket['ticket_prefix']),
        'ticket_number' => intval($ticket['ticket_number']),
        'ticket_subject' => sanitizeInput($ticket['ticket_subject']),
        'ticket_details' => formatTicketDetailsForEmail($ticket['ticket_details']),
        'ticket_priority' => sanitizeInput($ticket['ticket_priority']),
        'ticket_priority_lower' => strtolower($ticket['ticket_priority']),
        'ticket_status' => sanitizeInput($ticket['ticket_status_name']),
        'ticket_created_at' => date('d.m.Y H:i', strtotime($ticket['ticket_created_at'])),
        'contact_name' => sanitizeInput($ticket['contact_name']),
        'contact_email' => sanitizeInput($ticket['contact_email']),
        'assigned_to_name' => $assigned_to_name,
        'ticket_url' => getTicketViewUrl($ticket['ticket_id'], $ticket['ticket_url_key'], $config_base_url),
        'priority_color' => getPriorityColor($ticket['ticket_priority']),
        'status_color' => getStatusColor($ticket['ticket_status_name']),
        'config_base_url' => $config_base_url
    ];
    
    // Render base template with ticket_created content
    $html_body = renderEmailTemplate('ticket_created', $template_data, $lang);
    
    if ($html_body === false) {
        // Fallback: Template nicht gefunden, Original Email lassen
        return;
    }
    
    // Update the most recent email in queue for this ticket
    // Die Original-Email wurde bereits zur Queue hinzugefügt, wir aktualisieren sie
    $subject = $trans['title'] . " [" . $ticket['ticket_prefix'] . $ticket['ticket_number'] . "] - " . $ticket['ticket_subject'];
    
    mysqli_query($mysqli, "
        UPDATE email_queue 
        SET 
            email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "',
            email_content = '" . mysqli_real_escape_string($mysqli, $html_body) . "'
        WHERE email_recipient = '" . mysqli_real_escape_string($mysqli, $ticket['contact_email']) . "'
        AND email_subject LIKE '%[" . mysqli_real_escape_string($mysqli, $ticket['ticket_prefix']) . mysqli_real_escape_string($mysqli, $ticket['ticket_number']) . "]%'
        AND email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY email_id DESC
        LIMIT 1
    ");
    
    // Also update watcher emails
    $sql_watchers = mysqli_query($mysqli, "SELECT watcher_email FROM ticket_watchers WHERE watcher_ticket_id = $ticket_id");
    while ($row = mysqli_fetch_array($sql_watchers)) {
        $watcher_email = sanitizeInput($row['watcher_email']);
        
        mysqli_query($mysqli, "
            UPDATE email_queue 
            SET 
                email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "',
                email_content = '" . mysqli_real_escape_string($mysqli, $html_body) . "'
            WHERE email_recipient = '" . mysqli_real_escape_string($mysqli, $watcher_email) . "'
            AND email_subject LIKE '%[" . mysqli_real_escape_string($mysqli, $ticket['ticket_prefix']) . mysqli_real_escape_string($mysqli, $ticket['ticket_number']) . "]%'
            AND email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY email_id DESC
            LIMIT 1
        ");
    }
}

/**
 * Handler für ticket_reply_agent_public Trigger
 * 
 * WICHTIG: ITFlow übergibt $ticket_id statt $ticket_reply_id!
 * Wir müssen die neueste Reply für dieses Ticket holen.
 */
function handleTicketReplyEmail($ticket_id) {
    global $mysqli, $config_ticket_from_email, $config_ticket_from_name, $config_base_url;
    
    error_log("✓ custom_action_handler: handleTicketReplyEmail() called for ticket_id=$ticket_id");
    
    // Get latest ticket reply for this ticket
    $sql = mysqli_query($mysqli, "
        SELECT 
            tr.ticket_reply_id,
            tr.ticket_reply,
            tr.ticket_reply_created_at,
            t.ticket_id,
            t.ticket_prefix,
            t.ticket_number,
            t.ticket_subject,
            t.ticket_status,
            t.ticket_url_key,
            ts.ticket_status_name,
            c.contact_name,
            c.contact_email,
            u.user_name as reply_by_name
        FROM ticket_replies tr
        LEFT JOIN tickets t ON tr.ticket_reply_ticket_id = t.ticket_id
        LEFT JOIN contacts c ON t.ticket_contact_id = c.contact_id
        LEFT JOIN ticket_statuses ts ON t.ticket_status = ts.ticket_status_id
        LEFT JOIN users u ON tr.ticket_reply_by = u.user_id
        WHERE t.ticket_id = $ticket_id
        AND tr.ticket_reply_type = 'Public'
        ORDER BY tr.ticket_reply_id DESC
        LIMIT 1
    ");
    
    if (!$sql || mysqli_num_rows($sql) == 0) {
        error_log("✗ custom_action_handler: No public ticket reply found for ticket_id=$ticket_id");
        return;
    }
    
    $reply = mysqli_fetch_assoc($sql);
    error_log("✓ custom_action_handler: Found ticket reply_id=" . $reply['ticket_reply_id'] . " for ticket #" . $reply['ticket_prefix'] . $reply['ticket_number']);
    
    // Auto-detect language
    $content_for_detection = $reply['ticket_reply'] . ' ' . $reply['ticket_subject'];
    $lang = detectLanguage($content_for_detection);
    
    // Get translations
    $trans = getEmailTranslations('ticket_reply', $lang);
    
    // Prepare template variables
    $template_data = [
        'ticket_id' => $reply['ticket_id'],
        'ticket_prefix' => sanitizeInput($reply['ticket_prefix']),
        'ticket_number' => intval($reply['ticket_number']),
        'ticket_subject' => sanitizeInput($reply['ticket_subject']),
        'ticket_status' => sanitizeInput($reply['ticket_status_name']),
        'ticket_reply' => formatTicketDetailsForEmail($reply['ticket_reply']),
        'reply_by_name' => sanitizeInput($reply['reply_by_name']),
        'contact_name' => sanitizeInput($reply['contact_name']),
        'contact_email' => sanitizeInput($reply['contact_email']),
        'ticket_url' => getTicketViewUrl($reply['ticket_id'], $reply['ticket_url_key'], $config_base_url),
        'status_color' => getStatusColor($reply['ticket_status_name']),
        'config_base_url' => $config_base_url
    ];
    
    // Render template
    $html_body = renderEmailTemplate('ticket_reply', $template_data, $lang);
    
    if ($html_body === false) {
        error_log("✗ custom_action_handler: Failed to render ticket_reply template");
        return;
    }
    
    error_log("✓ custom_action_handler: Rendered ticket_reply HTML (" . strlen($html_body) . " bytes) for ticket #" . $reply['ticket_prefix'] . $reply['ticket_number']);
    
    // Update email queue
    $subject = $trans['title'] . " [" . $reply['ticket_prefix'] . $reply['ticket_number'] . "] - " . $reply['ticket_subject'];
    
    $update_query = "
        UPDATE email_queue 
        SET 
            email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "',
            email_content = '" . mysqli_real_escape_string($mysqli, $html_body) . "'
        WHERE email_recipient = '" . mysqli_real_escape_string($mysqli, $reply['contact_email']) . "'
        AND email_subject LIKE '%[" . mysqli_real_escape_string($mysqli, $reply['ticket_prefix']) . mysqli_real_escape_string($mysqli, $reply['ticket_number']) . "]%'
        AND email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY email_id DESC
        LIMIT 1
    ";
    
    error_log("✓ custom_action_handler: Updating email queue for recipient: " . $reply['contact_email']);
    error_log("  Subject pattern: %[" . $reply['ticket_prefix'] . $reply['ticket_number'] . "]%");
    
    mysqli_query($mysqli, $update_query);
    
    $affected = mysqli_affected_rows($mysqli);
    if ($affected > 0) {
        error_log("✓ custom_action_handler: Successfully updated $affected email(s) in queue");
    } else {
        error_log("✗ custom_action_handler: No emails updated in queue (affected rows: $affected)");
        error_log("  This usually means the email was already sent or the WHERE conditions didn't match");
    }
}

/**
 * Handler für ticket_assign Trigger
 */
function handleTicketAssignedEmail($ticket_id) {
    global $mysqli, $config_ticket_from_email, $config_ticket_from_name, $config_base_url;
    
    // Get ticket details
    $sql = mysqli_query($mysqli, "
        SELECT 
            t.*,
            ts.ticket_status_name,
            c.contact_name,
            u.user_name as assigned_to_name,
            u.user_email as assigned_to_email
        FROM tickets t
        LEFT JOIN contacts c ON t.ticket_contact_id = c.contact_id
        LEFT JOIN ticket_statuses ts ON t.ticket_status = ts.ticket_status_id
        LEFT JOIN users u ON t.ticket_assigned_to = u.user_id
        WHERE t.ticket_id = $ticket_id
        AND t.ticket_assigned_to > 0
    ");
    
    if (!$sql || mysqli_num_rows($sql) == 0) {
        return;
    }
    
    $ticket = mysqli_fetch_assoc($sql);
    
    // Auto-detect language
    $lang = detectLanguage($ticket['ticket_details'] . ' ' . $ticket['ticket_subject']);
    
    // Get translations
    $trans = getEmailTranslations('ticket_assigned', $lang);
    
    // Prepare template variables
    $template_data = [
        'ticket_id' => $ticket['ticket_id'],
        'ticket_prefix' => sanitizeInput($ticket['ticket_prefix']),
        'ticket_number' => intval($ticket['ticket_number']),
        'ticket_subject' => sanitizeInput($ticket['ticket_subject']),
        'ticket_details' => formatTicketDetailsForEmail($ticket['ticket_details']),
        'ticket_priority' => sanitizeInput($ticket['ticket_priority']),
        'ticket_priority_lower' => strtolower($ticket['ticket_priority']),
        'ticket_status' => sanitizeInput($ticket['ticket_status_name']),
        'contact_name' => sanitizeInput($ticket['assigned_to_name']),
        'assigned_to_name' => sanitizeInput($ticket['assigned_to_name']),
        'ticket_url' => getTicketViewUrl($ticket['ticket_id'], $ticket['ticket_url_key'], $config_base_url),
        'priority_color' => getPriorityColor($ticket['ticket_priority']),
        'status_color' => getStatusColor($ticket['ticket_status_name']),
        'config_base_url' => $config_base_url
    ];
    
    // Render template
    $html_body = renderEmailTemplate('ticket_assigned', $template_data, $lang);
    
    if ($html_body === false) {
        return;
    }
    
    // Update email queue for assigned user
    $subject = $trans['title'] . " [" . $ticket['ticket_prefix'] . $ticket['ticket_number'] . "] - " . $ticket['ticket_subject'];
    
    mysqli_query($mysqli, "
        UPDATE email_queue 
        SET 
            email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "',
            email_content = '" . mysqli_real_escape_string($mysqli, $html_body) . "'
        WHERE email_recipient = '" . mysqli_real_escape_string($mysqli, $ticket['assigned_to_email']) . "'
        AND email_subject LIKE '%[" . mysqli_real_escape_string($mysqli, $ticket['ticket_prefix']) . mysqli_real_escape_string($mysqli, $ticket['ticket_number']) . "]%'
        AND email_queued_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY email_id DESC
        LIMIT 1
    ");
}

/**
 * Handler für ticket_resolve Trigger
 */
function handleTicketResolvedEmail($ticket_id) {
    global $mysqli, $config_ticket_from_email, $config_ticket_from_name, $config_base_url, $config_ticket_autoclose_hours, $config_smtp_host, $config_ticket_client_general_notifications;
    
    // Check if emails are enabled
    if (empty($config_smtp_host) || $config_ticket_client_general_notifications != 1) {
        return;
    }
    
    // Get ticket details with assigned user
    $sql = mysqli_query($mysqli, "
        SELECT 
            t.*,
            ts.ticket_status_name,
            c.contact_name,
            c.contact_email,
            u.user_name as assigned_to_name
        FROM tickets t
        LEFT JOIN contacts c ON t.ticket_contact_id = c.contact_id
        LEFT JOIN ticket_statuses ts ON t.ticket_status = ts.ticket_status_id
        LEFT JOIN users u ON t.ticket_assigned_to = u.user_id
        WHERE t.ticket_id = $ticket_id
    ");
    
    if (!$sql || mysqli_num_rows($sql) == 0) {
        return;
    }
    
    $ticket = mysqli_fetch_assoc($sql);
    
    // Validate contact email
    if (!filter_var($ticket['contact_email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    
    // Auto-detect language
    $lang = detectLanguage($ticket['ticket_details'] . ' ' . $ticket['ticket_subject']);
    
    // Get translations
    $trans = getEmailTranslations('ticket_resolved', $lang);
    
    // Get assigned user name
    $assigned_to_name = sanitizeInput($ticket['assigned_to_name'] ?? '');
    if (empty($assigned_to_name) && $ticket['ticket_assigned_to'] > 0) {
        $assigned_to_name = $trans['unassigned'] ?? 'Unassigned';
    }
    
    // Prepare template variables
    $template_data = [
        'ticket_id' => $ticket['ticket_id'],
        'ticket_prefix' => sanitizeInput($ticket['ticket_prefix']),
        'ticket_number' => intval($ticket['ticket_number']),
        'ticket_subject' => sanitizeInput($ticket['ticket_subject']),
        'ticket_status' => sanitizeInput($ticket['ticket_status_name']),
        'ticket_priority' => sanitizeInput($ticket['ticket_priority']),
        'ticket_created_at' => date('d.m.Y H:i', strtotime($ticket['ticket_resolved_at'] ?? $ticket['ticket_created'] ?? 'now')),
        'assigned_to_name' => $assigned_to_name,
        'contact_name' => sanitizeInput($ticket['contact_name']),
        'contact_email' => sanitizeInput($ticket['contact_email']),
        'ticket_url' => getTicketViewUrl($ticket['ticket_id'], $ticket['ticket_url_key'], $config_base_url),
        'reopen_url' => 'https://' . $config_base_url . '/guest/guest_view_ticket.php?ticket_id=' . $ticket['ticket_id'] . '&url_key=' . $ticket['ticket_url_key'],
        'status_color' => getStatusColor($ticket['ticket_status_name']),
        'priority_color' => getPriorityColor($ticket['ticket_priority']),
        'config_base_url' => $config_base_url
    ];
    
    // Add autoclose notice if configured
    if ($config_ticket_autoclose_hours > 0) {
        $autoclose_trans = $lang == 'de' 
            ? "Dieses Ticket wird automatisch in {$config_ticket_autoclose_hours} Stunden geschlossen, wenn keine weiteren Aktionen erfolgen."
            : "This ticket will be automatically closed in {$config_ticket_autoclose_hours} hours if no further action is taken.";
        $template_data['autoclose_notice'] = $autoclose_trans;
    }
    
    // Render template
    $html_body = renderEmailTemplate('ticket_resolved', $template_data, $lang);
    
    if ($html_body === false) {
        return;
    }
    
    // Add email to queue
    $subject = $trans['title'] . " [" . $ticket['ticket_prefix'] . $ticket['ticket_number'] . "] - " . $ticket['ticket_subject'];
    
    // Escape HTML body to prevent SQL injection
    $escaped_body = mysqli_real_escape_string($mysqli, $html_body);
    
    // Direct SQL INSERT to avoid mysqli_real_escape_string issues in addToMailQueue
    mysqli_query($mysqli, "INSERT INTO email_queue SET 
        email_recipient = '" . mysqli_real_escape_string($mysqli, $ticket['contact_email']) . "', 
        email_recipient_name = '" . mysqli_real_escape_string($mysqli, $ticket['contact_name']) . "', 
        email_from = '" . mysqli_real_escape_string($mysqli, $config_ticket_from_email) . "', 
        email_from_name = '" . mysqli_real_escape_string($mysqli, $config_ticket_from_name) . "', 
        email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "', 
        email_content = '$escaped_body', 
        email_queued_at = NOW()
    ");
}

/**
 * Handler für ticket_close Trigger
 */
function handleTicketClosedEmail($ticket_id) {
    global $mysqli, $config_ticket_from_email, $config_ticket_from_name, $config_base_url, $config_smtp_host, $config_ticket_client_general_notifications;
    
    // Check if emails are enabled
    if (empty($config_smtp_host) || $config_ticket_client_general_notifications != 1) {
        return;
    }
    
    // Get ticket details with assigned user
    $sql = mysqli_query($mysqli, "
        SELECT 
            t.*,
            ts.ticket_status_name,
            c.contact_name,
            c.contact_email,
            u.user_name as assigned_to_name
        FROM tickets t
        LEFT JOIN contacts c ON t.ticket_contact_id = c.contact_id
        LEFT JOIN ticket_statuses ts ON t.ticket_status = ts.ticket_status_id
        LEFT JOIN users u ON t.ticket_assigned_to = u.user_id
        WHERE t.ticket_id = $ticket_id
    ");
    
    if (!$sql || mysqli_num_rows($sql) == 0) {
        return;
    }
    
    $ticket = mysqli_fetch_assoc($sql);
    
    // Validate contact email
    if (!filter_var($ticket['contact_email'], FILTER_VALIDATE_EMAIL)) {
        return;
    }
    
    // Auto-detect language
    $lang = detectLanguage($ticket['ticket_details'] . ' ' . $ticket['ticket_subject']);
    
    // Get translations
    $trans = getEmailTranslations('ticket_closed', $lang);
    
    // Get assigned user name
    $assigned_to_name = sanitizeInput($ticket['assigned_to_name'] ?? '');
    if (empty($assigned_to_name) && $ticket['ticket_assigned_to'] > 0) {
        $assigned_to_name = $trans['unassigned'] ?? 'Unassigned';
    }
    
    // Prepare template variables
    $template_data = [
        'ticket_id' => $ticket['ticket_id'],
        'ticket_prefix' => sanitizeInput($ticket['ticket_prefix']),
        'ticket_number' => intval($ticket['ticket_number']),
        'ticket_subject' => sanitizeInput($ticket['ticket_subject']),
        'ticket_status' => sanitizeInput($ticket['ticket_status_name']),
        'ticket_priority' => sanitizeInput($ticket['ticket_priority']),
        'ticket_closed_at' => date('d.m.Y H:i', strtotime($ticket['ticket_closed'] ?? 'now')),
        'assigned_to_name' => $assigned_to_name,
        'contact_name' => sanitizeInput($ticket['contact_name']),
        'contact_email' => sanitizeInput($ticket['contact_email']),
        'ticket_url' => getTicketViewUrl($ticket['ticket_id'], $ticket['ticket_url_key'], $config_base_url),
        'status_color' => getStatusColor($ticket['ticket_status_name']),
        'priority_color' => getPriorityColor($ticket['ticket_priority']),
        'config_base_url' => $config_base_url
    ];
    
    // Render template
    $html_body = renderEmailTemplate('ticket_closed', $template_data, $lang);
    
    if ($html_body === false) {
        return;
    }
    
    // Add email to queue
    $subject = $trans['title'] . " [" . $ticket['ticket_prefix'] . $ticket['ticket_number'] . "] - " . $ticket['ticket_subject'];
    
    // Escape HTML body to prevent SQL injection
    $escaped_body = mysqli_real_escape_string($mysqli, $html_body);
    
    // Insert email directly into queue (bypassing addToMailQueue to avoid SQL issues)
    mysqli_query($mysqli, "INSERT INTO email_queue SET 
        email_recipient = '" . mysqli_real_escape_string($mysqli, $ticket['contact_email']) . "', 
        email_recipient_name = '" . mysqli_real_escape_string($mysqli, $ticket['contact_name']) . "', 
        email_from = '" . mysqli_real_escape_string($mysqli, $config_ticket_from_email) . "', 
        email_from_name = '" . mysqli_real_escape_string($mysqli, $config_ticket_from_name) . "', 
        email_subject = '" . mysqli_real_escape_string($mysqli, $subject) . "', 
        email_content = '$escaped_body', 
        email_queued_at = NOW()
    ");
}

/**
 * Hilfsfunktion: Rendert Email mit Base Template
 */
?>
