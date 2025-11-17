<?php

defined('FROM_POST_HANDLER') || die("Direct file access is not allowed");

if (isset($_POST['edit_mail_smtp_settings'])) {
    
    validateCSRFToken($_POST['csrf_token']);

    $config_smtp_provider            = sanitizeInput($_POST['config_smtp_provider']);
    $config_smtp_host                = sanitizeInput($_POST['config_smtp_host']);
    $config_smtp_port                = intval($_POST['config_smtp_port'] ?? 0);
    $config_smtp_encryption          = sanitizeInput($_POST['config_smtp_encryption']);
    $config_smtp_username            = sanitizeInput($_POST['config_smtp_username']);
    $config_smtp_password            = sanitizeInput($_POST['config_smtp_password']);

    // Shared OAuth fields
    $config_mail_oauth_client_id     = sanitizeInput($_POST['config_mail_oauth_client_id']);
    $config_mail_oauth_client_secret = sanitizeInput($_POST['config_mail_oauth_client_secret']);
    $config_mail_oauth_tenant_id     = sanitizeInput($_POST['config_mail_oauth_tenant_id']);
    $config_mail_oauth_refresh_token = sanitizeInput($_POST['config_mail_oauth_refresh_token']);
    $config_mail_oauth_access_token  = sanitizeInput($_POST['config_mail_oauth_access_token']);

    mysqli_query($mysqli, "
        UPDATE settings SET
            config_smtp_provider              = '$config_smtp_provider',
            config_smtp_host                  = '$config_smtp_host',
            config_smtp_port                  = $config_smtp_port,
            config_smtp_encryption            = '$config_smtp_encryption',
            config_smtp_username              = '$config_smtp_username',
            config_smtp_password              = '$config_smtp_password',
            config_mail_oauth_client_id       = '$config_mail_oauth_client_id',
            config_mail_oauth_client_secret   = '$config_mail_oauth_client_secret',
            config_mail_oauth_tenant_id       = '$config_mail_oauth_tenant_id',
            config_mail_oauth_refresh_token   = '$config_mail_oauth_refresh_token',
            config_mail_oauth_access_token    = '$config_mail_oauth_access_token'
        WHERE company_id = 1
    ");

    logAction("Settings", "Edit", "$session_name edited SMTP settings");
    
    flash_alert("SMTP Mail Settings updated");
    
    redirect();

}

if (isset($_POST['edit_mail_imap_settings'])) {
    
    validateCSRFToken($_POST['csrf_token']);

    $config_imap_provider            = sanitizeInput($_POST['config_imap_provider']);
    $config_imap_host                = sanitizeInput($_POST['config_imap_host']);
    $config_imap_port                = intval($_POST['config_imap_port'] ?? 0);
    $config_imap_encryption          = sanitizeInput($_POST['config_imap_encryption']);
    $config_imap_username            = sanitizeInput($_POST['config_imap_username']);
    $config_imap_password            = sanitizeInput($_POST['config_imap_password']);

    // Shared OAuth fields
    $config_mail_oauth_client_id     = sanitizeInput($_POST['config_mail_oauth_client_id']);
    $config_mail_oauth_client_secret = sanitizeInput($_POST['config_mail_oauth_client_secret']);
    $config_mail_oauth_tenant_id     = sanitizeInput($_POST['config_mail_oauth_tenant_id']);
    $config_mail_oauth_refresh_token = sanitizeInput($_POST['config_mail_oauth_refresh_token']);
    $config_mail_oauth_access_token  = sanitizeInput($_POST['config_mail_oauth_access_token']);

    mysqli_query($mysqli, "
        UPDATE settings SET
            config_imap_provider              = '$config_imap_provider',
            config_imap_host                  = '$config_imap_host',
            config_imap_port                  = $config_imap_port,
            config_imap_encryption            = '$config_imap_encryption',
            config_imap_username              = '$config_imap_username',
            config_imap_password              = '$config_imap_password',
            config_mail_oauth_client_id       = '$config_mail_oauth_client_id',
            config_mail_oauth_client_secret   = '$config_mail_oauth_client_secret',
            config_mail_oauth_tenant_id       = '$config_mail_oauth_tenant_id',
            config_mail_oauth_refresh_token   = '$config_mail_oauth_refresh_token',
            config_mail_oauth_access_token    = '$config_mail_oauth_access_token'
        WHERE company_id = 1
    ");

    logAction("Settings", "Edit", "$session_name edited IMAP settings");
    
    flash_alert("IMAP Mail Settings updated");
    
    redirect();

}

if (isset($_POST['edit_mail_from_settings'])) {

    validateCSRFToken($_POST['csrf_token']);

    $config_mail_from_email = sanitizeInput(filter_var($_POST['config_mail_from_email'], FILTER_VALIDATE_EMAIL));
    $config_mail_from_name = sanitizeInput(preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['config_mail_from_name']));

    $config_invoice_from_email = sanitizeInput(filter_var($_POST['config_invoice_from_email'], FILTER_VALIDATE_EMAIL));
    $config_invoice_from_name = sanitizeInput(preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['config_invoice_from_name']));

    $config_quote_from_email = sanitizeInput(filter_var($_POST['config_quote_from_email'], FILTER_VALIDATE_EMAIL));
    $config_quote_from_name = sanitizeInput(preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['config_quote_from_name']));

    $config_ticket_from_email = sanitizeInput(filter_var($_POST['config_ticket_from_email'], FILTER_VALIDATE_EMAIL));
    $config_ticket_from_name = sanitizeInput(preg_replace('/[^a-zA-Z0-9\s]/', '', $_POST['config_ticket_from_name']));

    mysqli_query($mysqli,"UPDATE settings SET config_mail_from_email = '$config_mail_from_email', config_mail_from_name = '$config_mail_from_name', config_invoice_from_email = '$config_invoice_from_email', config_invoice_from_name = '$config_invoice_from_name', config_quote_from_email = '$config_quote_from_email', config_quote_from_name = '$config_quote_from_name', config_ticket_from_email = '$config_ticket_from_email', config_ticket_from_name = '$config_ticket_from_name' WHERE company_id = 1");

    logAction("Settings", "Edit", "$session_name edited mail from settings");

    flash_alert("Mail From Settings updated");

    redirect();

}

