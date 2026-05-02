<?php
/**
 * Image_Generator — wraps Google Gemini image generation with resize + WebP conversion.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Image_Generator {

    public const MODEL = 'gemini-2.5-flash-image';

    /**
     * Generate an image and import it into the Media Library.
     *
     * @param array $input See np/generate-image input_schema.
     * @return array|\WP_Error
     */
    public static function generate( array $input ) {
        $api_key = self::get_api_key();
        if ( ! $api_key ) {
            return new \WP_Error( 'np_no_key', __( 'Gemini API key not configured. Go to Settings → NP MCP Builder.', 'np-mcp-builder' ), array( 'status' => 500 ) );
        }

        $prompt = isset( $input['prompt'] ) ? trim( (string) $input['prompt'] ) : '';
        if ( $prompt === '' ) {
            return new \WP_Error( 'np_invalid_prompt', __( 'prompt is required', 'np-mcp-builder' ), array( 'status' => 400 ) );
        }

        $alt = isset( $input['alt'] ) ? trim( wp_strip_all_tags( (string) $input['alt'] ) ) : '';
        if ( $alt === '' ) {
            $alt = wp_trim_words( $prompt, 12, '' );
        }
        $title       = ! empty( $input['title'] )       ? wp_strip_all_tags( (string) $input['title'] ) : $alt;
        $caption     = ! empty( $input['caption'] )     ? (string) $input['caption']                   : $alt;
        $description = ! empty( $input['description'] ) ? (string) $input['description']               : $prompt;
        $slug        = ! empty( $input['slug'] )        ? sanitize_title( (string) $input['slug'] )    : sanitize_title( $title );
        if ( $slug === '' ) {
            $slug = 'ai-image-' . substr( md5( $prompt . microtime( true ) ), 0, 8 );
        }

        $defaults = self::get_defaults();
        $max_w   = isset( $input['max_width'] )    ? max( 256, min( 2048, (int) $input['max_width'] ) ) : (int) $defaults['max_width'];
        $quality = isset( $input['quality'] )      ? max( 40, min( 95, (int) $input['quality'] ) )      : (int) $defaults['quality'];
        $aspect  = isset( $input['aspect_ratio'] ) ? (string) $input['aspect_ratio']                    : (string) $defaults['aspect_ratio'];

        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . self::MODEL . ':generateContent';
        $body = array(
            'contents'         => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ),
            'generationConfig' => array(
                'responseModalities' => array( 'IMAGE' ),
                'imageConfig'        => array( 'aspectRatio' => $aspect ),
            ),
        );

        $resp = wp_remote_post( $endpoint, array(
            'timeout' => 120,
            'headers' => array(
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $api_key,
            ),
            'body'    => wp_json_encode( $body ),
        ) );
        if ( is_wp_error( $resp ) ) {
            return $resp;
        }
        $code = wp_remote_retrieve_response_code( $resp );
        $raw  = wp_remote_retrieve_body( $resp );
        if ( $code < 200 || $code >= 300 ) {
            return new \WP_Error( 'np_gemini_http', 'Gemini API HTTP ' . $code . ': ' . substr( $raw, 0, 500 ), array( 'status' => 502 ) );
        }

        $data = json_decode( $raw, true );
        $b64  = null;
        if ( is_array( $data ) && ! empty( $data['candidates'] ) ) {
            foreach ( $data['candidates'] as $c ) {
                if ( empty( $c['content']['parts'] ) ) { continue; }
                foreach ( $c['content']['parts'] as $part ) {
                    if ( ! empty( $part['inlineData']['data'] ) )  { $b64 = $part['inlineData']['data']; break 2; }
                    if ( ! empty( $part['inline_data']['data'] ) ) { $b64 = $part['inline_data']['data']; break 2; }
                }
            }
        }
        if ( ! $b64 ) {
            return new \WP_Error( 'np_gemini_noimage', 'Gemini did not return an image. Response: ' . substr( $raw, 0, 500 ), array( 'status' => 502 ) );
        }
        $bin = base64_decode( $b64, true );
        if ( ! $bin ) {
            return new \WP_Error( 'np_b64', 'Invalid base64 from Gemini.', array( 'status' => 502 ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $tmp_in = wp_tempnam( $slug . '.png' );
        if ( file_put_contents( $tmp_in, $bin ) === false ) {
            return new \WP_Error( 'np_write', 'Could not write temp file.', array( 'status' => 500 ) );
        }

        $editor = wp_get_image_editor( $tmp_in );
        if ( is_wp_error( $editor ) ) {
            @unlink( $tmp_in );
            return $editor;
        }
        $size = $editor->get_size();
        if ( $size['width'] > $max_w || $size['height'] > $max_w ) {
            $editor->resize( $max_w, $max_w, false );
        }
        $editor->set_quality( $quality );
        $tmp_out = wp_tempnam( $slug . '.webp' );
        $saved   = $editor->save( $tmp_out, 'image/webp' );
        @unlink( $tmp_in );
        if ( is_wp_error( $saved ) ) {
            @unlink( $tmp_out );
            return $saved;
        }

        $base       = ! empty( $input['filename'] ) ? sanitize_file_name( (string) $input['filename'] ) : $slug;
        $file_array = array( 'name' => $base . '.webp', 'tmp_name' => $saved['path'] );
        $parent     = isset( $input['attach_to'] ) ? (int) $input['attach_to'] : 0;

        $aid = media_handle_sideload( $file_array, $parent, $title, array(
            'post_excerpt' => $caption,
            'post_content' => $description,
            'post_name'    => $slug,
        ) );
        if ( is_wp_error( $aid ) ) {
            @unlink( $saved['path'] );
            return $aid;
        }

        update_post_meta( (int) $aid, '_wp_attachment_image_alt', $alt );
        update_post_meta( (int) $aid, '_np_ai_prompt', $prompt );
        update_post_meta( (int) $aid, '_np_ai_model', self::MODEL );
        update_post_meta( (int) $aid, '_np_ai_aspect', $aspect );

        if ( $parent > 0 && ! empty( $input['set_featured'] ) ) {
            set_post_thumbnail( $parent, (int) $aid );
        }

        $file_path = get_attached_file( (int) $aid );
        $filesize  = $file_path && file_exists( $file_path ) ? filesize( $file_path ) : 0;
        $meta      = wp_get_attachment_metadata( (int) $aid );

        return array(
            'attachment_id' => (int) $aid,
            'url'           => wp_get_attachment_url( $aid ),
            'edit_link'     => admin_url( 'post.php?post=' . (int) $aid . '&action=edit' ),
            'alt'           => $alt,
            'title'         => $title,
            'caption'       => $caption,
            'description'   => $description,
            'slug'          => $slug,
            'filename'      => $base . '.webp',
            'filesize'      => (int) $filesize,
            'width'         => isset( $meta['width'] )  ? (int) $meta['width']  : 0,
            'height'        => isset( $meta['height'] ) ? (int) $meta['height'] : 0,
            'parent'        => $parent,
            'model'         => self::MODEL,
        );
    }

    /**
     * Read the Gemini key from the plugin options first, then fall back to the
     * legacy `np_gemini_api_key` option for backward compatibility.
     */
    public static function get_api_key(): string {
        $key = (string) Plugin::get_option( 'gemini_api_key', '' );
        if ( $key !== '' ) { return $key; }
        return (string) get_option( 'np_gemini_api_key', '' );
    }

    public static function get_defaults(): array {
        return array(
            'max_width'    => (int) ( Plugin::get_option( 'default_max_width', 1280 ) ?: 1280 ),
            'quality'      => (int) ( Plugin::get_option( 'default_quality', 78 ) ?: 78 ),
            'aspect_ratio' => (string) ( Plugin::get_option( 'default_aspect_ratio', '16:9' ) ?: '16:9' ),
        );
    }
}
