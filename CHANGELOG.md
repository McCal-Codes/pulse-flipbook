# Changelog

All notable changes to this project are documented in this file.

## 0.1.6 - 2026-04-15

- Added a native dynamic Gutenberg block `pulse/issue-flipbook` for issue pages and templates.
- Added an editor placeholder and block controls for viewer height and download visibility.
- Added a styled empty-state fallback when an Issue has no PDF yet, instead of blank output.
- Updated auto-inject behavior to avoid duplicate rendering when block/template placement is present.

## 0.1.5 - 2026-04-15

- Improved Issue PDF picker behavior for wp-admin by aligning validation across Media modal selection and server-side attachment checks.
- Added clearer feedback for editors who cannot upload files due to missing capabilities.
- Refreshed plugin asset versions to ensure updated admin script behavior is loaded immediately.

## 0.1.4 - 2026-04-15

- Fixed Issue PDF media selection to accept PDF MIME variants and `.pdf` extension fallback.
- Added clearer admin feedback when a user lacks media upload permissions.
- Hardened attachment validation for flipbook source PDFs on save and render paths.
- Bumped admin and frontend asset versions for reliable cache invalidation.

