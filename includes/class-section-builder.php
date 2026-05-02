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

            case 'spacer':
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'spacer',
                    'settings' => array(
                        'space' => array( 'unit' => 'px', 'size' => isset( $s['height'] ) ? (int) $s['height'] : 40 ),
                    ),
                    'elements' => array(),
                );
                break;

            case 'video':
                $url = isset( $s['url'] ) ? (string) $s['url'] : '';
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'video',
                    'settings' => array(
                        'video_type' => 'youtube',
                        'youtube_url' => $url,
                        'aspect_ratio' => isset( $s['aspect_ratio'] ) ? (string) $s['aspect_ratio'] : '169',
                    ),
                    'elements' => array(),
                );
                break;

            case 'schema':
                // Inject raw JSON-LD via an html widget.
                $json = isset( $s['json'] ) ? (string) $s['json'] : '';
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'html',
                    'settings' => array( 'html' => '<script type="application/ld+json">' . $json . '</script>' ),
                    'elements' => array(),
                );
                break;

            case 'problem_agitation':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FEF2F2',
                    'padding'               => array( 'unit' => 'px', 'top' => '64', 'right' => '24', 'bottom' => '64', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1100 ),
                );
                if ( ! empty( $s['eyebrow'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['eyebrow'],
                            'header_size' => 'h6', 'align' => 'center', 'title_color' => '#B91C1C',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 14 ),
                            'typography_font_weight' => '700',
                            'typography_text_transform' => 'uppercase',
                            'typography_letter_spacing' => array( 'unit' => 'px', 'size' => 2 ),
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                            'title_color' => '#0F172A',
                        ),
                        'elements' => array(),
                    );
                }
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
                if ( $items ) {
                    $list = array();
                    foreach ( $items as $it ) {
                        $text = is_array( $it ) ? ( $it['text'] ?? '' ) : (string) $it;
                        $list[] = array(
                            'text' => wp_kses_post( $text ),
                            'selected_icon' => array( 'value' => 'fas fa-times-circle', 'library' => 'fa-solid' ),
                            '_id' => self::eid(),
                        );
                    }
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon-list',
                        'settings' => array(
                            'icon_list' => $list,
                            'icon_color' => '#DC2626',
                            'space_between' => array( 'unit' => 'px', 'size' => 14 ),
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 17 ),
                            'text_color' => '#0F172A',
                        ),
                        'elements' => array(),
                    );
                }
                break;

            case 'benefits_grid':
            case 'features_grid':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFFFFF',
                    'padding'               => array( 'unit' => 'px', 'top' => '72', 'right' => '24', 'bottom' => '72', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1200 ),
                );
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
                $cols  = max( 1, min( 4, isset( $s['columns'] ) ? (int) $s['columns'] : 3 ) );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['subtitle'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="text-align:center;font-size:18px;line-height:1.6;color:#475569;max-width:720px;margin:8px auto 32px;">' . wp_kses_post( (string) $s['subtitle'] ) . '</p>',
                        ),
                        'elements' => array(),
                    );
                }
                $col_size = (int) floor( 100 / $cols );
                $columns  = array();
                $card_color = isset( $s['card_color'] ) ? (string) $s['card_color'] : '#F8FAFC';
                $accent     = isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981';
                foreach ( $items as $it ) {
                    if ( ! is_array( $it ) ) { continue; }
                    $card_widgets = array();
                    if ( ! empty( $it['icon'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon',
                            'settings' => array(
                                'selected_icon' => array( 'value' => (string) $it['icon'], 'library' => 'fa-solid' ),
                                'primary_color' => $accent,
                                'size' => array( 'unit' => 'px', 'size' => 36 ),
                                'align' => 'left',
                            ),
                            'elements' => array(),
                        );
                    } elseif ( ! empty( $it['image_url'] ) || ! empty( $it['attachment_id'] ) ) {
                        $aid = isset( $it['attachment_id'] ) ? (int) $it['attachment_id'] : 0;
                        $url = $aid ? (string) wp_get_attachment_url( $aid ) : (string) $it['image_url'];
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'image',
                            'settings' => array(
                                'image' => array( 'id' => $aid ?: '', 'url' => $url ),
                                'image_size' => 'medium',
                                'align' => 'left',
                                'border_radius' => array( 'unit' => 'px', 'top' => '10', 'right' => '10', 'bottom' => '10', 'left' => '10', 'isLinked' => true ),
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $it['title'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                            'settings' => array(
                                'title' => (string) $it['title'], 'header_size' => 'h3',
                                'typography_typography' => 'custom',
                                'typography_font_size' => array( 'unit' => 'px', 'size' => 22 ),
                                'typography_font_weight' => '700',
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $it['text'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                            'settings' => array(
                                'editor' => '<p style="font-size:16px;line-height:1.65;color:#475569;">' . wp_kses_post( (string) $it['text'] ) . '</p>',
                            ),
                            'elements' => array(),
                        );
                    }
                    $columns[] = array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array(
                            '_column_size'        => $col_size,
                            '_inline_size'        => null,
                            'background_background' => 'classic',
                            'background_color'    => $card_color,
                            'border_radius'       => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'isLinked' => true ),
                            'padding'             => array( 'unit' => 'px', 'top' => '28', 'right' => '24', 'bottom' => '28', 'left' => '24', 'isLinked' => false ),
                            'space_between_widgets' => 14,
                        ),
                        'elements' => $card_widgets,
                        'isInner'  => false,
                    );
                }
                if ( $columns ) {
                    // We already set $widgets (heading + subtitle); nest grid below them as inner section.
                    $inner = array(
                        'id' => self::eid(), 'elType' => 'section',
                        'settings' => array( 'gap' => 'extended', 'structure' => str_pad( (string) $cols, 2, '0', STR_PAD_LEFT ) ),
                        'elements' => $columns, 'isInner' => true,
                    );
                    $widgets[] = $inner;
                }
                $columns = null; // rebuild outer below
                break;

            case 'steps':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFFFFF',
                    'padding'               => array( 'unit' => 'px', 'top' => '72', 'right' => '24', 'bottom' => '72', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1100 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
                $accent = isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981';
                $cols   = max( 1, min( 4, count( $items ) ) );
                $col_size = (int) floor( 100 / $cols );
                $step_columns = array();
                foreach ( $items as $idx => $it ) {
                    if ( ! is_array( $it ) ) { continue; }
                    $num = isset( $it['number'] ) ? (string) $it['number'] : (string) ( $idx + 1 );
                    $card_widgets = array(
                        array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                            'settings' => array(
                                'title' => $num, 'header_size' => 'div',
                                'align' => 'center', 'title_color' => $accent,
                                'typography_typography' => 'custom',
                                'typography_font_size' => array( 'unit' => 'px', 'size' => 64 ),
                                'typography_font_weight' => '900',
                                'typography_line_height' => array( 'unit' => 'em', 'size' => 1 ),
                            ),
                            'elements' => array(),
                        ),
                    );
                    if ( ! empty( $it['title'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                            'settings' => array(
                                'title' => (string) $it['title'], 'header_size' => 'h3', 'align' => 'center',
                                'typography_typography' => 'custom',
                                'typography_font_size' => array( 'unit' => 'px', 'size' => 22 ),
                                'typography_font_weight' => '700',
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $it['text'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                            'settings' => array(
                                'editor' => '<p style="text-align:center;font-size:16px;line-height:1.6;color:#475569;">' . wp_kses_post( (string) $it['text'] ) . '</p>',
                            ),
                            'elements' => array(),
                        );
                    }
                    $step_columns[] = array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => $col_size, '_inline_size' => null, 'space_between_widgets' => 10 ),
                        'elements' => $card_widgets, 'isInner' => false,
                    );
                }
                if ( $step_columns ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'section',
                        'settings' => array( 'gap' => 'extended', 'structure' => str_pad( (string) $cols, 2, '0', STR_PAD_LEFT ) ),
                        'elements' => $step_columns, 'isInner' => true,
                    );
                }
                $columns = null;
                break;

            case 'testimonials':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#0F172A',
                    'padding'               => array( 'unit' => 'px', 'top' => '72', 'right' => '24', 'bottom' => '72', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1200 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'title_color' => '#FFFFFF',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
                $cols  = max( 1, min( 4, count( $items ) ) );
                $col_size = (int) floor( 100 / $cols );
                $tc = array();
                foreach ( $items as $it ) {
                    if ( ! is_array( $it ) ) { continue; }
                    $card_widgets = array();
                    // Stars.
                    $rating = isset( $it['rating'] ) ? max( 1, min( 5, (int) $it['rating'] ) ) : 5;
                    $card_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="color:#FCD34D;font-size:20px;letter-spacing:2px;margin:0 0 8px;">' . str_repeat( '★', $rating ) . '</p>',
                        ),
                        'elements' => array(),
                    );
                    if ( ! empty( $it['quote'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                            'settings' => array(
                                'editor' => '<p style="color:#E2E8F0;font-size:17px;line-height:1.65;font-style:italic;">"' . wp_kses_post( (string) $it['quote'] ) . '"</p>',
                            ),
                            'elements' => array(),
                        );
                    }
                    // Author row: optional avatar + name/role.
                    $author_html = '<div style="display:flex;align-items:center;gap:12px;margin-top:14px;">';
                    if ( ! empty( $it['avatar_url'] ) || ! empty( $it['avatar_id'] ) ) {
                        $aid = isset( $it['avatar_id'] ) ? (int) $it['avatar_id'] : 0;
                        $au  = $aid ? (string) wp_get_attachment_url( $aid ) : (string) $it['avatar_url'];
                        $author_html .= '<img src="' . esc_url( $au ) . '" alt="' . esc_attr( $it['name'] ?? '' ) . '" style="width:48px;height:48px;border-radius:999px;object-fit:cover;" />';
                    }
                    $author_html .= '<div>';
                    if ( ! empty( $it['name'] ) ) {
                        $author_html .= '<div style="color:#FFFFFF;font-weight:700;">' . esc_html( (string) $it['name'] ) . '</div>';
                    }
                    if ( ! empty( $it['role'] ) ) {
                        $author_html .= '<div style="color:#94A3B8;font-size:14px;">' . esc_html( (string) $it['role'] ) . '</div>';
                    }
                    $author_html .= '</div></div>';
                    $card_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'html',
                        'settings' => array( 'html' => $author_html ),
                        'elements' => array(),
                    );
                    $tc[] = array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array(
                            '_column_size' => $col_size, '_inline_size' => null,
                            'background_background' => 'classic', 'background_color' => '#1E293B',
                            'border_radius' => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'isLinked' => true ),
                            'padding' => array( 'unit' => 'px', 'top' => '28', 'right' => '24', 'bottom' => '28', 'left' => '24', 'isLinked' => false ),
                            'space_between_widgets' => 4,
                        ),
                        'elements' => $card_widgets, 'isInner' => false,
                    );
                }
                if ( $tc ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'section',
                        'settings' => array( 'gap' => 'extended', 'structure' => str_pad( (string) $cols, 2, '0', STR_PAD_LEFT ) ),
                        'elements' => $tc, 'isInner' => true,
                    );
                }
                $columns = null;
                break;

            case 'faq':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#F8FAFC',
                    'padding'               => array( 'unit' => 'px', 'top' => '72', 'right' => '24', 'bottom' => '72', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 900 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
                $accordion = array();
                foreach ( $items as $it ) {
                    if ( ! is_array( $it ) ) { continue; }
                    $accordion[] = array(
                        '_id'        => self::eid(),
                        'tab_title'  => (string) ( $it['question'] ?? '' ),
                        'tab_content'=> '<p style="font-size:16px;line-height:1.65;color:#334155;">' . wp_kses_post( (string) ( $it['answer'] ?? '' ) ) . '</p>',
                    );
                }
                if ( $accordion ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'accordion',
                        'settings' => array(
                            'tabs'        => $accordion,
                            'icon_align'  => 'right',
                            'border_color'=> '#E2E8F0',
                            'title_color' => '#0F172A',
                            'tab_active_color' => isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 18 ),
                            'typography_font_weight' => '600',
                        ),
                        'elements' => array(),
                    );
                }
                break;

            case 'stats':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#10B981',
                    'padding'               => array( 'unit' => 'px', 'top' => '56', 'right' => '24', 'bottom' => '56', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1100 ),
                );
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
                $cols  = max( 1, min( 4, count( $items ) ) );
                $col_size = (int) floor( 100 / $cols );
                $sc = array();
                foreach ( $items as $it ) {
                    if ( ! is_array( $it ) ) { continue; }
                    $sc[] = array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => $col_size, '_inline_size' => null ),
                        'elements' => array(
                            array(
                                'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                                'settings' => array(
                                    'title' => (string) ( $it['number'] ?? '' ), 'header_size' => 'div',
                                    'align' => 'center', 'title_color' => '#FFFFFF',
                                    'typography_typography' => 'custom',
                                    'typography_font_size' => array( 'unit' => 'px', 'size' => 48 ),
                                    'typography_font_weight' => '900',
                                ),
                                'elements' => array(),
                            ),
                            array(
                                'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                                'settings' => array(
                                    'editor' => '<p style="text-align:center;color:rgba(255,255,255,0.92);font-size:16px;font-weight:600;">' . esc_html( (string) ( $it['label'] ?? '' ) ) . '</p>',
                                ),
                                'elements' => array(),
                            ),
                        ),
                        'isInner' => false,
                    );
                }
                $columns = $sc ?: null;
                break;

            case 'pricing':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFFFFF',
                    'padding'               => array( 'unit' => 'px', 'top' => '72', 'right' => '24', 'bottom' => '72', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1100 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 36 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                $plans = isset( $s['plans'] ) && is_array( $s['plans'] ) ? array_values( $s['plans'] ) : array();
                $cols  = max( 1, min( 4, count( $plans ) ) );
                $col_size = (int) floor( 100 / $cols );
                $accent = isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981';
                $pc = array();
                foreach ( $plans as $p ) {
                    if ( ! is_array( $p ) ) { continue; }
                    $is_featured = ! empty( $p['featured'] );
                    $card_widgets = array();
                    if ( ! empty( $p['name'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                            'settings' => array(
                                'title' => (string) $p['name'], 'header_size' => 'h3', 'align' => 'center',
                                'title_color' => $is_featured ? '#FFFFFF' : '#0F172A',
                                'typography_typography' => 'custom',
                                'typography_font_size' => array( 'unit' => 'px', 'size' => 22 ),
                                'typography_font_weight' => '700',
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( isset( $p['price'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                            'settings' => array(
                                'title' => (string) $p['price'], 'header_size' => 'div', 'align' => 'center',
                                'title_color' => $is_featured ? '#FFFFFF' : $accent,
                                'typography_typography' => 'custom',
                                'typography_font_size' => array( 'unit' => 'px', 'size' => 48 ),
                                'typography_font_weight' => '900',
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $p['period'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                            'settings' => array(
                                'editor' => '<p style="text-align:center;color:' . ( $is_featured ? 'rgba(255,255,255,0.85)' : '#64748B' ) . ';font-size:14px;margin:0 0 16px;">' . esc_html( (string) $p['period'] ) . '</p>',
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $p['features'] ) && is_array( $p['features'] ) ) {
                        $list = array();
                        foreach ( $p['features'] as $f ) {
                            $list[] = array(
                                'text' => esc_html( (string) $f ),
                                'selected_icon' => array( 'value' => 'fas fa-check', 'library' => 'fa-solid' ),
                                '_id' => self::eid(),
                            );
                        }
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon-list',
                            'settings' => array(
                                'icon_list'    => $list,
                                'icon_color'   => $is_featured ? '#FFFFFF' : $accent,
                                'text_color'   => $is_featured ? '#FFFFFF' : '#334155',
                                'space_between'=> array( 'unit' => 'px', 'size' => 10 ),
                                'typography_typography' => 'custom',
                                'typography_font_size'  => array( 'unit' => 'px', 'size' => 15 ),
                            ),
                            'elements' => array(),
                        );
                    }
                    if ( ! empty( $p['button_text'] ) && ! empty( $p['button_url'] ) ) {
                        $card_widgets[] = array(
                            'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'button',
                            'settings' => array(
                                'text' => (string) $p['button_text'],
                                'link' => array( 'url' => (string) $p['button_url'], 'is_external' => '', 'nofollow' => '' ),
                                'align' => 'center',
                                'background_color' => $is_featured ? '#FFFFFF' : $accent,
                                'button_text_color' => $is_featured ? $accent : '#FFFFFF',
                                'border_radius' => array( 'unit' => 'px', 'top' => '8', 'right' => '8', 'bottom' => '8', 'left' => '8', 'isLinked' => true ),
                                'text_padding'  => array( 'unit' => 'px', 'top' => '14', 'right' => '24', 'bottom' => '14', 'left' => '24', 'isLinked' => false ),
                            ),
                            'elements' => array(),
                        );
                    }
                    $pc[] = array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array(
                            '_column_size' => $col_size, '_inline_size' => null,
                            'background_background' => 'classic',
                            'background_color' => $is_featured ? $accent : '#F8FAFC',
                            'border_radius' => array( 'unit' => 'px', 'top' => '16', 'right' => '16', 'bottom' => '16', 'left' => '16', 'isLinked' => true ),
                            'padding' => array( 'unit' => 'px', 'top' => '32', 'right' => '24', 'bottom' => '32', 'left' => '24', 'isLinked' => false ),
                            'space_between_widgets' => 8,
                        ),
                        'elements' => $card_widgets, 'isInner' => false,
                    );
                }
                if ( $pc ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'section',
                        'settings' => array( 'gap' => 'extended', 'structure' => str_pad( (string) $cols, 2, '0', STR_PAD_LEFT ) ),
                        'elements' => $pc, 'isInner' => true,
                    );
                }
                $columns = null;
                break;

            case 'author_bio':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFFFFF',
                    'padding'               => array( 'unit' => 'px', 'top' => '56', 'right' => '24', 'bottom' => '56', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1000 ),
                );
                $aid = isset( $s['attachment_id'] ) ? (int) $s['attachment_id'] : 0;
                $img_url = $aid ? (string) wp_get_attachment_url( $aid ) : ( isset( $s['image_url'] ) ? (string) $s['image_url'] : '' );
                $left_widgets = array();
                if ( $img_url ) {
                    $left_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'image',
                        'settings' => array(
                            'image' => array( 'id' => $aid ?: '', 'url' => $img_url ),
                            'image_size' => 'medium',
                            'border_radius' => array( 'unit' => 'px', 'top' => '999', 'right' => '999', 'bottom' => '999', 'left' => '999', 'isLinked' => true ),
                            'align' => 'center',
                        ),
                        'elements' => array(),
                    );
                }
                $right_widgets = array();
                if ( ! empty( $s['name'] ) ) {
                    $right_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['name'], 'header_size' => 'h3',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 26 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['role'] ) ) {
                    $right_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="font-size:14px;color:#64748B;text-transform:uppercase;letter-spacing:1.5px;font-weight:700;margin:0 0 8px;">' . esc_html( (string) $s['role'] ) . '</p>',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['bio'] ) ) {
                    $right_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="font-size:17px;line-height:1.7;color:#334155;">' . wp_kses_post( (string) $s['bio'] ) . '</p>',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['credentials'] ) && is_array( $s['credentials'] ) ) {
                    $list = array();
                    foreach ( $s['credentials'] as $c ) {
                        $list[] = array(
                            'text' => esc_html( (string) $c ),
                            'selected_icon' => array( 'value' => 'fas fa-award', 'library' => 'fa-solid' ),
                            '_id' => self::eid(),
                        );
                    }
                    $right_widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon-list',
                        'settings' => array(
                            'icon_list' => $list,
                            'icon_color' => isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981',
                            'space_between' => array( 'unit' => 'px', 'size' => 8 ),
                        ),
                        'elements' => array(),
                    );
                }
                $columns = array(
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 33, '_inline_size' => null ),
                        'elements' => $left_widgets, 'isInner' => false,
                    ),
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 67, '_inline_size' => null, 'space_between_widgets' => 10 ),
                        'elements' => $right_widgets, 'isInner' => false,
                    ),
                );
                break;

            case 'guarantee':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FEFCE8',
                    'padding'               => array( 'unit' => 'px', 'top' => '40', 'right' => '24', 'bottom' => '40', 'left' => '24', 'isLinked' => false ),
                    'border_radius'         => array( 'unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'isLinked' => true ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 900 ),
                );
                $widgets[] = array(
                    'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon',
                    'settings' => array(
                        'selected_icon' => array( 'value' => isset( $s['icon'] ) ? (string) $s['icon'] : 'fas fa-shield-alt', 'library' => 'fa-solid' ),
                        'primary_color' => '#CA8A04',
                        'size' => array( 'unit' => 'px', 'size' => 48 ),
                        'align' => 'center',
                    ),
                    'elements' => array(),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h3', 'align' => 'center',
                            'title_color' => '#713F12',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 26 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                if ( ! empty( $s['text'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'text-editor',
                        'settings' => array(
                            'editor' => '<p style="text-align:center;font-size:17px;line-height:1.65;color:#854D0E;max-width:680px;margin:0 auto;">' . wp_kses_post( (string) $s['text'] ) . '</p>',
                        ),
                        'elements' => array(),
                    );
                }
                break;

            case 'feature_list':
                $section_settings = array(
                    'background_background' => 'classic',
                    'background_color'      => isset( $s['bg'] ) ? (string) $s['bg'] : '#FFFFFF',
                    'padding'               => array( 'unit' => 'px', 'top' => '64', 'right' => '24', 'bottom' => '64', 'left' => '24', 'isLinked' => false ),
                    'content_width'         => array( 'unit' => 'px', 'size' => 1100 ),
                );
                if ( ! empty( $s['title'] ) ) {
                    $widgets[] = array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'heading',
                        'settings' => array(
                            'title' => (string) $s['title'], 'header_size' => 'h2', 'align' => 'center',
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 32 ),
                            'typography_font_weight' => '800',
                        ),
                        'elements' => array(),
                    );
                }
                $items = isset( $s['items'] ) && is_array( $s['items'] ) ? array_values( $s['items'] ) : array();
                $accent = isset( $s['accent'] ) ? (string) $s['accent'] : '#10B981';
                $half = (int) ceil( count( $items ) / 2 );
                $left_items  = array_slice( $items, 0, $half );
                $right_items = array_slice( $items, $half );
                $build_list = static function ( array $list ) use ( $accent ) {
                    $entries = array();
                    foreach ( $list as $i ) {
                        $entries[] = array(
                            'text' => esc_html( (string) $i ),
                            'selected_icon' => array( 'value' => 'fas fa-check-circle', 'library' => 'fa-solid' ),
                            '_id' => self::eid(),
                        );
                    }
                    return array(
                        'id' => self::eid(), 'elType' => 'widget', 'widgetType' => 'icon-list',
                        'settings' => array(
                            'icon_list' => $entries,
                            'icon_color' => $accent,
                            'space_between' => array( 'unit' => 'px', 'size' => 12 ),
                            'typography_typography' => 'custom',
                            'typography_font_size' => array( 'unit' => 'px', 'size' => 16 ),
                        ),
                        'elements' => array(),
                    );
                };
                $columns = array(
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 50, '_inline_size' => null ),
                        'elements' => $left_items ? array( $build_list( $left_items ) ) : array(),
                        'isInner' => false,
                    ),
                    array(
                        'id' => self::eid(), 'elType' => 'column',
                        'settings' => array( '_column_size' => 50, '_inline_size' => null ),
                        'elements' => $right_items ? array( $build_list( $right_items ) ) : array(),
                        'isInner' => false,
                    ),
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
