<?php

if (!defined('ABSPATH')) { exit; }

// Scan the post_author field for all posts and output a report
function scan_posts_for_authors() {
    // error_log(print_r($_POST, true));
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
        $author_link = get_edit_user_link($author_id);
        $post_link = get_edit_post_link($post->ID);
        if ($author_info) {
            $report[] = "Post: <a href='" . esc_url($post_link) . "'>" . esc_html(get_the_title($post->ID)) . "</a> (Post ID: {$post->ID}) has assigned author: <a href='" . esc_url($author_link) ."'>" . $author_info->display_name . "</a> (User ID: $author_id)";
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
        $author_link = get_edit_user_link($author_id);
        $post_link = get_edit_post_link($post->ID);        
        if ($author_info) {
            $team_members = get_posts(array(
                'post_type' => 'team',
                'posts_per_page' => -1,
                'title' => $author_info->display_name,
            ));

            if (empty($team_members)) {
                $report[] = "Post: <a href='" . esc_url($post_link) . "'>" . esc_html(get_the_title($post->ID)) . "</a> (Post ID: {$post->ID}) has assigned author: <a href='" . esc_url($author_link) ."'>" . $author_info->display_name . "</a> (User ID: $author_id) but no corresponding team member post.";
            }
        }
    }

    if (empty($report)) {
        $report[] = "All post authors have corresponding team member posts.";
    }

    return $report;
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