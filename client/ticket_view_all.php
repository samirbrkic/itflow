<?php
/*
 * Client Portal
 * Primary contact view: all tickets
 */

require_once 'includes/inc_all.php';


if ($session_contact_primary == 0 && !$session_contact_is_technical_contact) {
    header("Location: post.php?logout");
    exit();
}

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

$all_tickets = mysqli_query($mysqli, "SELECT ticket_id, ticket_prefix, ticket_number, ticket_subject, ticket_status_name, contact_name FROM tickets LEFT JOIN contacts ON ticket_contact_id = contact_id LEFT JOIN ticket_statuses ON ticket_status = ticket_status_id WHERE $ticket_status_snippet AND ticket_client_id = $session_client_id ORDER BY ticket_id DESC");
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center">
        <div class="mr-3" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--primary-color), var(--accent-purple)); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; box-shadow: var(--shadow-md);">
            <i class="fas fa-globe fa-lg text-white"></i>
        </div>
        <h2 class="mb-0 text-gradient" style="font-weight: 700;"><?php echo __('client_portal_all_tickets', 'All tickets'); ?></h2>
    </div>
    
    <div class="col-md-3">
        <form method="get">
            <label style="font-weight: 600; color: var(--gray-700); margin-bottom: 0.5rem;"><?php echo __('client_portal_ticket_status', 'Ticket Status'); ?></label>
            <select class="form-control" name="status" onchange="this.form.submit()" style="color: var(--gray-800); font-weight: 500;">
                <option value="%" <?php if ($status == "%") {echo "selected";}?>><?php echo __('client_portal_any', 'Any'); ?></option>
                <option value="Open" <?php if ($status == "Open") {echo "selected";}?>><?php echo __('client_portal_ticket_open', 'Open'); ?></option>
                <option value="Closed" <?php if ($status == "Closed") {echo "selected";}?>><?php echo __('client_portal_ticket_closed', 'Closed'); ?></option>
            </select>
        </form>
    </div>
</div>

<table class="table shadow-soft rounded-modern">
    <thead>
    <tr>
        <th scope="col">#</th>
        <th scope="col"><?php echo __('client_portal_ticket_subject', 'Subject'); ?></th>
        <th scope="col"><?php echo __('client_portal_contact', 'Contact'); ?></th>
        <th scope="col"><?php echo __('client_portal_ticket_status', 'Status'); ?></th>
    </tr>
    </thead>
    <tbody>

        <?php
        while ($row = mysqli_fetch_array($all_tickets)) {
            $ticket_id = intval($row['ticket_id']);
            $ticket_prefix = nullable_htmlentities($row['ticket_prefix']);
            $ticket_number = intval($row['ticket_number']);
            $ticket_subject = nullable_htmlentities($row['ticket_subject']);
            $ticket_status_raw = nullable_htmlentities($row['ticket_status_name']);
            $ticket_contact_name = nullable_htmlentities($row['contact_name']);
            
            // Translate status name
            $ticket_status = __('ticket_status_' . strtolower(str_replace(' ', '_', $ticket_status_raw)), $ticket_status_raw);

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

            echo "<tr>";
            echo "<td><a href='ticket.php?id=$ticket_id' style='color: var(--primary-color); font-weight: 600;'><i class='fas fa-hashtag mr-1'></i>$ticket_prefix$ticket_number</a></td>";
            echo "<td><a href='ticket.php?id=$ticket_id' style='color: var(--gray-800);'>$ticket_subject</a></td>";
            echo "<td style='color: var(--gray-600);'><i class='fas fa-user mr-1'></i>$ticket_contact_name</td>";
            echo "<td><span class='badge $badge_class'>$ticket_status</span></td>";
            echo "</tr>";
        }
        ?>
        </tbody>
    </table>
</div>

<?php
require_once 'includes/footer.php';

