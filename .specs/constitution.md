# GNN Plugin Standard — Admin Menu, Action Links & GitHub Release

The following standards apply in this project.

## Project Info
- Plugin Name: GNN Lightbox
- FULL_SLUG: gnn-lightbox
- SHORT_SLUG: lightbox
- TEXT_DOMAIN: gnn-lightbox
- PLUGIN_PREFIX: gnn_lightbox
- PLUGIN_PREFIX_UPPER: GNN_LIGHTBOX
- GITHUB_OWNER: BigDesigner
- GITHUB_REPO: gnn-lightbox
- SETTINGS_PAGE_SLUG: lightbox-settings
- MENU_POSITION: 79.108

## 1. Admin Menu Position Scheme ("79.XXX")
All GNN plugins use a decimal, unique position value between 79 and WordPress's own "Settings" menu (80) in the add_menu_page() call: the 79.XXX format. This plugin uses `79.108`.

## 2. Plugin Row Action Links (Donate / Settings / Check Updates)
On wp-admin -> Plugins list, three links appear under the plugin's row in a fixed order: Donate -> Settings -> Check Updates.

## 3. Automatic Updates via GitHub Releases
Every plugin includes a `{PLUGIN_PREFIX}-updater` class that connects its own GitHub repository's Releases (vX.Y.Z tags) to WordPress's native update system.
The updater class lives in `class-gnn-lightbox-updater.php`.

## 4. Version Bumping & Release Workflow
Every plugin keeps a single version number that is updated in three places at once:
1. The `VERSION` file (repo root)
2. The `Version:` header in the main plugin file
3. `Project-Id-Version` in `languages/{FULL_SLUG}.pot`

`.github/workflows/release.yml` triggers on every `v*` tag push.
