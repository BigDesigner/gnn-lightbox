# 0002: WordPress 7.x Compatibility, Security Hardening & WCAG Accessibility

- **Status**: Accepted
- **Confidence**: Verified
- **Date**: 2026-11-20

## Context
Following WordPress 6.7–7.1.5 core changes (JIT textdomain loading, Gutenberg Interactivity API `wp-lightbox-container`, native AVIF support) and 2026 lightbox plugin XSS CVEs, the plugin required compatibility, security, and accessibility updates.

## Decision
1. **Textdomain Loading**: Hook `load_plugin_textdomain()` to `init` with priority `1` instead of `plugins_loaded` to prevent `_load_textdomain_just_in_time` notices.
2. **URL & Output Hardening**:
   - Validate all image URLs in JS via `isSafeUrl()` (`http:` and `https:` only) to block `javascript:` / `data:` schemes.
   - Never inject untrusted text (`alt`, `title`, captions) via `innerHTML`; assign `img.alt` as a DOM property.
   - Add `rel="noopener noreferrer"` to external `target="_blank"` links and sanitize updater package URLs with `esc_url_raw()`.
   - Validate GitHub API response (`is_object` and `tag_name`) before caching in transient; align `$obj->slug` with `$this->slug`.
3. **Modern Media & Gutenberg**:
   - Support `.avif` and query-string URLs (`?v=...`) in `auto_lightbox_content()` while preserving quote types (`class=(["\'])`).
   - Support `ignore` (default) and `override` modes for `.wp-lightbox-container` without breaking on synthetic link objects (`closest`).
4. **Accessibility**: Add `role="dialog"`, `aria-modal="true"`, `Tab`/`Shift+Tab` focus trap, focus restoration on close, and keyboard handlers on navigation buttons.

## Consequences
- Full compatibility with WordPress 6.0–7.1.5+ and PHP 7.4+.
- Resistant to DOM/Stored XSS patterns seen in 2026 lightbox CVEs.
