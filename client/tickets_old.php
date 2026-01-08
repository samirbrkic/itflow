<?php
/*
 * Client Portal
 * Landing / Home page for the client portal
 */

header("Content-Security-Policy: default-src 'self'");

require_once "includes/inc_all.php";


// Ticket status from GET
if (!isset($_GET['status']) || ($_GET['status']) == 'Open') {
    // Default to showing open
    $status = 'Open';
    $ticket_status_snippet = "ticket_closed_at IS NULL";
} elseif (isset($_GET['status']) && ($_GET['status']) == 'Closed') {
    $status = 'Closed';
    $ticket_status_snippet = "ticket_closed_at IS NOT NULL";
} else {
    $status = '%';
    $ticket_status_snippet = "ticket_status LIKE '%'";
}

$contact_tickets = mysqli_query($mysqli, "SELECT ticket_id, ticket_prefix, ticket_number, ticket_subject, ticket_status_name FROM tickets LEFT JOIN contacts ON ticket_contact_id = contact_id LEFT JOIN ticket_statuses ON ticket_status = ticket_status_id WHERE $ticket_status_snippet AND ticket_contact_id = $session_contact_id AND ticket_client_id = $session_client_id ORDER BY ticket_id DESC");

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


?>

<div class="d-flex align-items-center mb-4">
    <div class="mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--accent-purple)); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
        <i class="fas fa-ticket-alt fa-lg text-white"></i>
    </div>
    <h2 class="mb-0 text-gradient" style="font-weight: 700;"><?php echo __('client_portal_tickets', 'Tickets'); ?></h2>
</div>
<div class="row">

    <div class="col-md-10">

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
            while ($row = mysqli_fetch_array($contact_tickets)) {
                $ticket_id = intval($row['ticket_id']);
                $ticket_prefix = nullable_htmlentities($row['ticket_prefix']);
                $ticket_number = intval($row['ticket_number']);
                $ticket_subject = nullable_htmlentities($row['ticket_subject']);
                $ticket_status_raw = nullable_htmlentities($row['ticket_status_name']);
                
                // Translate status name
                $ticket_status = __('ticket_status_' . strtolower(str_replace(' ', '_', $ticket_status_raw)), $ticket_status_raw);
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
                        <?php
                        // Determine badge color based on status (multilingual support)
                        $badge_class = 'badge-secondary';
                        $status_lower = strtolower($ticket_status);
                        
                        // Red: Open/New (Offen/Neu)
                        if (strpos($status_lower, 'open') !== false || strpos($status_lower, 'offen') !== false || 
                            strpos($status_lower, 'new') !== false || strpos($status_lower, 'neu') !== false) {
                            $badge_class = 'badge-danger';
                        } 
                        // Blue: In Progress/Working/Assigned (In Bearbeitung/Zugewiesen)
                        elseif (strpos($status_lower, 'progress') !== false || strpos($status_lower, 'bearbeitung') !== false ||
                                strpos($status_lower, 'working') !== false || strpos($status_lower, 'arbeit') !== false ||
                                strpos($status_lower, 'assigned') !== false || strpos($status_lower, 'zugewiesen') !== false) {
                            $badge_class = 'badge-primary';
                        } 
                        // Orange: Waiting/Hold/Pending (Wartend/Warten/Ausstehend)
                        elseif (strpos($status_lower, 'waiting') !== false || strpos($status_lower, 'wartend') !== false ||
                                strpos($status_lower, 'warten') !== false || strpos($status_lower, 'wartet') !== false ||
                                strpos($status_lower, 'hold') !== false || strpos($status_lower, 'angehalten') !== false ||
                                strpos($status_lower, 'pending') !== false || strpos($status_lower, 'ausstehend') !== false) {
                            $badge_class = 'badge-warning';
                        } 
                        // Green: Resolved/Closed/Completed (Gelöst/Geschlossen/Abgeschlossen)
                        elseif (strpos($status_lower, 'resolved') !== false || strpos($status_lower, 'gelöst') !== false || strpos($status_lower, 'gelöst') !== false ||
                                strpos($status_lower, 'closed') !== false || strpos($status_lower, 'geschlossen') !== false ||
                                strpos($status_lower, 'completed') !== false || strpos($status_lower, 'abgeschlossen') !== false ||
                                strpos($status_lower, 'erledigt') !== false) {
                            $badge_class = 'badge-success';
                        } 
                        // Gray: Cancelled (Abgebrochen/Storniert)
                        elseif (strpos($status_lower, 'cancelled') !== false || strpos($status_lower, 'canceled') !== false ||
                                strpos($status_lower, 'abgebrochen') !== false || strpos($status_lower, 'storniert') !== false) {
                            $badge_class = 'badge-secondary';
                        }
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $ticket_status; ?></span>
                    </td>
                </tr>
            <?php
            }
            ?>
            </tbody>
        </table>

    </div>

    <div class="col-md-2">

        <a href="ticket_add.php" class="btn btn-primary btn-block btn-lg mb-3 shadow-soft">
            <i class="fas fa-plus-circle mr-2"></i><?php echo __('client_portal_new_ticket', 'New ticket'); ?>
        </a>

        <hr style="border-color: var(--gray-200);">

        <a href="?status=Open" class="btn btn-danger btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-folder-open mr-2"></i><?php echo __('client_portal_ticket_open', 'My Open tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets_open ?></div>
        </a>

        <a href="?status=Closed" class="btn btn-success btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-check-circle mr-2"></i><?php echo __('client_portal_ticket_closed', 'Closed tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets_closed ?></div>
        </a>

        <a href="?status=%" class="btn btn-secondary btn-block p-3 mb-3 text-left shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-list mr-2"></i><?php echo __('client_portal_tickets', 'All my tickets'); ?>
            <div class="float-right" style="font-size: 1.25rem; font-weight: 700;"><?php echo $total_tickets ?></div>
        </a>
        <?php
        if ($session_contact_primary == 1 || $session_contact_is_technical_contact) {
        ?>

        <hr style="border-color: var(--gray-200);">

        <a href="ticket_view_all.php" class="btn btn-dark btn-block p-2 mb-3 shadow-soft" style="border-radius: var(--radius-lg);">
            <i class="fas fa-globe mr-2"></i><?php echo __('client_portal_all_tickets', 'All Tickets'); ?>
        </a>

        <?php
        }
        ?>

    </div>
</div>

<?php require_once "includes/footer.php";
 ?>
