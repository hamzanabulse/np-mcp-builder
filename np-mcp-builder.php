<?php
/**
 * Plugin Name:       NP MCP Builder
 * Plugin URI:        https://github.com/hamzaalinabulsi/np-mcp-builder
 * Description:       Mega MCP abilities for WordPress + Elementor: build complete styled blog posts in one call, generate AI images (Gemini) with full SEO metadata, manage taxonomies, post CRUD, theme customizer, and convert Markdown to Elementor — all exposed as MCP tools for Claude/Cursor/any MCP client.
 * Version:           1.0.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Author:            Hamza Ali Nabulsi
 * Author URI:        https://hamzanabulsi.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       np-mcp-builder
 * Domain Path:       /languages
 *
 * @package NP_MCP_Builder
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NP_MCP_BUILDER_VERSION', '1.0.0' );
define( 'NP_MCP_BUILDER_FILE', __FILE__ );
define( 'NP_MCP_BUILDER_DIR', plugin_dir_path( __FILE__ ) );
define( 'NP_MCP_BUILDER_URL', plugin_dir_url( __FILE__ ) );
define( 'NP_MCP_BUILDER_BASENAME', plugin_basename( __FILE__ ) );

// Bootstrap.
require_once NP_MCP_BUILDER_DIR . 'includes/class-plugin.php';

// Initialize on plugins_loaded so all dependencies (Abilities API, mcp-adapter) are present.
add_action( 'plugins_loaded', static function () {
    NP_MCP_Builder\Plugin::instance()->init();
}, 5 );

// Activation: flag so we can show the welcome notice / set defaults.
register_activation_hook( __FILE__, static function () {
    if ( ! get_option( 'np_mcp_builder_activated_at' ) ) {
        update_option( 'np_mcp_builder_activated_at', time(), false );
    }
} );

// Deactivation: nothing to do (settings preserved).
register_deactivation_hook( __FILE__, static function () { /* noop */ } );
