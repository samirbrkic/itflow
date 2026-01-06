<?php
/**
 * Email Template Helper für ITFlow Custom Modifications
 * 
 * Dieses File liegt im /custom/ Verzeichnis und wird NICHT von Updates überschrieben.
 * 
 * Funktionen:
 * - Laden von HTML Email Templates
 * - Logo als Base64 einbetten
 * - Variable Replacement in Templates
 * - Multi-Language Support
 * - Email-Typ Detection
 * 
 * Usage in custom_action_handler.php:
 *   require_once __DIR__ . '/email_template_helper.php';
 *   $emailBody = renderEmailTemplate('ticket_created', $variables, $lang);
 */

require_once __DIR__ . '/translation_helper.php';

/**
 * Konvertiert das Company Logo zu Base64 für Inline-Embedding
 * 
 * @return string Base64-encoded Logo oder leerer String
 */
function getCompanyLogoBase64() {
    global $mysqli;
    
    // Fallback if no database connection
    if (!$mysqli) {
        return '';
    }
    
    // Get company logo from database
    $sql = mysqli_query($mysqli, "SELECT company_logo FROM companies WHERE company_id = 1 LIMIT 1");
    if ($sql && mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_assoc($sql);
        $company_logo = $row['company_logo'];
        
        if (!empty($company_logo)) {
            $logo_path = dirname(__DIR__) . "/uploads/settings/$company_logo";
            
            if (file_exists($logo_path)) {
                $image_data = file_get_contents($logo_path);
                
                // Detect MIME type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $logo_path);
                finfo_close($finfo);
                
                $base64 = base64_encode($image_data);
                return "data:$mime_type;base64,$base64";
            }
        }
    }
    
    return '';
}

/**
 * Holt Company Informationen aus der Datenbank
 * 
 * @return array Company Details
 */
function getCompanyInfo() {
    global $mysqli;
    
    // Fallback if no database connection
    $default_info = [
        'name' => 'IT Support',
        'phone' => '',
        'email' => '',
        'website' => '',
        'address' => '',
        'city' => '',
        'state' => '',
        'zip' => ''
    ];
    
    if (!$mysqli) {
        return $default_info;
    }
    
    $sql = mysqli_query($mysqli, "SELECT company_name, company_phone, company_phone_country_code, company_email, company_website, company_address, company_city, company_state, company_zip FROM companies WHERE company_id = 1 LIMIT 1");
    
    if ($sql && mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_assoc($sql);
        
        return [
            'name' => sanitizeInput($row['company_name']),
            'phone' => sanitizeInput(formatPhoneNumber($row['company_phone'], $row['company_phone_country_code'])),
            'email' => sanitizeInput($row['company_email']),
            'website' => sanitizeInput($row['company_website']),
            'address' => sanitizeInput($row['company_address']),
            'city' => sanitizeInput($row['company_city']),
            'state' => sanitizeInput($row['company_state']),
            'zip' => sanitizeInput($row['company_zip'])
        ];
    }
    
    return $default_info;
}

/**
 * Lädt ein Email Template von der Festplatte
 * 
 * @param string $template_name Name des Templates (z.B. 'ticket_created')
 * @return string Template HTML oder false bei Fehler
 */
function loadEmailTemplate($template_name) {
    $template_path = __DIR__ . "/email_templates/{$template_name}.html";
    
    if (file_exists($template_path)) {
        return file_get_contents($template_path);
    }
    
    return false;
}

/**
 * Ersetzt Variablen im Template
 * 
 * @param string $template HTML Template
 * @param array $variables Key-Value Array mit Variablen
 * @return string Processed Template
 */
function replaceTemplateVariables($template, $variables) {
    foreach ($variables as $key => $value) {
        // Replace {{KEY}} syntax
        $template = str_replace("{{" . strtoupper($key) . "}}", $value, $template);
        
        // Replace $key syntax (for backward compatibility)
        $template = str_replace('$' . $key, $value, $template);
    }
    
    return $template;
}

/**
 * Holt übersetzte Strings für Email Templates
 * 
 * @param string $template_type Template Typ (z.B. 'ticket_created')
 * @param string $lang Sprachcode (de, en)
 * @return array Übersetzungen
 */
