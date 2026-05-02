<?php
/**
 * Schema_Builder — generates JSON-LD strings for common schema types.
 *
 * @package NP_MCP_Builder
 */

namespace NP_MCP_Builder;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class Schema_Builder {

    /**
     * Build a FAQPage schema from a list of {question, answer} items.
     *
     * @param array $items
     * @return string JSON.
     */
    public static function faq( array $items ): string {
        $main = array();
        foreach ( $items as $it ) {
            if ( ! is_array( $it ) || empty( $it['question'] ) || empty( $it['answer'] ) ) { continue; }
            $main[] = array(
                '@type' => 'Question',
                'name'  => (string) $it['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( (string) $it['answer'] ),
                ),
            );
        }
        $data = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $main,
        );
        return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Build a LocalBusiness / ProfessionalService schema.
     *
     * @param array $b business descriptor
     * @return string
     */
    public static function local_business( array $b ): string {
        $type = isset( $b['type'] ) ? (string) $b['type'] : 'ProfessionalService';
        $data = array(
            '@context' => 'https://schema.org',
            '@type'    => $type,
        );
        $copy = array( 'name', 'description', 'url', 'image', 'logo', 'telephone', 'email', 'priceRange', 'areaServed' );
        foreach ( $copy as $k ) {
            if ( isset( $b[ $k ] ) && $b[ $k ] !== '' ) { $data[ $k ] = $b[ $k ]; }
        }
        if ( ! empty( $b['address'] ) && is_array( $b['address'] ) ) {
            $data['address'] = array_merge( array( '@type' => 'PostalAddress' ), $b['address'] );
        }
        if ( ! empty( $b['geo'] ) && is_array( $b['geo'] ) ) {
            $data['geo'] = array_merge( array( '@type' => 'GeoCoordinates' ), $b['geo'] );
        }
        if ( ! empty( $b['hours'] ) && is_array( $b['hours'] ) ) {
            $data['openingHoursSpecification'] = array_map( static function ( $h ) {
                return array_merge( array( '@type' => 'OpeningHoursSpecification' ), (array) $h );
            }, $b['hours'] );
        }
        if ( ! empty( $b['same_as'] ) && is_array( $b['same_as'] ) ) {
            $data['sameAs'] = array_values( array_map( 'strval', $b['same_as'] ) );
        }
        if ( ! empty( $b['rating'] ) && is_array( $b['rating'] ) ) {
            $data['aggregateRating'] = array_merge(
                array( '@type' => 'AggregateRating' ),
                $b['rating']
            );
        }
        if ( ! empty( $b['reviews'] ) && is_array( $b['reviews'] ) ) {
            $data['review'] = array_map( static function ( $r ) {
                $r = (array) $r;
                $review = array(
                    '@type'         => 'Review',
                    'reviewRating'  => array(
                        '@type'       => 'Rating',
                        'ratingValue' => isset( $r['rating'] ) ? (int) $r['rating'] : 5,
                        'bestRating'  => 5,
                    ),
                    'author'        => array( '@type' => 'Person', 'name' => (string) ( $r['author'] ?? '' ) ),
                    'reviewBody'    => wp_strip_all_tags( (string) ( $r['body'] ?? $r['quote'] ?? '' ) ),
                );
                return $review;
            }, $b['reviews'] );
        }
        return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Build a Service schema.
     */
    public static function service( array $svc ): string {
        $data = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'Service',
            'serviceType' => (string) ( $svc['name'] ?? '' ),
        );
        if ( ! empty( $svc['description'] ) ) { $data['description'] = (string) $svc['description']; }
        if ( ! empty( $svc['provider'] ) )    { $data['provider']    = $svc['provider']; }
        if ( ! empty( $svc['area'] ) )        { $data['areaServed']  = $svc['area']; }
        if ( ! empty( $svc['url'] ) )         { $data['url']         = (string) $svc['url']; }
        return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Build a BreadcrumbList schema from [{name, url}, ...].
     */
    public static function breadcrumbs( array $crumbs ): string {
        $items = array();
        foreach ( array_values( $crumbs ) as $i => $c ) {
            if ( ! is_array( $c ) ) { continue; }
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $i + 1,
                'name'     => (string) ( $c['name'] ?? '' ),
                'item'     => (string) ( $c['url'] ?? '' ),
            );
        }
        return wp_json_encode( array(
            '@context' => 'https://schema.org',
            '@type'    => 'BreadcrumbList',
            'itemListElement' => $items,
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Build a WebPage / Article schema.
     */
    public static function web_page( array $page ): string {
        $type = isset( $page['type'] ) ? (string) $page['type'] : 'WebPage';
        $data = array(
            '@context' => 'https://schema.org',
            '@type'    => $type,
        );
        $copy = array( 'name', 'headline', 'description', 'url', 'image', 'datePublished', 'dateModified', 'inLanguage' );
        foreach ( $copy as $k ) {
            if ( isset( $page[ $k ] ) && $page[ $k ] !== '' ) { $data[ $k ] = $page[ $k ]; }
        }
        if ( ! empty( $page['author'] ) ) {
            $data['author'] = is_array( $page['author'] )
                ? array_merge( array( '@type' => 'Person' ), $page['author'] )
                : array( '@type' => 'Person', 'name' => (string) $page['author'] );
        }
        if ( ! empty( $page['publisher'] ) ) {
            $data['publisher'] = is_array( $page['publisher'] )
                ? array_merge( array( '@type' => 'Organization' ), $page['publisher'] )
                : array( '@type' => 'Organization', 'name' => (string) $page['publisher'] );
        }
        return wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Render a JSON-LD <script> tag for use inside an Elementor html widget.
     */
    public static function script_tag( string $json ): string {
        return '<script type="application/ld+json">' . $json . '</script>';
    }
}
