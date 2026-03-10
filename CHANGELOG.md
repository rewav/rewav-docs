# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-03-09

### Added
- Full-Text Search functionality across all documentation files.
- Mermaid.js support for rendering diagrams in Markdown.
- Relative image resolution for local documentation images.
- Bitbucket Pipelines configuration for automated ZIP packaging.

### Fixed
- Relative image resolution for symlinked environments (e.g., Kinsta).
- Documentation table overflow issues in the Admin UI.

### Changed
- Increased content max-width to 1200px for better readability.

## [1.0.0] - 2026-03-09

### Added
- Initial release.
- Markdown discovery and rendering.
- Admin documentation index and single view.
- Plugin settings for path and scan depth.
- Support for custom capabilities `rewav_docs_view` and `rewav_docs_manage`.
