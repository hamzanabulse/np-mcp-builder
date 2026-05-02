<?php
/**
 * Section_Builder — converts friendly section descriptors to Elementor JSON nodes.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Section_Builder {

    /** Generate a 7-char Elementor element id. */
    public static function eid(): string {
        return substr( md5( uniqid( '', true ) . wp_rand() ), 0, 7 );
    }

    /**
     * Build one Elementor section node from a friendly spec.
     *
     * @param array $s   Friendly section spec.
     * @param array $ctx Mutable context (post_id, generated_attachments).
     * @return array|null
     */
    public static function build( array $s, array &$ctx = array() ) {
        $type    = isset( $s['type'] ) ? (string) $s['type'] : 'paragraph';
        $widgets = array();
        $columns = null;
        $section_settings = array(
            'content_width' => array( 'unit' => 'px', 'size' => 760 ),
            'gap'           => 'default',
        );

        switch ( $type ) {
            case 'hero':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#0F1115',
                    'padding'               => array( 'unit' => 'px', 'top' => '80', 'right' => '24', 'bottom' => '80', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 900 ),
                );
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                    'settings' => array(
                        'title' => isset( $s['title'] ) ? (string) $s['title'] : '',
                        'header_size' => 'h1', 'align' => 'center', 'title_color' => '#FFFFFF',
                        'typography_typography' => 'custom',
                        'typography_font_size'   => array( 'unit' => 'px', 'size' => 44 ),
                        'typography_font_weight' => '800',
                        'typography_line_height' => array( 'unit' => 'em', 'size' => 1.15 ),
                    ),
                    'elements' => array(),
                );
                if ( ! empty( $s['subtitle'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="text-align:center;color:rgba(255,255,255,0.85);font-size:18px;line-height:1.6;margin-top:14px;">' . wp_kses_post( (string) $s['subtitle'] ) . '</p>',
                            'text_color' => '#FFFFFF',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['cta_text'] ) && ! empty( $s['cta_url'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'button',
                        'settings' => array(
                            'text' => (string) $s['cta_text'],
                            'link' => array( 'url' => (string) $s['cta_url'], 'is_external' => '', 'nofollow' => '' ),
                            'align' => 'center',
                            'background_color' => '#FF6F1B', 'button_text_color' => '#FFFFFF',
                            'hover_color' => '#FFFFFF',
                            'button_background_hover_color' => '#E2540A',
                            'border_radius' => array( 'unit' => 'px', 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'isLinked' => true ),
                            'text_padding'  => array( 'unit' => 'px', 'top' => '14', 'right' => '28', 'bottom' => '14', 'left' => '28', 'isLinked' => false ),
                            'typography_typography' => 'custom',
                            'typography_font_weight' => '700',
                        ),
                        'elements' => array(),
                    );
                }
                break;

            case 'heading':
                $level = isset( $s['level'] ) ? strtolower( (string) $s['level'] ) : 'h2';
                if ( ! in_array( $level, array( 'h1','h2','h3','h4','h5','h6' ), true ) ) { $level = 'h2'; }
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                    'settings' => array(
                        'title' => isset( $s['text'] ) ? (string) $s['text'] : '',
                        'header_size' => $level,
                        'align' => isset( $s['align'] ) ? (string) $s['align'] : 'left',
                        'typography_typography' => 'custom',
                        'typography_font_size' => array( 'unit' => 'px', 'size' => $level === 'h2' ? 32 : ( $level === 'h3' ? 24 : 20 ) ),
                        'typography_font_weight' => '800',
                        'typography_line_height' => array( 'unit' => 'em', 'size' => 1.25 ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'paragraph':
            case 'text':
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                    'settings' => array(
                        'editor' => isset( $s['text'] ) ? wp_kses_post( (string) $s['text'] ) : '',
                        'typography_typography' => 'custom',
                        'typography_font_size'  => array( 'unit' => 'px', 'size' => 17 ),
                        'typography_line_height' => array( 'unit' => 'em', 'size' => 1.7 ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'image':
                $att_id = isset( $s['attachment_id'] ) ? (int) $s['attachment_id'] : 0;
                $url    = $att_id ? (string) wp_get_attachment_url( $att_id ) : ( isset( $s['url'] ) ? (string) $s['url'] : '' );
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'image',
                    'settings' => array(
                        'image' => array( 'id' => $att_id ?: '', 'url' => $url ),
                        'image_size' => 'large',
                        'caption_source' => ! empty( $s['caption'] ) ? 'custom' : 'none',
                        'caption' => isset( $s['caption'] ) ? wp_kses_post( (string) $s['caption'] ) : '',
                        'align' => 'center',
                        'border_radius' => array( 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'image_gen':
                if ( ! empty( $s['prompt'] ) && ! empty( $s['alt'] ) ) {
                    $gen = Image_Generator::generate( array(
                        'prompt'       => (string) $s['prompt'],
                        'alt'          => (string) $s['alt'],
                        'title'        => isset( $s['title'] ) ? (string) $s['title'] : '',
                        'caption'      => isset( $s['caption'] ) ? (string) $s['caption'] : '',
                        'description'  => isset( $s['description'] ) ? (string) $s['description'] : '',
                        'aspect_ratio' => isset( $s['aspect_ratio'] ) ? (string) $s['aspect_ratio'] : '16:9',
                        'attach_to'    => isset( $ctx['post_id'] ) ? (int) $ctx['post_id'] : 0,
                    ) );
                    if ( ! is_wp_error( $gen ) && ! empty( $gen['attachment_id'] ) ) {
                        $att_id = (int) $gen['attachment_id'];
                        $ctx['generated_attachments'][] = $att_id;
                        $widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'image',
                            'settings' => array(
                                'image' => array( 'id' => $att_id, 'url' => (string) wp_get_attachment_url( $att_id ) ),
                                'image_size' => 'large',
                                'caption_source' => ! empty( $s['caption'] ) ? 'custom' : 'none',
                                'caption' => isset( $s['caption'] ) ? wp_kses_post( (string) $s['caption'] ) : '',
                                'align' => 'center',
                                'border_radius' => array( 'unit' => 'px', 'top' => '12', 'right' => '12', 'bottom' => '12', 'left' => '12', 'isLinked' => true ),
                            ),
                            'elements' => array(),
                        );
                    }
                }
                break;

            case 'list':
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
                $list = array();
                foreach ( $items as $it ) {
                    $text = is_array( $it ) ? ( $it['text'] ?? '' ) : (string) $it;
                    $list[] = array(
                        'text' => wp_kses_post( $text ),
                        'selected_icon' => array( 'value' => 'fas fa-check-circle', 'library' => 'fa-solid' ),
                        '_id' => self::eid(),
                    );
                }
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon-list',
                    'settings' => array(
                        'icon_list' => $list,
                        'icon_color' => '#FF6F1B',
                        'space_between' => array( 'unit' => 'px', 'size' => 12 ),
                        'typography_typography' => 'custom',
                        'typography_font_size' => array( 'unit' => 'px', 'size' => 17 ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'quote':
                $quote_html = '<blockquote style="border-left:4px solid #FF6F1B;padding:12px 0 12px 24px;margin:24px 0;font-size:20px;line-height:1.55;font-style:italic;color:#1a1a1a;">'
                    . wp_kses_post( isset( $s['text'] ) ? (string) $s['text'] : '' )
                    . ( ! empty( $s['author'] ) ? '<cite style="display:block;margin-top:10px;font-size:14px;font-style:normal;color:#6b7280;">— ' . esc_html( (string) $s['author'] ) . '</cite>' : '' )
                    . '</blockquote>';
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                    'settings' => array( 'editor' => $quote_html ),
                    'elements' => array(),
                );
                break;

            case 'divider':
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'divider',
                    'settings' => array(
                        'color' => '#e5e7eb',
                        'weight' => array( 'unit' => 'px', 'size' => 1 ),
                        'gap' => array( 'unit' => 'px', 'size' => 24 ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'cta':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFF7F0',
                    'padding'               => array( 'unit' => 'px', 'top' => '50', 'right' => '32', 'bottom' => '50', 'left' => '32', 'isLinked' => false ),
                    'border_radius'         => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'isLinked' => true ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 900 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 28 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['text'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array( 'editor' => '<p style="text-align:center;font-size:17px;line-height:1.6;margin-top:8px;">' . wp_kses_post( (string) $s['text'] ) . '</p>' ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['button_text'] ) && ! empty( $s['button_url'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'button',
                        'settings' => array(
                            'text' => (string) $s['button_text'],
                            'link' => array( 'url' => (string) $s['button_url'], 'is_external' => '', 'nofollow' => '' ),
                            'align' => 'center',
                            'background_color' => '#FF6F1B', 'button_text_color' => '#FFFFFF',
                            'border_radius' => array( 'unit' => 'px', 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'isLinked' => true ),
                            'text_padding' => array( 'unit' => 'px', 'top' => '14', 'right' => '28', 'bottom' => '14', 'left' => '28', 'isLinked' => false ),
                        ),
                        'elements' => array(),
                    );
                }
                break;

            case 'html':
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'html',
                    'settings' => array( 'html' => isset( $s['code'] ) ? (string) $s['code'] : '' ),
                    'elements' => array(),
                );
                break;

            case 'two_columns':
                $left  = isset( $s['left'] ) && is_array( $s['left'] ) ? $s['left'] : array();
                $right = isset( $s['right'] ) && is_array( $s['right'] ) ? $s['right'] : array();
                $build_widgets = static function ( array $list ) use ( &$ctx ) {
                    $out = array();
                    foreach ( $list as $sub ) {
                        $node = self::build( (array) $sub, $ctx );
                        if ( $node && ! empty( $node['elements'] ) ) {
                            foreach ( $node['elements'] as $col ) {
                                foreach ( ( $col['elements'] ?? array() ) as $w ) { $out[] = $w; }
                            }
                        }
                    }
                    return $out;
                };
                $columns = array(
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 50, '_inline_size' => null ),
                        'elements' => $build_widgets( $left ),
                        'isInner' => false,
                    ),
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 50, '_inline_size' => null ),
                        'elements' => $build_widgets( $right ),
                        'isInner' => false,
                    ),
                );
                break;

            default:
                return null;
        }

        if ( $columns === null ) {
            $columns = array(
                array(
                    'id' => self::eid(), 'elType' => 'column',
                    'settings' => array( '_column_size' => 100, '_inline_size' => null ),
                    'elements' => $widgets,
                    'isInner' => false,
                ),
            );
        }

        return array(
            'id' => self::eid(),
            'elType' => 'section',
            'settings' => $section_settings,
            'elements' => $columns,
            'isInner' => false,
        );
    }
}
