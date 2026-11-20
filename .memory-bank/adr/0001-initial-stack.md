# 0001: Initial Stack

- **Status**: Accepted
- **Confidence**: Verified
- **Updated**: 2026-11-20

## Context
We are building and maintaining a modern WordPress plugin named "GNN Lightbox" under `gnn-lightbox/`.

## Decision
- Language: PHP 7.4+ (Backend), Vanilla ES6+ JS (Frontend), CSS3 (Styling)
- Framework: Vanilla JS, Vanilla CSS3 (no jQuery, no image sprites; SVG + CSS variables)
- Paradigm: WordPress Plugin (`Requires at least: 6.0`, `Tested up to: 7.1`)
- Updates: GitHub Releases via `GNN_Lightbox_Updater` (`gnn-lightbox/class-gnn-lightbox-updater.php`)
- CI/CD: GitHub Actions (`.github/workflows/release.yml` using `actions/checkout@v7`, `actions/upload-artifact@v7`, `softprops/action-gh-release@v3`)

## Consequences
- Requires strict adherence to GNN Plugin Standards.
- Zero external frontend runtime dependencies.
