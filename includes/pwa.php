<?php
/**
 * Progressive Web App support.
 *
 * Registers rewrite endpoints for the web app manifest and service worker,
 * injects the manifest link tag into the front-end <head>, and registers the
 * service worker on pages that use Upkeepify shortcodes or carry a contractor
 * invite token.
 *
 * Service worker scope: '/' (site root). The SW file is served via a rewrite
 * rule with the Service-Worker-Allowed header so it can control all pages.
 *
 * Push notification support will be added in a later release once the full
 * response lifecycle (Steps 2–4 of the roadmap) is in place.
 *
 * @package Upkeepify
 * @since   1.1
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

// ─── Rewrite rules ───────────────────────────────────────────────────────────

/**
 * Register custom rewrite rules for the manifest and service worker endpoints.
 *
 * @hook init
 */
function upkeepify_pwa_add_rewrite_rules() {
    add_rewrite_rule( '^upkeepify-sw\.js$',       'index.php?upkeepify_pwa=sw',       'top' );
    add_rewrite_rule( '^upkeepify-manifest\.json$', 'index.php?upkeepify_pwa=manifest', 'top' );
}
add_action( 'init', 'upkeepify_pwa_add_rewrite_rules' );

/**
 * Expose the upkeepify_pwa query variable to WordPress.
 *
 * @param  string[] $vars Registered query variable names.
 * @return string[]
 * @hook   query_vars
 */
function upkeepify_pwa_add_query_vars( $vars ) {
    $vars[] = 'upkeepify_pwa';
    return $vars;
}
add_filter( 'query_vars', 'upkeepify_pwa_add_query_vars' );

// ─── Endpoint handler ────────────────────────────────────────────────────────

/**
 * Serve the manifest or service worker when the rewrite rule fires.
 *
 * @hook template_redirect
 */
function upkeepify_pwa_template_redirect() {
    $pwa = get_query_var( 'upkeepify_pwa' );
    if ( ! $pwa ) {
        return;
    }

    if ( $pwa === 'manifest' ) {
        upkeepify_pwa_serve_manifest();
    } elseif ( $pwa === 'sw' ) {
        upkeepify_pwa_serve_service_worker();
    }

    exit;
}
add_action( 'template_redirect', 'upkeepify_pwa_template_redirect' );

/**
 * Output the web app manifest as JSON.
 */
function upkeepify_pwa_serve_manifest() {
    $icon_url = UPKEEPIFY_PLUGIN_URL . 'favicon.png';

    $manifest = array(
        'name'             => get_bloginfo( 'name' ) . ' — Upkeepify',
        'short_name'       => 'Upkeepify',
        'description'      => __( 'Report and track maintenance issues.', 'upkeepify' ),
        'start_url'        => home_url( '/' ),
        'display'          => 'standalone',
        'background_color' => '#ffffff',
        'theme_color'      => '#0073aa',
        'icons'            => array(
            array(
                'src'     => $icon_url,
                'sizes'   => '192x192',
                'type'    => 'image/png',
                'purpose' => 'any maskable',
            ),
            array(
                'src'     => $icon_url,
                'sizes'   => '512x512',
                'type'    => 'image/png',
                'purpose' => 'any maskable',
            ),
        ),
    );

    // NOTE: Add 192×192 and 512×512 PNG icons to the plugin root for full PWA
    // compliance. The manifest currently references favicon.png at both sizes
    // as a placeholder.

    header( 'Content-Type: application/manifest+json; charset=utf-8' );
    header( 'Cache-Control: public, max-age=3600' );
    echo wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
}

/**
 * Serve the service worker JavaScript with the correct headers.
 *
 * The Service-Worker-Allowed header extends the SW scope to '/' so it can
 * intercept requests across the whole site, not just /wp-content/plugins/…
 */
function upkeepify_pwa_serve_service_worker() {
    $sw_file = UPKEEPIFY_PLUGIN_DIR . 'js/service-worker.js';

    if ( ! file_exists( $sw_file ) ) {
        status_header( 404 );
        return;
    }

    // Inject the plugin URL so the SW can cache assets without hardcoded paths.
    $content = file_get_contents( $sw_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
    $content = str_replace( '__UPKEEPIFY_PLUGIN_URL__', esc_url_raw( UPKEEPIFY_PLUGIN_URL ), $content );

    header( 'Content-Type: application/javascript; charset=utf-8' );
    header( 'Service-Worker-Allowed: /' );
    header( 'Cache-Control: no-cache, no-store, must-revalidate' );
    echo $content; // phpcs:ignore WordPress.Security.EscapeOutput
}

// ─── Head / footer injection ─────────────────────────────────────────────────

/**
 * Add the manifest link and theme-color meta tag to the front-end <head>.
 *
 * @hook wp_head
 */
function upkeepify_pwa_add_manifest_link() {
    echo '<link rel="manifest" href="' . esc_url( home_url( '/upkeepify-manifest.json' ) ) . '">' . "\n";
    echo '<meta name="theme-color" content="#0073aa">' . "\n";
}
add_action( 'wp_head', 'upkeepify_pwa_add_manifest_link' );

/**
 * Register the service worker on Upkeepify pages.
 *
 * Only injects the registration script on pages that contain an Upkeepify
 * shortcode, or on any page that carries a contractor invite token in the
 * query string. This keeps the SW footprint scoped to where it is useful.
 *
 * @hook wp_footer
 */
function upkeepify_pwa_register_service_worker() {
    global $post;

    $has_shortcode = false;

    if ( $post instanceof WP_Post ) {
        $shortcodes = array(
            UPKEEPIFY_SHORTCODE_TASK_FORM,
            UPKEEPIFY_SHORTCODE_PROVIDER_RESPONSE_FORM,
            UPKEEPIFY_SHORTCODE_LIST_TASKS,
            UPKEEPIFY_SHORTCODE_MAINTENANCE_TASKS,
            UPKEEPIFY_SHORTCODE_TASK_CALENDAR,
        );
        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $has_shortcode = true;
                break;
            }
        }
    }

    // Also register on contractor invite pages (token in query string).
    $has_token = isset( $_GET[ UPKEEPIFY_QUERY_VAR_TOKEN ] );

    if ( ! $has_shortcode && ! $has_token ) {
        return;
    }
    ?>
<script>
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/upkeepify-sw.js', { scope: '/' })
            .then(function (reg) {
                if (typeof console !== 'undefined' && console.log) {
                    console.log('Upkeepify SW registered, scope:', reg.scope);
                }
            })
            .catch(function (err) {
                if (typeof console !== 'undefined' && console.warn) {
                    console.warn('Upkeepify SW registration failed:', err);
                }
            });
    });
}
</script>
    <?php
}
add_action( 'wp_footer', 'upkeepify_pwa_register_service_worker' );
