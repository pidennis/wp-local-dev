<?php
/**
 * Define WP_LOCAL_DEV as true to rewrite all existing links to the current host.
 * Drop this file to /mu-plugins/.
 */

if ( defined( 'WP_LOCAL_DEV' ) && WP_LOCAL_DEV ) {

    final class PILocalDevFilter
    {
        /**
         * The original Host this WordPress instance is configured for.
         */
        private static $originalHost;

        public function __construct() { }

        /**
         * Initializes all hooks to rewrite URLs.
         */
        public function init()
        {
            if ( ! empty( self::$originalHost ) ) {
                return;
            }

            // Remember the original host
            self::$originalHost = parse_url( home_url( '/' ), PHP_URL_HOST );

            $hooks = array( 'post_link', 'site_url', 'home_url', 'admin_url', 'includes_url', 'plugins_url', 'content_url', 'stylesheet_directory_uri', 'wp_redirect' );
            foreach ( $hooks as $hook ) {
                add_filter( $hook, array( $this, 'rewriteUrl' ), 99, 1 );
            }

            add_filter( 'pre_option_home', array( $this, 'getHome' ) );
            add_filter( 'pre_option_siteurl', array( $this, 'getHome' ) );

            add_filter( 'the_content', array( $this, 'filterContent' ) );
            add_filter( 'term_description', array( $this, 'filterContent' ) );

            add_filter( 'get_site', array( $this, 'onGetSite' ), 99 );
        }

        /**
         * Replaces the host of the specified URL with our current host.
         * @param string $url The URL to rewrite.
         * @return string The rewritten URL.
         */
        public function rewriteUrl( $url )
        {
            $host = parse_url( $url, PHP_URL_HOST );
            if ( empty( $host ) ) {
                return $url;
            }

            // Replace host with local host
            $url = str_replace( $host, $_SERVER['HTTP_HOST'], $url );

            // Force http on localhost
            return set_url_scheme( $url, 'http' );
        }

        /**
         * Returns the URL to our local homepage.
         * @return string The current URL to our local homepage.
         */
        public function getHome()
        {
            return set_url_scheme( '//' . $_SERVER['HTTP_HOST'], 'http' );
        }

        /**
         * Replaces all links to our original domain with links to our local host.
         * @param string $content The content to filter.
         * @return string The filtered content.
         */
        public function filterContent( $content )
        {
            return preg_replace( '#(href="https?://' . self::$originalHost . ')#i', 'href="' . $this->getHome(), $content );
        }

        /**
         * Replaces the domain in WP_Site.
         * @param WP_Site $site The site to filter.
         * @return WP_Site The filtered site object.
         */
        public function onGetSite( WP_Site $site )
        {
            $site->domain = $this->rewriteUrl( $site->domain );
            return $site;
        }
    }

    ( new PILocalDevFilter() )->init();
}