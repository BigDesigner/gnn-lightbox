# Pipeline

## Completed Sprints
- [x] S01: Sentinel Memory Bank Bootstrapped (2026-08-04)
- [x] S02: Plugin files created under `gnn-lightbox/` (GNN standards, `v1.0.0`)
  - [x] `gnn-lightbox/VERSION`
  - [x] `gnn-lightbox/gnn-lightbox.php` (Admin menu `79.108`, action links)
  - [x] `gnn-lightbox/class-gnn-lightbox-updater.php`
  - [x] `.github/workflows/release.yml`
  - [x] `gnn-lightbox/languages/gnn-lightbox.pot`
- [x] S03: Modern frontend & settings UI (`v1.1.0`, 2026-08-04)
  - [x] Vanilla JS & CSS3 lightbox, SVG icons, light/dark themes, gallery navigation, legacy & Gutenberg modes
  - [x] `.archive/` removed
- [x] S04: WordPress 7.1.4 compatibility, security & CI/CD audit (`v1.2.0`, 2026-11-18)
  - [x] `init` textdomain hook, updater `$slug` fix, `actions/checkout@v7`, `upload-artifact@v7`, `action-gh-release@v3`, `.avif` support, `isSafeUrl()`, WCAG modal attributes
- [x] S05: Gutenberg override & focus-trap hardening (`v1.2.1`, 2026-11-20)
  - [x] Fixed `link.closest` crash in Gutenberg `override` mode
  - [x] Fixed single-quote `class='...'` regex, unified gallery selector, added `Tab` focus trap and empty `href` shortcode guard

## Backlog
- None (up to date as of `v1.2.1`).
