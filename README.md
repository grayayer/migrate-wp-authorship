
# A WordPress Plugin to Reassign WordPress Post Authors to Team Member CPT
Decouple content authorship from user accounts by reassigning post authors to a "Team Member" custom post type. Enhance security, enable multiple authors per post, and create rich author profiles.

![featured image for the Reassign WP Authors Plugin](/images/author-migration-plugin-featured-image-with-title.jpg)

## Why Would You Do This?

* <strong>Improved Security:</strong> By decoupling the author display from the user accounts required for logging into the WordPress admin area, you can remove active user accounts for individuals who no longer need access to the site's backend. This reduces the potential attack surface and enhances overall security.
* <strong>Better Management of Multiple Authors:</strong> The native WordPress user system is limited in its ability to handle multiple authors for a single post or page. By utilizing a custom "team member" CPT with a relationship field, you can easily associate multiple authors with a piece of content, providing proper recognition to all contributors.
* <strong>Enhanced Author Profiles:</strong> A dedicated "team member" CPT allows you to create rich, detailed profiles for each author, including biographies, photos, job titles, and other relevant information. These author profiles can be leveraged for SEO purposes and to provide additional context to readers.
* <strong>Future-Proofing:</strong> Separating the author display from the WordPress user system future-proofs your content authorship management. If an author leaves the company or changes roles, you can easily update their "team member" post without affecting the underlying user account or disrupting the content attribution.
* <strong>Consistent Branding and Design:</strong> By using a custom "team member" CPT, you have greater control over the display and styling of author information on the front-end, ensuring consistency with your overall branding and design.
* <strong>Scalability:</strong> As your content team grows, managing author profiles and associations through a dedicated CPT can be more scalable and efficient compared to relying on the WordPress user system, which is primarily designed for administrative purposes.
* <strong>Data Integrity:</strong> By automating the process of associating posts with "team member" posts, you can reduce the risk of human error and ensure consistent data integrity across your content authorship records.

## Instructions for Using Plugin
* Use this tool to designate a different custom post type (team member) as the displayed author on the front end, instead of using the wp post_author, then generate a report.
* This works because the team member custom post type (CPT) has a relationship field for each post that associates it with a wp post_author. These operations depend on user ID. Make sure that a <a href="https://www.advancedcustomfields.com/resources/user/">User field</a> has been assigned to the target post type
* You can select a different CPT other than blog post, but that CPT has to have the an ACF relationship field named "article_authors" that is associated with a team member CPT.

Please view my blog post at https://studiok40.com/reassign-wordpress-post-authors-to-team-member-cpt/ for complete instructions. 
