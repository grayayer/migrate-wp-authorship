<?php

// Scan the post_author field for all posts and output a report
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