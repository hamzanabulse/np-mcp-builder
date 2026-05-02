# Security Audit — np-mcp-builder

Date: 2026-05-02
Plugin version: 1.3.0
Tester: Hamza Ali Nabulsi
Environment: WordPress 6.9.4, PHP 8.3.6, Ubuntu 24.04

This document records the result of running the in-repo audit harness
(`bin/audit-security.sh`) plus a few hand checks. The harness wraps
PHP CodeSniffer with the WordPress and PHPCompatibilityWP standards.

## 1. PHPCS — WordPress security sniffs

Sniffs run:

- `WordPress.Security.NonceVerification`
- `WordPress.Security.ValidatedSanitizedInput`
- `WordPress.Security.EscapeOutput`
- `WordPress.Security.PluginMenuSlug`
- `WordPress.Security.SafeRedirect`
- `WordPress.DB.PreparedSQL`
- `WordPress.DB.PreparedSQLPlaceholders`
- `WordPress.DB.DirectDatabaseQuery`
- `WordPress.WP.AlternativeFunctions`
- `WordPress.PHP.NoSilencedErrors`

Result after the 1.3.0 hardening pass:

```
A TOTAL OF 0 ERRORS AND 0 WARNINGS
```

### Issues found and fixed in this audit

| Where | Severity | Fix |
|-------|----------|-----|
| `class-plugin.php:166` — `$_SERVER['REQUEST_URI']` read without unslash/sanitize | error | wrapped in `sanitize_text_field( wp_unslash( ... ) )` |
| `class-settings.php:308` — `$_GET['cleared']` read without unslash | error | wrapped in `sanitize_key( wp_unslash( ... ) )` and documented as a display-only flag set by the nonce-verified admin-post handler |
| `class-settings.php:148` — tab routing param without nonce | warning (false positive) | annotated with phpcs:ignore + reason; this is a read-only routing param, not a form submission |
| `class-image-generator.php` — four `@unlink( … )` calls | warning | replaced with `wp_delete_file( … )` |
| `class-image-generator.php:100` — `file_put_contents` to `wp_tempnam` path | warning (intentional) | annotated with phpcs:ignore + reason; binary write to an internally-controlled path |
| `class-site-abilities.php:346` — direct `$wpdb->query` for transient purge | warning (intentional) | annotated with phpcs:ignore + reason; per-row deletion would be O(N) and there is no caching layer for the options table itself |

## 2. PHP 8.0 → 8.4 compatibility

`PHPCompatibilityWP`, runtime-set `testVersion = 8.0-`:

```
0 issues
```

## 3. Static checks (grep)

| Check                                                    | Hits |
|----------------------------------------------------------|-----:|
| `eval(`                                                  | 0    |
| `exec` / `shell_exec` / `passthru` / `system` / `popen`  | 0    |
| `file_get_contents` on a `$_*` superglobal               | 0    |
| `include`/`require` of a variable path                   | 0    |
| `unserialize()`                                          | 0    |
| `echo $_*`                                               | 0    |
| `$wpdb->query` with interpolated string                  | 0    |
| Hard-coded keys / secrets                                | 0    |
| Files missing `ABSPATH` guard (excluding bootstrap and `uninstall.php`) | 0 |

Hardening signals (counted, just for reference — these are *good* numbers):

| Signal                                                   | Hits |
|----------------------------------------------------------|-----:|
| `permission_callback` on `wp_register_ability` calls     | 49   |
| `current_user_can()` calls                               | 54   |
| `esc_html` / `esc_attr` / `esc_url` / `esc_textarea`     | 45   |
| `sanitize_*()` calls                                     | 43+  |
| `wp_unslash()` calls                                     | 2    |
| `wp_verify_nonce` / `check_admin_referer` / `wp_nonce_field` | 3 |

## 4. Permission audit

Every entry in `Plugin::ABILITY_MAP` (49 items) registers via
`wp_register_ability` with a `permission_callback`.

```
includes/abilities/class-content-abilities.php       :  5
includes/abilities/class-elementor-abilities.php     :  4
includes/abilities/class-elementor-data-abilities.php:  6
includes/abilities/class-image-abilities.php         :  1
includes/abilities/class-menu-abilities.php          :  5
includes/abilities/class-seo-abilities.php           :  6
includes/abilities/class-site-abilities.php          : 11
includes/abilities/class-taxonomy-abilities.php      :  5
includes/abilities/class-theme-abilities.php         :  2
includes/abilities/class-user-abilities.php          :  4
                                              total : 49
```

`current_user_can()` capability frequency:

```
17  edit_posts
13  manage_options
 9  edit_theme_options
 3  manage_categories
 3  activate_plugins
 2  switch_themes
 1  upload_files
 1  publish_posts
 1  list_users
 1  edit_users
 1  edit_post
 1  edit_pages
 1  delete_users
 1  create_users
```

## 5. Direct-access protection

- All PHP files inside `includes/` and `admin/` start with
  `if ( ! defined( 'ABSPATH' ) ) { exit; }`.
- `uninstall.php` checks `WP_UNINSTALL_PLUGIN`.
- The bootstrap file `np-mcp-builder.php` checks `ABSPATH` immediately
  after the plugin header.

## 6. Operational guards

- `np/deactivate-plugin` resolves
  `plugin_basename( NP_MCP_BUILDER_FILE )` and refuses to deactivate
  itself.
- `np/delete-user` refuses to delete the current user
  (`get_current_user_id()`).
- The maintenance-mode handler short-circuits when
  `WP_CLI` is set or the URI starts with `/wp-admin` or `/wp-login`,
  so the operator can always recover.
- All cache-clearing entry points run `current_user_can( 'manage_options' )`.

## 7. End-to-end smoke test

After the security pass the full smoke test still passes:

```
=== Registration ===
Expected:   49
Registered: 49
Missing:    0

=== Read-only callback dry-runs ===
[OK] np/site-info
[OK] np/list-posts
[OK] np/list-plugins
[OK] np/list-themes
[OK] np/list-menus
[OK] np/list-users
[OK] np/system-info
[OK] np/get-yoast-global
[OK] np/get-elementor-kit
[OK] np/elementor-list-templates
[OK] np/audit-seo
```

MCP HTTP transport (`tools/list` + three `tools/call`) returns
expected JSON; see `bin/smoke-call.sh`.

## 8. Reproduce

```bash
git clone https://github.com/hamzanabulse/np-mcp-builder.git
cd np-mcp-builder
bash bin/audit-security.sh
```

The harness installs PHPCS + WP standards in `/tmp/np-audit/` on
first run.
