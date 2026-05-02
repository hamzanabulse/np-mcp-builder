<?php
/**
 * Smoke test — run via:
 *   wp --allow-root --path=/var/www/wordpress eval-file bin/test-abilities.php
 *
 * Verifies all abilities in Plugin::ABILITY_MAP register, then dry-runs a few
 * read-only ones to make sure the callbacks don't fatal.
 */

if ( ! function_exists( 'wp_get_ability' ) ) {
    echo "FAIL: Abilities API not present.\n";
    exit( 1 );
}

// Force-init the abilities (in case priority mismatch).
do_action( 'wp_abilities_api_categories_init' );
do_action( 'wp_abilities_api_init' );

$expected = array_keys( \NP_MCP_Builder\Plugin::ABILITY_MAP );
$ok       = 0;
$missing  = array();
foreach ( $expected as $tool ) {
    if ( wp_get_ability( $tool ) ) {
        $ok++;
    } else {
        $missing[] = $tool;
    }
}

echo "=== Registration ===\n";
echo "Expected:   " . count( $expected ) . "\n";
echo "Registered: {$ok}\n";
echo "Missing:    " . count( $missing ) . "\n";
if ( $missing ) {
    foreach ( $missing as $m ) { echo "  - {$m}\n"; }
}

echo "\n=== Read-only callback dry-runs ===\n";

$cases = array(
    'np/site-info'     => array(),
    'np/list-posts'    => array( 'per_page' => 1 ),
    'np/list-plugins'  => array(),
    'np/list-themes'   => array(),
    'np/list-menus'    => array(),
    'np/list-users'    => array( 'per_page' => 1 ),
    'np/system-info'   => array(),
    'np/get-yoast-global'  => array(),
    'np/get-elementor-kit' => array(),
    'np/elementor-list-templates' => array(),
    'np/audit-seo'         => array( 'limit' => 5 ),
);

foreach ( $cases as $tool => $input ) {
    $ability = wp_get_ability( $tool );
    if ( ! $ability ) {
        echo "[SKIP] {$tool} — not registered\n";
        continue;
    }
    try {
        $result = $ability->execute( $input );
        if ( is_wp_error( $result ) ) {
            echo "[ERR ] {$tool} — " . $result->get_error_code() . ': ' . $result->get_error_message() . "\n";
        } else {
            $hint = '';
            if ( is_array( $result ) ) {
                $first_key = array_key_first( $result );
                if ( $first_key !== null ) {
                    $val = $result[ $first_key ];
                    if ( is_scalar( $val ) ) { $hint = " ({$first_key}=" . substr( (string) $val, 0, 40 ) . ')'; }
                    elseif ( is_array( $val ) ) { $hint = " ({$first_key}=" . count( $val ) . ' items)'; }
                }
            }
            echo "[OK  ] {$tool}{$hint}\n";
        }
    } catch ( \Throwable $e ) {
        echo "[FAIL] {$tool} — " . $e->getMessage() . "\n";
    }
}

echo "\n=== Done ===\n";
