<?php

// Utility function to handle form submission
// How to use: 
function handle_form_submission($submit_name, $callback, $report_title, $addendum = '') {
    if (isset($_POST[$submit_name])) {
        $report = $callback();
        // Handle displaying the report similarly to our existing process.
        // This example uses admin_notices to display the report, adjust as necessary.
        if (!empty($report)) {
            add_action('admin_notices', function() use ($report, $submit_name, $report_title, $addendum) {
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