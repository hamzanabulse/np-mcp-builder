<?php
/**
 * Theme abilities — get/set theme_mod (Customizer values).
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Theme_Abilities {

    public static function register(): void {
        wp_register_ability( 'np/set-theme-mod', array(
            'label'       => 'Set Theme Mod (Customizer)',
            'description' => 'Set a theme_mod value (any Customizer setting).',
            'category'    => 'np-theme',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'key', 'value' ),
                'properties' => array(
                    'key'   => array( 'type' => 'string' ),
                    'value' => array(),
                ),
            ),
            'execute_callback' => static function ( array $input ) {
                set_theme_mod( $input['key'], $input['value'] );
                return array( 'key' => $input['key'], 'value' => get_theme_mod( $input['key'] ) );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/get-theme-mod', array(
            'label'       => 'Get Theme Mod',
            'description' => 'Read a theme_mod (Customizer) value.',
            'category'    => 'np-theme',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'key' ),
                'properties' => array( 'key' => array( 'type' => 'string' ) ),
            ),
            'execute_callback' => static function ( array $input ) {
                return array( 'key' => $input['key'], 'value' => get_theme_mod( $input['key'] ) );
            },
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }
}
