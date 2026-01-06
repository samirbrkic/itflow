<?php
/**
 * Translation Helper for ITFlow Custom Modifications
 * 
 * Dieses File liegt im /custom/ Verzeichnis und wird NICHT von Updates überschrieben.
 * Hier können wiederverwendbare Übersetzungs- und Lokalisierungsfunktionen definiert werden.
 * 
 * Usage in customAction():
 *   require_once __DIR__ . '/translation_helper.php';
 *   $translated = translateEmailTemplate('ticket_created', $lang);
 */

// Spracherkennung basierend auf Ticket-Inhalt
function detectLanguage($text) {
    // Erweiterte deutsche Keywords für bessere Erkennung
    $germanIndicators = [
        // Artikel
        'der', 'die', 'das', 'dem', 'den', 'des', 'ein', 'eine', 'einem', 'einer', 'eines',
        // Konjunktionen
        'und', 'oder', 'aber', 'weil', 'dass', 'wenn', 'als',
        // Pronomen
        'ich', 'du', 'er', 'sie', 'wir', 'ihr', 'mich', 'mir', 'sich',
        // Verben
        'ist', 'sind', 'war', 'waren', 'wurde', 'werden', 'hat', 'haben', 'kann', 'könnte', 'sollte',
        // Präpositionen
        'von', 'zu', 'mit', 'bei', 'nach', 'vor', 'für', 'auf', 'in', 'an', 'über', 'unter',
        // Andere häufige Wörter
        'nicht', 'auch', 'nur', 'noch', 'schon', 'sehr', 'mehr', 'alle', 'diese', 'dieser',
        // Höflichkeitsformen
        'bitte', 'danke', 'gerne', 'freundlich', 'gruß', 'grüße',
        // Ticket-spezifisch
        'ticket', 'problem', 'fehler', 'funktioniert', 'geht', 'gibt', 'brauche', 'benötige', 'hätte'
    ];
    
    $text_lower = mb_strtolower($text, 'UTF-8');
    $germanCount = 0;
    
    // Prüfe auf deutsche Umlaute (starker Indikator)
    if (preg_match('/[äöüÄÖÜß]/u', $text)) {
        $germanCount += 5; // Starker Bonus für Umlaute
    }
    
    // Zähle deutsche Keywords
    foreach ($germanIndicators as $indicator) {
        // Wortgrenze beachten für genauere Erkennung
        if (preg_match('/\b' . preg_quote($indicator, '/') . '\b/u', $text_lower)) {
            $germanCount++;
        }
    }
    
    // Debug-Log für Entwickler
    error_log("Language Detection: Found $germanCount German indicators in text: " . substr($text, 0, 100));
    
    // Wenn mindestens 2 deutsche Indikator-Wörter gefunden werden (oder Umlaute vorhanden)
    return $germanCount >= 2 ? 'de' : 'en';
}

// Email-Template-Übersetzungen
function getEmailTemplate($templateName, $lang = 'en') {
    $templates = [
        'ticket_created' => [
            'en' => [
                'subject' => 'New Ticket Created',
                'greeting' => 'Hello',
                'body' => 'A new ticket has been created.',
                'closing' => 'Best regards'
            ],
            'de' => [
                'subject' => 'Neues Ticket erstellt',
                'greeting' => 'Hallo',
                'body' => 'Ein neues Ticket wurde erstellt.',
                'closing' => 'Mit freundlichen Grüßen'
            ]
        ],
        'ticket_updated' => [
            'en' => [
                'subject' => 'Ticket Updated',
                'greeting' => 'Hello',
                'body' => 'Your ticket has been updated.',
                'closing' => 'Best regards'
            ],
            'de' => [
                'subject' => 'Ticket aktualisiert',
                'greeting' => 'Hallo',
                'body' => 'Ihr Ticket wurde aktualisiert.',
                'closing' => 'Mit freundlichen Grüßen'
            ]
        ],
        'ticket_closed' => [
            'en' => [
                'subject' => 'Ticket Closed',
                'greeting' => 'Hello',
                'body' => 'Your ticket has been closed.',
                'closing' => 'Best regards'
            ],
            'de' => [
                'subject' => 'Ticket geschlossen',
                'greeting' => 'Hallo',
                'body' => 'Ihr Ticket wurde geschlossen.',
                'closing' => 'Mit freundlichen Grüßen'
            ]
        ]
    ];
    
    if (!isset($templates[$templateName])) {
        return null;
    }
    
    if (!isset($templates[$templateName][$lang])) {
        $lang = 'en'; // Fallback auf Englisch
    }
    
    return $templates[$templateName][$lang];
}