function getEmailTranslations($template_type, $lang = 'en') {
    $translations = [
        'ticket_created' => [
            'en' => [
                'title' => 'New Ticket Created',
                'greeting' => 'Hello',
                'intro' => 'A new support ticket has been created for you.',
                'ticket_details_title' => 'Ticket Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Subject',
                'status_label' => 'Status',
                'priority_label' => 'Priority',
                'assigned_label' => 'Assigned to',
                'created_label' => 'Created',
                'view_button' => 'View Ticket',
                'footer_text' => 'This is an automated message from',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'contact_support' => 'Need help? Contact our support team.',
                'details_label' => 'Details'
            ],
            'de' => [
                'title' => 'Neues Ticket erstellt',
                'greeting' => 'Hallo',
                'intro' => 'Ein neues Support-Ticket wurde für Sie erstellt.',
                'ticket_details_title' => 'Ticket-Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Betreff',
                'status_label' => 'Status',
                'priority_label' => 'Priorität',
                'assigned_label' => 'Zugewiesen an',
                'created_label' => 'Erstellt',
                'view_button' => 'Ticket anzeigen',
                'footer_text' => 'Dies ist eine automatische Nachricht von',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'contact_support' => 'Benötigen Sie Hilfe? Kontaktieren Sie unser Support-Team.',
                'details_label' => 'Details'
            ]
        ],
        'ticket_reply' => [
            'en' => [
                'title' => 'New Reply to Your Ticket',
                'greeting' => 'Hello',
                'intro' => 'There is a new reply to your support ticket.',
                'ticket_details_title' => 'Ticket Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Subject',
                'status_label' => 'Status',
                'reply_title' => 'Reply',
                'reply_from' => 'From',
                'view_button' => 'View Ticket',
                'footer_text' => 'This is an automated message from',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>'
            ],
            'de' => [
                'title' => 'Neue Antwort zu Ihrem Ticket',
                'greeting' => 'Hallo',
                'intro' => 'Es gibt eine neue Antwort zu Ihrem Support-Ticket.',
                'ticket_details_title' => 'Ticket-Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Betreff',
                'status_label' => 'Status',
                'reply_title' => 'Antwort',
                'reply_from' => 'Von',
                'view_button' => 'Ticket anzeigen',
                'footer_text' => 'Dies ist eine automatische Nachricht von',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>'
            ]
        ],
        'ticket_resolved' => [
            'en' => [
                'title' => 'Ticket Resolved',
                'greeting' => 'Hello',
                'intro' => 'Your support ticket has been marked as resolved.',
                'ticket_details_title' => 'Ticket Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Subject',
                'status_label' => 'Status',
                'priority_label' => 'Priority',
                'assigned_label' => 'Assigned to',
                'created_label' => 'Created',
                'details_label' => 'Details',
                'resolution_title' => 'Resolution',
                'view_button' => 'View Ticket',
                'reopen_title' => 'Issue Not Resolved?',
                'reopen_text' => 'If your request/issue is resolved, you can simply ignore this email. If you need further assistance, please reply or click the button below to re-open the ticket.',
                'reopen_button' => 'Re-open Ticket',
                'footer_text' => 'This is an automated message from',
                'contact_support' => 'Need help? Contact our support team.',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'autoclose_notice' => 'This ticket will be automatically closed if no further action is taken.'
            ],
            'de' => [
                'title' => 'Ticket gelöst',
                'greeting' => 'Hallo',
                'intro' => 'Ihr Support-Ticket wurde als gelöst markiert.',
                'ticket_details_title' => 'Ticket-Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Betreff',
                'status_label' => 'Status',
                'priority_label' => 'Priorität',
                'assigned_label' => 'Zugewiesen an',
                'created_label' => 'Erstellt',
                'details_label' => 'Details',
                'resolution_title' => 'Lösung',
                'view_button' => 'Ticket anzeigen',
                'reopen_title' => 'Problem nicht gelöst?',
                'reopen_text' => 'Falls Ihre Anfrage/Ihr Problem gelöst ist, können Sie diese E-Mail einfach ignorieren. Falls Sie weitere Unterstützung benötigen, antworten Sie bitte oder klicken Sie auf die Schaltfläche unten, um das Ticket wieder zu öffnen.',
                'reopen_button' => 'Ticket wieder öffnen',
                'footer_text' => 'Dies ist eine automatische Nachricht von',
                'contact_support' => 'Benötigen Sie Hilfe? Kontaktieren Sie unser Support-Team.',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'autoclose_notice' => 'Dieses Ticket wird automatisch geschlossen, wenn keine weiteren Aktionen erfolgen.'
            ]
        ],
        'ticket_closed' => [
            'en' => [
                'title' => 'Ticket Closed',
                'greeting' => 'Hello',
                'intro' => 'Your support ticket has been successfully closed.',
                'ticket_details_title' => 'Ticket Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Subject',
                'status_label' => 'Status',
                'priority_label' => 'Priority',
                'assigned_label' => 'Assigned to',
                'closed_label' => 'Closed on',
                'view_button' => 'View Ticket',
                'footer_text' => 'This is an automated message from',
                'contact_support' => 'This ticket is now closed. Please do not reply to this email, as replies will not be recorded in our system and will not be visible to our support team. If you have further questions about the same topic, please create a new ticket and reference this ticket number: {{TICKET_PREFIX}}{{TICKET_NUMBER}}',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'thank_you' => 'Thank you for your trust.'
            ],
            'de' => [
                'title' => 'Ticket geschlossen',
                'greeting' => 'Hallo',
                'intro' => 'Ihr Support-Ticket wurde erfolgreich geschlossen.',
                'ticket_details_title' => 'Ticket-Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Betreff',
                'status_label' => 'Status',
                'priority_label' => 'Priorität',
                'assigned_label' => 'Zugewiesen an',
                'closed_label' => 'Geschlossen am',
                'view_button' => 'Ticket anzeigen',
                'footer_text' => 'Dies ist eine automatische Nachricht von',
                'contact_support' => 'Dieses Ticket ist nun geschlossen. Bitte antworten Sie nicht auf diese E-Mail, da Antworten nicht im System erfasst werden und für unser Support-Team nicht sichtbar sind. Bei weiteren Fragen zum selben Thema erstellen Sie bitte ein neues Ticket und referenzieren Sie dabei diese Ticketnummer: {{TICKET_PREFIX}}{{TICKET_NUMBER}}',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>',
                'thank_you' => 'Vielen Dank für Ihr Vertrauen.'
            ]
        ],
        'ticket_assigned' => [
            'en' => [
                'title' => 'Ticket Assigned',
                'greeting' => 'Hello',
                'intro' => 'A support ticket has been assigned to you.',
                'ticket_details_title' => 'Ticket Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Subject',
                'status_label' => 'Status',
                'priority_label' => 'Priority',
                'assigned_label' => 'Assigned to',
                'created_label' => 'Created',
                'details_label' => 'Details',
                'assignment_title' => 'Ticket Assignment',
                'assigned_to_label' => 'Assigned to',
                'assigned_notice' => 'This ticket has been assigned to you',
                'ticket_details_label' => 'Ticket Details',
                'view_button' => 'View Ticket',
                'footer_text' => 'This is an automated message from',
                'contact_support' => 'Need help? Contact our support team.',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>'
            ],
            'de' => [
                'title' => 'Ticket zugewiesen',
                'greeting' => 'Hallo',
                'intro' => 'Ein Support-Ticket wurde Ihnen zugewiesen.',
                'ticket_details_title' => 'Ticket-Details',
                'ticket_label' => 'Ticket',
                'subject_label' => 'Betreff',
                'status_label' => 'Status',
                'priority_label' => 'Priorität',
                'assigned_label' => 'Zugewiesen an',
                'created_label' => 'Erstellt',
                'details_label' => 'Details',
                'assignment_title' => 'Ticket-Zuweisung',
                'assigned_to_label' => 'Zugewiesen an',
                'assigned_notice' => 'Dieses Ticket wurde Ihnen zugewiesen',
                'ticket_details_label' => 'Ticket-Details',
                'view_button' => 'Ticket anzeigen',
                'footer_text' => 'Dies ist eine automatische Nachricht von',
                'contact_support' => 'Benötigen Sie Hilfe? Kontaktieren Sie unser Support-Team.',
                'reply_instruction' => '<i style="color: #808080">##- Please type your reply above this line -##</i>'
            ]
        ]
    ];
    
    if (!isset($translations[$template_type])) {
        return $translations['ticket_created'][$lang] ?? $translations['ticket_created']['en'];
    }
    
    if (!isset($translations[$template_type][$lang])) {
        $lang = 'en'; // Fallback
    }
    
    return $translations[$template_type][$lang];
}

