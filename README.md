# NP MCP Builder

A WordPress plugin that turns your site into a Model Context Protocol (MCP) server, exposing high‑level content, taxonomy, theme and Elementor operations as callable abilities. Built on the WordPress Abilities API (6.9+) and the official [`mcp-adapter`](https://github.com/WordPress/mcp-adapter) plugin.

[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-21759b)](https://wordpress.org/) [![PHP](https://img.shields.io/badge/PHP-8.0%2B-777bb4)](https://www.php.net/) [![License](https://img.shields.io/badge/License-GPLv2%2B-green.svg)](LICENSE) [![Elementor](https://img.shields.io/badge/Elementor-compatible-92003B)](https://elementor.com/)

> **Keywords:** WordPress MCP server, Model Context Protocol, WordPress Abilities API, Claude Desktop WordPress, AI content generation, Gemini image generation, Elementor automation, headless WordPress AI, MCP tools WordPress, Yoast SEO automation.

---

## Why this plugin

Most MCP integrations for WordPress expose dozens of low‑level REST endpoints — one tool per CRUD operation — which forces the client to chain many calls and leak implementation details. NP MCP Builder takes the opposite approach: **a small, opinionated set of high‑level abilities** that match real editorial workflows.

A single call to `np/elementor-build-blog` produces a complete, styled Elementor post with featured image, categories, tags, focus keyword, meta description and a full layout. A single call to `np/elementor-build-landing` produces a conversion‑focused landing page (hero, problem agitation, benefits grid, steps, testimonials, FAQ, pricing, guarantee, CTAs) with auto JSON‑LD schema (FAQ, LocalBusiness, Service, BreadcrumbList, WebPage) and full Yoast SEO. A single call to `np/elementor-from-markdown` converts a Markdown article into a styled Elementor page. A single call to `np/generate-image` produces a generated, resized, WebP‑optimised image with full Media Library SEO metadata.

## Features

- **16 abilities** registered through the official WordPress Abilities API.
- **One‑shot Elementor blog builder** with a friendly section schema (hero, heading, paragraph, image, generated image, list, quote, divider, CTA, two‑column row, raw HTML).
- **Markdown → Elementor** converter that preserves headings, lists, blockquotes, horizontal rules and inline formatting.
- **Image pipeline** using Google Gemini (`gemini-2.5-flash-image`), with automatic resize, WebP conversion, alt/title/caption/description and attachment‑to‑post wiring.
- **Native taxonomy management** — list, create, update, delete and assign terms (categories, tags, custom taxonomies) with auto‑creation by name.
- **Yoast SEO integration** — sets focus keyword and meta description directly on `wp_insert_post` calls.
- **Settings page** for the Gemini API key, default aspect ratio, default max width and default WebP quality.
- **Capability‑checked permissions** on every ability.
- **Namespaced under** `NP_MCP_Builder\` for clean integration with other plugins.

## How it fits together

WordPress 6.9 ships the **Abilities API** as a core registry: a way to declare typed, permission‑checked operations identified by names like `np/create-post`. The Abilities API itself does **not** expose anything over HTTP — it is just an in‑process registry.

The official **[`mcp-adapter`](https://github.com/WordPress/mcp-adapter)** plugin (maintained under the `WordPress/` GitHub organisation) is the bridge that takes registered abilities and exposes them as MCP tools at `/wp-json/mcp/v1/`, so MCP clients (Claude Desktop, Cursor, etc.) can discover and call them.

```
  NP MCP Builder        ──▶  registers 16 abilities
  Abilities API (core)  ──▶  in‑process registry
  mcp-adapter plugin    ──▶  exposes abilities as MCP tools over HTTP
  MCP client            ──▶  discovers and calls the tools
```

Without `mcp-adapter` the abilities are still registered and callable from PHP via `wp_get_ability( 'np/...' )->execute( ... )`, but they will not be reachable from an external MCP client.

## Requirements

| Component | Version | Purpose |
|----------|---------|---------|
| WordPress | 6.9 or later | Abilities API |
| PHP | 8.0 or later | typed properties, named arguments |
| [`mcp-adapter`](https://github.com/WordPress/mcp-adapter) | latest | exposes abilities as MCP tools (only needed for remote MCP clients) |
| Elementor | optional | required only by the `np/elementor-*` abilities |
| Google AI Studio API key | optional | required only by `np/generate-image` |

## Installation

```bash
cd wp-content/plugins
git clone https://github.com/hamzanabulse/np-mcp-builder.git
```

Or download the ZIP and upload it through **Plugins → Add New → Upload Plugin**.

Activate the plugin, then open **Settings → NP MCP Builder** and paste your Gemini API key (only needed for `np/generate-image`).

## Quick start

Once the `mcp-adapter` plugin is active, the MCP endpoint is exposed at:

```
/wp-json/mcp/v1/
```

Authenticate using a WordPress Application Password. Example client config (Claude Desktop, Cursor, etc.):

```json
{
  "mcpServers": {
    "wordpress": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "https://example.com/wp-json/mcp/v1/"],
      "env": {
        "AUTH_HEADER": "Basic <base64(user:application-password)>"
      }
    }
  }
}
```

## Abilities

| Ability | Category | Description |
|---------|----------|-------------|
| `np/site-info` | Site | Basic site info (name, URL, theme, language). |
| `np/list-posts` | Content | List posts/pages with filters. |
| `np/get-post` | Content | Read a single post with Yoast meta. |
| `np/create-post` | Content | Create post or page with categories, tags, Yoast fields. |
| `np/update-post` | Content | Update post fields and Yoast meta. |
| `np/generate-image` | Media | Gemini → resize → WebP → Media Library, with full SEO metadata. |
| `np/list-terms` | Taxonomy | List taxonomy terms. |
| `np/create-term` | Taxonomy | Create category, tag or custom taxonomy term. |
| `np/update-term` | Taxonomy | Rename / re‑slug / re‑parent a term. |
| `np/delete-term` | Taxonomy | Delete a term. |
| `np/set-post-terms` | Taxonomy | Assign terms by id, name or slug (auto‑create supported). |
| `np/set-theme-mod` | Theme | Set a Customizer value. |
| `np/get-theme-mod` | Theme | Read a Customizer value. |
| `np/elementor-build-blog` | Elementor | Build a full styled Elementor post in one call. |
| `np/elementor-build-landing` | Elementor | Build a conversion‑focused landing page in one call — hero, problem agitation, benefits grid, steps, testimonials, FAQ, stats, pricing, guarantee + auto JSON‑LD schema (FAQ, LocalBusiness, Service, BreadcrumbList, WebPage), full Yoast SEO, optional sticky WhatsApp button, custom CSS/JS. |
| `np/elementor-append-sections` | Elementor | Append/prepend sections to an existing Elementor post. |
| `np/elementor-from-markdown` | Elementor | Convert Markdown to a styled Elementor post. |

## Section schema (Elementor)

Each item in the `sections` array of `np/elementor-build-blog` or `np/elementor-build-landing` is one of:

```jsonc
// Core blocks
{ "type": "hero",        "title": "…", "subtitle": "…", "cta_text": "…", "cta_url": "…", "bg": "#0F1115" }
{ "type": "heading",     "level": "h2", "text": "…", "align": "left" }
{ "type": "paragraph",   "text": "<p>HTML allowed</p>" }
{ "type": "image",       "attachment_id": 123, "caption": "…" }
{ "type": "image_gen",   "prompt": "…", "alt": "…", "aspect_ratio": "16:9" }
{ "type": "list",        "items": ["one", "two", "three"] }
{ "type": "quote",       "text": "…", "author": "…" }
{ "type": "divider" }
{ "type": "cta",         "title": "…", "text": "…", "button_text": "…", "button_url": "…" }
{ "type": "two_columns", "left": [ /* sections */ ], "right": [ /* sections */ ] }
{ "type": "html",        "code": "<div>…</div>" }
{ "type": "spacer",      "height": 60 }
{ "type": "video",       "url": "https://youtu.be/…" }

