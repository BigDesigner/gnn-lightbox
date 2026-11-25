# 0003: Unified GNN GitHub Updater Standard

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-11-21

## Context
`GNN_Lightbox_Updater` had several gaps compared to the Unified GNN GitHub Updater Standard:
1. `load_updater()` in `gnn-lightbox.php` was gated behind `is_admin()`, preventing WP-Cron and WP-CLI background update checks.
2. No fallback when the GitHub REST API (`/releases/latest`) failed or was rate-limited.
3. No negative caching on transient failures, no semver tag validation, and no SSRF host allowlist validation on release URLs.
4. `check_for_update()` did not populate `no_update` or normalize `response`/`no_update` arrays.
5. `after_install()` did not capture/restore active plugin state (`upgrader_pre_install`), did not normalize paths with `wp_normalize_path()`, did not clear `wp_clean_plugins_cache(true)`, and did not delete the updater transient after installation.
6. `handle_manual_check()` lacked a `403` `wp_die()` response on capability failure, and `load-update-core.php` cache clearing was missing.

## Decision
Standardize `GNN_Lightbox_Updater` (`gnn-lightbox/class-gnn-lightbox-updater.php`) and `gnn-lightbox.php` on the Unified GNN GitHub Updater Standard:
- Remove `is_admin()` gating on `load_updater()` in `gnn-lightbox.php`.
- Register all standard hooks: `pre_set_site_transient_update_plugins`, `plugins_api`, `upgrader_pre_install`, `upgrader_post_install`, `admin_init`, and `load-update-core.php`.
- Implement `get_local_version()` using `GNN_LIGHTBOX_VERSION` with `get_plugin_data()` fallback.
- Implement `get_remote_release()` with GitHub API primary fetch, `wp_remote_head` + `/releases/expanded_assets/{tag}` fallback, `.zip` asset preference, semver validation, `(object)['failed' => true]` 300s negative cache, and `sanitize_github_url()` HTTPS host allowlist.
- Implement `check_for_update()`, `capture_pre_install_state()`, `after_install()`, `handle_manual_check()`, and `clear_cache()` per standard, preserving `gnn_lightbox_check_update`, `gnn_lightbox_manual_update`, and `gnn_lightbox_update_info`.

## Consequences
- Reliable plugin updates via wp-admin, WP-Cron, and WP-CLI, resilient to GitHub API rate limits.
- Consistent reactivation and directory normalization during upgrades.


## Amendment (2026-11-25): Disk-First Local Version Detection
- **Root Cause:** Reading in-memory version constants (`GNN_*_VERSION` / `$this->version`) first inside `get_local_version()` returned the pre-upgrade version when WordPress fired `pre_set_site_transient_update_plugins` within the same PHP request right after overwriting plugin files, causing the update banner to persist until a second update run.
- **Resolution:** `get_local_version()` now reads the live `Version` header directly from `WP_PLUGIN_DIR . '/' . $this->plugin_basename` via `clearstatcache( true, $main_file )` and `get_file_data()` first, falling back to in-memory constants only if the file is unavailable.