// UI-String-Übersetzungen
function translateString($key, $lang = 'en') {
    $translations = [
        'main_issue' => [
            'en' => 'Main Issue',
            'de' => 'Hauptproblem'
        ],
        'actions_taken' => [
            'en' => 'Actions Taken',
            'de' => 'Durchgeführte Maßnahmen'
        ],
        'resolution' => [
            'en' => 'Resolution or Next Steps',
            'de' => 'Lösung oder nächste Schritte'
        ],
        'ticket_status' => [
            'en' => 'Ticket Status',
            'de' => 'Ticket-Status'
        ],
        'priority' => [
            'en' => 'Priority',
            'de' => 'Priorität'
        ],
        'category' => [
            'en' => 'Category',
            'de' => 'Kategorie'
        ],
        'assigned_to' => [
            'en' => 'Assigned to',
            'de' => 'Zugewiesen an'
        ],
        'created_by' => [
            'en' => 'Created by',
            'de' => 'Erstellt von'
        ],
        'last_updated' => [
            'en' => 'Last Updated',
            'de' => 'Zuletzt aktualisiert'
        ]
    ];
    
    if (!isset($translations[$key])) {
        return $key; // Fallback auf Key selbst
    }
    
    if (!isset($translations[$key][$lang])) {
        $lang = 'en'; // Fallback auf Englisch
    }
    
    return $translations[$key][$lang];
}

// Beispiel: Wrapper für addToMailQueue mit automatischer Spracherkennung
function addToMailQueueTranslated($params, $templateName = null) {
    // Erkenne Sprache aus Subject oder Body
    $textToAnalyze = ($params['subject'] ?? '') . ' ' . strip_tags($params['body'] ?? '');
    $lang = detectLanguage($textToAnalyze);
    
    // Wenn Template angegeben, verwende übersetztes Template
    if ($templateName) {
        $template = getEmailTemplate($templateName, $lang);
        if ($template) {
            // Ersetze Platzhalter im Template
            // Dies ist ein Beispiel - muss je nach Use-Case angepasst werden
            $params['subject'] = $params['subject'] ?? $template['subject'];
        }
    }
    
    // Originale addToMailQueue Funktion aufrufen
    // (Diese Funktion muss im globalen Scope verfügbar sein)
    if (function_exists('addToMailQueue')) {
        return addToMailQueue($params);
    }
    
    return false;
}

// Hilfsfunktion: Email-Body mit deutscher Übersetzung anreichern
function enrichEmailBodyWithGerman($englishBody, $germanTranslations = []) {
    // Fügt deutsche Übersetzungen zu einem englischen Email-Body hinzu
    // Format: "English Text / Deutsche Übersetzung"
    
    $enrichedBody = $englishBody;
    
    foreach ($germanTranslations as $english => $german) {
        $enrichedBody = str_replace($english, "$english / $german", $enrichedBody);
    }
    
    return $enrichedBody;
}

// Formatierungs-Helfer für AI-Prompts
function getAIPromptLanguageInstruction($detectedLang = null) {
    $instruction = "**IMPORTANT: Always respond in the same language as the ticket content.** ";
    
    if ($detectedLang === 'de') {
        $instruction .= "The ticket appears to be in German, so respond in German.";
    } elseif ($detectedLang === 'en') {
        $instruction .= "The ticket appears to be in English, so respond in English.";
    }
    
    return $instruction;
}

// Debug-Funktion für Custom-Code
function customLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/custom_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Beispiel-Integration in custom_action_handler.php:
 * 
 * <?php
 * require_once __DIR__ . '/translation_helper.php';
 * 
 * switch($trigger) {
 *     case 'ticket_create':
 *         customLog("Ticket created: $entity", "INFO");
 *         
 *         // Sprache erkennen und entsprechendes Email-Template verwenden
 *         $ticketContent = getTicketContent($entity); // Eigene Funktion
 *         $lang = detectLanguage($ticketContent);
 *         
 *         $template = getEmailTemplate('ticket_created', $lang);
 *         // ... Email senden mit übersetztem Template
 *         break;
 *         
 *     case 'ticket_update':
 *         customLog("Ticket updated: $entity", "INFO");
 *         break;
 * }
 */
