FEATURES
1. Build a tool which will automatically create those team members automatically for you.
2. Provide ACF import files users can use for team mates, and posts. - https://imranhsayed.medium.com/saving-the-acf-json-to-your-plugin-or-theme-file-f3b72b99257b
3. Add in an option for a user to write in their specific field names for ACF, and the CPT names, instead of relying on my specific naming structures. 
4. Look into ways to integrate a pre-git-commit hook that ensures every git commit also includes a change of plugin versioning
5. Add a post type selector at the top of UI so that users can use for post types besides just the native 'post'
7. Bundle acf with the plugin? https://www.advancedcustomfields.com/resources/including-acf-within-a-plugin-or-theme/

UI IMPROVEMENTS
1. Make messages to users translatable 
2. Provide a sample data set for testing
3. Pack ACF .json file for ease of importing

SECURITY UPGRADES
1. ~~data-migration.php - perform a global nonce check before processing any form submission~~
2. ~~add $abspath check on files~~
3.  ~~add nonces check on forms~~
4. Make all output text filtered through wp_kses() or some kind of sanitation because the wordpress.org review team generally want to see
5. Double check all of your use of $_POST to make certain we are escaping or sanitizing everything; data-migration.php's team_member_sync_handle_form_submission() passes $_POST['selected_post_status']) to another method without any sanitation or validation at all


CLEAN CODE BEST PRACTICES AND MAINTAINABILITY
1. ~~Move terminology from Team Member sync to be Migrate Authorship~~
2. PHPDoc!
3. Realign with OOP, since we don't have a class 
4. add a phpcs.xml set up with WP rulesets defined, so we can run PHP Code Sniffer



TUTORIAL & INSTRUCTIONS
1. Create better instructions in the readme.md file, not just reference the article I wrote
2. Add screenshots for a "before and after" view of the front end
3. Add sample code for backend theme construction
4. Add screenshot of the finished backend team member selector on blog posts
