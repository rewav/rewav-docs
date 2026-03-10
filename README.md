# Rewav Docs

**WordPress Markdown Documentation Manager**

Display Markdown documentation files from your filesystem directly inside the WordPress Dashboard.

## Features

- **Filesystem Discovery:** Automatically scans for `.md` and `.markdown` files.
- **Admin UI:** Clean, standard WordPress Admin interface for reading documentation.
- **Security:** Strict path traversal prevention and capability-based access control.
- **Markdown Rendering:** Powered by Parsedown for fast and safe HTML conversion.
- **Customizable:** Hooks and filters for every aspect of the plugin.

## Getting Started

1. Activate the plugin.
2. Create a folder at `wp-content/uploads/rewav-docs`.
3. Add your `.md` files to that folder.
4. Navigate to **Documentation** in your WordPress Admin menu.

## Security & Hosting Configuration

To prevent direct browser access to your `.md` files (e.g., `yoursite.com/rewav-docs/secret.md`), follow the guide for your hosting provider.

### 1. General (Best Practice: Outside Web Root)
The most secure method is to store your documentation folder **outside** the public web directory.
- **Path Example:** `/var/www/site/documentation/` (instead of `/var/www/site/public/documentation/`)
- **Plugin Setting:** Use the absolute server path in the plugin settings.
- **Why:** The web server has no URL mapping to this folder, making it physically impossible to access via a browser, while PHP retains full access.

### 2. Apache (Bluehost, SiteGround, Local)
Create a `.htaccess` file inside your documentation folder:
```apache
<Files "*.md">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</Files>
```

### 3. Kinsta (Nginx)
Kinsta ignores `.htaccess`. You must add an Nginx rule.
- Go to **Kinsta Dashboard > Sites > [Your Site] > Redirects**.
- Alternatively, contact Kinsta Support and ask them to: *"Block all web access to .md files in the /path/to/your/docs/ folder."*

### 4. Pantheon
Create (or update) a `pantheon.yml` file in the root of your Git repository:
```yaml
api_version: 1
protected_web_paths:
  - /wp-content/themes/your-theme/rewav-docs/
```

### 5. WP Engine (Nginx)
Use the **Redirect Rules** in the WP Engine User Portal:
- **Source:** `^/path/to/docs/.*\.md$`
- **Redirect Type:** `403 Forbidden` (or redirect to home).
- Or contact support to add a custom Nginx "deny" rule for the specific path.

### 6. Directory Protection (Fallback)
Always place a blank `index.php` in your documentation subfolders to prevent directory listing:
```php
<?php // Silence is golden.
```

## Requirements

- WordPress 6.3+
- PHP 8.1+

## License

GPLv2 or later.
