<?php

// This partial renders the admin page
function team_member_sync_admin_page() {
    ?>
    <style>
        .plugin-wrap {
            max-width: 900px;
        }
        h3 {
            font-size: 1.15em;
        }
        .notice.notice-info.is-dismissible {
            margin: 1.5rem 1.5rem 2rem 0;
        }

    </style>
    <div class="plugin-wrap">
        <h1>Reassign WordPress Post Authors to Team Member CPT</h1>

        <h2>Instructions</h2>
        <ul style="list-style: disc;padding-left: 1rem;">
        <li>Use this tool to designate a different custom post type (team member) as the displayed author on the front end, instead of using the wp post_author, then generate a report.</li>
        <li>This works because the team member custom post type (CPT) has a relationship field for each post that associates it with a wp post_author. These operations depend on user ID. Make sure that a <a href="https://www.advancedcustomfields.com/resources/user/">User field</a> has been assigned to the target post type</li>
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