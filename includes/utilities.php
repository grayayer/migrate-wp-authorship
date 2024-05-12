<?php

if (!defined('ABSPATH')) { exit; }

/**
 * UTILITY FUNCTION TO HANDLE FORM SUBMISSION.
 *
 * @param callable $submit_function_name The function to be called when the UI button is clicked.
 * @param callable $callback A callback for the report.
 * @param string $report_title The report title.
 * @param string $addendum Extra notes for reporting.
 */

function handle_form_submission($submit_function_name, $callback, $report_title, $addendum = '') {
    if (isset($_POST[$submit_function_name])) {
        $report = $callback();
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report, $submit_function_name, $report_title, $addendum) {
                echo '<div class="notice notice-info is-dismissible"><p><strong>' . $report_title . ':</strong></p><ul>';
                foreach ($report as $line) {
                    echo '<li>' . $line . '</li>';
                }
                echo '</ul>';
                if (!empty($addendum)) {
                    echo '<p>' . $addendum . '</p>';
                }
                echo '</div>';
            });
        }
    }
}


/**
 * GENERATE A DROPDOWN OF POST TYPES
 *
 * @param string $selected The selected post type
 * @return void
 */

function generate_post_types_dropdown($selected = '') {
    $post_types = get_post_types( array('public' => true), 'objects' );

    echo '<select name="post_type">';
    foreach ($post_types as $post_type) {
        $selected_attr = '';
        if ($selected === $post_type->name) {
            $selected_attr = ' selected';
        }
        echo '<option value="' . esc_attr($post_type->name) . '"' . $selected_attr . '>' . esc_html($post_type->labels->singular_name) . '</option>';
    }
    echo '</select>';
}