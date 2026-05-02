# Security Policy

## Supported Versions

Only the latest minor release receives security fixes.

| Version | Supported |
|---------|-----------|
| 1.3.x   | yes       |
| 1.2.x   | no        |
| < 1.2   | no        |

## Reporting a Vulnerability

If you find a vulnerability, please do not open a public issue.

Email **hamzaalinabulsi@gmail.com** with:

- a short description of the issue,
- the affected version (output of `wp plugin status np-mcp-builder`),
- steps to reproduce, and
- whatever proof-of-concept you have.

You can expect a first reply within 72 hours and a fix or mitigation
plan within 14 days for confirmed issues. Coordinated disclosure is
preferred — once a fix is shipped we will credit the reporter in the
release notes unless you ask us not to.

## Threat Model

This plugin exposes WordPress functionality as MCP tools. The hostile
parties we worry about are:

1. **Unauthenticated visitors** hitting the plugin's MCP endpoints
   directly. Mitigated by `permission_callback` on every ability and
   by the `mcp-adapter` plugin's own auth layer.
2. **Authenticated low-privilege users** trying to call abilities that
   should require `manage_options`. Mitigated by mapping each ability
   to a real WordPress capability (see the matrix below).
3. **AI clients** (Claude, ChatGPT…) that have been instructed by a
   malicious prompt. The plugin treats every input as untrusted: all
   string inputs go through `sanitize_*` / `wp_kses_post`, all SQL
   uses `$wpdb->prepare` or controlled `LIKE` patterns, and dangerous
   operations (`np/deactivate-plugin`, `np/delete-user`) refuse to act
   on the running plugin or current user.
4. **Direct file access** to plugin files. Every PHP file checks for
   `ABSPATH` and `uninstall.php` checks for `WP_UNINSTALL_PLUGIN`.

## Capability Matrix

Each ability is registered with an explicit `permission_callback`.
The current mapping is:

| Capability             | Used by abilities |
|------------------------|-------------------|
| `manage_options`       | site settings, permalinks, maintenance, system info, Yoast global, kit, deactivate self-check |
| `edit_posts`           | post and Elementor read/write, taxonomy assign, SEO audit, get-seo-head |
| `edit_theme_options`   | theme mods, switch theme, kit update, menu management |
| `manage_categories`    | term create/update/delete |
| `activate_plugins`     | list / activate / deactivate plugins |
| `switch_themes`        | list / switch themes |
| `list_users`           | list users |
| `create_users`         | create user |
| `edit_users`           | update user |
| `delete_users`         | delete user (refuses current user) |
| `upload_files`         | image generation |
| `publish_posts`        | post creation |

## Hardening Notes

- All HTTP responses go through `WP_REST_Response` / Abilities API
  return values; no raw HTML is echoed from ability callbacks.
- `np/deactivate-plugin` resolves the running plugin's basename via
  `plugin_basename( NP_MCP_BUILDER_FILE )` and refuses to act on
  itself, so the operator cannot accidentally lock themselves out.
- `np/delete-user` reads `get_current_user_id()` and refuses to
  delete that ID.
- The maintenance-mode handler runs at `template_redirect` priority
  0, exits with `503` and a `Retry-After` header, but skips the
  request entirely when `WP_CLI` is defined or the URI starts with
  `/wp-admin` or `/wp-login` so the operator can always recover.
- The single direct database call (transient purge in `np/clear-cache`)
  uses two fixed `LIKE` patterns; user input never reaches the SQL.
- The Gemini API key is stored in the standard `wp_options` table
  through the Settings API; it is never echoed back in any response
  or admin field with `value=`.

## Audit Tooling

The repo ships its own audit harness at
[`bin/audit-security.sh`](bin/audit-security.sh). To reproduce locally:

```bash
bash bin/audit-security.sh
```

It runs:

1. `WordPress` PHPCS standard, security sniffs only.
2. `PHPCompatibilityWP` against PHP 8.0+.
3. Hand-rolled grep checks for dangerous functions, raw superglobals,
   missing `ABSPATH` guards, hard-coded secrets.
4. A pass that confirms every `wp_register_ability` call has a
   `permission_callback`.

The latest run is summarised in
[`docs/SECURITY-AUDIT.md`](docs/SECURITY-AUDIT.md).
