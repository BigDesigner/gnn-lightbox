# Changelog

## [1.2.3] - 2026-11-25
### Fixed
- Updated `class-gnn-lightbox-updater.php` to read the local plugin version directly from disk via `get_local_version()` instead of relying on potentially stale WordPress update transients.

## [1.2.1] - 2026-11-20
### Fixed
- Fixed `TypeError: link.closest is not a function` crash in Gutenberg `override` mode (`gnn-lightbox/assets/js/gnn-lightbox.js`).
- Fixed `auto_lightbox_content()` regex breaking single-quoted `class='...'` attributes (`gnn-lightbox/gnn-lightbox.php`).
- Aligned `openLightbox()` gallery selector and `.wp-lightbox-container` exclusion with `bindLinks()` (`gnn-lightbox/assets/js/gnn-lightbox.js`).

### Improved
- Added `Tab` / `Shift+Tab` modal focus trap in `gnn-lightbox.js`.
- Returned shortcode inner content directly when `[gnn_lightbox]` `href` is empty.

## [1.2.0] - 2026-11-18
### Changed
- Moved `load_textdomain()` hook from `plugins_loaded` to `init` (priority `1`) for WordPress 6.7+ / 7.x compatibility.
- Updated plugin headers (`Requires at least: 6.0`, `Requires PHP: 7.4`) and updater metadata (`tested: 7.1`).
- Updated `.github/workflows/release.yml` actions (`checkout@v7`, `upload-artifact@v7`, `softprops/action-gh-release@v3`).

### Fixed & Hardened
- Fixed updater `$obj->slug` mismatch with `plugin_info()` and added transient response validation + `esc_url_raw()` sanitization.
- Added `isSafeUrl()` (`http:`/`https:` only) check in `gnn-lightbox.js` and `rel="noopener noreferrer"` on the Donate link.
- Added `.avif` and query-string support in `auto_lightbox_content()`.
- Added WCAG modal attributes (`role="dialog"`, `aria-modal="true"`), focus management, `alt` forwarding, mobile `inset: 0`, and `-webkit-backdrop-filter`.

## [1.1.0] - 2026-08-04
- Added admin settings card UI (status, auto-detect, legacy `lightbox` compatibility, Gutenberg mode, light/dark theme presets, gallery navigation).

## [1.0.0] - 2026-08-04
- Initial release built from scratch following GNN Plugin Standards (menu position `79.108`, action links, GitHub updater, Vanilla JS & CSS3).
