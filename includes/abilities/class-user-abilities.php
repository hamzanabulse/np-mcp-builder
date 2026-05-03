<?php
/**
 * User_Abilities — list, create, update and delete WordPress users.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class User_Abilities {

    public static function register(): void {
        if ( ! function_exists( 'wp_register_ability' ) ) { return; }

        wp_register_ability( 'np/list-users', array(
            'label' => 'List users', 'category' => 'np-users',
            'description' => 'List users with id, login, email, role, registered date.',
            'input_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'role'    => array( 'type' => 'string' ),
                    'search'  => array( 'type' => 'string' ),
                    'number'  => array( 'type' => 'integer', 'default' => 50 ),
                    'offset'  => array( 'type' => 'integer', 'default' => 0 ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'list_users' ),
            'permission_callback' => static function () { return current_user_can( 'list_users' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/create-user', array(
            'label' => 'Create user', 'category' => 'np-users',
            'description' => 'Create a new WordPress user with role. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'login', 'email', 'confirm' ),
                'properties' => array(
                    'login'        => array( 'type' => 'string' ),
                    'email'        => array( 'type' => 'string' ),
                    'password'     => array( 'type' => 'string' ),
                    'role'         => array( 'type' => 'string', 'default' => 'subscriber' ),
                    'first_name'   => array( 'type' => 'string' ),
                    'last_name'    => array( 'type' => 'string' ),
                    'display_name' => array( 'type' => 'string' ),
                    'description'  => array( 'type' => 'string' ),
                    'website'      => array( 'type' => 'string' ),
                    'send_email'   => array( 'type' => 'boolean', 'default' => false ),
                    'confirm'      => array( 'type' => 'boolean', 'description' => 'Must be true to create a user.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'create_user' ),
            'permission_callback' => static function () { return current_user_can( 'create_users' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/update-user', array(
            'label' => 'Update user', 'category' => 'np-users',
            'description' => 'Update user fields and role by id or login. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'confirm' ),
                'properties' => array(
                    'user_id'      => array( 'type' => 'integer' ),
                    'login'        => array( 'type' => 'string' ),
                    'email'        => array( 'type' => 'string' ),
                    'password'     => array( 'type' => 'string' ),
                    'role'         => array( 'type' => 'string' ),
                    'first_name'   => array( 'type' => 'string' ),
                    'last_name'    => array( 'type' => 'string' ),
                    'display_name' => array( 'type' => 'string' ),
                    'description'  => array( 'type' => 'string' ),
                    'website'      => array( 'type' => 'string' ),
                    'confirm'      => array( 'type' => 'boolean', 'description' => 'Must be true to update a user.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'update_user' ),
            'permission_callback' => static function () { return current_user_can( 'edit_users' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );

        wp_register_ability( 'np/delete-user', array(
            'label' => 'Delete user', 'category' => 'np-users',
            'description' => 'Delete a user by id, optionally reassigning their content to another user. Safety: requires confirm=true.',
            'input_schema' => array(
                'type' => 'object', 'required' => array( 'user_id', 'confirm' ),
                'properties' => array(
                    'user_id'      => array( 'type' => 'integer' ),
                    'reassign_to'  => array( 'type' => 'integer' ),
                    'confirm'      => array( 'type' => 'boolean', 'description' => 'Must be true to delete a user.' ),
                ),
            ),
            'execute_callback'    => array( __CLASS__, 'delete_user' ),
            'permission_callback' => static function () { return current_user_can( 'delete_users' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }

    /* ==================================================================== */

    public static function list_users( array $input ): array {
        $args = array(
            'number' => max( 1, min( 200, (int) ( $input['number'] ?? 50 ) ) ),
            'offset' => max( 0, (int) ( $input['offset'] ?? 0 ) ),
        );
        if ( ! empty( $input['role'] ) )   { $args['role']   = sanitize_key( (string) $input['role'] ); }
        if ( ! empty( $input['search'] ) ) { $args['search'] = '*' . esc_attr( (string) $input['search'] ) . '*'; }
        $users = get_users( $args );
        $rows  = array();
        foreach ( $users as $u ) {
            $rows[] = array(
                'id'           => $u->ID,
                'login'        => $u->user_login,
                'email'        => $u->user_email,
                'display_name' => $u->display_name,
                'roles'        => $u->roles,
                'registered'   => $u->user_registered,
                'url'          => $u->user_url,
            );
        }
        return array( 'count' => count( $rows ), 'users' => $rows );
    }

    public static function create_user( array $input ) {
        $confirmed = self::require_confirm( $input, 'create a user' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        $login = sanitize_user( (string) ( $input['login'] ?? '' ), true );
        $email = sanitize_email( (string) ( $input['email'] ?? '' ) );
        if ( ! $login || ! is_email( $email ) ) {
            return new \WP_Error( 'np_user_invalid', 'Valid login and email required.' );
        }
        if ( username_exists( $login ) ) {
            return new \WP_Error( 'np_user_exists', 'Username already exists.' );
        }
        if ( email_exists( $email ) ) {
            return new \WP_Error( 'np_email_exists', 'Email already registered.' );
        }
        $password = (string) ( $input['password'] ?? wp_generate_password( 16, true, true ) );
        $user_id  = wp_create_user( $login, $password, $email );
        if ( is_wp_error( $user_id ) ) { return $user_id; }
        $update = array( 'ID' => $user_id );
        $copy   = array( 'first_name', 'last_name', 'display_name', 'description' );
        foreach ( $copy as $k ) {
            if ( isset( $input[ $k ] ) ) { $update[ $k ] = sanitize_text_field( (string) $input[ $k ] ); }
        }
        if ( isset( $input['website'] ) ) { $update['user_url'] = esc_url_raw( (string) $input['website'] ); }
        wp_update_user( $update );
        $role = sanitize_key( (string) ( $input['role'] ?? 'subscriber' ) );
        $u = new \WP_User( $user_id );
        $u->set_role( $role );
        if ( ! empty( $input['send_email'] ) ) { wp_new_user_notification( $user_id, null, 'user' ); }
        return array( 'user_id' => (int) $user_id, 'login' => $login, 'role' => $role );
    }

    public static function update_user( array $input ) {
        $confirmed = self::require_confirm( $input, 'update a user' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        $user = null;
        if ( ! empty( $input['user_id'] ) ) { $user = get_user_by( 'id', (int) $input['user_id'] ); }
        elseif ( ! empty( $input['login'] ) ) { $user = get_user_by( 'login', (string) $input['login'] ); }
        if ( ! $user ) { return new \WP_Error( 'np_user_missing', 'User not found.' ); }
        $update = array( 'ID' => $user->ID );
        $copy   = array( 'first_name', 'last_name', 'display_name', 'description' );
        foreach ( $copy as $k ) {
            if ( isset( $input[ $k ] ) ) { $update[ $k ] = sanitize_text_field( (string) $input[ $k ] ); }
        }
        if ( isset( $input['email'] ) )    { $update['user_email'] = sanitize_email( (string) $input['email'] ); }
        if ( isset( $input['password'] ) ) { $update['user_pass']  = (string) $input['password']; }
        if ( isset( $input['website'] ) )  { $update['user_url']   = esc_url_raw( (string) $input['website'] ); }
        $res = wp_update_user( $update );
        if ( is_wp_error( $res ) ) { return $res; }
        if ( ! empty( $input['role'] ) ) {
            $user->set_role( sanitize_key( (string) $input['role'] ) );
        }
        return array( 'user_id' => $user->ID );
    }

    public static function delete_user( array $input ) {
        $confirmed = self::require_confirm( $input, 'delete a user' );
        if ( is_wp_error( $confirmed ) ) { return $confirmed; }
        if ( ! function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        $user_id  = (int) ( $input['user_id'] ?? 0 );
        $reassign = isset( $input['reassign_to'] ) ? (int) $input['reassign_to'] : null;
        if ( $user_id === get_current_user_id() ) {
            return new \WP_Error( 'np_user_self', 'Refusing to delete the current user.' );
        }
        $ok = wp_delete_user( $user_id, $reassign );
        return array( 'deleted' => (bool) $ok, 'user_id' => $user_id );
    }

    private static function require_confirm( array $input, string $action ) {
        if ( empty( $input['confirm'] ) ) {
            return new \WP_Error( 'np_confirm_required', 'Safety check: pass confirm=true to ' . $action . '.' );
        }
        return true;
    }
}
