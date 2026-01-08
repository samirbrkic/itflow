<?php
/*
 * Client Portal
 * Ticket detail page
 */

require_once "includes/inc_all.php";

//Initialize the HTML Purifier to prevent XSS
require "../plugins/htmlpurifier/HTMLPurifier.standalone.php";

$purifier_config = HTMLPurifier_Config::createDefault();
$purifier_config->set('Cache.DefinitionImpl', null); // Disable cache by setting a non-existent directory or an invalid one
$purifier_config->set('URI.AllowedSchemes', ['data' => true, 'src' => true, 'http' => true, 'https' => true]);
$purifier = new HTMLPurifier($purifier_config);

$allowed_extensions = array('jpg', 'jpeg', 'gif', 'png', 'webp', 'pdf', 'txt', 'md', 'doc', 'docx', 'csv', 'xls', 'xlsx', 'xlsm', 'zip', 'tar', 'gz');

if (isset($_GET['id']) && intval($_GET['id'])) {
    $ticket_id = intval($_GET['id']);

    $ticket_contact_snippet = "AND ticket_contact_id = $session_contact_id";
    // Bypass ticket contact being session_id for a primary / technical contact viewing all tickets
    if ($session_contact_primary == 1 || $session_contact_is_technical_contact) {
        $ticket_contact_snippet = '';
    }

    $ticket_sql = mysqli_query($mysqli,
        "SELECT * FROM tickets
            LEFT JOIN users on ticket_assigned_to = user_id
            LEFT JOIN ticket_statuses ON ticket_status = ticket_status_id
            LEFT JOIN categories ON ticket_category = category_id
            WHERE ticket_id = $ticket_id AND ticket_client_id = $session_client_id
             $ticket_contact_snippet"
    );

    $ticket_row = mysqli_fetch_array($ticket_sql);

    if ($ticket_row) {

        $ticket_prefix = nullable_htmlentities($ticket_row['ticket_prefix']);
        $ticket_number = intval($ticket_row['ticket_number']);
        $ticket_status_raw = nullable_htmlentities($ticket_row['ticket_status_name']);
        $ticket_priority_raw = nullable_htmlentities($ticket_row['ticket_priority']);
        
        // Translate status name (i18n-compliant)
        $ticket_status = __('ticket_status_' . strtolower(str_replace(' ', '_', $ticket_status_raw)), $ticket_status_raw);
        
        // Translate priority (i18n-compliant)
        $ticket_priority = __('client_portal_priority_' . strtolower($ticket_priority_raw), $ticket_priority_raw);
        
        $ticket_subject = nullable_htmlentities($ticket_row['ticket_subject']);
        $ticket_details = $purifier->purify($ticket_row['ticket_details']);
        $ticket_assigned_to = nullable_htmlentities($ticket_row['user_name']);
        $ticket_resolved_at = nullable_htmlentities($ticket_row['ticket_resolved_at']);
        $ticket_closed_at = nullable_htmlentities($ticket_row['ticket_closed_at']);
        $ticket_feedback = nullable_htmlentities($ticket_row['ticket_feedback']);
        $ticket_category = nullable_htmlentities($ticket_row['category_name']);

        // Get Ticket Attachments (not associated with a specific reply)
        $sql_ticket_attachments = mysqli_query(
            $mysqli,
            "SELECT * FROM ticket_attachments
            WHERE ticket_attachment_reply_id IS NULL
            AND ticket_attachment_ticket_id = $ticket_id"
        );

        // Get Tasks
        $sql_tasks = mysqli_query( $mysqli, "SELECT * FROM tasks WHERE task_ticket_id = $ticket_id ORDER BY task_order ASC, task_id ASC");
        $task_count = mysqli_num_rows($sql_tasks);

        // Get Completed Task Count
        $sql_tasks_completed = mysqli_query($mysqli,
            "SELECT * FROM tasks
            WHERE task_ticket_id = $ticket_id
            AND task_completed_at IS NOT NULL"
        );
        $completed_task_count = mysqli_num_rows($sql_tasks_completed);

        ?>

        <ol class="breadcrumb d-print-none">
            <li class="breadcrumb-item">
                <a href="index.php"><?php echo __('client_portal_home', 'Home'); ?></a>
            </li>
            <li class="breadcrumb-item">
                <a href="tickets.php"><?php echo __('client_portal_tickets', 'Tickets'); ?></a>
            </li>
            <li class="breadcrumb-item active"><?php echo __('client_portal_ticket_number', 'Ticket'); ?> <?php echo $ticket_prefix . $ticket_number; ?></li>
        </ol>

        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">
                        <i class="fas fa-ticket-alt mr-2 text-primary"></i>
                        <?php echo __('client_portal_ticket_number', 'Ticket'); ?> <?php echo $ticket_prefix, $ticket_number ?>
                        <span class="badge <?php echo getStatusBadgeClass($ticket_status); ?> ml-2"><?php echo $ticket_status; ?></span>
                    </h4>
                    <div class="card-tools">
                        <?php
                        if (empty($ticket_resolved_at) && $task_count == $completed_task_count) { ?>
                            <a href="post.php?resolve_ticket=<?php echo $ticket_id; ?>" class="btn btn-sm btn-success confirm-link"><i class="fas fa-fw fa-check mr-1"></i> <?php echo __('client_portal_ticket_mark_resolved', 'Als gelöst markieren'); ?></a>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="card-body prettyContent">
                <div class="mb-3">
                    <h5 class="mb-3"><i class="fas fa-comment-alt mr-2 text-primary"></i><strong><?php echo __('client_portal_ticket_subject', 'Subject'); ?>:</strong> <?php echo $ticket_subject ?></h5>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p class="mb-2">
                            <strong><i class="fas fa-exclamation-circle mr-2 text-muted"></i><?php echo __('client_portal_ticket_priority', 'Priorität'); ?>:</strong> 
                            <span class="badge badge-<?php 
                                $priority_lower = strtolower($ticket_priority_raw);
                                echo $priority_lower == 'high' || $priority_lower == 'hoch' || $priority_lower == 'critical' || $priority_lower == 'kritisch' ? 'danger' : 
                                     ($priority_lower == 'medium' || $priority_lower == 'mittel' ? 'warning' : 'secondary'); 
                            ?>"><?php echo $ticket_priority ?></span>
                        </p>
                        <?php if (!empty($ticket_category)) { ?>
                            <p class="mb-2">
                                <strong><i class="fas fa-folder mr-2 text-muted"></i><?php echo __('client_portal_ticket_category', 'Category'); ?>:</strong> <?php echo $ticket_category ?>
                            </p>
                        <?php } ?>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if (empty($ticket_closed_at)) { ?>
                            <?php if ($task_count) { ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-tasks mr-2 text-muted"></i><?php echo __('client_portal_ticket_attachments', 'Tasks'); ?>:</strong> 
                                    <span class="badge badge-primary"><?php echo $completed_task_count . " / " .$task_count ?></span>
                                </p>
                            <?php } ?>
                            <?php if (!empty($ticket_assigned_to)) { ?>
                                <p class="mb-2">
                                    <strong><i class="fas fa-user mr-2 text-muted"></i><?php echo __('client_portal_ticket_assigned_to', 'Assigned to'); ?>:</strong> <?php echo $ticket_assigned_to ?>
                                </p>
                            <?php } ?>
                        <?php } ?>
                    </div>
                </div>
                
                <hr>
                <div class="ticket-content">
                    <?php echo $ticket_details ?>
                </div>

                <?php
                while ($ticket_attachment = mysqli_fetch_array($sql_ticket_attachments)) {
                    $name = nullable_htmlentities($ticket_attachment['ticket_attachment_name']);
                    $ref_name = nullable_htmlentities($ticket_attachment['ticket_attachment_reference_name']);
                    echo "<hr><i class='fas fa-fw fa-paperclip text-secondary mr-1'></i>$name | <a href='../uploads/tickets/$ticket_id/$ref_name' download='$name'><i class='fas fa-fw fa-download mr-1'></i>Download</a> | <a target='_blank' href='../uploads/tickets/$ticket_id/$ref_name'><i class='fas fa-fw fa-external-link-alt mr-1'></i>View</a>";
                }
                ?>
            </div>
        </div>

        <hr>

        <!-- Either show the reply comments box, option to re-open ticket, show ticket smiley feedback or thanks for feedback -->

        <?php if (empty($ticket_resolved_at)) { ?>
            <!-- Reply -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-reply mr-2"></i><?php echo __('client_portal_ticket_reply', 'Reply'); ?></h5>
                </div>
                <div class="card-body">
                    <form action="post.php" enctype="multipart/form-data" method="post">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id ?>">
                        <div class="form-group">
                            <label><?php echo __('client_portal_ticket_comment', 'Kommentar'); ?></label>
                            <textarea class="form-control tinymce" name="comment" rows="5" placeholder="<?php echo __('client_portal_ticket_add_comment_placeholder', 'Kommentar hinzufügen...'); ?>"></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-paperclip mr-2"></i><?php echo __('client_portal_ticket_attachments', 'Attachments'); ?></label>
                            <input type="file" class="form-control-file" name="file[]" multiple id="fileInput" accept=".jpg, .jpeg, .gif, .png, .webp, .pdf, .txt, .md, .doc, .docx, .odt, .csv, .xls, .xlsx, .ods, .pptx, .odp, .zip, .tar, .gz, .xml, .msg, .json, .wav, .mp3, .ogg, .mov, .mp4, .av1, .ovpn">
                        </div>
                        <button type="submit" class="btn btn-primary" name="add_ticket_comment">
                            <i class="fas fa-paper-plane mr-2"></i><?php echo __('client_portal_ticket_reply', 'Reply'); ?>
                        </button>
                    </form>
                </div>
            </div>

        <?php } elseif (empty($ticket_closed_at)) { ?>
            <!-- Re-open -->
            <div class="card">
                <div class="card-body text-center py-4">
                    <h4 class="mb-4"><i class="fas fa-check-circle text-success mr-2"></i><?php echo __('client_portal_ticket_resolved', 'Your ticket has been resolved'); ?></h4>
                    
                    <div class="d-flex justify-content-center gap-3">
                        <a href="post.php?reopen_ticket=<?php echo $ticket_id; ?>" class="btn btn-secondary btn-lg mr-3">
                            <i class="fas fa-fw fa-redo mr-2"></i><?php echo __('client_portal_ticket_reopen', 'Reopen ticket'); ?>
                        </a>
                        <a href="post.php?close_ticket=<?php echo $ticket_id; ?>" class="btn btn-success btn-lg confirm-link">
                            <i class="fas fa-fw fa-gavel mr-2"></i><?php echo __('client_portal_ticket_close', 'Close ticket'); ?>
                        </a>
                    </div>
                </div>
            </div>

        <?php } elseif (empty($ticket_feedback)) { ?>
            <div class="card">
                <div class="card-body text-center py-4">
                    <h4 class="mb-4"><?php echo __('client_portal_ticket_rate', 'Ticket closed. Please rate your ticket'); ?></h4>

                    <form action="post.php" method="post" class="d-inline">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket_id ?>">

                        <button type="submit" class="btn btn-success btn-lg mr-3" name="add_ticket_feedback" value="Good">
                            <i class="fas fa-smile mr-2"></i><?php echo __('client_portal_feedback_good', 'Gut'); ?>
                        </button>

                        <button type="submit" class="btn btn-danger btn-lg" name="add_ticket_feedback" value="Bad">
                            <i class="fas fa-frown mr-2"></i><?php echo __('client_portal_feedback_bad', 'Schlecht'); ?>
                        </button>
                    </form>
                </div>
            </div>

        <?php } else { ?>
            <div class="alert alert-success text-center">
                <h4><i class="fas fa-star mr-2"></i><?php echo __('client_portal_ticket_rated', 'Rated'); ?> <?php echo $ticket_feedback ?> - <?php echo __('client_portal_ticket_thanks', 'Thanks for your feedback!'); ?></h4>
            </div>

        <?php } ?>

        <!-- End comments/reopen/feedback -->

        <hr>
        <br>

        <?php
        $sql = mysqli_query($mysqli, "SELECT * FROM ticket_replies LEFT JOIN users ON ticket_reply_by = user_id LEFT JOIN contacts ON ticket_reply_by = contact_id WHERE ticket_reply_ticket_id = $ticket_id AND ticket_reply_archived_at IS NULL AND ticket_reply_type != 'Internal' ORDER BY ticket_reply_id DESC");

        while ($row = mysqli_fetch_array($sql)) {
            $ticket_reply_id = intval($row['ticket_reply_id']);
            $ticket_reply = $purifier->purify($row['ticket_reply']);
            $ticket_reply_created_at = nullable_htmlentities($row['ticket_reply_created_at']);
            $ticket_reply_updated_at = nullable_htmlentities($row['ticket_reply_updated_at']);
            $ticket_reply_by = intval($row['ticket_reply_by']);
            $ticket_reply_type = $row['ticket_reply_type'];

            if ($ticket_reply_type == "Client") {
                $ticket_reply_by_display = nullable_htmlentities($row['contact_name']);
                $user_initials = initials($row['contact_name']);
                $user_avatar = $row['contact_photo'];
                $avatar_link = "../uploads/clients/$session_client_id/$user_avatar";
            } else {
                $ticket_reply_by_display = nullable_htmlentities($row['user_name']);
                $user_id = intval($row['user_id']);
                $user_avatar = $row['user_avatar'];
                $user_initials = initials($row['user_name']);
                $avatar_link = "../uploads/users/$user_id/$user_avatar";
            }

            // Get attachments for this reply
            $sql_ticket_reply_attachments = mysqli_query(
                $mysqli,
                "SELECT * FROM ticket_attachments
                        WHERE ticket_attachment_reply_id = $ticket_reply_id
                        AND ticket_attachment_ticket_id = $ticket_id"
            );
            ?>

            <div class="card card-outline <?php if ($ticket_reply_type == 'Client') { echo "card-warning"; } else { echo "card-info"; } ?> mb-3">
                <div class="card-header">
                    <h3 class="card-title">
                        <div class="media">
                            <?php
                            if (!empty($user_avatar)) {
                                ?>
                                <img src="<?php echo $avatar_link ?>" alt="User Avatar" class="img-size-50 mr-3 img-circle">
                                <?php
                            } else {
                                ?>
                                <span class="fa-stack fa-2x">
                                    <i class="fa fa-circle fa-stack-2x text-secondary"></i>
                                    <span class="fa fa-stack-1x text-white"><?php echo $user_initials; ?></span>
                                </span>
                                <?php
                            }
                            ?>

                            <div class="media-body">
                                <?php echo $ticket_reply_by_display; ?>
                                <br>
                                <small class="text-muted"><?php echo $ticket_reply_created_at; ?> <?php if (!empty($ticket_reply_updated_at)) { echo "(edited: $ticket_reply_updated_at)"; } ?></small>
                            </div>
                        </div>
                    </h3>
                </div>

                <div class="card-body prettyContent">
                    <?php echo $ticket_reply; ?>

                    <?php
                    while ($ticket_attachment = mysqli_fetch_array($sql_ticket_reply_attachments)) {
                        $name = nullable_htmlentities($ticket_attachment['ticket_attachment_name']);
                        $ref_name = nullable_htmlentities($ticket_attachment['ticket_attachment_reference_name']);
                        echo "<hr><i class='fas fa-fw fa-paperclip text-secondary mr-1'></i>$name | <a href='../uploads/tickets/$ticket_id/$ref_name' download='$name'><i class='fas fa-fw fa-download mr-1'></i>Download</a> | <a target='_blank' href='../uploads/tickets/$ticket_id/$ref_name'><i class='fas fa-fw fa-external-link-alt mr-1'></i>View</a>";
                    }
                    ?>
                </div>
            </div>

            <?php

        }

        ?>

        <?php
    } else {
        echo "Ticket ID not found!";
    }

} else {
    header("Location: index.php");
}

require_once "includes/footer.php";


