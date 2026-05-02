<?php
/**
 * Uninstall — run when the plugin is deleted from wp-admin.
 *
 * @package NP_MCP_Builder
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) { exit; }

delete_option( 'np_mcp_builder_options' );
delete_option( 'np_mcp_builder_activated_at' );
// Legacy key from earlier theme integration.
delete_option( 'np_gemini_api_key' );
