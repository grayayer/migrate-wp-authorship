<?php
/*
Plugin Name: Reassign WordPress Post Authors to Team Member CPT
Description: Decouple content authorship from user accounts by reassigning post authors to a "Team Member" custom post type. Enhance security, enable multiple authors per post, and create rich author profiles.
Version: .5.1
Author: Gray Ayer
Author URI: https://studiok40.com/
Plugin URI: https://github.com/grayayer/migrate-wp-authorship/
*/

// Hook into the admin menu to add a submenu page
add_action('admin_menu', 'team_member_sync_menu');

function team_member_sync_menu() {
    add_submenu_page(
        'tools.php', // Add to Tools menu. Change as needed.
        'Team Member Sync',
        'Team Member Sync',
        'manage_options',
        'team-member-sync',
        'team_member_sync_admin_page'
        
    );
}

require_once plugin_dir_path(__FILE__) . 'includes/admin-ui.php'; // Separate the admin UI file to keep our code nice and organized
require_once plugin_dir_path(__FILE__) . 'includes/utilities.php'; // call the utilities file which contains reusable functions
require_once plugin_dir_path(__FILE__) . 'includes/comparisons.php'; 

// This plugin depends on ACF, so this checks whether plugin is active and if not, display an admin notice to the user
add_action('admin_init', 'team_member_sync_check_acf_active');
function team_member_sync_check_acf_active() {
    if (!class_exists('acf')) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>The Reassign WordPress Post Authors to Team Member CPT plugin requires the <a href="https://www.advancedcustomfields.com/">Advanced Custom Fields plugin</a> to be active. Please install and activate the plugin to use this tool.</p></div>';
        });
    }
}

/** Generate Reports based on Button Clicks */

// Hook into the scan_post_authors_submit button and display a list of all the blog post wp authors
add_action('admin_init', function() {
    handle_form_submission('scan_post_authors_submit', 'scan_posts_for_authors', 'All Post Authors');
});

// Hook into the scan_post_authors_compare_team_submit and compare the list of all the blog post wp authors against the team member posts that exist.
// This works by comparing the name field of wp_users who are authors, against the title of team member posts
add_action('admin_init', function() {
    handle_form_submission('scan_post_authors_compare_team_submit', 'comparison_of_authors_for_team_members', 'Post Authors to Team Members Comparison Report');
});

/// SCAN FOR TEAM MEMBER POSTS WITHOUT WP USER
add_action('admin_init', function() {
    $addendum_msg = 'If the team member has not written any articles, they will not need to a have a user ID associated with them, but they will still show up on this report.';
    handle_form_submission('team_member_scan_submit', 'scan_team_members_without_wp_user', 'Team Member Scan Report', $addendum_msg);
});

// SCAN FOR AUTHORS THAT DON'T HAVE TEAM MEMBER POSTS YET
add_action('admin_init', 'handle_author_to_team_member_scan_submission');
function handle_author_to_team_member_scan_submission() {
    if (isset($_POST['author_to_team_member_scan_submit'])) {
        $report = scan_authors_for_missing_team_members();
        // Use admin_notices to display $report
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>Author to Team Member Scan Report:</strong></p><ul>';
                foreach ($report as $line) {
                    // Assuming $line includes a user identifier at the end in the format: "some text for user: USERNAME (User ID: ID)"
                    preg_match('/\(User ID: (\d+)\)$/', $line, $matches);
                    $user_id = $matches[1] ?? null;
                    if ($user_id) {
                        $edit_link = get_edit_user_link($user_id);
                        // Replace the end of the $line with a hyperlink, assuming the user ID was successfully extracted
                        $line = preg_replace('/\(User ID: \d+\)$/', "(<a href=\"$edit_link\" target=\"_blank\">Edit User</a>)", $line);
                    }
                    echo '<li>' . $line . '</li>';
                }
                echo '</ul></div>';
            });
        }        
    }
}







// include the data-migration.php file
require_once plugin_dir_path(__FILE__) . 'includes/data-migration.php';