<?php
/*
Plugin Name: Reassign WordPress Post Authors to Team Member CPT
Description: Decouple content authorship from user accounts by reassigning post authors to a "Team Member" custom post type. Enhance security, enable multiple authors per post, and create rich author profiles.
Version: .5
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
// If there is an "author" field array, scan it for any users designated there that don't have a corresponding team members, then output this to a report
function scan_authors_for_missing_team_members() {
    $args = array(
        'post_type' => 'post', // Adjust for your specific post type
        'posts_per_page' => -1,
        'post_status' => 'any',
    );

    $posts = get_posts($args);
    $report = array();

    foreach ($posts as $post) {
        $author_ids = get_field('author', $post->ID, false); // Ensure this returns an array of IDs
        if ($author_ids) {
            foreach ($author_ids as $author_id) {
                // Check for existing team member post
                $exists = get_posts(array(
                    'post_type' => 'team',
                    'meta_query' => array(
                        array(
                            'key' => 'wp_user',
                            'value' => $author_id,
                            'compare' => '='
                        )
                    )
                ));

                if (empty($exists)) {
                    // error_log('the $exists array is empty');
                    $user_info = get_userdata($author_id);
                    if ($user_info) {
                        // Including the post title along with the user's display name
                        $edit_post_link = get_edit_post_link($post->ID);
                        $report[] = "Missing team member post for user: " . $user_info->display_name . " (User ID: $author_id) in <a href=\"{$edit_post_link}\" target=\"_blank\">" . esc_html(get_the_title($post->ID)) . "</a> (Post ID: {$post->ID})";
                    }
                }
            }
        }
    }

    if (empty($report)) {
        $report[] = "All authors have corresponding team member posts.";
    }

    return $report;
}

/// SCAN FOR TEAM MEMBER POSTS WITHOUT WP USER
add_action('admin_init', 'team_member_scan_handle_submission');
function team_member_scan_handle_submission() {
    if (isset($_POST['team_member_scan_submit'])) {
        $report = scan_team_members_without_wp_user();
        // Handle displaying the report similarly to our existing process.
        // This example uses admin_notices to display the report, adjust as necessary.
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>Team Member Scan Report:</strong></p><ul>';
                foreach ($report as $line) {
                    echo '<li>' . $line . '</li>';
                }
                echo '</ul>
                <p>If the team member has not written any articles, they will not need to a have a user ID associated with them, but they will still show up on the report.</p>
                </div>';
            });
        }
    }
}


function scan_team_members_without_wp_user() {
    $args = array(
        'post_type' => 'team',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft'), // Include both published and draft posts
    );

    $team_posts = get_posts($args);
    $report = array();

    foreach ($team_posts as $post) {
        $related_wp_user = get_field('wp_user', $post->ID); // Assuming 'wp_user' is your ACF field name.
        
        if (empty($related_wp_user)) {
            // Compile report details for team members missing the related WP user field.
            $edit_link = get_edit_post_link($post->ID);
            $report[] = "<a href=\"{$edit_link}\" target=\"_blank\">{$post->post_title}</a> (ID: {$post->ID}) is missing a related WP user.";
        }
    }

    if (empty($report)) {
        $report[] = "All team members have a related WP user set.";
    }

    return $report;
}

// scans the "author" field array for any users selected there that don't have corresponding team members and output a report
function scan_posts_without_team_members() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'draft'), // Include both published and draft posts
    );

    $posts = get_posts($args);
    $report = array();

    foreach ($posts as $post) {
        $author_ids = get_field('author', $post->ID, false); // Retrieves the user IDs as an array
        $missing_team_members = array();

        foreach ($author_ids as $user_id) {
            $team_members = get_posts(array(
                'post_type' => 'team', // Your team member custom post type.
                'posts_per_page' => -1,
                'meta_query' => array(
                    array(
                        'key' => 'wp_user', // The ACF field key for the User field on team member posts.
                        'value' => $user_id, // The WP user ID.
                        'compare' => '=',
                    ),
                ),
            ));

            if (empty($team_members)) {
                $missing_team_members[] = $user_id;
            }
        }

        if (!empty($missing_team_members)) {
            $edit_link = get_edit_post_link($post->ID);
            $missing_user_string = implode(', ', $missing_team_members);
            $report[] = "<a href=\"{$edit_link}\" target=\"_blank\">{$post->post_title}</a> (ID: {$post->ID}) is missing team members for WP User(s): {$missing_user_string}";
        }
    }

    if (empty($report)) {
        $report[] = "All posts have team members assigned to their authors.";
    }

    return $report;
}

// include the data-migration.php file
require_once plugin_dir_path(__FILE__) . 'data-migration.php';