<?php
/**
 * Stub for missing class-page-abilities.php (file shipped empty in this build).
 * Provides a no-op implementation so the plugin loads.
 */
if ( ! class_exists( 'Elementor_MCP_Page_Abilities' ) ) {
    class Elementor_MCP_Page_Abilities {
        public function __construct( $data = null, $factory = null ) {}
        public function register(): void {}
        public function get_ability_names(): array { return array(); }
    }
}