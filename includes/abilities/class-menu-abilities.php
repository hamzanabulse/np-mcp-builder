<?php
/**
 * Menu_Abilities — manage WP nav menus and locations.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Menu_Abilities {

    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }

        wp_register_ability( 'np/list-menus', array(
            'label' => 'List nav menus', 'category' => 'np-menu',
            'description' => 'List all navigation menus, their items and assigned theme locations.',
            'input_schema'  => array( 'type' => 'object', 'properties' => array() ),
            'execute_callback'    => array( __CLASS__, 'list_menus' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/create-menu', array(
            'label' => 'Create nav menu', 'category' => 'np-menu',
            'description' => 'Create a new nav menu, optionally assigning theme locations and items.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'name' ),
                'properties' => array(
                    'name'      => array( 'type' => 'string' ),
                    'locations' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'items'     => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'create_menu' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-menu', array(
            'label' => 'Update nav menu', 'category' => 'np-menu',
            'description' => 'Replace items of an existing nav menu (by id or name) and/or update theme locations.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'menu_id'   => array( 'type' => 'integer' ),
                    'name'      => array( 'type' => 'string' ),
                    'locations' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
                    'items'     => array( 'type' => 'array', 'items' => array( 'type' => 'object' ) ),
                    'replace_items' => array( 'type' => 'boolean', 'default' => false ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_menu' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/delete-menu', array(
            'label' => 'Delete nav menu', 'category' => 'np-menu',
            'description' => 'Delete a nav menu by id or name.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'menu_id' => array( 'type' => 'integer' ),
                    'name'    => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'delete_menu' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/assign-menu-location', array(
            'label' => 'Assign menu location', 'category' => 'np-menu',
            'description' => 'Assign a nav menu to a theme location (or remove if menu_id=0).',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'location' ),
                'properties' => array(
                    'location' => array( 'type' => 'string' ),
                    'menu_id'  => array( 'type' => 'integer' ),
                    'name'     => array( 'type' => 'string' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'assign_location' ),
            'permission_callback' => static function () { return current_user_can( 'edit_theme_options' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    /* ==================================================================== */

    private static function resolve_menu( array $input ): ?\WP_Term {
        if ( ! empty( $input['menu_id'] ) ) {
            $m = wp_get_nav_menu_object( (int) $input['menu_id'] );
            if ( $m ) { return $m; }
        }
        if ( ! empty( $input['name'] ) ) {
            $m = wp_get_nav_menu_object( (string) $input['name'] );
            if ( $m ) { return $m; }
        }
        return null;
    }

    public static function list_menus(): array {
        $menus     = wp_get_nav_menus();
        $locations = get_nav_menu_locations();
        $rows      = array();
        foreach ( $menus as $menu ) {
            $items = wp_get_nav_menu_items( $menu->term_id ) ?: array();
            $rows[] = array(
                'id'        => $menu->term_id,
                'name'      => $menu->name,
                'slug'      => $menu->slug,
                'count'     => count( $items ),
                'locations' => array_keys( array_filter( $locations, static fn( $id ) => (int) $id === (int) $menu->term_id ) ),
                'items'     => array_map( static function ( $i ) {
                    return array(
                        'id'     => $i->ID,
                        'title'  => $i->title,
                        'url'    => $i->url,
                        'parent' => (int) $i->menu_item_parent,
                        'order'  => (int) $i->menu_order,
                        'type'   => $i->type,
                    );
                }, $items ),
            );
        }
        $registered = function_exists( 'get_registered_nav_menus' ) ? get_registered_nav_menus() : array();
        return array( 'count' => count( $rows ), 'menus' => $rows, 'registered_locations' => $registered );
    }

    public static function create_menu( array $input ) {
        $name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
        if ( $name === '' ) { return new \WP_Error( 'np_menu_name', 'Menu name required.' ); }
        $menu_id = wp_create_nav_menu( $name );
        if ( is_wp_error( $menu_id ) ) { return $menu_id; }
        if ( ! empty( $input['items'] ) ) { self::add_items( (int) $menu_id, (array) $input['items'] ); }
        if ( ! empty( $input['locations'] ) ) { self::set_locations( (int) $menu_id, (array) $input['locations'] ); }
        return array( 'menu_id' => (int) $menu_id, 'name' => $name );
    }

    public static function update_menu( array $input ) {
        $menu = self::resolve_menu( $input );
        if ( ! $menu ) { return new \WP_Error( 'np_menu_missing', 'Menu not found.' ); }
        if ( ! empty( $input['replace_items'] ) ) {
            $existing = wp_get_nav_menu_items( $menu->term_id ) ?: array();
            foreach ( $existing as $i ) { wp_delete_post( $i->ID, true ); }
        }
        if ( ! empty( $input['items'] ) ) { self::add_items( $menu->term_id, (array) $input['items'] ); }
        if ( isset( $input['locations'] ) ) { self::set_locations( $menu->term_id, (array) $input['locations'] ); }
        return array( 'menu_id' => $menu->term_id, 'name' => $menu->name );
    }

    public static function delete_menu( array $input ) {
        $menu = self::resolve_menu( $input );
        if ( ! $menu ) { return new \WP_Error( 'np_menu_missing', 'Menu not found.' ); }
        $res = wp_delete_nav_menu( $menu->term_id );
        if ( is_wp_error( $res ) ) { return $res; }
        return array( 'deleted' => $menu->term_id );
    }

    public static function assign_location( array $input ) {
        $location = sanitize_key( (string) ( $input['location'] ?? '' ) );
        if ( $location === '' ) { return new \WP_Error( 'np_loc', 'Location required.' ); }
        $locations = get_theme_mod( 'nav_menu_locations', array() );
        if ( empty( $input['menu_id'] ) && empty( $input['name'] ) ) {
            unset( $locations[ $location ] );
        } else {
            $menu = self::resolve_menu( $input );
            if ( ! $menu ) { return new \WP_Error( 'np_menu_missing', 'Menu not found.' ); }
            $locations[ $location ] = $menu->term_id;
        }
        set_theme_mod( 'nav_menu_locations', $locations );
        return array( 'locations' => get_nav_menu_locations() );
    }

    /* -------------------------------------------------------------------- */

    private static function add_items( int $menu_id, array $items ): array {
        $created = array();
        $parent_map = array(); // index in array → menu_item ID
        foreach ( array_values( $items ) as $i => $it ) {
            $type   = $it['type'] ?? 'custom';
            $parent = isset( $it['parent_index'] ) && isset( $parent_map[ (int) $it['parent_index'] ] )
                ? $parent_map[ (int) $it['parent_index'] ] : 0;
            $args = array(
                'menu-item-title'     => sanitize_text_field( (string) ( $it['title'] ?? '' ) ),
                'menu-item-url'       => esc_url_raw( (string) ( $it['url'] ?? '' ) ),
                'menu-item-status'    => 'publish',
                'menu-item-type'      => $type,
                'menu-item-parent-id' => $parent,
            );
            if ( $type === 'post_type' && ! empty( $it['object_id'] ) ) {
                $args['menu-item-object']    = (string) ( $it['object'] ?? 'page' );
                $args['menu-item-object-id'] = (int) $it['object_id'];
            } elseif ( $type === 'taxonomy' && ! empty( $it['object_id'] ) ) {
                $args['menu-item-object']    = (string) ( $it['object'] ?? 'category' );
                $args['menu-item-object-id'] = (int) $it['object_id'];
            }
            $id = wp_update_nav_menu_item( $menu_id, 0, $args );
            if ( is_wp_error( $id ) ) { continue; }
            $parent_map[ $i ] = (int) $id;
            $created[] = (int) $id;
        }
        return $created;
    }

    private static function set_locations( int $menu_id, array $locations ): void {
        $current = get_theme_mod( 'nav_menu_locations', array() );
        foreach ( $locations as $loc ) {
            $current[ sanitize_key( (string) $loc ) ] = $menu_id;
        }
        set_theme_mod( 'nav_menu_locations', $current );
    }
}
