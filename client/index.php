<?php
/*
 * Client Portal
 * Landing / Home page - Modern Dashboard with Ticket Overview
 */

header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'");

require_once "includes/inc_all.php";

// Badge helper function now in functions.php for reusability

// ========================================
// VIEW FILTER PARAMETER
// ========================================

$view_filter = isset($_GET['view']) && $_GET['view'] == 'my' ? 'my' : 'all';

// ========================================
// TICKET STATISTICS - COMPANY WIDE
// ========================================

// Total Open Tickets (Company)
$sql_open_tickets = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets WHERE ticket_client_id = $session_client_id AND ticket_closed_at IS NULL");
$row = mysqli_fetch_array($sql_open_tickets);
$total_open_tickets = intval($row['count']);

// Total In Progress Tickets (Company)
$sql_in_progress = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets 
    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id 
    WHERE ticket_client_id = $session_client_id 
    AND ticket_closed_at IS NULL 
    AND (LOWER(ticket_status_name) LIKE '%progress%' OR LOWER(ticket_status_name) LIKE '%bearbeitung%' OR LOWER(ticket_status_name) LIKE '%working%' OR LOWER(ticket_status_name) LIKE '%arbeit%')");
$row = mysqli_fetch_array($sql_in_progress);
$total_in_progress = intval($row['count']);

// Total Resolved Today (Company)
$sql_resolved_today = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets 
    LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id 
    WHERE ticket_client_id = $session_client_id 
    AND DATE(ticket_closed_at) = CURDATE()
    AND (LOWER(ticket_status_name) LIKE '%resolved%' OR LOWER(ticket_status_name) LIKE '%gelöst%')");
$row = mysqli_fetch_array($sql_resolved_today);
$total_resolved_today = intval($row['count']);

// Total Tickets (Company)
$sql_total_tickets = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets WHERE ticket_client_id = $session_client_id");
$row = mysqli_fetch_array($sql_total_tickets);
$total_tickets_company = intval($row['count']);

// ========================================
// MY TICKET STATISTICS
// ========================================

// My Open Tickets
$sql_my_open = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets WHERE ticket_client_id = $session_client_id AND ticket_contact_id = $session_contact_id AND ticket_closed_at IS NULL");
$row = mysqli_fetch_array($sql_my_open);
$my_open_tickets = intval($row['count']);

// My Total Tickets
$sql_my_total = mysqli_query($mysqli, "SELECT COUNT(*) as count FROM tickets WHERE ticket_client_id = $session_client_id AND ticket_contact_id = $session_contact_id");
$row = mysqli_fetch_array($sql_my_total);
$my_total_tickets = intval($row['count']);

// ========================================
// RECENT TICKET UPDATES (5 fixed)
// ========================================