/**
 * Rendert ein vollständiges Email Template mit allen Variablen
 * 
 * @param string $template_type Template Typ (z.B. 'ticket_created', 'ticket_reply')
 * @param array $data Ticket/Email Daten
 * @param string $lang Sprachcode (optional, wird automatisch erkannt)
 * @return string Vollständiges HTML Email
 */
function renderEmailTemplate($template_type, $data, $lang = null) {
    // Auto-detect language if not provided
    if ($lang === null) {
        $content_for_detection = ($data['ticket_details'] ?? '') . ' ' . ($data['ticket_subject'] ?? '') . ' ' . ($data['ticket_reply'] ?? '');
        $lang = detectLanguage($content_for_detection);
    }
    
    // Load specific content template
    $content_template = loadEmailTemplate($template_type);
    
    if ($content_template === false) {
        error_log("✗ renderEmailTemplate: Template '$template_type' not found");
        return false;
    }
    
    // Load base template (wrapper with header/footer)
    $base_template = loadEmailTemplate('base_template');
    
    if ($base_template === false) {
        error_log("✗ renderEmailTemplate: base_template.html not found");
        return false;
    }
    
    // Get translations
    $translations = getEmailTranslations($template_type, $lang);
    
    // Get company info
    $company = getCompanyInfo();
    
    // Get logo as base64
    $logo_base64 = getCompanyLogoBase64();
    
    // Replace placeholders in translation strings (especially contact_support)
    foreach ($translations as $key => $value) {
        if (is_string($value)) {
            // Replace ticket-related placeholders in translation strings
            foreach ($data as $data_key => $data_value) {
                $placeholder = '{{' . strtoupper($data_key) . '}}';
                $translations[$key] = str_replace($placeholder, $data_value, $translations[$key]);
            }
        }
    }
    
    // Prepare all variables for content template
    $variables = array_merge($data, $translations, [
        'company_name' => $company['name'],
        'company_phone' => $company['phone'],
        'company_email' => $company['email'],
        'company_website' => $company['website'],
        'company_logo_base64' => $logo_base64,
        'current_year' => date('Y'),
        'lang' => $lang
    ]);
    
    // Replace variables in content template first
    $rendered_content = replaceTemplateVariables($content_template, $variables);
    
    // Now wrap the rendered content in base template
    $variables['main_content'] = $rendered_content;
    $final_html = replaceTemplateVariables($base_template, $variables);
    
    return $final_html;
}

