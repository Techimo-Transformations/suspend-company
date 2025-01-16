<?php
/**
 * Plugin Name: Suspend Company
 * Description: Addon plugin for learndash to suspend the companies / groups.
 * Version: 1.0.1
 * Author: Techimo Transformations
 * Author URI: https://techimo.co/
 */

// Menu
add_action('admin_menu', 'suspend_company_add_menu');
function suspend_company_add_menu() {   
    add_menu_page(
        'Suspend Company',
        'Suspend Company',
        'manage_options',
        'suspend-company',
        'suspend_company_render_page',
        'dashicons-building',
        30
    );
}

function suspend_company_render_page() {    
    $groups = learndash_get_groups();
    ?>
        <div class="wrap">
            <h1>Suspend Company</h1><br/>
            <div id="refresh-div" style="display: none;text-align: center;box-shadow: rgba(0, 0, 0, 0.15) 2px 2px 5px 0px;background: #fff;"><br /><button id="refresh-button" style="color: #000000;
            border: none;background: #f3bd1c;font-size: 15px;font-weight: 500;" class="button animated-button">The Company has been updated successfully, click here to update the status.</button>
            <p style="margin-top: 10px;"><em>Note: Or you can refresh this page manually.</em></p><br /></div><br />
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><strong>ID</strong></th>
                        <th scope="col"><strong>Company Name</strong></th>
                        <th scope="col"><strong>Actions</strong></th>
                        <th scope="col"><strong>Status</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $counter = 1;
                    foreach ($groups as $group) {
                        $group_id = $group->ID
                        ?>
                        <tr>
                            <td><?php echo $counter; ?></td>
                            <td><?php echo $group->post_title; ?></td>
                            <td>
                                <?php if (is_company_suspended($group_id)) { ?>
                                    <button id="activate-btn" style="background: #edffe7;border: 1px solid #d8f1cf !important;color: #000;" class="button activate-company-btn" data-group-id="<?php echo $group_id; ?>">Activate</button>
                                    <button class="button suspend-company-btn" data-group-id="<?php echo $group_id; ?>" disabled style="background-color: darkorange; display: none;">Suspended</button>
                                <?php } else { ?>
                                    <button id="suspend-btn" style="background: #fff3dc;;border: 1px solid #ecdcbf !important;color: #000;" class="button suspend-company-btn" data-group-id="<?php echo $group_id; ?>">Suspend</button>
                                    <button class="button activate-company-btn" data-group-id="<?php echo $group_id; ?>" disabled style="background-color: green; display: none;">Active</button>
                                <?php } ?>
                            </td>
                            <td>
                                <?php if (is_company_suspended($group_id)) { ?>
                                    <span class="group-status" style="color: red;">Suspended</span>
                                <?php } else { ?>
                                    <span class="group-status" style="color: green;">Active</span>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php
                        $counter++;
                    } ?>
                </tbody>
            </table>
        </div>

    <script>
        jQuery(document).ready(function($) {
            $('.suspend-company-btn').click(function() {
                $('#refresh-div').fadeIn(800, function() {
                    $('html, body').animate({
                        scrollTop: $('#refresh-div').offset().top - ($(window).height() / 2)
                    }, 700);
                });
                var groupId = $(this).data('group-id');
                var suspendBtn = $(this);
                var activateBtn = suspendBtn.next('.activate-company-btn');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'suspend_company_change_status',
                        group_id: groupId,
                        status: 'draft',
                    },
                    success: function(response) {
                        console.log('Company Suspended: Group ID ' + groupId);
                        suspendBtn.hide();
                        suspendBtn.prop('disabled', true);
                        activateBtn.show();
                        activateBtn.prop('disabled', false);

                        // Lock users
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'suspend_company_lock_users',
                                group_id: groupId,
                            },
                            success: function(response) {
                                console.log('Users Locked: Group ID ' + groupId);
                            },
                        });
                    },
                });
            });

            $('.activate-company-btn').click(function() {
                $('#refresh-div').fadeIn(800, function() {
                    $('html, body').animate({
                        scrollTop: $('#refresh-div').offset().top - ($(window).height() / 2)
                    }, 500);
                });
                var groupId = $(this).data('group-id');
                var activateBtn = $(this);
                var suspendBtn = activateBtn.prev('.suspend-company-btn');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'suspend_company_change_status',
                        group_id: groupId,
                        status: 'publish',
                    },
                    success: function(response) {
                        console.log('Company Activated: Group ID ' + groupId);
                        activateBtn.hide();
                        activateBtn.prop('disabled', true);
                        suspendBtn.show();
                        suspendBtn.prop('disabled', false);

                        // Unlock users
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'suspend_company_unlock_users',
                                group_id: groupId,
                            },
                            success: function(response) {
                                console.log('Users Unlocked: Group ID ' + groupId);
                            },
                        });
                    },
                });
            });

            $('#refresh-button').click(function() {
                location.reload();
            });
        });
    </script>
    <style>
        #activate-btn:hover{background: #d8f1cf !important;}
        #suspend-btn:hover{background: #ecdcbf !important;}
        @keyframes pulseAnimation {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
            100% {
                transform: scale(1);
            }
        }

        .animated-button {
            animation-name: pulseAnimation;
            animation-duration: 1s;
            animation-iteration-count: infinite;
            animation-timing-function: ease-in-out;
        }
    </style>
    <?php

}

