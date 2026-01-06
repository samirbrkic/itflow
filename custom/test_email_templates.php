<?php
/**
 * Test Script für Email Templates
 * 
 * Testet das HTML Email Template System ohne echte Email zu versenden.
 * Generiert HTML-Vorschau-Dateien zum Überprüfen im Browser.
 * 
 * Usage:
 *   php test_email_templates.php
 * 
 * Output:
 *   Erstellt HTML-Dateien in /custom/email_templates/test_output/
 */

// Change to script directory
chdir(__DIR__);

// Load required files
require_once __DIR__ . '/email_template_helper.php';
require_once __DIR__ . '/translation_helper.php';

// Mock database connection if not available
if (!isset($mysqli)) {
    echo "⚠️  Warnung: Keine Datenbankverbindung. Nutze Mock-Daten.\n\n";
    $mysqli = null;
}

// Create test output directory
$output_dir = __DIR__ . '/email_templates/test_output';
if (!is_dir($output_dir)) {
    mkdir($output_dir, 0755, true);
}

echo "═══════════════════════════════════════════════════\n";
echo "  ITFlow HTML Email Template Tester\n";
echo "═══════════════════════════════════════════════════\n\n";

// Test data for different templates
$test_cases = [
    'ticket_created' => [
        'name' => 'Ticket Created (Deutsch)',
        'lang' => 'de',
        'data' => [
            'ticket_id' => 123,
            'ticket_prefix' => '#',
            'ticket_number' => 2025001,
            'ticket_subject' => 'Server nicht erreichbar',
            'ticket_details' => 'Der Hauptserver im Rechenzentrum ist seit heute Morgen nicht mehr erreichbar. Alle Dienste sind betroffen. <br><br>Fehler: Connection timeout<br>IP: 192.168.1.100',
            'ticket_priority' => 'High',
            'ticket_priority_lower' => 'high',
            'ticket_status' => 'Open',
            'ticket_created_at' => date('d.m.Y H:i'),
            'contact_name' => 'Max Mustermann',
            'contact_email' => 'max.mustermann@example.com',
            'assigned_to_name' => 'John Technikier',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=123&url_key=abc123',
            'priority_color' => '#fd7e14',
            'status_color' => '#007bff',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_created:en' => [
        'name' => 'Ticket Created (English)',
        'template' => 'ticket_created',
        'lang' => 'en',
        'data' => [
            'ticket_id' => 124,
            'ticket_prefix' => '#',
            'ticket_number' => 2025002,
            'ticket_subject' => 'Printer offline',
            'ticket_details' => 'The printer in the main office is showing as offline. We cannot print any documents.<br><br>Printer Model: HP LaserJet 5200<br>Location: Building A, Floor 2',
            'ticket_priority' => 'Medium',
            'ticket_priority_lower' => 'medium',
            'ticket_status' => 'Open',
            'ticket_created_at' => date('m/d/Y H:i'),
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane.smith@example.com',
            'assigned_to_name' => 'Tech Support Team',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=124&url_key=def456',
            'priority_color' => '#ffc107',
            'status_color' => '#007bff',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_reply' => [
        'name' => 'Ticket Reply (Deutsch)',
        'lang' => 'de',
        'data' => [
            'ticket_id' => 123,
            'ticket_prefix' => '#',
            'ticket_number' => 2025001,
            'ticket_subject' => 'Server nicht erreichbar',
            'ticket_status' => 'Working',
            'ticket_reply' => 'Wir haben das Problem identifiziert. Der Server hatte einen Netzwerk-Timeout.<br><br>Maßnahmen:<br>- Netzwerkkabel überprüft<br>- Router neu gestartet<br>- Server-Verbindung wiederhergestellt<br><br>Bitte testen Sie, ob Sie nun wieder Zugriff haben.',
            'reply_by_name' => 'John Technikier',
            'contact_name' => 'Max Mustermann',
            'contact_email' => 'max.mustermann@example.com',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=123&url_key=abc123',
            'status_color' => '#17a2b8',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_resolved' => [
        'name' => 'Ticket Resolved (Deutsch)',
        'lang' => 'de',
        'data' => [
            'ticket_id' => 123,
            'ticket_prefix' => '#',
            'ticket_number' => 2025001,
            'ticket_subject' => 'Server nicht erreichbar',
            'ticket_priority' => 'High',
            'ticket_priority_lower' => 'high',
            'ticket_status' => 'Resolved',
            'ticket_created_at' => date('d.m.Y H:i'),
            'assigned_to_name' => 'John Technikier',
            'contact_name' => 'Max Mustermann',
            'contact_email' => 'max.mustermann@example.com',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=123&url_key=abc123',
            'priority_color' => '#fd7e14',
            'status_color' => '#28a745',
            'autoclose_notice' => 'Dieses Ticket wird automatisch in 24 Stunden geschlossen, wenn keine weiteren Aktionen erfolgen.',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_closed_en' => [
        'name' => 'Ticket Closed (English)',
        'template' => 'ticket_closed',
        'lang' => 'en',
        'data' => [
            'ticket_id' => 124,
            'ticket_prefix' => '#',
            'ticket_number' => 2025002,
            'ticket_subject' => 'Printer offline',
            'ticket_priority' => 'Medium',
            'ticket_priority_lower' => 'medium',
            'ticket_status' => 'Closed',
            'ticket_closed_at' => date('m/d/Y H:i'),
            'assigned_to_name' => 'Tech Support Team',
            'contact_name' => 'Jane Smith',
            'contact_email' => 'jane.smith@example.com',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=124&url_key=def456',
            'priority_color' => '#ffc107',
            'status_color' => '#6c757d',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_closed_de' => [
        'name' => 'Ticket Closed (Deutsch)',
        'template' => 'ticket_closed',
        'lang' => 'de',
        'data' => [
            'ticket_id' => 128,
            'ticket_prefix' => '#',
            'ticket_number' => 2025005,
            'ticket_subject' => 'Monitor zeigt kein Bild',
            'ticket_priority' => 'High',
            'ticket_priority_lower' => 'high',
            'ticket_status' => 'Geschlossen',
            'ticket_closed_at' => date('d.m.Y H:i', strtotime('-3 hours')),
            'assigned_to_name' => 'Lisa IT-Support',
            'contact_name' => 'Stefan Weber',
            'contact_email' => 'stefan.weber@example.com',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=128&url_key=mno112',
            'priority_color' => '#dc3545',
            'status_color' => '#6c757d',
            'config_base_url' => 'portal.samix.one'
        ]
    ],
    'ticket_assigned' => [
        'name' => 'Ticket Assigned (Deutsch)',
        'lang' => 'de',
        'data' => [
            'ticket_id' => 125,
            'ticket_prefix' => '#',
            'ticket_number' => 2025003,
            'ticket_subject' => 'Neues Laptop Setup benötigt',
            'ticket_details' => 'Neuer Mitarbeiter startet nächste Woche. Laptop muss konfiguriert werden mit:<br>- Windows 11<br>- Office 365<br>- VPN-Client<br>- Firmen-Standardsoftware',
            'ticket_priority' => 'Low',
            'ticket_priority_lower' => 'low',
            'ticket_status' => 'Assigned',
            'ticket_created_at' => date('d.m.Y H:i'),
            'contact_name' => 'Tech Support Team',
            'assigned_to_name' => 'Sarah IT-Admin',
            'ticket_url' => 'https://portal.samix.one/guest/guest_view_ticket.php?ticket_id=125&url_key=ghi789',
            'priority_color' => '#28a745',
            'status_color' => '#007bff',
            'config_base_url' => 'portal.samix.one'
        ]
    ]
];

// Test each template
$results = [];
foreach ($test_cases as $template_key => $test) {
    echo "Testing: {$test['name']}\n";
    echo str_repeat("─", 50) . "\n";
    
    // Determine template name (use 'template' field if provided, otherwise use key without language suffix)
    $template_type = isset($test['template']) ? $test['template'] : explode(':', $template_key)[0];
    
    // Render template
    $html = renderEmailTemplate($template_type, $test['data'], $test['lang']);
    
    if ($html === false) {
        echo "❌ FAILED: Template konnte nicht gerendert werden\n\n";
        $results[$template_key] = false;
        continue;
    }
    
    // Save to file
    $filename = $template_type . '_' . $test['lang'] . '.html';
    $filepath = $output_dir . '/' . $filename;
    file_put_contents($filepath, $html);
    
    echo "✅ SUCCESS: Template gerendert\n";
    echo "   Output: $filepath\n";
    echo "   Size: " . number_format(strlen($html)) . " bytes\n\n";
    
    $results[$template_key] = true;
}

// Summary
echo "═══════════════════════════════════════════════════\n";
echo "  Test Summary\n";
echo "═══════════════════════════════════════════════════\n\n";

$success_count = count(array_filter($results));
$total_count = count($results);

echo "Templates tested: $total_count\n";
echo "Success: $success_count\n";
echo "Failed: " . ($total_count - $success_count) . "\n\n";

if ($success_count === $total_count) {
    echo "🎉 Alle Templates erfolgreich generiert!\n\n";
} else {
    echo "⚠️  Einige Templates konnten nicht generiert werden.\n\n";
}

echo "Preview-Dateien:\n";
echo "  Location: $output_dir/\n";
echo "  Files:\n";
foreach (glob($output_dir . '/*.html') as $file) {
    echo "    - " . basename($file) . "\n";
}

echo "\n";
echo "Zum Anzeigen im Browser:\n";
echo "  1. Öffne die HTML-Dateien in einem Browser\n";
echo "  2. Oder nutze: firefox $output_dir/*.html\n";
echo "\n";

echo "═══════════════════════════════════════════════════\n";
echo "  Test abgeschlossen!\n";
echo "═══════════════════════════════════════════════════\n";

?>
