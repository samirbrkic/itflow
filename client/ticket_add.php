<?php
/*
 * Client Portal
 * New ticket form
 */

require_once 'includes/inc_all.php';

// Allow clients to select a related asset when raising a ticket
$sql_assets = mysqli_query($mysqli, "SELECT asset_id, asset_name, asset_type FROM assets WHERE asset_contact_id = $session_contact_id AND asset_client_id = $session_client_id AND asset_archived_at IS NULL ORDER BY asset_name ASC");

?>

    <ol class="breadcrumb d-print-none">
        <li class="breadcrumb-item">
            <a href="index.php"><?php echo __('client_portal_home', 'Home'); ?></a>
        </li>
        <li class="breadcrumb-item">
            <a href="tickets.php"><?php echo __('client_portal_tickets', 'Tickets'); ?></a>
        </li>
        <li class="breadcrumb-item active"><?php echo __('client_portal_new_ticket', 'New Ticket'); ?></li>
    </ol>

    <h3><?php echo __('client_portal_ticket_create', 'Raise a new ticket'); ?></h3>

    <div class="col-md-8">
        <form action="post.php" method="post">

            <div class="form-group">
                <label><?php echo __('client_portal_ticket_subject', 'Subject'); ?> <strong class="text-danger">*</strong></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-fw fa-tag"></i></span>
                    </div>
                    <input type="text" class="form-control" name="subject" placeholder="<?php echo __('client_portal_ticket_subject', 'Subject'); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <div class="form-group">
                        <label><?php echo __('client_portal_ticket_priority', 'Priority'); ?> <strong class="text-danger">*</strong></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fa fa-fw fa-thermometer-half"></i></span>
                            </div>
                            <select class="form-control select2" name="priority" required>
                                <option><?php echo __('client_portal_priority_low', 'Low'); ?></option>
                                <option><?php echo __('client_portal_priority_medium', 'Medium'); ?></option>
                                <option><?php echo __('client_portal_priority_high', 'High'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="form-group">
                    <label><?php echo __('client_portal_ticket_category', 'Category'); ?></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-layer-group"></i></span>
                        </div>
                        <select class="form-control select2" name="category">
                            <option value="0">- <?php echo __('client_portal_ticket_category', 'No Category'); ?> -</option>
                            <?php
                            $sql_categories = mysqli_query($mysqli, "SELECT category_id, category_name FROM categories WHERE category_type = 'Ticket' AND category_archived_at IS NULL");
                            while ($row = mysqli_fetch_array($sql_categories)) {
                                $category_id = intval($row['category_id']);
                                $category_name = nullable_htmlentities($row['category_name']);

                                ?>
                                <option value="<?php echo $category_id; ?>"><?php echo $category_name; ?></option>
                            <?php } ?>

                        </select>
                    </div>
                </div>
                </div>
            </div>

            <?php if (mysqli_num_rows($sql_assets) > 0) { ?>
                <div class="form-group">
                    <label><?php echo __('client_portal_assets', 'Asset'); ?></label>
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fa fa-fw fa-desktop"></i></span>
                        </div>
                        <select class="form-control select2" name="asset">
                            <option value="0">- None -</option>
                            <?php

                            while ($row = mysqli_fetch_array($sql_assets)) {
                                $asset_id = intval($row['asset_id']);
                                $asset_name = sanitizeInput($row['asset_name']);
                                $asset_type = sanitizeInput($row['asset_type']);
                                ?>
                                <option value="<?php echo $asset_id ?>"><?php echo "$asset_name ($asset_type)"; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                </div>
            <?php } ?>


            <div class="form-group">
                <label><?php echo __('client_portal_ticket_description', 'Details'); ?> <strong class="text-danger">*</strong></label>
                <textarea class="form-control tinymce" name="details"></textarea>
            </div>

            <button class="btn btn-primary" name="add_ticket"><?php echo __('client_portal_ticket_create', 'Raise ticket'); ?></button>

        </form>
    </div>

<?php
require_once 'includes/footer.php';