add_action('wp_ajax_suspend_company_change_status', 'suspend_company_change_status');
function suspend_company_change_status() {
    $group_id = $_POST['group_id'];
    $status = $_POST['status'];
    $updated = wp_update_post(array(
        'ID' => $group_id,
        'post_status' => $status,
    ));
    if ($updated) {
        wp_send_json_success('Group status updated successfully');
    } else {
        wp_send_json_error('Failed to update group status');
    }
}
function is_company_suspended($group_id) {
    $group = get_post($group_id);

    if ($group) {
        $status = $group->post_status;
        if ($status === 'draft') {
            return true;
        }
    }

    return false;
}


add_action('wp_ajax_suspend_company_lock_users', 'suspend_company_lock_users');
function suspend_company_lock_users() {
    $group_id = $_POST['group_id'];

    // Get all users belonging to the group
    $users = get_users(array(
        'meta_key' => 'group_id',
        'meta_value' => $group_id,
    ));

    // Lock users
    foreach ($users as $user) {
        update_user_meta($user->ID, 'baba_user_locked', 'yes');
    }

    wp_die();
}

add_action('wp_ajax_suspend_company_unlock_users', 'suspend_company_unlock_users');
function suspend_company_unlock_users() {
    $group_id = $_POST['group_id'];

    // Get all users belonging to the group
    $users = get_users(array(
        'meta_key' => 'group_id',
        'meta_value' => $group_id,
    ));

    // Unlock users
    foreach ($users as $user) {
        delete_user_meta($user->ID, 'baba_user_locked');
    }

    wp_die();
}

/**
 * Suspend or Activate Company and Lock/Unlock Users
 *
 * @param int    $group_id The ID of the group
 * @param string $status   The status to set for the group ('draft' or 'publish')
 */
function suspend_activate_company($group_id, $status) {
    $group = get_post($group_id);

    // Check if the group exists and is valid
    if (!$group || $group->post_type !== 'group') {
        return;
    }

    // Update the group status
    wp_update_post(array(
        'ID' => $group_id,
        'post_status' => $status,
    ));

    // Get all users belonging to the group
    $users = get_users(array(
        'meta_key' => 'group_id',
        'meta_value' => $group_id,
    ));

    // Lock or unlock users based on the group status
    foreach ($users as $user) {
        if ($status === 'draft') {
            // Lock the user
            update_user_meta($user->ID, 'baba_user_locked', 'yes');
        } else {
            // Unlock the user
            delete_user_meta($user->ID, 'baba_user_locked');
        }
    }
}

// add_shortcode('suspend-company', 'suspend_company_render_page')