if (isset($_POST['test_email_smtp'])) {

    validateCSRFToken($_POST['csrf_token']);

    $test_email = intval($_POST['test_email']);
    
    if($test_email == 1) {
        $email_from = sanitizeInput($config_mail_from_email);
        $email_from_name = sanitizeInput($config_mail_from_name);
    } elseif ($test_email == 2) {
        $email_from = sanitizeInput($config_invoice_from_email);
        $email_from_name = sanitizeInput($config_invoice_from_name);
    } elseif ($test_email == 3) {
        $email_from = sanitizeInput($config_quote_from_email);
        $email_from_name = sanitizeInput($config_quote_from_name);
    } else {
        $email_from = sanitizeInput($config_ticket_from_email);
        $email_from_name = sanitizeInput($config_ticket_from_name);
    }

    $email_to = sanitizeInput($_POST['email_to']);
    $subject = "Test email from ITFlow";
    $body = "This is a test email from ITFlow. If you are reading this, it worked!";

    $data = [
        [
            'from' => $email_from,
            'from_name' => $email_from_name,
            'recipient' => $email_to,
            'recipient_name' => 'Chap',
            'subject' => $subject,
            'body' => $body
        ]
    ];
    
    $mail = addToMailQueue($data);

    if ($mail === true) {
        flash_alert("Test email queued! <a class='text-bold text-light' href='mail_queue.php'>Check Admin > Mail queue</a>");
    } else {
        flash_alert("Failed to add test mail to queue", 'error');
    }

    redirect();

}

if (isset($_POST['test_email_imap'])) {

    validateCSRFToken($_POST['csrf_token']);

    $host       = $config_imap_host;
    $port       = (int) $config_imap_port;
    $encryption = strtolower(trim($config_imap_encryption)); // e.g. "ssl", "tls", "none"
    $username   = $config_imap_username;
    $password   = $config_imap_password;

    // Build remote socket (implicit SSL vs plain TCP)
    $transport = 'tcp';
    if ($encryption === 'ssl') {
        $transport = 'ssl';
    }

    $remote_socket = $transport . '://' . $host . ':' . $port;

    // Stream context (you can tighten these if you want strict validation)
    $contextOptions = [];
    if (in_array($encryption, ['ssl', 'tls'], true)) {
        $contextOptions['ssl'] = [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'allow_self_signed' => true,
        ];
    }

    $context = stream_context_create($contextOptions);

    try {
        $errno  = 0;
        $errstr = '';

        // 10-second timeout, adjust as needed
        $fp = @stream_socket_client(
            $remote_socket,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$fp) {
            throw new Exception("Could not connect to IMAP server: [$errno] $errstr");
        }

        stream_set_timeout($fp, 10);

        // Read server greeting (IMAP servers send something like: * OK Dovecot ready)
        $greeting = fgets($fp, 1024);
        if ($greeting === false || strpos($greeting, '* OK') !== 0) {
            fclose($fp);
            throw new Exception("Invalid IMAP greeting: " . trim((string) $greeting));
        }

        // If you really want STARTTLS for "tls" (port 143), you can do it here
        if ($encryption === 'tls' && stripos($greeting, 'STARTTLS') !== false) {
            // Request STARTTLS
            fwrite($fp, "A0001 STARTTLS\r\n");
            $line = fgets($fp, 1024);
            if ($line === false || stripos($line, 'A0001 OK') !== 0) {
                fclose($fp);
                throw new Exception("STARTTLS failed: " . trim((string) $line));
            }

            // Enable crypto on the stream
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                throw new Exception("Unable to enable TLS encryption on IMAP connection.");
            }
        }

        // --- Do LOGIN command ---
        $tag = 'A0002';

        // Simple quoting; this may fail with some special chars in username/password.
        $loginCmd = sprintf(
            "%s LOGIN \"%s\" \"%s\"\r\n",
            $tag,
            addcslashes($username, "\\\""),
            addcslashes($password, "\\\"")
        );

        fwrite($fp, $loginCmd);

        $success   = false;
        $errorLine = '';

        while (!feof($fp)) {
            $line = fgets($fp, 2048);
            if ($line === false) {
                break;
            }

            // Look for tagged response for our LOGIN
            if (strpos($line, $tag . ' ') === 0) {
                if (stripos($line, $tag . ' OK') === 0) {
                    $success = true;
                } else {
                    $errorLine = trim($line);
                }
                break;
            }
        }

        // Always logout / close
        fwrite($fp, "A0003 LOGOUT\r\n");
        fclose($fp);

        if ($success) {
            flash_alert("Connected successfully");
        } else {
            if (!$errorLine) {
                $errorLine = 'Unknown IMAP authentication error';
            }
            throw new Exception($errorLine);
        }

    } catch (Exception $e) {
        flash_alert("<strong>IMAP connection failed:</strong> " . htmlspecialchars($e->getMessage()), 'error');
    }

    redirect();
}
