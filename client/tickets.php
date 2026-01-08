<?php
/*
 * Client Portal
 * Tickets Page with Pagination & Search
 */

header("Content-Security-Policy: default-src 'self'");

require_once "includes/inc_all.php";

// Badge color helper function (same as index.php)
function getStatusBadgeClass($status_name) {
    $status_lower = strtolower($status_name);
    if (stripos($status_lower, 'open') !== false || stripos($status_lower, 'offen') !== false || 
        stripos($status_lower, 'new') !== false || stripos($status_lower, 'neu') !== false) {
        return 'badge-danger';
    } elseif (stripos($status_lower, 'progress') !== false || stripos($status_lower, 'bearbeitung') !== false ||
            stripos($status_lower, 'working') !== false || stripos($status_lower, 'arbeit') !== false ||
            stripos($status_lower, 'assigned') !== false || stripos($status_lower, 'zugewiesen') !== false) {
        return 'badge-primary';
    } elseif (stripos($status_lower, 'waiting') !== false || stripos($status_lower, 'wartend') !== false ||
            stripos($status_lower, 'warten') !== false || stripos($status_lower, 'wartet') !== false ||
            stripos($status_lower, 'hold') !== false || stripos($status_lower, 'angehalten') !== false ||
            stripos($status_lower, 'pending') !== false || stripos($status_lower, 'ausstehend') !== false) {
        return 'badge-warning';
    } elseif (stripos($status_lower, 'resolved') !== false || stripos($status_lower, 'gelöst') !== false ||
            stripos($status_lower, 'closed') !== false || stripos($status_lower, 'geschlossen') !== false ||
            stripos($status_lower, 'completed') !== false || stripos($status_lower, 'abgeschlossen') !== false ||
            stripos($status_lower, 'erledigt') !== false) {
        return 'badge-success';
    } elseif (stripos($status_lower, 'cancelled') !== false || stripos($status_lower, 'canceled') !== false ||
            stripos($status_lower, 'abgebrochen') !== false || stripos($status_lower, 'storniert') !== false) {
        return 'badge-secondary';
    }
    return 'badge-secondary';
}

// ========================================
// PAGINATION & SEARCH PARAMETERS
// ========================================

// Search query
$search_query = '';
if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = sanitizeInput($_GET['q']);
}

// Records per page
$records_per_page = 20; // Default
if (isset($_GET['records'])) {
    $records_input = intval($_GET['records']);
    if ($records_input == 0) {
        $records_per_page = 9999999; // Show all
    } elseif (in_array($records_input, [20, 50, 100])) {
        $records_per_page = $records_input;
    }
}

// Current page
$page = 1;
if (isset($_GET['page']) && intval($_GET['page']) > 0) {
    $page = intval($_GET['page']);
}

$record_from = ($page - 1) * $records_per_page;
$record_to = $records_per_page;

// ========================================
// TICKET STATUS FILTER
// ========================================

if (!isset($_GET['status']) || ($_GET['status']) == 'Open') {
    $status = 'Open';
    $ticket_status_snippet = "ticket_closed_at IS NULL";
} elseif (isset($_GET['status']) && ($_GET['status']) == 'Closed') {
    $status = 'Closed';
    $ticket_status_snippet = "ticket_closed_at IS NOT NULL";
} else {
    $status = '%';
    $ticket_status_snippet = "ticket_status LIKE '%'";
}

// ========================================
// SEARCH WHERE CLAUSE
// ========================================

$search_where = '';
if (!empty($search_query)) {
    $search_where = "AND (CONCAT(ticket_prefix, ticket_number) LIKE '%$search_query%' 
        OR ticket_subject LIKE '%$search_query%' 
        OR ticket_status_name LIKE '%$search_query%')";
}

// ========================================
// TICKETS QUERY WITH PAGINATION
// ========================================

