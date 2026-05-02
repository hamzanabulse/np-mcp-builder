<?php
$r = NP_MCP_Builder\Image_Generator::generate( array(
    'prompt' => 'Professional GitHub repository social preview banner, wide 16:9 hero image. Dark navy background (#0F172A) with subtle blue grid pattern. Bold large white text in the center reading "NP MCP Builder" in a modern geometric sans-serif. Directly below in lighter cyan: "WordPress + Elementor MCP Control Plane". Bottom row: small horizontal pill badges for "49 abilities", "Yoast SEO", "Elementor", "Gemini AI", "Claude". Top right: a subtle glowing WordPress logo W mark. Clean, minimalist, technical aesthetic similar to Stripe or Linear documentation banners. No people. No clutter. Vector-style flat illustration with soft glow accents.',
    'aspect_ratio' => '16:9',
    'max_width'    => 1280,
    'quality'      => 92,
    'title'        => 'NP MCP Builder social preview',
    'alt_text'     => 'NP MCP Builder GitHub banner',
    'filename'     => 'np-mcp-builder-social',
) );
if ( is_wp_error( $r ) ) {
    echo 'ERR: ' . $r->get_error_message() . PHP_EOL;
} else {
    echo 'id=' . $r['id'] . PHP_EOL;
    echo 'url=' . $r['url'] . PHP_EOL;
    echo 'path=' . get_attached_file( $r['id'] ) . PHP_EOL;
}
