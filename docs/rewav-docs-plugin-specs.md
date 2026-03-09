# Plugin Specification: Rewav Docs
**WordPress Markdown Documentation Manager**
Version: 1.0.0-spec
Status: Pre-Development — Planning & Specification
Prepared by: Senior WordPress Developer (WP VIP Advanced Professional)
Date: 2026-03-09

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Plugin Identity & Repository Requirements](#2-plugin-identity--repository-requirements)
3. [Architecture Overview](#3-architecture-overview)
4. [File & Folder Structure](#4-file--folder-structure)
5. [Constants & Configuration](#5-constants--configuration)
6. [Core Features — Detailed Specs](#6-core-features--detailed-specs)
7. [Admin UI Specifications](#7-admin-ui-specifications)
8. [Security Requirements](#8-security-requirements)
9. [Performance Requirements](#9-performance-requirements)
10. [Accessibility Requirements](#10-accessibility-requirements)
11. [Internationalization (i18n)](#11-internationalization-i18n)
12. [WordPress Coding Standards Checklist](#12-wordpress-coding-standards-checklist)
13. [Plugin Repository Compliance](#13-plugin-repository-compliance)
14. [Hooks & Extensibility API](#14-hooks--extensibility-api)
15. [Data Flow Diagrams](#15-data-flow-diagrams)
16. [Error Handling Strategy](#16-error-handling-strategy)
17. [Testing Requirements](#17-testing-requirements)
18. [Future Roadmap (v2+)](#18-future-roadmap-v2)
19. [Open Questions & Decisions Needed](#19-open-questions--decisions-needed)

---

## 1. Executive Summary

**Plugin Name:** Rewav Docs
**Plugin Slug:** `rewav-docs`
**Short Description:** Display Markdown documentation files from your filesystem directly inside the WordPress Dashboard.
**Target Users:** WordPress developers and site administrators who maintain internal or project documentation alongside a WordPress installation.

### Problem Being Solved
Development teams often maintain `.md` documentation files (READMEs, API docs, runbooks, changelogs) alongside a WordPress project. Without this plugin, those files live outside the CMS, forcing users to switch between tools to read or reference them. **Rewav Docs bridges that gap** by surfacing Markdown files inside the WP Admin as formatted, readable documentation — no database imports, no duplication.

### Core Principles (Non-Negotiable)
- Read-only: The plugin **never modifies** the source `.md` files.
- Zero bloat: No writing to `wp_options` beyond plugin settings. No custom database tables unless explicitly justified.
- Standards-first: 100% compliant with [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/) and ready for the official plugin repository on day one.
- Extensible: Developers can override almost any behavior through well-documented hooks.

---

## 2. Plugin Identity & Repository Requirements

| Field | Value |
|---|---|
| Plugin Name | Rewav Docs |
| Plugin Slug | `rewav-docs` |
| Text Domain | `rewav-docs` |
| Domain Path | `/languages` |
| Requires at least | WordPress 6.3 |
| Tested up to | WordPress 6.7 (latest stable at time of writing) |
| Requires PHP | 8.1 |
| License | GPLv2 or later |
| License URI | https://www.gnu.org/licenses/gpl-2.0.html |
| Author | (TBD — must match WP.org account) |

### Why These Minimums?
- **WP 6.3** — Introduces stable `wp_enqueue_block_assets()` improvements and is the floor for most modern hook APIs.
- **PHP 8.1** — Required for named arguments, enums, and `readonly` properties that keep the codebase clean and modern. WP.org still supports PHP 7.4 in practice, but since this plugin is developer-targeted, 8.1 is justified and must be documented clearly.

---

## 3. Architecture Overview

```
rewav-docs/
├── rewav-docs.php              ← Main plugin file (bootstrap only)
├── uninstall.php               ← Runs on plugin deletion
├── readme.txt                  ← WP.org repository readme
├── README.md                   ← GitHub/developer readme
├── CHANGELOG.md
├── includes/
│   ├── class-rewav-docs.php            ← Core singleton / service locator
│   ├── class-rewav-docs-loader.php     ← Hook registration manager
│   ├── class-rewav-docs-admin.php      ← Admin menu, pages, assets
│   ├── class-rewav-docs-file-scanner.php  ← Filesystem MD discovery
│   ├── class-rewav-docs-markdown-renderer.php  ← MD → HTML conversion
│   ├── class-rewav-docs-settings.php   ← Settings API integration
│   └── class-rewav-docs-capabilities.php  ← Role/capability management
├── admin/
│   ├── css/
│   │   └── rewav-docs-admin.css
│   ├── js/
│   │   └── rewav-docs-admin.js
│   └── partials/
│       ├── rewav-docs-admin-display.php    ← Documentation index page
│       ├── rewav-docs-document-view.php    ← Single document view
│       └── rewav-docs-settings-page.php    ← Settings page
├── languages/
│   └── rewav-docs.pot
└── vendor/ (if Composer dependencies are used)
    └── (Markdown parser library)
```

### Design Pattern
The plugin uses the **Service Locator + Loader pattern** popularized by the [WordPress Plugin Boilerplate](https://wppb.me/). This pattern is familiar to WP developers, keeps hooks organized in one place, and makes the codebase easy to follow for AI-assisted development.

- **No global functions** exposed to the plugin namespace beyond the bootstrap entry point.
- **No static god-classes** — dependencies are injected where needed.
- All classes follow the `Rewav_Docs_*` naming convention, prefixed consistently to avoid collisions.

---

## 4. File & Folder Structure

### Default Documentation Folder
```
wp-content/
└── uploads/
    └── rewav-docs/
        ├── getting-started.md
        ├── api-reference.md
        └── subdirectory/
            └── advanced-config.md
```

**Why `uploads/`?** The `uploads` directory is the only location on a standard WordPress install that is guaranteed to be writable by the web server and outside version control by default. Developers can drop `.md` files here (via SFTP, CI/CD pipeline, etc.) without touching the plugin itself. A constant allows overriding to any absolute path.

### Folder Discovery Rules
- The plugin scans **recursively** up to a configurable depth (default: 3 levels).
- Only files with the `.md` or `.markdown` extension are processed.
- Files and folders whose names begin with `_` or `.` (dot-files) are **ignored** (e.g., `_drafts/`, `.git/`).
- A maximum of **200 files** are indexed per scan to prevent runaway performance issues on misconfigured installs (this limit is filterable via hook).

---

## 5. Constants & Configuration

### Developer-Facing Constants (defined in `wp-config.php`)

```php
// Override the documentation folder path (absolute path, no trailing slash).
// Default: WP_CONTENT_DIR . '/uploads/rewav-docs'
define( 'REWAV_DOCS_PATH', '/var/www/html/docs' );

// Override the maximum folder scan depth.
// Default: 3
define( 'REWAV_DOCS_SCAN_DEPTH', 5 );

// Disable the built-in Markdown renderer and bring your own.
// Default: false
define( 'REWAV_DOCS_DISABLE_RENDERER', false );

// Enable debug mode (outputs scan errors to error_log, visible to admins).
// Default: false
define( 'REWAV_DOCS_DEBUG', false );
```

### Constant Resolution Priority
The plugin resolves configuration in this order (highest to lowest priority):

1. PHP Constant defined in `wp-config.php`
2. Value stored in the WordPress Settings page (if no constant overrides it)
3. Plugin default

When a constant is in use, the corresponding Settings UI field must be **disabled and display a notice**: _"This setting is currently overridden by the `REWAV_DOCS_PATH` constant in wp-config.php."_

---

## 6. Core Features — Detailed Specs

### 6.1 File Scanner (`Rewav_Docs_File_Scanner`)

**Responsibility:** Discover all valid Markdown files in the configured directory.

**Behavior:**
- Uses PHP's `RecursiveDirectoryIterator` and `RecursiveIteratorIterator`.
- Returns an array of `Rewav_Docs_File` value objects, each containing:
  - `string $absolute_path` — Full server path to the file.
  - `string $relative_path` — Path relative to the docs root (used for display).
  - `string $slug` — URL-safe identifier derived from the relative path (forward slashes replaced with `--`).
  - `string $title` — Derived from the first H1 in the file, falling back to the filename (without extension, underscores/hyphens replaced with spaces, title-cased).
  - `int $modified_time` — Unix timestamp of last file modification (for cache invalidation).
- Results are cached using the **WordPress Transients API** with a 5-minute TTL. Cache is busted when:
  - A user visits the Documentation index page and the newest file `mtime` differs from the cached value.
  - An admin manually triggers "Refresh File Index" from the settings page.
- The scan **never** exposes file contents to unauthenticated users or via REST API unless explicitly enabled.

**Error Conditions:**
- Docs directory does not exist → Show admin notice with setup instructions.
- Docs directory exists but is unreadable (permissions) → Show admin notice with the path and suggested `chmod` command.
- No `.md` files found → Show a helpful empty-state message in the admin UI.

### 6.2 Markdown Renderer (`Rewav_Docs_Markdown_Renderer`)

**Responsibility:** Convert raw Markdown content to safe, well-formed HTML.

**Library Recommendation:** [Parsedown](https://parsedown.org/) or [league/commonmark](https://commonmark.thephpleague.com/).
- **Parsedown** — Single-file, no Composer required, very fast, widely trusted in the WP ecosystem.
- **league/commonmark** — Fully CommonMark-spec-compliant, extensible, requires Composer.
- **Decision for v1:** Use Parsedown (bundled in `/vendor/`) to keep the plugin self-contained and Composer-optional. Switch to league/commonmark if spec compliance becomes a requirement.

**Security — CRITICAL:**
- All rendered HTML **must** pass through `wp_kses_post()` before output **OR** use a stricter custom allowed-tags list appropriate for documentation (no `<script>`, no inline event handlers, no `<iframe>` by default).
- Raw file contents must **never** be output without sanitization.
- Mermaid/diagram rendering (v2 roadmap) must be treated as untrusted content.

**Supported Markdown Features (v1):**
- Headings (H1–H6)
- Bold, italic, strikethrough
- Ordered and unordered lists (nested)
- Inline code and fenced code blocks (with language hint for syntax highlighting)
- Blockquotes
- Horizontal rules
- Links (external links open in `_blank` with `rel="noopener noreferrer"`)
- Images (rendered only if URL is absolute HTTPS or a relative path within the docs folder)
- Tables (GFM-style)

**Syntax Highlighting:**
- Use [Prism.js](https://prismjs.com/) (loaded only on documentation pages, not sitewide).
- Languages to support in v1: `php`, `javascript`, `bash`, `json`, `yaml`, `html`, `css`, `sql`.

### 6.3 Dashboard Menu & Navigation

**Menu Registration:**
```php
add_menu_page(
    __( 'Documentation', 'rewav-docs' ),   // Page title
    __( 'Documentation', 'rewav-docs' ),   // Menu title
    'rewav_docs_view',                      // Custom capability (see §6.5)
    'rewav-docs',                           // Menu slug
    [ $this, 'render_index_page' ],         // Callback
    'dashicons-media-document',             // Icon
    3                                       // Position (below Dashboard, above Posts)
);
```

**Submenus:**
- `rewav-docs` → Documentation (index, lists all docs)
- `rewav-docs-settings` → Settings (admin-only)

**Navigation within the plugin:**
- The index page groups documents by their subdirectory (top-level folder = section).
- Each document links to a single-document view using a `?page=rewav-docs&doc={slug}` query parameter.
- Breadcrumb navigation on document view: `Documentation > {Section} > {Title}`.
- A "Back to Documentation" link is always visible.

### 6.4 Document View Page

**URL Pattern:** `wp-admin/admin.php?page=rewav-docs&doc={slug}`

**Rendering Steps:**
1. Receive `doc` parameter → sanitize with `sanitize_text_field()`.
2. Resolve slug back to absolute path via the File Scanner's index.
3. Verify the resolved path is within the configured docs root (path traversal prevention — **mandatory**).
4. Read file contents with `WP_Filesystem`.
5. Pass contents through Markdown renderer.
6. Output through the admin template partial.

**Page Layout:**
- Left sidebar: Full document tree (collapsible sections) for easy navigation.
- Main content area: Rendered Markdown with a floating "scroll to top" button for long documents.
- Top bar: Document title, last modified date, a "Copy path" button for developers.

### 6.5 Capabilities & Roles

The plugin registers **two custom capabilities** to allow fine-grained access control:

| Capability | Default Role | Description |
|---|---|---|
| `rewav_docs_view` | Editor, Administrator | Can view documentation pages |
| `rewav_docs_manage` | Administrator | Can access plugin settings |

Capabilities are added to roles on plugin **activation** and removed on **uninstall** (not deactivation).

Roles are mapped via a filterable array so site owners can assign capabilities to custom roles:
```php
apply_filters( 'rewav_docs_default_capabilities', [
    'administrator' => [ 'rewav_docs_view', 'rewav_docs_manage' ],
    'editor'        => [ 'rewav_docs_view' ],
] );
```

### 6.6 Settings Page

Built using the **WordPress Settings API** (`register_setting`, `add_settings_section`, `add_settings_field`).

**Settings Fields:**

| Field | Type | Description |
|---|---|---|
| Documentation Path | Text | Absolute path to docs folder. Disabled if `REWAV_DOCS_PATH` constant is set. |
| Scan Depth | Number (1–10) | Max folder recursion depth. |
| Default Role Access | Multi-checkbox | Which roles get `rewav_docs_view`. |
| Enable Syntax Highlighting | Toggle | Load Prism.js on docs pages. |
| Refresh File Index | Button | Manually busts the file scanner cache. |

All settings stored under a single `rewav_docs_options` option key as a serialized array (one `get_option()` call on load).

---

## 7. Admin UI Specifications

### Design Guidelines
- Use WordPress's native admin UI components: `.wp-list-table`, `.notice`, `.button`, `.postbox`, `.card`.
- Do **not** introduce a custom design system or external CSS framework (no Bootstrap, no Tailwind).
- The plugin's CSS file should be minimal — only styles that are not already provided by WP core.
- Dark mode: Use CSS variables from WP 5.7+ admin color scheme system where possible.

### Index Page (Documentation List)
- Displays documents in a `<table>` using `.wp-list-table` class.
- Columns: Title, Section (subdirectory), Last Modified, Actions (View).
- Sortable by Title and Last Modified.
- A search/filter input (client-side JS filter, no AJAX needed for v1).
- Empty state illustration + instructions if no docs are found.

### Document View Page
- Rendered content is wrapped in a `<div class="rewav-docs-content">` container.
- The container's CSS scopes all Markdown-generated styles (headings, code blocks, tables) so they don't bleed into WP admin styles.
- "Print this document" link (uses `window.print()` with a print-specific CSS that hides the WP sidebar).

---

## 8. Security Requirements

This section is treated as non-negotiable for WP.org repository acceptance.

### 8.1 Path Traversal Prevention
**Threat:** An attacker crafts a `doc` slug containing `../` sequences to read files outside the docs directory.

**Mitigation:**
```php
// After resolving the slug to an absolute path:
$real_docs_root = realpath( $this->get_docs_path() );
$real_file_path = realpath( $resolved_path );

if ( false === $real_file_path || 0 !== strpos( $real_file_path, $real_docs_root ) ) {
    wp_die( esc_html__( 'Invalid document.', 'rewav-docs' ), 403 );
}
```

### 8.2 Nonces
- All admin form submissions and AJAX requests **must** use WordPress nonces (`wp_nonce_field`, `check_admin_referer`, `wp_verify_nonce`).
- The "Refresh Index" action uses a nonce tied to `rewav_docs_refresh_index`.

### 8.3 Capability Checks
- Every admin page callback **must** begin with a `current_user_can()` check.
- No content is ever rendered before the capability check passes.

### 8.4 Output Escaping
- All output follows the WP escaping rule: **escape late, escape everything**.
- Functions used: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
- Translated strings use their escaping equivalents: `esc_html__()`, `esc_attr__()`.

### 8.5 Input Sanitization
- All `$_GET` and `$_POST` values sanitized before use.
- File paths sanitized and validated against the docs root before any file operation.
- Settings values sanitized in the `sanitize_callback` registered with `register_setting()`.

### 8.6 WP_Filesystem
- **All** file read operations use the `WP_Filesystem` API, not raw `file_get_contents()`.
- This is required for WP VIP and strongly preferred by the WP.org review team.

---

## 9. Performance Requirements

| Concern | Solution |
|---|---|
| File system scans are slow | Cache results with Transients API (5-min TTL, busted on demand) |
| Large MD files render slowly | Cache rendered HTML per-file in a transient (key = slug + mtime hash) |
| Prism.js is large | Load only on `rewav-docs` admin pages, not globally |
| DB queries | Plugin should make **zero** additional DB queries on front-end pages |
| Memory | File contents are read and rendered on-demand, not preloaded en masse |

---

## 10. Accessibility Requirements

The plugin must meet **WCAG 2.1 AA** standards in the admin UI.

- All interactive elements are keyboard navigable.
- Focus styles are visible and meet contrast requirements.
- Images in rendered Markdown without `alt` text are flagged in the document view (developer notice, not an error).
- The document sidebar navigation uses a `<nav aria-label="Documentation navigation">` landmark.
- The rendered Markdown container has `role="main"` and an appropriate `aria-label`.
- Admin notices use the standard WP notice pattern with correct ARIA roles.

---

## 11. Internationalization (i18n)

- All user-facing strings are wrapped in `__()`, `_e()`, `_n()`, or their escaping equivalents.
- Text domain: `rewav-docs` (must match the `Text Domain` plugin header exactly).
- A `.pot` file is generated and committed in `/languages/`.
- `load_plugin_textdomain()` is called on the `plugins_loaded` action (not `init`).
- Date formatting uses `date_i18n()`, not `date()`.

---

## 12. WordPress Coding Standards Checklist

The following tools must be configured and pass with zero errors before any PR is merged:

| Tool | Config | Notes |
|---|---|---|
| PHP_CodeSniffer | `WordPress-Core` + `WordPress-Extra` rulesets | Run via `composer phpcs` |
| PHP_CodeSniffer | `WordPress-Docs` ruleset | All classes/functions must have docblocks |
| PHPStan | Level 6 | Via `composer phpstan` |
| PHPMD | WordPress-friendly ruleset | Check for unnecessary complexity |
| ESLint | `@wordpress/eslint-plugin` | Applied to admin JS |
| Stylelint | `@wordpress/stylelint-config` | Applied to admin CSS |

`.editorconfig` file must be present with WP standard indentation (tabs, not spaces).

---

## 13. Plugin Repository Compliance

Items required for submission to wordpress.org/plugins:

### readme.txt
Must contain:
- `=== Rewav Docs ===` header block with all required fields.
- `== Description ==` section (at least 300 characters).
- `== Installation ==` section.
- `== Frequently Asked Questions ==` section.
- `== Screenshots ==` section (at least 2 screenshots).
- `== Changelog ==` with version history.
- `== Upgrade Notice ==` section.

### No Proprietary Code
- No calls to external APIs in v1 without explicit user consent.
- No telemetry, tracking, or usage reporting.
- No obfuscated code.
- No base64-encoded executable strings.

### No Bundled Vulnerabilities
- All bundled third-party libraries (Parsedown, Prism.js) must be:
  - Explicitly declared with version and license in `readme.txt`.
  - Up to date with no known CVEs at time of submission.
  - Licensed under GPL-compatible licenses.

### Assets
- `/assets/` directory (at WP.org SVN root level, outside the plugin folder) containing:
  - `banner-772x250.png` and `banner-1544x500.png`
  - `icon-128x128.png` and `icon-256x256.png`
  - `screenshot-1.png`, `screenshot-2.png` (matching `== Screenshots ==` in readme.txt)

---

## 14. Hooks & Extensibility API

The plugin must expose a documented public API via WordPress hooks. This is essential for the plugin to be genuinely useful in real-world projects where teams need to customize behavior without modifying the plugin source.

### Filters

```php
// Filter the absolute path to the documentation folder.
apply_filters( 'rewav_docs_folder_path', string $path );

// Filter the maximum scan depth.
apply_filters( 'rewav_docs_scan_depth', int $depth );

// Filter the array of discovered file objects before caching.
apply_filters( 'rewav_docs_files', array $files );

// Filter the raw Markdown content before rendering.
apply_filters( 'rewav_docs_pre_render', string $markdown, string $file_path );

// Filter the rendered HTML after conversion (before output escaping).
apply_filters( 'rewav_docs_rendered_html', string $html, string $file_path );

// Filter the maximum number of files indexed.
apply_filters( 'rewav_docs_max_files', int $max );

// Filter which file extensions are treated as Markdown.
apply_filters( 'rewav_docs_allowed_extensions', array $extensions ); // Default: ['md', 'markdown']

// Filter the capability required to view documentation.
apply_filters( 'rewav_docs_view_capability', string $capability ); // Default: 'rewav_docs_view'

// Filter the menu position of the Documentation menu item.
apply_filters( 'rewav_docs_menu_position', int $position ); // Default: 3

// Filter the transient cache TTL in seconds.
apply_filters( 'rewav_docs_cache_ttl', int $seconds ); // Default: 300
```

### Actions

```php
// Fires after the file index is refreshed/rebuilt.
do_action( 'rewav_docs_index_refreshed', array $files );

// Fires before a document is rendered.
do_action( 'rewav_docs_before_render', string $slug, string $file_path );

// Fires after a document is rendered.
do_action( 'rewav_docs_after_render', string $slug, string $html );

// Fires on plugin activation.
do_action( 'rewav_docs_activated' );

// Fires on plugin deactivation.
do_action( 'rewav_docs_deactivated' );
```

---

## 15. Data Flow Diagrams

### File Discovery Flow
```
Plugin Load
    │
    ▼
Read REWAV_DOCS_PATH constant (or wp-config fallback → Settings → default)
    │
    ▼
Check Transient: 'rewav_docs_file_index'
    │
    ├─ HIT ──► Return cached file list
    │
    └─ MISS ─► Scan filesystem (RecursiveDirectoryIterator)
                │
                ▼
              Filter files by extension
                │
                ▼
              Build Rewav_Docs_File[] array
                │
                ▼
              apply_filters('rewav_docs_files', $files)
                │
                ▼
              set_transient('rewav_docs_file_index', $files, TTL)
                │
                ▼
              Return file list
```

### Document Render Flow
```
Request: ?page=rewav-docs&doc={slug}
    │
    ▼
current_user_can('rewav_docs_view') → FAIL → wp_die(403)
    │ PASS
    ▼
Sanitize slug from $_GET['doc']
    │
    ▼
Resolve slug → absolute path (via file index)
    │
    ▼
Path traversal check (realpath comparison) → FAIL → wp_die(403)
    │ PASS
    ▼
Check Transient: 'rewav_docs_render_{hash}'
    │
    ├─ HIT ──► Output cached HTML
    │
    └─ MISS ─► WP_Filesystem->get_contents($path)
                │
                ▼
              apply_filters('rewav_docs_pre_render', $markdown, $path)
                │
                ▼
              Parsedown::text($markdown)
                │
                ▼
              apply_filters('rewav_docs_rendered_html', $html, $path)
                │
                ▼
              wp_kses_post($html)
                │
                ▼
              set_transient('rewav_docs_render_{hash}', $html, TTL)
                │
                ▼
              Output to template partial
```

---

## 16. Error Handling Strategy

| Scenario | Behavior |
|---|---|
| Docs folder does not exist | Admin notice (warning) with setup instructions on all Documentation admin pages |
| Docs folder is not readable | Admin notice (error) with path and suggested `chmod 755` |
| No `.md` files found | Friendly empty-state UI with documentation link |
| A specific `.md` file is unreadable | File is skipped during scan; a dismissible notice is shown listing skipped files |
| Markdown rendering throws an exception | Caught, logged via `error_log()` if `REWAV_DOCS_DEBUG` is true, fallback to showing raw Markdown in a `<pre>` block |
| Invalid or tampered `doc` slug | `wp_die()` with 403 HTTP status |
| Transient storage fails | Graceful degradation — scan/render without caching, no fatal error |

---

## 17. Testing Requirements

### Unit Tests (PHPUnit + WP_Mock)
- `Rewav_Docs_File_Scanner`: Test discovery, depth limiting, ignored files, empty directory, unreadable directory.
- `Rewav_Docs_Markdown_Renderer`: Test rendering of each supported element, XSS prevention, path traversal attempt.
- `Rewav_Docs_Settings`: Test sanitization callbacks for all fields.
- `Rewav_Docs_Capabilities`: Test capability assignment on activation and removal on uninstall.

### Integration Tests (WP_UnitTestCase)
- Full admin page load for index and document view.
- Settings save and retrieval.
- Constant overrides respected over DB settings.

### Browser/E2E Tests (Playwright or Cypress — optional for v1)
- Navigate to Documentation menu.
- Click a document and verify rendered HTML appears.
- Attempt path traversal via URL manipulation.

### Automated CI
- GitHub Actions workflow running:
  1. PHPCS (coding standards)
  2. PHPStan (static analysis)
  3. PHPUnit (unit + integration tests)
  4. ESLint + Stylelint
- Triggered on every PR and push to `main`.

---

## 18. Future Roadmap (v2+)

These features are **out of scope for v1** but should be designed for in the architecture:

| Feature | Notes |
|---|---|
| **Full-text search** | Client-side (Fuse.js) or WP REST-powered search across all docs content |
| **Front-end public docs page** | Optional shortcode/block to expose docs on the public site with access control |
| **Gutenberg Block** | `[rewav-docs-embed slug="..."]` block to embed a document inside a post/page |
| **Mermaid.js diagram support** | Render `\`\`\`mermaid` code blocks as diagrams |
| **Versioning support** | Support `v1/`, `v2/` subdirectory conventions as version switcher |
| **Dark mode** for rendered docs | Follow WP admin color scheme |
| **Export to PDF** | Browser print + CSS print stylesheet |
| **Changelog auto-detection** | Detect `CHANGELOG.md` at root and surface it prominently |
| **WP-CLI command** | `wp rewav-docs refresh-index` to bust cache from CLI |
| **Multisite support** | Per-site docs path configuration in network installs |

---

## 19. Open Questions & Decisions Needed

Before development begins, the following decisions must be confirmed:

1. **Markdown parser:** Parsedown (no Composer) vs. league/commonmark (Composer required, spec-compliant)? Recommendation: Parsedown for v1.

2. **Composer usage:** Should the plugin ship with a `vendor/` directory (simpler for end users) or require Composer during development only and bundle the result? Recommendation: Bundle `vendor/` for WP.org distribution.

3. **Cache strategy for WP VIP:** WP VIP environments do not support filesystem-based caching. If WP VIP compatibility is a goal, transients must use `wp_cache_set()` / object cache instead. Decision needed on target audience.

4. **Plugin slug ownership:** Has `rewav-docs` been checked for availability on wordpress.org/plugins? Must be verified before any WP.org submission preparation.

5. **Screenshots and branding:** Who is responsible for the banner/icon assets required by the WP.org plugin directory?

6. **Author / WP.org account:** The plugin must be submitted from a registered WP.org account. Which account will own this plugin?

7. **Recursive scan default depth:** Is 3 levels deep enough for the expected folder structures, or should the default be higher?

8. **Multisite v1:** Should v1 include basic multisite support (network-activated plugin, per-site path), or is this strictly a single-site v1?

---

*This specification was prepared following WordPress VIP Advanced Professional development standards, WordPress Coding Standards, and the WordPress Plugin Handbook guidelines for repository-ready plugins.*

*Next step: Review open questions (§19), make decisions, then proceed to scaffolding with Claude Code or Gemini CLI using this document as the source of truth.*
