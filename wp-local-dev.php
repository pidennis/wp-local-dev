<?php
final class WPLocalDev
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
        self::$originalHost = parse_url( get_option( 'home' ), PHP_URL_HOST );

        $hooks = array( 'post_link', 'site_url', 'home_url', 'plugins_url', 'content_url', 'option_home', 'option_siteurl' );
        foreach ( $hooks as $hook ) {
            add_filter( $hook, array( $this, 'replaceHost' ), 99 );
        }

        add_filter( 'wp_redirect', array( $this, 'rewriteInternalUrls' ) );

        add_filter( 'the_content', array( $this, 'filterContent' ) );
        add_filter( 'term_description', array( $this, 'filterContent' ) );

        add_filter( 'get_site', array( $this, 'onGetSite' ), 99 );
    }

    /**
     * Replaces the host of the specified url with the current HTTP_HOST.
     * @param $url The URL with the host to replace.
     * @return string The url with a replaced host name.
     */
    public function replaceHost( $url )
    {
        // Get the host of the url
        $host = parse_url( $url, PHP_URL_HOST );

        // Replace the host with the current HTTP_HOST
        $url = str_replace( $host, $_SERVER['HTTP_HOST'], $url );

        // Always force http on localhost
        return set_url_scheme( $url, 'http' );
    }

    /**
     * Replaces the host in the specified URL if it points to the host configured as home_url in this WordPress instance.
     * @param string $url The URL to rewrite.
     * @return string The URL.
     */
    public function rewriteInternalUrls( $url )
    {
        if ( parse_url( $url, PHP_URL_HOST ) !== self::$originalHost ) {
            return $url;
        }

        return $this->replaceHost( $url );
    }

    /**
     * Replaces all links to our original domain with links to our local host.
     * @param string $content The content to filter.
     * @return string The filtered content.
     */
    public function filterContent( $content )
    {
        return preg_replace( '#(href="https?://' . self::$originalHost . ')#i', 'href="' . get_option( 'home' ), $content );
    }

    /**
     * Replaces the domain in WP_Site.
     * @param WP_Site $site The site to filter.
     * @return WP_Site The site object with a rewritten domain.
     */
    public function onGetSite( WP_Site $site )
    {
        $site->domain = $this->replaceHost( $site->domain );
        return $site;
    }
}