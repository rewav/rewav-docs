=== Rewav Docs ===
Contributors: (TBD)
Tags: documentation, markdown, docs, admin, developer
Requires at least: 6.3
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display Markdown documentation files from your filesystem directly inside the WordPress Dashboard.

== Description ==

Rewav Docs bridges the gap between your codebase and your content management system. By surfacing Markdown files inside the WP Admin as formatted, readable documentation, you can keep your development team informed and your documentation alongside your project — no database imports, no duplication.

== Installation ==

1. Upload the `rewav-docs` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Configure the documentation path in Settings > Rewav Docs (default is `wp-content/uploads/rewav-docs`).
4. Add Markdown files to your configured folder.

== Frequently Asked Questions ==

= Can I customize the documentation folder? =
Yes! You can change it in the plugin settings or define the `REWAV_DOCS_PATH` constant in your `wp-config.php`.

= Is it safe? =
Absolutely. The plugin has built-in path traversal prevention and uses WordPress standard sanitization and capability checks.

== Screenshots ==

1. (TBD)
2. (TBD)

== Changelog ==

= 1.0.2 =
* Improved Admin UI: Breadcrumbs, Title, and Back Button are now grouped in an inline header.
* Improved Admin UI: Search Bar is now inline with the Page Title on the index page.
* Improved Admin UI: Compact sidebar with reduced width and padding.
* Added security and hosting configuration guide to README.md (Kinsta, Pantheon, WP Engine, etc.).

= 1.0.1 =
* Added Full-Text Search for documentation files.
* Added Mermaid.js support for diagrams.
* Added Relative Image Resolution for Markdown images.
* Fixed relative image paths for symlinked environments like Kinsta.
* Fixed table overflow issues in the admin UI.
* Increased content max-width for better readability.
* Added Bitbucket Pipelines for automated ZIP packaging.

= 1.0.0 =
* Initial release.
