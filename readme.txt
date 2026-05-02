=== NP MCP Builder ===
Contributors: hamzaalinabulsi
Tags: mcp, ai, elementor, gemini, abilities-api, claude, openai
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 1.0.0
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
* [mcp-adapter](https://github.com/Automattic/mcp-adapter) plugin (for the MCP server endpoint)
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

= 1.0.0 =
* Initial release. 16 abilities across content, media, taxonomy, theme and Elementor.
