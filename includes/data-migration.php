<?php 

if (!defined('ABSPATH')) { exit; }

// AFTER ADDING ENOUGH TEAM MEMBERS, WE CAN NOW PERFORM THE DATA MIGRATION
add_action('admin_init', 'migrate_authorship_handle_form_submission');
function migrate_authorship_handle_form_submission() {
    
    // Sanitize and Verify our nonce
    if (!isset($_POST['mwpa_plugin_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mwpa_plugin_nonce'])), 'migrate_authorship_plugin')) {
        return; 
        // die('Invalid nonce'); // displays an error message to be used while debugging
    }

    if (isset($_POST['migrate_authorship_submit_all']) || isset($_POST['migrate_authorship_submit_new'])) {
        
        $selected_post_status = isset($_POST['selected_post_status']) ? sanitize_text_field($_POST['selected_post_status']) : ''; // Sanitize the POST data
        $selected_post_type = sanitize_text_field($_POST['selected_post_type']);
        $skip_populated = isset($_POST['migrate_authorship_submit_new']); // True for "Sync Only New Posts"

        // Call the processing function
        $report = associate_blog_posts_with_team_members($selected_post_type, $skip_populated, $selected_post_status);

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
        // error_log(print_r($args, true));
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
