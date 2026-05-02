<div align="center">

<img src=".github/social-preview.png" alt="NP MCP Builder — WordPress + Elementor MCP Control Plane" width="800">

# NP MCP Builder

**The complete WordPress + Elementor MCP control plane.**
49 high-level abilities for AI assistants — content, media, SEO, Elementor, site administration — exposed as MCP tools to Claude, ChatGPT and any MCP-compatible client.

[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-21759B?logo=wordpress&logoColor=white)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Elementor](https://img.shields.io/badge/Elementor-3.x-D63384?logo=elementor&logoColor=white)](https://elementor.com/)
[![Yoast SEO](https://img.shields.io/badge/Yoast%20SEO-supported-A4286A?logo=yoast&logoColor=white)](https://yoast.com/)
[![Gemini](https://img.shields.io/badge/Google%20Gemini-AI%20images-4285F4?logo=googlegemini&logoColor=white)](https://ai.google.dev/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](LICENSE)
[![Version](https://img.shields.io/badge/version-1.3.0-success)](https://github.com/hamzanabulse/np-mcp-builder/releases)
[![Security audit](https://img.shields.io/badge/PHPCS--security-passing-success)](docs/SECURITY-AUDIT.md)

[Installation](#-installation) • [Abilities](#-abilities-49-tools) • [Examples](#-examples) • [Architecture](#-architecture) • [Roadmap](#-roadmap)

</div>

---

## ✨ Why NP MCP Builder?

Most MCP servers for WordPress expose low-level CRUD over the REST API and force the AI to do all the heavy lifting (build Elementor JSON node-by-node, write JSON-LD by hand, juggle Yoast meta keys). **NP MCP Builder ships a higher-level vocabulary**: an AI sends one call like *"build a landing page about dental implants in Amman with 6 FAQs and a sticky WhatsApp button"* and the plugin produces a fully-styled Elementor page, generates a hero image with Gemini, sets the featured image, writes Yoast OG/Twitter tags, injects FAQPage + LocalBusiness + BreadcrumbList JSON-LD, and clears the Elementor CSS cache — in a single tool call.

It is built on top of the official [WordPress Abilities API](https://github.com/WordPress/abilities-api) and [WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter), so abilities are first-class WordPress citizens (visible to other plugins, REST-discoverable, permission-checked) and the MCP transport is handled by the official adapter.

---

## 🚀 Highlights

- **49 abilities** across 9 categories (Content, Media, Taxonomy, Theme, Elementor, Site, Menus, Users, SEO).
- **One-shot Elementor builders** — `np/elementor-build-blog` and `np/elementor-build-landing` accept a friendly schema (hero, faq, testimonials, pricing, stats, problem-agitation, benefits-grid, steps, CTA, video, …) and emit fully-styled pages.
- **AI-native image generation** — `np/generate-image` calls Google Gemini, resizes, converts to WebP, uploads to the Media Library with full SEO metadata (alt, title, caption, description).
- **Auto JSON-LD schema** — `Schema_Builder` produces FAQPage, LocalBusiness/ProfessionalService, Service, BreadcrumbList, WebPage with AggregateRating + Reviews; injected into `<head>` from post meta.
- **Deep Yoast integration** — read/write per-post meta (focus keyword, meta description, canonical, robots, OG title/desc/image, Twitter title/desc), read/write **global** Yoast settings (organization, person, social, sitemap, breadcrumbs), call Yoast's own `/yoast/v1/get_head` endpoint for any post or URL, and audit a whole site for missing SEO essentials.
- **Elementor power tools** — read/write raw `_elementor_data`, list / save / apply library templates, regenerate per-post CSS, read/write the active kit (global colors, typography, container width).
- **Full site administration** — list/activate/deactivate plugins (refuses self-deactivation), list/switch themes, update site settings, change permalink structure, clear caches (Elementor + object + transients), toggle maintenance mode, system info.
- **Tabbed admin dashboard** — Overview, Abilities (per-tool on/off toggles), Tools (one-click cache clear), Settings (Gemini key, image defaults), Maintenance, About.
- **Per-ability toggles** — disabled abilities are not registered with the Abilities API and not exposed via MCP — true zero-trust surface area.
- **Maintenance mode** — 503 page for visitors, admins keep working, `Retry-After` header for crawlers.

---

## 📋 Requirements

| | Required for |
|---|---|
| WordPress 6.9+ | Abilities API |
| PHP 8.0+ | Plugin |
| [WordPress/mcp-adapter](https://github.com/WordPress/mcp-adapter) | Exposing abilities as MCP tools over HTTP |
| [Elementor](https://wordpress.org/plugins/elementor/) (free) | `np/elementor-*` abilities |
| [Yoast SEO](https://wordpress.org/plugins/wordpress-seo/) (free) | `np/*-yoast-*`, `np/get-seo-head`, `np/audit-seo`, post-level SEO meta |
| Google AI Studio API key | `np/generate-image` |
| **Pro license key** | The 39 Pro abilities (everything beyond the free 10 read-only tools) |

---

## 💎 Free vs Pro

The plugin ships with **10 free read-only abilities** out of the box — enough to discover, audit and inspect a WordPress site from an AI client. The remaining **39 Pro abilities** (every write/build/automation tool) require a Pro license key, which is verified against [hamzanabulsi.com](https://hamzanabulsi.com) using **Ed25519** signed tokens.

| Tier | Abilities | Description |
|---|---|---|
| **Free** | 10 | `site-info`, `system-info`, `list-posts`, `list-plugins`, `list-themes`, `list-menus`, `list-users`, `get-yoast-global`, `get-elementor-kit`, `elementor-list-templates` |
| **Pro** | 49 (10 + 39) | Everything: content CRUD, image generation, taxonomy, theme, Elementor builders + templates, site administration, menus, users, full SEO automation |

**To request a Pro key**, email **[hamzaalinabulsi@gmail.com](mailto:hamzaalinabulsi@gmail.com)** with your site URL — single-site keys are issued; agency keys for multiple sites are available on request.

Once you have a key, paste it under **WP Admin → NP MCP → License → Activate license**. The plugin checks in once a week and continues working **offline for 14 days** if the server is unreachable.

### Why a license server?

- Tokens are **Ed25519-signed** by the server's secret key. The plugin only ships the public key — anyone who modifies the token (extends expiry, swaps domain, …) will fail signature verification and immediately fall back to the free tier.
- Domain binding is enforced both **server-side** (one site per key by default) and **client-side** (token's `site_url` must match `home_url()`).
- Revocation propagates within ~7 days (next refresh) — instantly if the site is online.
- See [`includes/class-license.php`](includes/class-license.php) for the full client logic.

---

##  Installation

### Option 1 — Clone from GitHub (recommended)

On the server (one-time install):

```bash
cd /var/www/your-wordpress/wp-content/plugins
git clone https://github.com/hamzanabulse/np-mcp-builder.git
wp --allow-root --path=/var/www/your-wordpress plugin activate np-mcp-builder
```

To pull future updates:

```bash
cd /var/www/your-wordpress/wp-content/plugins/np-mcp-builder
git pull
wp --allow-root --path=/var/www/your-wordpress plugin deactivate np-mcp-builder
wp --allow-root --path=/var/www/your-wordpress plugin activate np-mcp-builder
```

The deactivate/activate cycle forces WordPress to reload the new code (otherwise opcache may serve the old class definitions).

### Option 2 — ZIP upload

1. Download the latest ZIP from [Releases](https://github.com/hamzanabulse/np-mcp-builder/releases) (or **Code → Download ZIP**).
2. WordPress admin → **Plugins → Add New → Upload Plugin** → select the ZIP → **Install Now** → **Activate**.

### Post-install setup

1. Open **NP MCP Builder** in the admin sidebar.
2. **Settings tab** → paste your Google Gemini API key (only required for `np/generate-image`).
3. Make sure [`mcp-adapter`](https://github.com/WordPress/mcp-adapter) is also installed and active (it provides the actual `/wp-json/mcp/v1/*` HTTP endpoint).
4. **Abilities tab** → toggle off any tools you do not want exposed.

### Connect Claude Desktop

Generate an Application Password in **Users → Profile → Application Passwords**, then base64-encode `username:app-password`:

```bash
echo -n 'your-username:xxxx xxxx xxxx xxxx xxxx xxxx' | base64
```

Edit `claude_desktop_config.json`:

```jsonc
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": [
        "-y",
        "mcp-remote",
        "https://YOUR-SITE.com/wp-json/mcp/mcp-adapter-default-server",
        "--header",
        "Authorization: Basic YOUR_BASE64_TOKEN"
      ]
    }
  }
}
```

Restart Claude Desktop. You should see all 49 `np-*` tools available.

### Verify the install

```bash
wp --allow-root --user=YOUR_ADMIN --path=/var/www/your-wordpress eval-file bin/test-abilities.php
```

Expected: `Registered: 49 / 49 — Missing: 0` followed by 11 `[OK]` lines for the read-only tools.

---

## 🧰 Abilities (49 tools)

<details open>
<summary><b>Content (5)</b></summary>

| Tool | Purpose |
|---|---|
| `np/site-info` | Site name, URL, language, timezone, post counts. |
| `np/list-posts` | Paginated list of posts/pages with filters. |
| `np/get-post` | Read a single post with full Yoast meta. |
| `np/create-post` | Create post or page with categories, tags, featured image, Yoast meta. |
| `np/update-post` | Update any post field + Yoast meta. |

</details>

<details>
<summary><b>Media (1)</b></summary>

| Tool | Purpose |
|---|---|
| `np/generate-image` | Gemini → resize → WebP → Media Library with SEO metadata (alt/title/caption/description). |

</details>

<details>
<summary><b>Taxonomy (5)</b></summary>

| Tool | Purpose |
|---|---|
| `np/list-terms` | List terms in any taxonomy. |
| `np/create-term` | Create a term. |
| `np/update-term` | Rename / re-slug / re-parent. |
| `np/delete-term` | Delete term. |
| `np/set-post-terms` | Assign terms to a post. |

</details>

<details>
<summary><b>Theme customizer (2)</b></summary>

| Tool | Purpose |
|---|---|
| `np/get-theme-mod` | Read a Customizer value. |
| `np/set-theme-mod` | Set a Customizer value. |

</details>

<details open>
<summary><b>Elementor — high-level builders (4)</b></summary>

| Tool | Purpose |
|---|---|
| `np/elementor-build-blog` | One call → styled Elementor blog post (hero, sections, featured image, categories, tags, Yoast meta). |
| `np/elementor-build-landing` | Conversion landing page with auto JSON-LD schema (FAQ, LocalBusiness, Service, Breadcrumbs, WebPage), sticky WhatsApp, custom CSS/JS. |
| `np/elementor-append-sections` | Append/prepend sections to an existing Elementor post. |
| `np/elementor-from-markdown` | Convert Markdown to a styled Elementor post. |

</details>

<details>
<summary><b>Elementor — data &amp; templates (6)</b></summary>

| Tool | Purpose |
|---|---|
| `np/elementor-get-data` | Read raw `_elementor_data` + page settings of a post. |
| `np/elementor-set-data` | Replace raw Elementor data of a post. |
| `np/elementor-list-templates` | List `elementor_library` templates by type. |
| `np/elementor-save-as-template` | Snapshot an existing post into the template library. |
| `np/elementor-apply-template` | Apply (replace or append) a template to a target post. |
| `np/elementor-regenerate-css` | Clear per-post CSS or rebuild Elementor file cache. |

</details>

<details>
<summary><b>Site administration (11)</b></summary>

| Tool | Purpose |
|---|---|
| `np/list-plugins` | All installed plugins + state. |
| `np/activate-plugin` | Activate a plugin (refuses to act on itself). |
| `np/deactivate-plugin` | Deactivate (refuses self-deactivation). |
| `np/list-themes` / `np/switch-theme` | Theme inventory + switching. |
| `np/get-site-settings` / `np/update-site-settings` | Core options (title, tagline, admin_email, timezone, …). |
| `np/update-permalinks` | Change permalink structure + flush rewrites. |
| `np/clear-cache` | Elementor `files_manager` + object cache + transient flush. |
| `np/maintenance-mode` | Toggle the built-in 503 page. |
| `np/system-info` | WP / PHP / MySQL versions + plugin/theme detection. |

</details>

<details>
<summary><b>Menus (5)</b></summary>

| Tool | Purpose |
|---|---|
| `np/list-menus` | List nav menus + theme locations. |
| `np/create-menu` | Create a menu, optionally seed with items (with nesting via `parent_index`). |
| `np/update-menu` | Replace items or change locations. |
| `np/delete-menu` | Delete a menu. |
| `np/assign-menu-location` | Assign menu → theme location. |

</details>

<details>
<summary><b>Users (4)</b></summary>

| Tool | Purpose |
|---|---|
| `np/list-users` | Paginated user list. |
| `np/create-user` | Create user with role + extended profile. |
| `np/update-user` | Update fields and role. |
| `np/delete-user` | Delete user, optional content reassign (refuses current user). |

</details>

<details>
<summary><b>SEO &amp; Elementor kit (6)</b></summary>

| Tool | Purpose |
|---|---|
| `np/get-yoast-global` / `np/update-yoast-global` | Organization, person, social, sitemap, breadcrumbs. |
| `np/get-elementor-kit` / `np/update-elementor-kit` | Active kit globals (colors, typography, container width). |
| `np/get-seo-head` | **Yoast-rendered head** (HTML + structured JSON + full schema.org @graph) for any post or URL — uses Yoast's `/yoast/v1/get_head` endpoint internally. |
| `np/audit-seo` | Scan posts/pages and report missing focus keyword, meta description, canonical, OG image, featured image, schema page type, short title, thin content — with per-post issue list and a heuristic score. |

</details>

---

## 💡 Examples

### One-shot landing page

```jsonc
// np/elementor-build-landing
{
  "title": "Dental Implants Amman",
  "slug": "dental-implants-amman",
  "post_type": "page",
  "yoast": {
    "focus_keyword": "dental implants amman",
    "meta_description": "Premium dental implants in Amman with a 10-year guarantee.",
    "og_image_id": 1234,
    "schema_page_type": "Service"
  },
  "sections": [
    { "type": "hero", "heading": "Restore your smile in 24 hours", "subheading": "All-on-4 implants by board-certified surgeons.", "cta_text": "Book a free consultation", "cta_url": "#book", "image_id": 1234 },
    { "type": "problem_agitation", "items": ["Embarrassed to smile", "Pain when chewing", "Loose dentures"] },
    { "type": "benefits_grid", "items": [ {"icon":"shield","title":"10-year guarantee"}, {"icon":"clock","title":"Same-day teeth"} ] },
    { "type": "testimonials", "items": [ {"name":"Sara", "rating":5, "quote":"Life-changing."} ] },
    { "type": "pricing", "items": [ {"name":"Single implant","price":"380 JOD","features":["Titanium post","Crown","Lifetime checkup"]} ] },
    { "type": "faq", "items": [ {"q":"Is it painful?","a":"Local anesthesia + IV sedation."} ] },
    { "type": "cta", "heading": "Ready to smile again?", "cta_text": "WhatsApp us", "cta_url": "https://wa.me/962790000000" }
  ],
  "faqs": [ {"q":"Is it painful?","a":"Local anesthesia + IV sedation."} ],
  "business": { "name": "Amman Dental", "phone": "+962790000000", "address": "Abdoun, Amman", "rating": 4.9, "reviews": 312 },
  "service": { "name": "Dental Implants", "area": "Amman" },
  "breadcrumbs": [ {"name":"Home","url":"/"}, {"name":"Services","url":"/services/"}, {"name":"Dental Implants"} ],
  "sticky_whatsapp": "+962790000000"
}
```

The plugin builds the full Elementor JSON, sets the featured image, writes all Yoast meta, injects four JSON-LD blocks (`FAQPage`, `LocalBusiness`, `Service`, `BreadcrumbList`) into `<head>`, adds the floating WhatsApp button via `_np_mcp_custom_js`, and clears the Elementor cache.

### AI-generated image

```jsonc
// np/generate-image
{
  "prompt": "Photorealistic dental clinic, white interior, soft daylight",
  "aspect_ratio": "16:9",
  "title": "Modern dental clinic interior",
  "alt_text": "Bright dental clinic with white furniture and natural light"
}
```

Returns the new attachment ID, URL, dimensions, and full SEO metadata.

### SEO audit + fix loop

```jsonc
// 1. np/audit-seo  → returns posts missing focus_keyword / meta_description / og_image
// 2. for each post: np/update-post with the fixes
// 3. np/get-seo-head { "post_id": 42 } → verify the rendered <head> + schema graph
```

---

## 🏛️ Architecture

```
┌────────────┐   MCP/HTTP    ┌───────────────┐   Abilities API   ┌──────────────────┐
│  AI client │ ────────────► │  mcp-adapter  │ ────────────────► │  NP MCP Builder  │
│ (Claude…)  │               │   (WordPress) │                   │   49 abilities   │
└────────────┘               └───────────────┘                   └────────┬─────────┘
                                                                          │
            ┌─────────────────────┬─────────────────────┬─────────────────┼────────────────┐
            ▼                     ▼                     ▼                 ▼                ▼
      WP core (posts,        Elementor             Yoast SEO         Google          Custom hooks
      taxonomies, users,     (`_elementor_data`,   (per-post +       Gemini          (admin UI,
      menus, options…)        kit, library)        global +          (image gen)     maintenance,
                                                   `/yoast/v1/`)                     schema injection)
```

- **Bootstrap**: `np-mcp-builder.php` → `Plugin::instance()->init()` on `plugins_loaded` priority 5.
- **Categories** registered on `wp_abilities_api_categories_init`.
- **Abilities** registered on `wp_abilities_api_init`. Each ability class lives in `includes/abilities/`. Disabled abilities are unregistered after the fact via `wp_unregister_ability`.
- **MCP exposure** via the `mcp_adapter_default_server_config` filter — only enabled tools are advertised.
- **Schema / CSS / JS** injected into `wp_head` and `wp_footer` from per-post meta (`_np_mcp_schema_jsonld`, `_np_mcp_custom_css`, `_np_mcp_custom_js`).
- **Maintenance** is a `template_redirect` priority-0 short-circuit returning a 503 with a styled inline page.

---

## 🛡️ Security

- Every ability has an explicit `permission_callback` that maps to a real WordPress capability (`edit_posts`, `manage_options`, `edit_theme_options`, `list_users`, `manage_categories`, …).
- `np/deactivate-plugin` refuses to deactivate **itself** (would lock you out of the MCP server).
- `np/delete-user` refuses to delete the **current** user.
- Disabled abilities are fully unregistered — they cannot be invoked even by direct REST call.
- All HTML output (maintenance page, schema, custom CSS/JS) is escaped through `esc_html` / `wp_strip_all_tags` / `wp_kses_post` as appropriate.

The plugin ships its own audit harness at [`bin/audit-security.sh`](bin/audit-security.sh).
The full report and capability matrix live in [`docs/SECURITY-AUDIT.md`](docs/SECURITY-AUDIT.md)
and the disclosure policy in [`SECURITY.md`](SECURITY.md).

---

## 🗺️ Roadmap

- [ ] WooCommerce abilities (products, variations, orders, coupons).
- [ ] ACF custom-field abilities.
- [ ] Multilingual abilities (Polylang / WPML).
- [ ] Bulk SEO fix tool (audit → auto-write meta_description with AI → re-verify).
- [ ] Ability for image batch optimization (bulk WebP conversion).

---

## 📝 Changelog

### 1.3.0 — Yoast REST + Elementor data tools

- New SEO abilities: `np/get-seo-head` (Yoast-rendered head HTML + JSON + schema graph for any post/URL via `/yoast/v1/get_head`), `np/audit-seo` (whole-site SEO scan with per-post issue list).
- New Elementor data abilities: `np/elementor-get-data`, `np/elementor-set-data`, `np/elementor-list-templates`, `np/elementor-save-as-template`, `np/elementor-apply-template`, `np/elementor-regenerate-css`.
- Plugin total now **49 abilities**.

### 1.2.0 — Admin dashboard + site control

- Tabbed admin dashboard (Overview / Abilities / Tools / Settings / Maintenance / About).
- Per-ability on/off toggles.
- Site abilities (plugins, themes, settings, permalinks, cache, maintenance, system info).
- Menu, User, SEO, Elementor-kit abilities.
- Built-in maintenance mode.

### 1.1.0 — Landing pages + schema

- `np/elementor-build-landing` with 13 new conversion-focused section types.
- `Schema_Builder` for FAQPage / LocalBusiness / Service / BreadcrumbList / WebPage.
- Extended Yoast meta (canonical, noindex, OG, Twitter).
- Sticky WhatsApp + per-page custom CSS/JS.

### 1.0.0 — Initial release

- 16 abilities across content, media, taxonomy, theme and Elementor.

---

## 🧪 Testing

A smoke test that registers all abilities and dry-runs the read-only ones lives in `bin/test-abilities.php`:

```bash
wp --allow-root --user=YOUR_ADMIN --path=/var/www/wordpress eval-file bin/test-abilities.php
```

Expected output:

```
=== Registration ===
Expected:   49
Registered: 49
Missing:    0
=== Read-only callback dry-runs ===
[OK  ] np/site-info …
[OK  ] np/list-posts …
… (11 OK)
```

---

## 📜 License

GPL-2.0-or-later — same as WordPress.

---

## 🙋 Author

**Hamza Ali Nabulsi** — [hamzanabulsi.com](https://hamzanabulsi.com)
Issues and PRs welcome on [GitHub](https://github.com/hamzanabulse/np-mcp-builder/issues).

If this plugin saved you time, a ⭐️ on the repo is the best kind of thank-you.
