<?php
/*
Plugin Name: Reassign WordPress Post Authors to Team Member CPT
Description: Decouple content authorship from user accounts by reassigning post authors to a "Team Member" custom post type. Enhance security, enable multiple authors per post, and create rich author profiles.
Version: .4.1
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

// Render the admin page
function team_member_sync_admin_page() {
    ?>
    <style>
        .plugin-wrap {
            max-width: 900px;
        }
        h3 {
            font-size: 1.15em;
        }
    </style>
    <div class="plugin-wrap">
        <h1>Team Member Sync</h1>

        <h2>Instructions</h2>
        <ul style="list-style: disc;padding-left: 1rem;">
        <li>Use this tool to designate a different custom post type (team member) as the displayed author on the front end, instead of using the wp post_author, then generate a report.</li>
        <li>This works because the team member custom post type has a relationship field for each post that associates it with a wp post_author. These operations depend on user ID. Make sure that a <a href="https://www.advancedcustomfields.com/resources/user/">User field</a> has been assigned to the target post type</li>
        <li>You can select a different CPT other than blog post, but that CPT has to have the an ACF relationship field named "article_authors" that is associated with a team member CPT.</li>
        <li><strong>Warning:</strong> This tool will update the "article_authors" field on the selected post type with the team members associated with the post's author. This action cannot be undone.</li>
        <p>For more information on how to use this tool, please refer to the <a href="https://studiok40.com/blog/decouple-wordpress-post-authors-from-user-accounts/">full tutorial</a>.</p>
        </ul>

        <h2>1. Pre Migration Work</h2>
        <h3>A) You're going to need to create a team member post for each author of a post.</h3>
        <p> First identify which authors don't have a corresponding team member post, but not yet. Click the buttons to create a list of all your authors.</p>
        <form method="post" action="">
            <input type="submit" name="scan_post_authors_submit" value="Create List of Post Authors" class="button button-secondary"><!-- Create a list of all the blog post wp authors -->
            <input type="submit" name="scan_post_authors_compare_team_submit" value="Scan for Missing Team Member Posts" class="button button-primary"><!-- Take the list of all the blog post wp authors, and compare it against the team member posts that exist-->
        </form>
        <p>The report generated is purely for informational purposes, and will help you identify which authors don't have a corresponding team member post, so you can create that manually. After that you will need to designate an User ID Relationship in those Team Posts. <em>Before you ask, yes, next on my roadmap is to build a tool which will automatically create those team members automatically for you.</em></p>
        <h3>B) Scan team members and identifying any that are missing a user ID relationship</h3>
        <form method="post" action="">
            <input type="submit" name="team_member_scan_submit" value="Scan Team Members for Missing WP User ID" class="button button-secondary">
        </form>
        <p>Sometimes there will be team members who aren't authors, so don't worry about those people</p>
        <br>
        <h3>Do you have a half-working solution using ACF User field on the posts without a team CPT?</h3>
        <p> As a workaround on my original project, previous developers had given the posts a ACF field of "author" where a content editor can designate one or multiple authors for a post.
            This was the primary way article authorship had been designated when the plugin was developed.
            However, these authors aren't necessarily designated as a native wp post_author, You wouldn't know looking at the users table that you need to create these team posts.
            This tool will scan the "author" field array for any users selected there that don't have corresponding team members and output a report, so you can create the missing team member posts prior to running the sync tool.
        </p>    
        <!-- A button to scan for posts without team members -->
        <form method="post" action="">
            <input type="submit" name="author_to_team_member_scan_submit" value="Scan for Missing Team Member Posts when ACF User ID designated" class="button button-secondary">
        </form>

        <br>
        <h2>2. Migration of Data</h2>
        <strong>Only do this after you've created the necessary amount of team posts necessary to re-assign to posts, and you've properly designated an User ID Relationship in that Team Post</strong>
        <p>While we're assuming that you're likely applying this migration to blog posts, in case there are other post types you'd like to apply this to, you can select a different post type here.</p>
        <form method="post" action="">
            <?php

            // Post types dropdown
            echo '<select name="selected_post_type">';
            $post_types = get_post_types(['public' => true], 'objects');
            foreach ($post_types as $post_type) {
                echo '<option value="' . esc_attr($post_type->name) . '">' . esc_html($post_type->label) . '</option>';
            }
            echo '</select>';
            ?>
            <br><br>
            <select name="selected_post_status">
                <option value="any">Any Status</option>
                <option value="publish">Published</option>
                <option value="draft">Draft</option>
                <option value="pending">Pending Review</option>
                <option value="private">Private</option>
                <!-- Add any other post statuses as needed -->
            </select>
            <br><br>

            <input type="submit" name="team_member_sync_submit_all" value="Sync All" class="button button-primary">
            <input type="submit" name="team_member_sync_submit_new" value="Sync Only New Posts" class="button button-secondary">
        </form>
    </div>
    <?php
}

// hook into the scan_post_authors_submit and create a list of all the blog post wp authors
add_action('admin_init', 'scan_post_authors_handle_submission');
function scan_post_authors_handle_submission() {
    if (isset($_POST['scan_post_authors_submit'])) {
        $report = scan_posts_for_authors();
        // Handle displaying the report similarly to our existing process.
        // This example uses admin_notices to display the report, adjust as necessary.
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>Post Authors Scan Report:</strong></p><ul>';
                foreach ($report as $line) {
                    echo '<li>' . $line . '</li>';
                }
                echo '</ul></div>';
            });
        }
    }
}

// hook into the scan_post_authors_compare_team_submit and compare the list of all the blog post wp authors against the team member posts that exist. The comparison will compare the name field of wp_users who are authors, against the title of team member posts
add_action('admin_init', 'scan_post_authors_compare_team_handle_submission');
function scan_post_authors_compare_team_handle_submission() {
    if (isset($_POST['scan_post_authors_compare_team_submit'])) {
        $report = comparison_of_authors_for_team_members();
        // Handle displaying the report similarly to our existing process.
        // This example uses admin_notices to display the report, adjust as necessary.
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>Post Authors to Team Members Comparison Report:</strong></p><ul>';
                foreach ($report as $line) {
                    echo '<li>' . $line . '</li>';
                }
                echo '</ul></div>';
            });
        }
    }
}

// function that will scan the post_author field for all posts and output a report
function scan_posts_for_authors() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'any',
    );

    $posts = get_posts($args);
    $report = array();

    foreach ($posts as $post) {
        $author_id = $post->post_author;
        $author_info = get_userdata($author_id);
        if ($author_info) {
            $report[] = "Post: " . esc_html(get_the_title($post->ID)) . " (Post ID: {$post->ID}) has author: " . $author_info->display_name . " (User ID: $author_id)";
        }
    }

    if (empty($report)) {
        $report[] = "No posts found with authors.";
    }

    return $report;
}

// comparison_of_authors_for_team_members that will scan the post_author field for all posts and compare it against the team member posts that exist
function comparison_of_authors_for_team_members() {
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => -1,
        'post_status' => 'any',
    );

    $posts = get_posts($args);
    $report = array();

    foreach ($posts as $post) {
        $author_id = $post->post_author;
        $author_info = get_userdata($author_id);
        if ($author_info) {
            $team_members = get_posts(array(
                'post_type' => 'team',
                'posts_per_page' => -1,
                'title' => $author_info->display_name,
            ));

            if (empty($team_members)) {
                $report[] = "Post: " . esc_html(get_the_title($post->ID)) . " (Post ID: {$post->ID}) has author: " . $author_info->display_name . " (User ID: $author_id) but no corresponding team member post.";
            }
        }
    }

    if (empty($report)) {
        $report[] = "All post authors have corresponding team member posts.";
    }

    return $report;
}

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

// scan the "author" field array for any users selected there that don't have corresponding team members and output a report
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


// DATA MIGRATION FOR TEAM MEMBER SYNC
add_action('admin_init', 'team_member_sync_handle_form_submission');
function team_member_sync_handle_form_submission() {
    if (isset($_POST['team_member_sync_submit_all']) || isset($_POST['team_member_sync_submit_new'])) {
        $selected_post_type = sanitize_text_field($_POST['selected_post_type']);
        $skip_populated = isset($_POST['team_member_sync_submit_new']); // True for "Sync Only New Posts"

        // Call the processing function
        $report = associate_blog_posts_with_team_members($selected_post_type, $skip_populated, $_POST['selected_post_status']);

        // Determine if a report was generated but no posts were updated (specific to "Sync Only New Posts")
        if ($skip_populated && empty($report)) {
            $report[] = "No new posts found that require updating. All applicable posts already have 'article_authors' assigned.";
        }

        // Display report as an admin notice
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report) {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Sync Report:</strong></p><ul>';
                foreach ($report as $line) {
                    echo '<li>' . wp_kses($line, array(
                        'a' => array(
                            'href' => array(),
                            'target' => array(),
                        ),
                    )) . '</li>';
                }
                echo '</ul></div>';
            });
        } else if ($skip_populated) {
            // If "Sync Only New Posts" was clicked but no report was generated due to no applicable posts
            add_action('admin_notices', function() {
                echo '<div class="notice notice-info is-dismissible"><p>No new posts to sync. All selected posts already have their "article_authors" fields populated.</p></div>';
            });
        }
    }
}
function associate_blog_posts_with_team_members($selected_post_type, $skip_populated = false, $selected_post_status = 'any') {
        // Prepare the query args
        $args = array(
            'post_type' => $selected_post_type,
            'posts_per_page' => -1,
            'post_status' => $selected_post_status === 'any' ? array('publish', 'pending', 'draft', 'private') : $selected_post_status,
        );
        error_log(print_r($args, true));
        $posts = get_posts($args);
    
        $report_details = [];
        $total_posts_updated = 0;
        $missed_posts = [];
    
        foreach ($posts as $post) {
            // Skip posts if "article_authors" is populated (when $skip_populated is true)
            if ($skip_populated) {
                $existing_authors = get_field('article_authors', $post->ID);
                if (!empty($existing_authors)) {
                    continue; // Skip this post
                }
            }
    
            // Process for determining and updating team member relationships
            $acf_author_ids = get_field('author', $post->ID, false); // false to get user ID(s) only
            $team_member_ids_for_relationship = [];
            $team_member_names = [];
    
            // Handling both ACF 'author' field and fallback to WP post author
            $acf_author_ids = !empty($acf_author_ids) ? $acf_author_ids : [$post->post_author];
            $found_team_member = false;
    
            foreach ($acf_author_ids as $user_id) {
                $user_id = is_array($user_id) ? $user_id['ID'] : $user_id; // Adjust based on ACF return format
                $team_members = get_posts([
                    'post_type' => 'team',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        ['key' => 'wp_user', 'value' => $user_id, 'compare' => '='],
                    ],
                ]);
    
                if (!empty($team_members)) {
                    $found_team_member = true;
                    foreach ($team_members as $team_member) {
                        $team_member_ids_for_relationship[] = $team_member->ID;
                        $team_member_names[] = get_the_title($team_member->ID);
                    }
                }
            }
    
            // Update the 'article_authors' field if team members were found
            if ($found_team_member) {
                update_field('article_authors', $team_member_ids_for_relationship, $post->ID);
                $total_posts_updated++;
                $report_details[] = "Updated post (ID: {$post->ID}) with team member(s): " . implode(', ', $team_member_names) . ".";
            } else {
                // Handling for missing team members, including additional user info
                // (The logic for handling missing team members and building $missed_posts remains the same)
            }
        }
    
        // Compile and return the report details
        if ($total_posts_updated > 0) {
            array_unshift($report_details, "Total posts updated: $total_posts_updated");
        }
        if (!empty($missed_posts)) {
            $report_details[] = "Posts not updated: " . count($missed_posts);
            $report_details = array_merge($report_details, $missed_posts);
        }
    
        return $report_details;
}
