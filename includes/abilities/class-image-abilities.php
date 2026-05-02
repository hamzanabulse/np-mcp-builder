<?php
/**
 * Image abilities — np/generate-image wraps Image_Generator.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder\Abilities;

use NP_MCP_Builder\Image_Generator;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Image_Abilities {

    public static function register(): void {
        wp_register_ability( 'np/generate-image', array(
            'label'       => 'Generate Image (Gemini → WebP)',
            'description' => 'Generate an image with Google Gemini (gemini-2.5-flash-image), resize, convert to WebP, save to Media Library with full SEO metadata. Returns attachment_id and url.',
            'category'    => 'np-media',
            'input_schema' => array(
                'type' => 'object',
                'required' => array( 'prompt' ),
                'properties' => array(
                    'prompt'       => array( 'type' => 'string' ),
                    'alt'          => array( 'type' => 'string' ),
                    'title'        => array( 'type' => 'string' ),
                    'caption'      => array( 'type' => 'string' ),
                    'description'  => array( 'type' => 'string' ),
                    'slug'         => array( 'type' => 'string' ),
                    'filename'     => array( 'type' => 'string' ),
                    'attach_to'    => array( 'type' => 'integer', 'description' => 'Optional post ID to attach this image to.' ),
                    'set_featured' => array( 'type' => 'boolean', 'description' => 'If attach_to is set, also use this as the featured image.' ),
                    'aspect_ratio' => array( 'type' => 'string', 'enum' => array( '1:1', '2:3', '3:2', '3:4', '4:3', '4:5', '5:4', '9:16', '16:9', '21:9' ), 'default' => '16:9' ),
                    'max_width'    => array( 'type' => 'integer', 'minimum' => 256, 'maximum' => 2048, 'default' => 1280 ),
                    'quality'      => array( 'type' => 'integer', 'minimum' => 40,  'maximum' => 95,   'default' => 78 ),
                ),
            ),
            'output_schema' => array(
                'type' => 'object',
                'properties' => array(
                    'attachment_id' => array( 'type' => 'integer' ),
                    'url'           => array( 'type' => 'string' ),
                    'edit_link'     => array( 'type' => 'string' ),
                    'alt'           => array( 'type' => 'string' ),
                    'title'         => array( 'type' => 'string' ),
                    'filename'      => array( 'type' => 'string' ),
                    'filesize'      => array( 'type' => 'integer' ),
                    'width'         => array( 'type' => 'integer' ),
                    'height'        => array( 'type' => 'integer' ),
                ),
            ),
            'execute_callback'    => array( Image_Generator::class, 'generate' ),
            'permission_callback' => static function () { return current_user_can( 'upload_files' ); },
            'meta' => array( 'mcp' => array( 'public' => true ) ),
        ) );
    }
}