/**
 * Erstellt einen View Ticket Link
 */
function getTicketViewUrl($ticket_id, $url_key, $config_base_url) {
    return "https://$config_base_url/guest/guest_view_ticket.php?ticket_id=$ticket_id&url_key=$url_key";
}

/**
 * Formatiert Ticket Details für Email Anzeige
 */
function formatTicketDetailsForEmail($details) {
    // Remove "Email from:" prefix that ITFlow adds
    $details = preg_replace('/^Email from:.*?\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}:-\s*/i', '', $details);
    
    // Remove CSS inline style rules completely (e.g., "P {margin-top:0;margin-bottom:0;}")
    // This matches single letter/word followed by space and CSS block
    $details = preg_replace('/\b[A-Z]+\s*\{[^}]+\}\s*/i', '', $details);
    
    // Strip ALL HTML tags to avoid inline styles and broken formatting
    $details = strip_tags($details);
    
    // Decode HTML entities
    $details = html_entity_decode($details, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remove multiple spaces and normalize whitespace
    $details = preg_replace('/\s+/', ' ', $details);
    
    // Trim whitespace
    $details = trim($details);
    
    // Convert newlines to <br> for email display (after trimming)
    $details = nl2br($details, false);
    
    return $details;
}

/**
 * Priority Badge Farben
 */
function getPriorityColor($priority) {
    $colors = [
        'Low' => '#28a745',
        'Medium' => '#ffc107',
        'High' => '#fd7e14',
        'Critical' => '#dc3545'
    ];
    
    return $colors[$priority] ?? '#6c757d';
}

/**
 * Status Badge Farben
 */
function getStatusColor($status_name) {
    $colors = [
        'Open' => '#007bff',
        'Working' => '#17a2b8',
        'Awaiting Customer' => '#ffc107',
        'Resolved' => '#28a745',
        'Closed' => '#6c757d'
    ];
    
    return $colors[$status_name] ?? '#6c757d';
}

?>