// Landing‑page conversion blocks
{ "type": "problem_agitation", "eyebrow": "…", "title": "…", "items": ["pain 1", "pain 2", "pain 3"] }
{ "type": "benefits_grid",     "title": "…", "subtitle": "…", "columns": 3,
  "items": [ { "icon": "fas fa-bolt", "title": "…", "text": "…" } ] }
{ "type": "steps",             "title": "…",
  "items": [ { "number": 1, "title": "…", "text": "…" } ] }
{ "type": "testimonials",      "title": "…",
  "items": [ { "quote": "…", "author": "…", "role": "…", "rating": 5 } ] }
{ "type": "faq",               "title": "…",
  "items": [ { "question": "…", "answer": "…" } ] }
{ "type": "stats",             "items": [ { "number": "500+", "label": "…" } ] }
{ "type": "pricing",           "plans": [ { "name": "Pro", "price": "$49", "period": "/mo",
  "features": [ "…" ], "button_text": "…", "button_url": "…", "featured": true } ] }
{ "type": "author_bio",        "name": "…", "role": "…", "bio": "…", "image_id": 12 }
{ "type": "guarantee",         "title": "…", "text": "…" }
{ "type": "feature_list",      "title": "…", "items": [ "feature 1", "feature 2", "…" ] }
{ "type": "schema",            "json": "{\"@context\":\"https://schema.org\"…}" }
```

## Landing pages with auto schema and Yoast SEO

`np/elementor-build-landing` accepts everything `np/elementor-build-blog`
accepts — plus extended Yoast meta (`yoast_canonical`, `yoast_noindex`,
`yoast_og_title`, `yoast_og_description`, `yoast_og_image_id`,
`yoast_twitter_title`, `yoast_twitter_description`) and friendly schema
inputs that are automatically rendered as JSON‑LD `<script>` tags in
`<head>`:

```jsonc
{
  "title": "Landing page title",
  "slug": "landing",
  "post_type": "page",
  "status": "publish",
  "yoast_focus_keyword": "main keyword",
  "yoast_meta_description": "… (≤ 160 chars)",
  "yoast_canonical": "https://example.com/landing",
  "yoast_og_title": "…",
  "yoast_og_description": "…",
  "sticky_whatsapp": "+970599123456",
  "custom_css": ".elementor-button{transition:transform .2s}.elementor-button:hover{transform:translateY(-2px)}",
  "breadcrumbs": [
    { "name": "Home", "url": "https://example.com/" },
    { "name": "Services", "url": "https://example.com/services/" }
  ],
  "business": {
    "type": "ProfessionalService",
    "name": "Acme Co.",
    "telephone": "+970599123456",
    "priceRange": "$$",
    "address": { "streetAddress": "…", "addressLocality": "Nablus", "addressCountry": "PS" },
    "rating": { "ratingValue": "4.9", "reviewCount": "127" },
    "reviews": [ { "author": "Sara", "rating": 5, "body": "…" } ]
  },
  "faqs": [
    { "question": "…", "answer": "…" }
  ],
  "sections": [ /* mix of any section types above */ ]
}
```

## Repository layout

```
np-mcp-builder/
├── np-mcp-builder.php              Plugin bootstrap and headers
├── readme.txt                      WordPress.org‑style readme
├── uninstall.php                   Cleanup on plugin deletion
├── LICENSE                         GPL‑2.0‑or‑later
└── includes/
    ├── class-plugin.php            Singleton: registers categories, abilities, MCP tools
    ├── class-image-generator.php   Gemini API client + WebP pipeline
    ├── class-section-builder.php   Friendly schema → Elementor JSON nodes
    ├── abilities/
    │   ├── class-content-abilities.php
    │   ├── class-image-abilities.php
    │   ├── class-taxonomy-abilities.php
    │   ├── class-theme-abilities.php
    │   └── class-elementor-abilities.php
    └── admin/
        └── class-settings.php
```

## Security

- Every ability declares an explicit `permission_callback` mapped to a WordPress capability (`manage_options`, `edit_posts`, `publish_posts`, `manage_categories`, `edit_theme_options`, `upload_files`).
- The Gemini API key is stored in the `wp_options` table and rendered as a `password`‑typed field in the admin UI.
- All input is validated against JSON Schema and sanitised with the appropriate WordPress helpers (`sanitize_text_field`, `wp_kses_post`, `sanitize_title`, etc.).
- No data leaves the site unless an ability explicitly requires it (only `np/generate-image` calls Gemini).

## Contributing

Issues and pull requests are welcome. Please run `php -l` on any modified PHP file before submitting and keep new abilities namespaced under `np/`.

## Versioning

Follows [Semantic Versioning](https://semver.org/). The public surface is the set of registered ability names together with their input and output schemas.

## License

[GPL‑2.0‑or‑later](LICENSE).

## Author

Hamza Ali Nabulsi — [hamzanabulsi.com](https://hamzanabulsi.com)
