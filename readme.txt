=== NP MCP Builder ===
Contributors: hamzaalinabulsi
Tags: mcp, ai, elementor, gemini, abilities-api, claude, openai
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Expose 16 high-level WordPress abilities (content, taxonomy, theme, AI image generation, and one-shot Elementor blog builders) to MCP clients such as Claude Desktop.

== Description ==

NP MCP Builder turns your WordPress site into a powerful MCP server using the new WordPress Abilities API and the mcp-adapter plugin.

It registers **16 friendly, high-level abilities** that an AI assistant can call:

* **Content** — `np/site-info`, `np/list-posts`, `np/get-post`, `np/create-post`, `np/update-post`
* **Media (AI)** — `np/generate-image` (Google Gemini → resized → WebP → Media Library, with full SEO metadata)
* **Taxonomy** — `np/list-terms`, `np/create-term`, `np/update-term`, `np/delete-term`, `np/set-post-terms`
* **Theme** — `np/get-theme-mod`, `np/set-theme-mod`
* **Elementor (one-shot mega builders)** — `np/elementor-build-blog`, `np/elementor-append-sections`, `np/elementor-from-markdown`

The Elementor abilities accept a friendly section schema (hero, heading, paragraph, image, image_gen, list, quote, divider, cta, two_columns, html) and produce a complete styled Elementor post with featured image, categories, tags, slug and Yoast meta — in a single tool call.

== Requirements ==

* WordPress 6.9+ (Abilities API)
* PHP 8.0+
* [mcp-adapter](https://github.com/WordPress/mcp-adapter) plugin (only required to expose the abilities as MCP tools over HTTP)
* Elementor (only required for the `np/elementor-*` abilities)
* A Google AI Studio API key (only required for `np/generate-image`)

== Installation ==

1. Upload the `np-mcp-builder` folder to `/wp-content/plugins/` (or install the ZIP through the Plugins screen).
2. Activate **NP MCP Builder** through the Plugins menu.
3. Go to **Settings → NP MCP Builder** and paste your Gemini API key.
4. Make sure the `mcp-adapter` plugin is active so the abilities are exposed as MCP tools.

== Frequently Asked Questions ==

= Where is the MCP endpoint? =

`/wp-json/mcp/v1/` (provided by the mcp-adapter plugin). Authenticate with a WordPress Application Password.

= Does this work without Elementor? =

Yes — only the three `np/elementor-*` abilities require Elementor. All other abilities work on a plain WordPress install.

= Can I generate images without Gemini? =

`np/generate-image` is Gemini-only at the moment. The other abilities don't need any external API.

== Changelog ==

= 1.3.0 =
* New SEO abilities: `np/get-seo-head` (returns Yoast-rendered SEO head HTML, structured JSON and full schema.org @graph for any post or URL via Yoast's `/yoast/v1/get_head` REST API) and `np/audit-seo` (scans posts/pages and reports missing focus keyword, meta description, canonical, OG image, featured image, schema page type, short title, thin content - per-post issue list with score).
* New Elementor data abilities: `np/elementor-get-data`, `np/elementor-set-data` (raw read/write of `_elementor_data`), `np/elementor-list-templates`, `np/elementor-save-as-template`, `np/elementor-apply-template` (replace or append), `np/elementor-regenerate-css`.
* Plugin total now 48+ abilities.

= 1.2.0 =
* New top-level admin dashboard with tabs: Overview, Abilities, Tools, Settings, Maintenance, About.
* Per-ability on/off toggles - disabled abilities are not registered with the Abilities API and not exposed via MCP.
* New Site abilities: list/activate/deactivate plugins, list/switch themes, get/update site settings, update permalinks, clear caches, maintenance mode, system info.
* New Menu abilities: list/create/update/delete nav menus, assign theme location.
* New User abilities: list/create/update/delete users (with reassign).
* New SEO abilities: get/update Yoast SEO global settings (organization/person, social, sitemap, breadcrumbs).
* New Elementor kit abilities: get/update active kit (global colors, typography).
* Built-in maintenance mode: 503 page for visitors, admins still see the site.
* One-click cache clearing from Tools tab (Elementor + object cache + transients).
* Plugin total now 40+ abilities.

= 1.1.0 =
* New ability `np/elementor-build-landing`: one-shot conversion-focused landing pages.
* New section types: problem_agitation, benefits_grid, steps, testimonials, faq (Elementor accordion), stats, pricing, author_bio, guarantee, feature_list, spacer, video, schema.
* New `Schema_Builder` helper: auto JSON-LD for FAQPage, LocalBusiness/ProfessionalService, Service, BreadcrumbList, WebPage, AggregateRating, Reviews. Schema is injected into `<head>` from post meta.
* Extended Yoast SEO support: title, canonical, noindex, OG (title/description/image), Twitter (title/description).
* Optional sticky WhatsApp button and per-page custom CSS/JS via post meta.

= 1.0.0 =
* Initial release. 16 abilities across content, media, taxonomy, theme and Elementor.