if ($view_filter == 'my') {
    // Show only my tickets
    $sql_recent_tickets = mysqli_query($mysqli, "SELECT tickets.ticket_id, tickets.ticket_prefix, tickets.ticket_number, tickets.ticket_subject, 
        tickets.ticket_created_at, tickets.ticket_updated_at, ticket_statuses.ticket_status_name, contacts.contact_name
        FROM tickets 
        LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
        LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
        WHERE tickets.ticket_client_id = $session_client_id AND tickets.ticket_contact_id = $session_contact_id
        ORDER BY tickets.ticket_updated_at DESC 
        LIMIT 5");
} else {
    // Show all company tickets
    $sql_recent_tickets = mysqli_query($mysqli, "SELECT tickets.ticket_id, tickets.ticket_prefix, tickets.ticket_number, tickets.ticket_subject, 
        tickets.ticket_created_at, tickets.ticket_updated_at, ticket_statuses.ticket_status_name, contacts.contact_name
        FROM tickets 
        LEFT JOIN ticket_statuses ON tickets.ticket_status = ticket_statuses.ticket_status_id
        LEFT JOIN contacts ON tickets.ticket_contact_id = contacts.contact_id
        WHERE tickets.ticket_client_id = $session_client_id 
        ORDER BY tickets.ticket_updated_at DESC 
        LIMIT 5");
}

// ========================================
// COMPANY CONTACT INFO
// ========================================

$sql_company_info = mysqli_query($mysqli, "SELECT company_name, company_phone, company_email, company_website 
    FROM companies WHERE company_id = 1");
$row = mysqli_fetch_array($sql_company_info);
$support_company_name = nullable_htmlentities($row['company_name']);
$support_company_phone = nullable_htmlentities($row['company_phone']);
$support_company_email = nullable_htmlentities($row['company_email']);
$support_company_website = nullable_htmlentities($row['company_website']);

// Billing & Technical queries (keep existing for optional cards)
$sql_invoice_amounts = mysqli_query($mysqli, "SELECT SUM(invoice_amount) AS invoice_amounts FROM invoices WHERE invoice_client_id = $session_client_id AND invoice_status != 'Draft' AND invoice_status != 'Cancelled' AND invoice_status != 'Non-Billable'");
$row = mysqli_fetch_array($sql_invoice_amounts);
$invoice_amounts = floatval($row['invoice_amounts']);

$sql_amount_paid = mysqli_query($mysqli, "SELECT SUM(payment_amount) AS amount_paid FROM payments, invoices WHERE payment_invoice_id = invoice_id AND invoice_client_id = $session_client_id");
$row = mysqli_fetch_array($sql_amount_paid);
$amount_paid = floatval($row['amount_paid']);
$balance = $invoice_amounts - $amount_paid;

$sql_recurring_monthly_total = mysqli_query($mysqli, "SELECT SUM(recurring_invoice_amount) AS recurring_monthly_total FROM recurring_invoices WHERE recurring_invoice_status = 1 AND recurring_invoice_frequency = 'month' AND recurring_invoice_client_id = $session_client_id");
$row = mysqli_fetch_array($sql_recurring_monthly_total);
$recurring_monthly_total = floatval($row['recurring_monthly_total']);

$sql_assigned_assets = mysqli_query($mysqli, "SELECT * FROM assets WHERE asset_contact_id = $session_contact_id AND asset_archived_at IS NULL ORDER BY asset_name ASC");

?>

<!-- Dashboard Header -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="d-flex align-items-center mb-3">
            <div class="mr-3 dashboard-icon-box">
                <i class="fas fa-chart-line fa-2x text-white"></i>
            </div>
            <div>
                <h2 class="mb-0 dashboard-title"><?php echo __('client_portal_dashboard', 'Dashboard'); ?></h2>
                <p class="text-muted mb-0"><?php echo __('client_portal_welcome_overview', 'Willkommen zur Übersicht'); ?></p>
            </div>
        </div>
    </div>
    <div class="col-md-4 text-right">
        <a href="ticket_add.php" class="btn btn-primary btn-lg shadow-soft">
            <i class="fas fa-plus-circle mr-2"></i><?php echo __('client_portal_new_ticket', 'Neues Ticket'); ?>
        </a>
    </div>
</div>

<!-- View Filter Toggle -->
<div class="row mb-4">
    <div class="col-12">
        <div class="btn-group shadow-sm" role="group">
            <a href="?view=all" class="btn <?php echo $view_filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-building mr-2"></i><?php echo __('client_portal_all_company_tickets', 'Alle Tickets der Firma'); ?> (<?php echo $total_tickets_company; ?>)
            </a>
            <a href="?view=my" class="btn <?php echo $view_filter == 'my' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                <i class="fas fa-user mr-2"></i><?php echo __('client_portal_my_tickets', 'Meine Tickets'); ?> (<?php echo $my_total_tickets; ?>)
            </a>
        </div>
    </div>
</div>

<!-- Ticket Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stat-label mb-2"><?php echo __('client_portal_open_tickets', 'Offen'); ?></h6>
                        <h2 class="stat-value stat-value-danger"><?php echo $view_filter == 'my' ? $my_open_tickets : $total_open_tickets; ?></h2>
                    </div>
                    <div class="icon-box icon-box-danger">
                        <i class="fas fa-folder-open fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stat-label mb-2"><?php echo __('client_portal_in_progress', 'In Bearbeitung'); ?></h6>
                        <h2 class="stat-value stat-value-primary"><?php echo $total_in_progress; ?></h2>
                    </div>
                    <div class="icon-box icon-box-primary">
                        <i class="fas fa-spinner fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stat-label mb-2"><?php echo __('client_portal_resolved_today', 'Heute gelöst'); ?></h6>
                        <h2 class="stat-value stat-value-success"><?php echo $total_resolved_today; ?></h2>
                    </div>
                    <div class="icon-box icon-box-success">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card-neutral">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="stat-label mb-2"><?php echo __('client_portal_total_tickets', 'Gesamt'); ?></h6>
                        <h2 class="stat-value stat-value-neutral"><?php echo $view_filter == 'my' ? $my_total_tickets : $total_tickets_company; ?></h2>
                    </div>
                    <div class="icon-box icon-box-neutral">
                        <i class="fas fa-list fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row">
    
    <!-- Left Column: Recent Ticket Updates -->
    <div class="col-md-8 mb-4">
        <div class="card shadow-soft">
            <div class="card-header" style="background: var(--surface-2); border-bottom: 1px solid var(--border);">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0" style="font-weight: 600; color: var(--gray-800);">
                        <i class="fas fa-clock mr-2" style="color: var(--primary-color);"></i>
                        <?php echo __('client_portal_recent_updates', 'Letzte Ticket-Updates'); ?>
                    </h5>
                    <a href="tickets.php" class="btn btn-sm btn-outline-primary">
                        <?php echo __('client_portal_view_all', 'Alle anzeigen'); ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <?php
                if (mysqli_num_rows($sql_recent_tickets) > 0) {
                    while ($row = mysqli_fetch_array($sql_recent_tickets)) {
                        $ticket_id = intval($row['ticket_id']);
                        $ticket_prefix = nullable_htmlentities($row['ticket_prefix']);
                        $ticket_number = intval($row['ticket_number']);
                        $ticket_subject = nullable_htmlentities($row['ticket_subject']);
                        $ticket_status_raw = nullable_htmlentities($row['ticket_status_name']);
                        $ticket_updated_at = nullable_htmlentities($row['ticket_updated_at']);
                        $ticket_contact_name = nullable_htmlentities($row['contact_name']);
                        
                        // Translate status
                        $ticket_status = __('ticket_status_' . strtolower(str_replace(' ', '_', $ticket_status_raw)), $ticket_status_raw);
                        
                        // Get badge color using helper function
                        $badge_class = getStatusBadgeClass($ticket_status);
                        
                        $time_ago = timeAgo($ticket_updated_at);
                        ?>
                        <div class="p-3 ticket-item" style="border-bottom: 1px solid var(--gray-100); transition: var(--transition-fast);">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center mb-2">
                                        <a href="ticket.php?id=<?php echo $ticket_id; ?>" class="ticket-link" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">
                                            <i class="fas fa-hashtag mr-1"></i><?php echo $ticket_prefix . $ticket_number; ?>
                                        </a>
                                        <span class="badge <?php echo $badge_class; ?> ml-2"><?php echo $ticket_status; ?></span>
                                    </div>
                                    <h6 class="mb-1" style="color: var(--gray-800);">
                                        <a href="ticket.php?id=<?php echo $ticket_id; ?>" style="color: var(--gray-800); text-decoration: none;">
                                            <?php echo $ticket_subject; ?>
                                        </a>
                                    </h6>
                                    <small class="text-muted">
                                        <i class="fas fa-user mr-1"></i><?php echo $ticket_contact_name; ?>
                                        <span class="mx-2">•</span>
                                        <i class="fas fa-clock mr-1"></i><?php echo __('client_portal_updated', 'Aktualisiert'); ?> <?php echo $time_ago; ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="p-4 text-center text-muted">
                        <i class="fas fa-inbox fa-3x mb-3" style="opacity: 0.3;"></i>
                        <p class="mb-0"><?php echo __('client_portal_no_tickets_found', 'Keine Tickets vorhanden'); ?></p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Quick Links -->
    <div class="col-md-4">

        <!-- Quick Links Card -->
        <div class="card shadow-soft">
            <div class="card-header" style="background: white; border-bottom: 2px solid var(--gray-100);">
                <h5 class="mb-0" style="font-weight: 600; color: var(--gray-800);">
                    <i class="fas fa-bolt mr-2" style="color: var(--warning-color);"></i>
                    <?php echo __('client_portal_quick_links', 'Schnellzugriff'); ?>
                </h5>
            </div>
            <div class="card-body p-2">
                <a href="tickets.php" class="btn btn-light btn-block text-left mb-2" style="border-radius: var(--radius-lg);">
                    <i class="fas fa-ticket-alt mr-2" style="color: var(--primary-color);"></i><?php echo __('client_portal_tickets', 'Alle Tickets'); ?>
                </a>
                <a href="ticket_add.php" class="btn btn-light btn-block text-left mb-2" style="border-radius: var(--radius-lg);">
                    <i class="fas fa-plus-circle mr-2" style="color: var(--success-color);"></i><?php echo __('client_portal_new_ticket', 'Neues Ticket'); ?>
                </a>
                <?php if ($session_contact_primary == 1 || $session_contact_is_billing_contact) { ?>
                <a href="invoices.php" class="btn btn-light btn-block text-left mb-2" style="border-radius: var(--radius-lg);">
                    <i class="fas fa-file-invoice-dollar mr-2" style="color: var(--accent-blue);"></i><?php echo __('client_portal_invoices', 'Rechnungen'); ?>
                </a>
                <?php } ?>
                <?php if ($session_contact_primary == 1 || $session_contact_is_technical_contact) { ?>
                <a href="documents.php" class="btn btn-light btn-block text-left" style="border-radius: var(--radius-lg);">
                    <i class="fas fa-file-alt mr-2" style="color: var(--accent-purple);"></i><?php echo __('client_portal_documents', 'Dokumente'); ?>
                </a>
                <?php } ?>
            </div>
        </div>

    </div>
</div>

<!-- Optional: Billing & Technical Cards (if data exists) -->
<?php if (($session_contact_primary == 1 || $session_contact_is_billing_contact) && ($balance > 0 || $recurring_monthly_total > 0)) { ?>
<div class="row mt-4">
    <div class="col-12">
        <h5 class="mb-3" style="font-weight: 600; color: var(--gray-700);">
            <i class="fas fa-wallet mr-2"></i><?php echo __('client_portal_financial_overview', 'Finanzübersicht'); ?>
        </h5>
    </div>
    
    <?php if ($balance > 0) { ?>
    <div class="col-md-4 mb-3">
        <a href="unpaid_invoices.php" class="card text-dark">
            <div class="card-header" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                <h3 class="card-title"><i class="fas fa-exclamation-circle mr-2"></i><?php echo __('client_portal_account_balance', 'Offener Betrag'); ?></h3>
            </div>
            <div class="card-body text-center">
                <div class="h4 text-danger mb-0"><b><?php echo numfmt_format_currency($currency_format, $balance, $session_company_currency); ?></b></div>
                <small class="text-muted"><i class="fas fa-arrow-right mr-1"></i>Unbezahlte Rechnungen anzeigen</small>
            </div>
        </a>
    </div>
    <?php } ?>

    <?php if ($recurring_monthly_total > 0) { ?>
    <div class="col-md-4 mb-3">
        <a href="recurring_invoices.php" class="card text-dark">
            <div class="card-header" style="background: linear-gradient(135deg, #10B981, #059669);">
                <h3 class="card-title"><i class="fas fa-sync-alt mr-2"></i><?php echo __('client_portal_recurring_monthly', 'Monatlich wiederkehrend'); ?></h3>
            </div>
            <div class="card-body text-center">
                <div class="h4 mb-0" style="color: var(--success-color);"><b><?php echo numfmt_format_currency($currency_format, $recurring_monthly_total, $session_company_currency); ?></b></div>
                <small class="text-muted"><i class="fas fa-calendar-alt mr-1"></i>Pro Monat</small>
            </div>
        </a>
    </div>
    <?php } ?>
</div>
<?php } ?>

<!-- Optional: My Assets (if assigned) -->
<?php if (mysqli_num_rows($sql_assigned_assets) > 0) { ?>
<div class="row mt-4">
    <div class="col-12">
        <h5 class="mb-3" style="font-weight: 600; color: var(--gray-700);">
            <i class="fas fa-desktop mr-2"></i><?php echo __('client_portal_your_assigned_assets', 'Mir zugewiesene Assets'); ?>
        </h5>
    </div>
    <div class="col-md-6">
        <div class="card shadow-soft">
            <div class="card-body">
                <?php
                while ($row = mysqli_fetch_array($sql_assigned_assets)) {
                    $asset_name = nullable_htmlentities($row['asset_name']);
                    $asset_type = nullable_htmlentities($row['asset_type']);
                    $asset_uri_client = sanitize_url($row['asset_uri_client']);
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 pb-3" style="border-bottom: 1px solid var(--gray-100);">
                        <div>
                            <h6 class="mb-0" style="color: var(--gray-800); font-weight: 600;">
                                <?php if ($asset_uri_client) { ?>
                                <a href="<?= $asset_uri_client ?>" target="_blank" style="color: var(--primary-color); text-decoration: none;">
                                    <i class='fas fa-external-link-alt mr-2'></i>
                                </a>
                                <?php } ?>
                                <?php echo $asset_name; ?>
                            </h6>
                            <small class="text-muted"><i class="fas fa-tag mr-1"></i><?php echo $asset_type; ?></small>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php require_once "includes/footer.php"; ?>
