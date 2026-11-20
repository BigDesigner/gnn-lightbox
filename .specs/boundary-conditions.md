# Boundary Conditions

- Do not use jQuery. Use Vanilla JavaScript (ES6+).
- Do not use old CSS sprites for UI elements. Use SVG, `border-radius`, `box-shadow`, and CSS custom properties.
- Do not output PHP errors. Use standard WordPress security functions (`esc_html`, `esc_attr`, `esc_url`, `esc_url_raw`, `check_admin_referer`, `current_user_can`) strictly.
- Load plugin textdomain on `init` (priority `1`), never on `plugins_loaded`.
- Validate image URLs in JS (`http:` / `https:` only) and never pass untrusted text into `innerHTML`.
- Add `rel="noopener noreferrer"` to all external `target="_blank"` links.
- Preserve WCAG modal accessibility (`role="dialog"`, `aria-modal="true"`, keyboard navigation, focus trap, and focus restoration).