$contact_tickets = mysqli_query($mysqli, "SELECT SQL_CALC_FOUND_ROWS ticket_id, ticket_prefix, ticket_number, ticket_subject, ticket_status_name 
    FROM tickets 
    LEFT JOIN contacts ON ticket_contact_id = contact_id 
    LEFT JOIN ticket_statuses ON ticket_status = ticket_status_id 
    WHERE $ticket_status_snippet 
    AND ticket_contact_id = $session_contact_id 
    AND ticket_client_id = $session_client_id 
    $search_where
    ORDER BY ticket_id DESC 
    LIMIT $record_from, $record_to");

// Get total count for pagination
$sql_total_found = mysqli_query($mysqli, "SELECT FOUND_ROWS()");
$row_count = mysqli_fetch_array($sql_total_found);
$total_found_tickets = intval($row_count[0]);

// Calculate pagination
$total_pages = ceil($total_found_tickets / $records_per_page);

// ========================================
// SIDEBAR STATISTICS
// ========================================

//Get Total tickets closed
$sql_total_tickets_closed = mysqli_query($mysqli, "SELECT COUNT(ticket_id) AS total_tickets_closed FROM tickets WHERE ticket_closed_at IS NOT NULL AND ticket_client_id = $session_client_id AND ticket_contact_id = $session_contact_id");
$row = mysqli_fetch_array($sql_total_tickets_closed);
$total_tickets_closed = intval($row['total_tickets_closed']);

//Get Total tickets open
$sql_total_tickets_open = mysqli_query($mysqli, "SELECT COUNT(ticket_id) AS total_tickets_open FROM tickets WHERE ticket_closed_at IS NULL AND ticket_client_id = $session_client_id AND ticket_contact_id = $session_contact_id");
$row = mysqli_fetch_array($sql_total_tickets_open);
$total_tickets_open = intval($row['total_tickets_open']);

//Get Total tickets
$sql_total_tickets = mysqli_query($mysqli, "SELECT COUNT(ticket_id) AS total_tickets FROM tickets WHERE ticket_client_id = $session_client_id AND ticket_contact_id = $session_contact_id");
$row = mysqli_fetch_array($sql_total_tickets);
$total_tickets = intval($row['total_tickets']);

// ========================================
// BUILD PRESERVE PARAMETERS FOR LINKS
// ========================================

function buildQueryString($exclude = []) {
    $params = [];
    foreach ($_GET as $key => $value) {
        if (!in_array($key, $exclude) && !empty($value)) {
            $params[$key] = urlencode($value);
        }
    }
    return !empty($params) ? '&' . http_build_query($params) : '';
}

?>

<!-- Header -->
<div class="d-flex align-items-center mb-4">
    <div class="mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--accent-purple)); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
        <i class="fas fa-ticket-alt fa-lg text-white"></i>
    </div>
    <h2 class="mb-0 text-gradient" style="font-weight: 700;"><?php echo __('client_portal_tickets', 'Tickets'); ?></h2>
</div>

<!-- Search & Filter Bar -->
<div class="row mb-4">
    <div class="col-md-10">
        <div class="card shadow-soft">
            <div class="card-body p-3">
                <form method="get" class="form-inline">
                    <!-- Preserve status parameter -->
                    <?php if (isset($_GET['status'])) { ?>
                    <input type="hidden" name="status" value="<?php echo htmlentities($_GET['status']); ?>">
                    <?php } ?>
                    
                    <!-- Search Input -->
                    <div class="input-group mr-3 mb-2" style="min-width: 300px;">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" name="q" class="form-control" placeholder="<?php echo __('client_portal_search_placeholder', 'Tickets durchsuchen...'); ?>" value="<?php echo htmlentities($search_query); ?>">
                    </div>
                    
                    <!-- Records per page Dropdown -->
                    <div class="form-group mr-3 mb-2">
                        <label class="mr-2 mb-0" style="font-weight: 600;"><?php echo __('client_portal_per_page', 'Pro Seite'); ?>:</label>
                        <select name="records" class="form-control" onchange="this.form.submit()" style="width: auto;">
                            <option value="20" <?php if ($records_per_page == 20) echo 'selected'; ?>>20</option>
                            <option value="50" <?php if ($records_per_page == 50) echo 'selected'; ?>>50</option>
                            <option value="100" <?php if ($records_per_page == 100) echo 'selected'; ?>>100</option>
                            <option value="0" <?php if ($records_per_page > 1000) echo 'selected'; ?>><?php echo __('client_portal_show_all', 'Alle'); ?></option>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <button type="submit" class="btn btn-primary mb-2">
                        <i class="fas fa-search mr-2"></i><?php echo __('client_portal_action_search', 'Suchen'); ?>
                    </button>
                    
                    <!-- Reset Button -->
                    <?php if (!empty($search_query) || isset($_GET['records'])) { ?>
                    <a href="?status=<?php echo urlencode($status); ?>" class="btn btn-secondary ml-2 mb-2">
                        <i class="fas fa-times mr-2"></i>Reset
                    </a>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row">

    <!-- Tickets Table -->
    <div class="col-md-10">
        
        <!-- Results Info -->
        <?php if ($total_found_tickets > 0) { ?>
        <div class="mb-3">
            <small class="text-muted">
                <?php echo __('client_portal_showing', 'Zeige'); ?> 
                <strong><?php echo $record_from + 1; ?> - <?php echo min($record_from + $records_per_page, $total_found_tickets); ?></strong> 
                <?php echo __('client_portal_of', 'von'); ?> 
                <strong><?php echo $total_found_tickets; ?></strong> 
                <?php echo __('client_portal_entries', 'Einträge'); ?>
            </small>
        </div>
        <?php } ?>

        <table class="table shadow-soft rounded-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo __('client_portal_ticket_subject', 'Subject'); ?></th>
                    <th><?php echo __('client_portal_ticket_status', 'Status'); ?></th>
                </tr>
            </thead>
            <tbody>

            <?php
            if (mysqli_num_rows($contact_tickets) > 0) {
                while ($row = mysqli_fetch_array($contact_tickets)) {
                    $ticket_id = intval($row['ticket_id']);
                    $ticket_prefix = nullable_htmlentities($row['ticket_prefix']);
                    $ticket_number = intval($row['ticket_number']);
                    $ticket_subject = nullable_htmlentities($row['ticket_subject']);
                    $ticket_status_raw = nullable_htmlentities($row['ticket_status_name']);
                    
                    // Translate status name
                    $ticket_status = __('ticket_status_' . strtolower(str_replace(' ', '_', $ticket_status_raw)), $ticket_status_raw);
                    
                    // Get badge class
                    $badge_class = getStatusBadgeClass($ticket_status);
            ?>

                <tr>
                    <td>
                        <a href="ticket.php?id=<?php echo $ticket_id; ?>" style="color: var(--primary-color); font-weight: 600;">
                            <i class="fas fa-hashtag mr-1"></i><?php echo "$ticket_prefix$ticket_number"; ?>
                        </a>
                    </td>
                    <td>
                        <a href="ticket.php?id=<?php echo $ticket_id; ?>" style="color: var(--gray-800);"><?php echo $ticket_subject; ?></a>
                    </td>
                    <td>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $ticket_status; ?></span>
                    </td>
                </tr>
            <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="3" class="text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3 text-muted" style="opacity: 0.3;"></i>
                        <p class="text-muted"><?php echo __('client_portal_no_tickets_found', 'Keine Tickets gefunden'); ?></p>
                        <?php if (!empty($search_query)) { ?>
                        <a href="?status=<?php echo urlencode($status); ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-times mr-1"></i>Suche zurücksetzen
                        </a>
                        <?php } ?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) { ?>
        <nav aria-label="Pagination">
            <ul class="pagination justify-content-center">
                <!-- Previous Page -->
                <?php if ($page > 1) { ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo buildQueryString(['page']); ?>">
                        <i class="fas fa-chevron-left mr-1"></i><?php echo __('client_portal_previous', 'Zurück'); ?>
                    </a>
                </li>
                <?php } ?>
                
                <!-- Page Numbers -->
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1' . buildQueryString(['page']) . '">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++) {
                    if ($i == $page) {
                        echo '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
                    } else {
                        echo '<li class="page-item"><a class="page-link" href="?page=' . $i . buildQueryString(['page']) . '">' . $i . '</a></li>';
                    }
                }
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $total_pages . buildQueryString(['page']) . '">' . $total_pages . '</a></li>';
                }
                ?>
                
                <!-- Next Page -->
                <?php if ($page < $total_pages) { ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo buildQueryString(['page']); ?>">
                        <?php echo __('client_portal_next', 'Weiter'); ?><i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </li>
                <?php } ?>
            </ul>
        </nav>
        <?php } ?>

    </div>

    <!-- Sidebar -->
    <div class="col-md-2">

        <a href="ticket_add.php" class="btn btn-primary btn-block btn-lg mb-3 shadow-soft">
            <i class="fas fa-plus-circle mr-2"></i><?php echo __('client_portal_new_ticket', 'New ticket'); ?>
        </a>

        <hr style="border-color: var(--gray-200);">

        <a href="?status=Open<?php echo buildQueryString(['status', 'page']); ?>" class="btn btn-danger btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-folder-open mr-2"></i><?php echo __('client_portal_ticket_open', 'My Open tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets_open ?></div>
        </a>

        <a href="?status=Closed<?php echo buildQueryString(['status', 'page']); ?>" class="btn btn-success btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-check-circle mr-2"></i><?php echo __('client_portal_ticket_closed', 'Closed tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets_closed ?></div>
        </a>

        <a href="?status=%<?php echo buildQueryString(['status', 'page']); ?>" class="btn btn-secondary btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-list mr-2"></i><?php echo __('client_portal_tickets', 'All my tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets ?></div>
        </a>
        
        <?php if ($session_contact_primary == 1 || $session_contact_is_technical_contact) { ?>
        <hr style="border-color: var(--gray-200);">

        <a href="ticket_view_all.php" class="btn btn-dark btn-block p-2 mb-3 shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-globe mr-2"></i><?php echo __('client_portal_all_tickets', 'All Tickets'); ?>
        </a>
        <?php } ?>

    </div>
</div>

<?php require_once "includes/footer.php"; ?>
