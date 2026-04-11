<?php
/**
 * Bootstrap file for PHPUnit tests.
 *
 * Sets up a WordPress-like environment for unit testing the plugin.
 *
 * @package Upkeepify
 */

define( 'WPINC', 'wp-includes' );
define( 'WP_DEBUG', false );
define( 'ABSPATH', '/tmp/' );

// PHP upload error constants
if ( ! defined( 'UPLOAD_ERR_OK' ) )        define( 'UPLOAD_ERR_OK', 0 );
if ( ! defined( 'UPLOAD_ERR_INI_SIZE' ) )  define( 'UPLOAD_ERR_INI_SIZE', 1 );
if ( ! defined( 'UPLOAD_ERR_FORM_SIZE' ) ) define( 'UPLOAD_ERR_FORM_SIZE', 2 );
if ( ! defined( 'UPLOAD_ERR_PARTIAL' ) )   define( 'UPLOAD_ERR_PARTIAL', 3 );
if ( ! defined( 'UPLOAD_ERR_NO_FILE' ) )   define( 'UPLOAD_ERR_NO_FILE', 4 );
if ( ! defined( 'UPLOAD_ERR_NO_TMP_DIR' ) ) define( 'UPLOAD_ERR_NO_TMP_DIR', 6 );
if ( ! defined( 'UPLOAD_ERR_CANT_WRITE' ) ) define( 'UPLOAD_ERR_CANT_WRITE', 7 );
if ( ! defined( 'UPLOAD_ERR_EXTENSION' ) ) define( 'UPLOAD_ERR_EXTENSION', 8 );
if ( ! defined( 'FILEINFO_MIME_TYPE' ) )   define( 'FILEINFO_MIME_TYPE', 16 );
if ( ! defined( 'DAY_IN_SECONDS' ) )       define( 'DAY_IN_SECONDS', 86400 );

// ─── WordPress core class stubs ───────────────────────────────────────────────

class WP_Error {
	private $errors     = [];
	private $error_data = [];

	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( ! empty( $code ) ) {
			$this->errors[ $code ][] = $message;
			if ( $data !== '' ) {
				$this->error_data[ $code ] = $data;
			}
		}
	}

	public function get_error_code() {
		$codes = array_keys( $this->errors );
		return ! empty( $codes ) ? $codes[0] : '';
	}

	public function get_error_message( $code = '' ) {
		if ( empty( $code ) ) {
			$code = $this->get_error_code();
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}

	public function get_error_codes() {
		return array_keys( $this->errors );
	}

	public function add( $code, $message, $data = '' ) {
		$this->errors[ $code ][] = $message;
		if ( $data !== '' ) {
			$this->error_data[ $code ] = $data;
		}
	}

	public function has_errors() {
		return ! empty( $this->errors );
	}
}

if ( ! class_exists( 'WP_Post' ) ) {
	class WP_Post {
		public function __construct( $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	class WP_Term {
		public function __construct( $data = [] ) {
			foreach ( $data as $key => $value ) {
				$this->$key = $value;
			}
		}
	}
}

// ─── In-memory stores (reset in test setUp) ───────────────────────────────────

$GLOBALS['_upkeepify_test_options']    = [];
$GLOBALS['_upkeepify_test_cache']      = [];
$GLOBALS['_upkeepify_test_transients'] = [];
$GLOBALS['_upkeepify_test_posts']      = [];
$GLOBALS['_upkeepify_test_post_meta']  = [];
$GLOBALS['_upkeepify_test_post_thumbnails'] = [];
$GLOBALS['_upkeepify_test_taxonomy_terms']  = [];
$GLOBALS['_upkeepify_test_term_meta']       = [];
$GLOBALS['_upkeepify_test_object_terms']    = [];
$GLOBALS['_upkeepify_test_inserted_posts']  = [];
$GLOBALS['_upkeepify_test_deleted_attachments'] = [];
$GLOBALS['_upkeepify_test_deleted_posts']   = [];
$GLOBALS['_upkeepify_test_deleted_terms']   = [];

// ─── Load plugin constants ────────────────────────────────────────────────────

require_once dirname( __DIR__ ) . '/includes/constants.php';

// ─── WordPress function stubs ─────────────────────────────────────────────────

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function get_post_meta( $post_id, $key, $single = false ) {
	$meta = isset( $GLOBALS['_upkeepify_test_post_meta'][ $post_id ][ $key ] )
		? $GLOBALS['_upkeepify_test_post_meta'][ $post_id ][ $key ]
		: ( $single ? '' : [] );
	return $meta;
}

function update_post_meta( $post_id, $key, $value ) {
	if ( ! isset( $GLOBALS['_upkeepify_test_post_meta'] ) ) {
		$GLOBALS['_upkeepify_test_post_meta'] = [];
	}
	if ( ! isset( $GLOBALS['_upkeepify_test_post_meta'][ $post_id ] ) ) {
		$GLOBALS['_upkeepify_test_post_meta'][ $post_id ] = [];
	}
	$GLOBALS['_upkeepify_test_post_meta'][ $post_id ][ $key ] = $value;
	return true;
}

function get_post( $post_id = null ) {
	return null;
}

function get_option( $option, $default = false ) {
	if ( array_key_exists( $option, $GLOBALS['_upkeepify_test_options'] ) ) {
		return $GLOBALS['_upkeepify_test_options'][ $option ];
	}
	return $default;
}

function update_option( $option, $value, $autoload = null ) {
	$GLOBALS['_upkeepify_test_options'][ $option ] = $value;
	return true;
}

function delete_option( $option ) {
	if ( isset( $GLOBALS['_upkeepify_test_options'][ $option ] ) ) {
		unset( $GLOBALS['_upkeepify_test_options'][ $option ] );
		return true;
	}
	return false;
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	$cache_key = $group . '||' . $key;
	if ( array_key_exists( $cache_key, $GLOBALS['_upkeepify_test_cache'] ) ) {
		if ( $found !== null ) $found = true;
		return $GLOBALS['_upkeepify_test_cache'][ $cache_key ];
	}
	if ( $found !== null ) $found = false;
	return false;
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	$GLOBALS['_upkeepify_test_cache'][ $group . '||' . $key ] = $data;
	return true;
}

function wp_cache_delete( $key, $group = '' ) {
	$cache_key = $group . '||' . $key;
	if ( isset( $GLOBALS['_upkeepify_test_cache'][ $cache_key ] ) ) {
		unset( $GLOBALS['_upkeepify_test_cache'][ $cache_key ] );
		return true;
	}
	return false;
}

function wp_cache_flush_group( $group ) {
	foreach ( array_keys( $GLOBALS['_upkeepify_test_cache'] ) as $key ) {
		if ( strpos( $key, $group . '||' ) === 0 ) {
			unset( $GLOBALS['_upkeepify_test_cache'][ $key ] );
		}
	}
	return true;
}

function get_transient( $transient ) {
	return isset( $GLOBALS['_upkeepify_test_transients'][ $transient ] )
		? $GLOBALS['_upkeepify_test_transients'][ $transient ]
		: false;
}

function set_transient( $transient, $value, $expiration = 0 ) {
	$GLOBALS['_upkeepify_test_transients'][ $transient ] = $value;
	return true;
}

function delete_transient( $transient ) {
	if ( isset( $GLOBALS['_upkeepify_test_transients'][ $transient ] ) ) {
		unset( $GLOBALS['_upkeepify_test_transients'][ $transient ] );
		return true;
	}
	return false;
}

function wp_mail( $to, $subject, $message, $headers = '', $attachments = [] ) {
	return true;
}

function wp_generate_password( $length = 12, $special_chars = true ) {
	return str_repeat( 'a', $length );
}

function wp_unslash( $value ) {
	return is_array( $value )
		? array_map( 'wp_unslash', $value )
		: stripslashes( (string) $value );
}

function sanitize_text_field( $str ) {
	return preg_replace( '/[\r\n\t]/', '', strip_tags( (string) $str ) );
}

function sanitize_textarea_field( $str ) {
	return (string) $str;
}

function sanitize_email( $email ) {
	return strtolower( trim( (string) $email ) );
}

function is_email( $email ) {
	return (bool) filter_var( $email, FILTER_VALIDATE_EMAIL );
}

function wp_kses( $string, $allowed_html ) {
	return strip_tags( (string) $string );
}

function wp_kses_post( $string ) {
	return strip_tags( (string) $string, '<p><a><strong><em><br><ul><ol><li>' );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

function esc_url( $url ) {
	return htmlspecialchars( (string) $url, ENT_QUOTES, 'UTF-8' );
}

function absint( $maybeint ) {
	return abs( intval( $maybeint ) );
}

function wp_json_encode( $data, $options = 0, $depth = 512 ) {
	return json_encode( $data, $options, $depth );
}

function get_bloginfo( $show = '', $filter = 'raw' ) {
	$info = [
		'name' => 'Test Site',
		'url'  => 'http://localhost',
	];
	return isset( $info[ $show ] ) ? $info[ $show ] : 'Test Site';
}

function current_time( $type ) {
	return $type === 'timestamp' ? time() : date( 'Y-m-d H:i:s' );
}

function get_user_by( $field, $value ) {
	return (object) [ 'user_login' => 'test_user' ];
}

function get_current_user_id() {
	return 0;
}

function current_user_can( $capability, ...$args ) {
	return false;
}

function __( $text, $domain = 'default' ) {
	return $text;
}

function esc_html__( $text, $domain = 'default' ) {
	return esc_html( __( $text, $domain ) );
}

function esc_attr__( $text, $domain = 'default' ) {
	return esc_attr( __( $text, $domain ) );
}

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	// no-op in tests
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	// no-op in tests
}

function add_shortcode( $tag, $callback ) {
	// no-op in tests
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	return array_merge( $pairs, is_array( $atts ) ? $atts : [] );
}

function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $display = true ) {
	$field = '<input type="hidden" id="' . esc_attr( $name ) . '" name="' . esc_attr( $name ) . '" value="test-nonce" />';
	if ( $display ) {
		echo $field;
	}
	return $field;
}

function get_object_taxonomies( $object_type, $output = 'names' ) {
	return [];
}

function get_taxonomy( $taxonomy ) {
	$labels = [
		UPKEEPIFY_TAXONOMY_TASK_CATEGORY    => 'Task Category',
		UPKEEPIFY_TAXONOMY_TASK_TYPE        => 'Task Type',
		UPKEEPIFY_TAXONOMY_TASK_STATUS      => 'Task Status',
		UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER => 'Service Provider',
		UPKEEPIFY_TAXONOMY_UNIT             => 'Unit',
	];
	if ( ! isset( $labels[ $taxonomy ] ) ) {
		return false;
	}
	$obj        = new stdClass();
	$obj->name  = $taxonomy;
	$obj->label = $labels[ $taxonomy ];
	return $obj;
}

function get_posts( $args = [] ) {
	$post_type = isset( $args['post_type'] ) ? $args['post_type'] : '';
	$posts = isset( $GLOBALS['_upkeepify_test_posts'][ $post_type ] )
		? $GLOBALS['_upkeepify_test_posts'][ $post_type ]
		: [];

	if ( ! empty( $args['meta_query'] ) && is_array( $args['meta_query'] ) ) {
		foreach ( $args['meta_query'] as $meta_clause ) {
			if ( empty( $meta_clause['key'] ) ) {
				continue;
			}

			$expected_value = isset( $meta_clause['value'] ) ? $meta_clause['value'] : null;
			$posts = array_values( array_filter( $posts, function( $post ) use ( $meta_clause, $expected_value ) {
				$post_id = is_object( $post ) && isset( $post->ID ) ? $post->ID : intval( $post );
				$actual  = get_post_meta( $post_id, $meta_clause['key'], true );

				return $expected_value === null || (string) $actual === (string) $expected_value;
			} ) );
		}
	}

	if ( isset( $args['posts_per_page'] ) && intval( $args['posts_per_page'] ) > 0 ) {
		$posts = array_slice( $posts, 0, intval( $args['posts_per_page'] ) );
	}

	if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
		return array_map( function( $post ) {
			return is_object( $post ) && isset( $post->ID ) ? $post->ID : intval( $post );
		}, $posts );
	}

	return $posts;
}

function wp_insert_post( $postarr, $wp_error = false ) {
	$post_id = isset( $postarr['ID'] ) ? intval( $postarr['ID'] ) : count( $GLOBALS['_upkeepify_test_inserted_posts'] ) + 1000;
	$post    = new WP_Post( array_merge( $postarr, [ 'ID' => $post_id ] ) );

	$GLOBALS['_upkeepify_test_inserted_posts'][ $post_id ] = $postarr;

	if ( ! empty( $postarr['post_type'] ) ) {
		if ( ! isset( $GLOBALS['_upkeepify_test_posts'][ $postarr['post_type'] ] ) ) {
			$GLOBALS['_upkeepify_test_posts'][ $postarr['post_type'] ] = [];
		}
		$GLOBALS['_upkeepify_test_posts'][ $postarr['post_type'] ][] = $post;
	}

	if ( ! empty( $postarr['meta_input'] ) && is_array( $postarr['meta_input'] ) ) {
		foreach ( $postarr['meta_input'] as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	return $post_id;
}

function get_post_thumbnail_id( $post_id ) {
	return isset( $GLOBALS['_upkeepify_test_post_thumbnails'][ $post_id ] )
		? $GLOBALS['_upkeepify_test_post_thumbnails'][ $post_id ]
		: 0;
}

function wp_delete_attachment( $attachment_id, $force_delete = false ) {
	$GLOBALS['_upkeepify_test_deleted_attachments'][] = [
		'attachment_id' => $attachment_id,
		'force_delete'  => (bool) $force_delete,
	];
	return true;
}

function wp_delete_post( $post_id, $force_delete = false ) {
	$GLOBALS['_upkeepify_test_deleted_posts'][] = [
		'post_id'      => $post_id,
		'force_delete' => (bool) $force_delete,
	];
	return true;
}

function wp_delete_term( $term_id, $taxonomy ) {
	$GLOBALS['_upkeepify_test_deleted_terms'][] = [
		'term_id'  => $term_id,
		'taxonomy' => $taxonomy,
	];
	return true;
}

function wp_check_filetype( $filename, $mimes = null ) {
	$ext   = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	$types = [
		'jpg'  => 'image/jpeg',
		'jpeg' => 'image/jpeg',
		'png'  => 'image/png',
		'gif'  => 'image/gif',
		'pdf'  => 'application/pdf',
		'txt'  => 'text/plain',
		'php'  => 'application/x-httpd-php',
		'exe'  => 'application/x-msdownload',
	];
	$type  = isset( $types[ $ext ] ) ? $types[ $ext ] : false;
	return [ 'ext' => $ext ?: false, 'type' => $type ];
}

function get_terms( $taxonomy, $args = [] ) {
	if ( is_array( $taxonomy ) ) {
		$args     = $taxonomy;
		$taxonomy = isset( $args['taxonomy'] ) ? $args['taxonomy'] : '';
	}

	if ( isset( $GLOBALS['_upkeepify_test_taxonomy_terms'][ $taxonomy ] ) ) {
		return $GLOBALS['_upkeepify_test_taxonomy_terms'][ $taxonomy ];
	}

	return [];
}

function get_term_meta( $term_id, $key, $single = false ) {
	if ( isset( $GLOBALS['_upkeepify_test_term_meta'][ $term_id ][ $key ] ) ) {
		return $GLOBALS['_upkeepify_test_term_meta'][ $term_id ][ $key ];
	}

	return $single ? '' : [];
}

function wp_get_object_terms( $object_id, $taxonomies, $args = [] ) {
	$taxonomy = is_array( $taxonomies ) ? reset( $taxonomies ) : $taxonomies;
	$terms    = isset( $GLOBALS['_upkeepify_test_object_terms'][ $object_id ][ $taxonomy ] )
		? $GLOBALS['_upkeepify_test_object_terms'][ $object_id ][ $taxonomy ]
		: [];

	if ( isset( $args['fields'] ) && 'ids' === $args['fields'] ) {
		return array_map( function( $term ) {
			return is_object( $term ) && isset( $term->term_id ) ? $term->term_id : intval( $term );
		}, $terms );
	}

	return $terms;
}

function add_query_arg( ...$args ) {
    // Supports both: add_query_arg( $key, $value, $url ) and add_query_arg( array $pairs, $url )
    if ( is_array( $args[0] ) ) {
        $pairs = $args[0];
        $url   = isset( $args[1] ) ? (string) $args[1] : '';
        foreach ( $pairs as $k => $v ) {
            $url = add_query_arg( $k, $v, $url );
        }
        return $url;
    }
    $key   = $args[0];
    $value = isset( $args[1] ) ? $args[1] : '';
    $url   = isset( $args[2] ) ? (string) $args[2] : '';
    $query = rawurlencode( $key ) . '=' . rawurlencode( (string) $value );
    if ( strpos( $url, '?' ) !== false ) {
        return $url . '&' . $query;
    }
    return $url . '?' . $query;
}

function trailingslashit( $string ) {
    return rtrim( (string) $string, '/\\' ) . '/';
}

function untrailingslashit( $string ) {
    return rtrim( (string) $string, '/\\' );
}

function admin_url( $path = '' ) {
    return 'http://localhost/wp-admin/' . ltrim( (string) $path, '/' );
}

function home_url( $path = '' ) {
    return 'http://localhost' . ( $path ? '/' . ltrim( (string) $path, '/' ) : '' );
}

function wp_get_referer() {
    return false;
}

function wp_safe_redirect( $location, $status = 302, $x_redirect_by = 'WordPress' ) {
    // no-op in tests
}

function wp_verify_nonce( $nonce, $action = -1 ) {
    return 1;
}

function wp_die( $message = '', $title = '', $args = [] ) {
    throw new \RuntimeException( is_string( $message ) ? $message : 'wp_die called' );
}

function get_term( $term, $taxonomy = '', $output = OBJECT, $filter = 'raw' ) {
    return null;
}

function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = '' ) {
    return '<img src="test-image-' . intval( $attachment_id ) . '.jpg" />';
}

function wp_trim_words( $text, $num_words = 55, $more = null ) {
    $words = explode( ' ', strip_tags( $text ) );
    return implode( ' ', array_slice( $words, 0, $num_words ) );
}

function wp_strip_all_tags( $string, $remove_breaks = false ) {
    return strip_tags( (string) $string );
}

if ( ! defined( 'OBJECT' ) ) {
    define( 'OBJECT', 'OBJECT' );
}

// ─── Session bootstrap ───────────────────────────────────────────────────────
// Start the session here, before PHPUnit prints any output, so that the
// session_start() call inside upkeepify_task_form_shortcode() finds the
// session already active and skips it (avoiding "headers already sent" warnings).
if ( session_status() === PHP_SESSION_NONE ) {
    ini_set( 'session.save_path', sys_get_temp_dir() );
    session_start();
}

// ─── Load plugin files ────────────────────────────────────────────────────────

$plugin_dir = dirname( __DIR__ ) . '/includes/';
require_once $plugin_dir . 'meta-registry.php';
require_once $plugin_dir . 'data-validation.php';
require_once $plugin_dir . 'utility-functions.php';
require_once $plugin_dir . 'upload-handlers.php';
require_once $plugin_dir . 'caching.php';
require_once $plugin_dir . 'notification-system.php';
require_once $plugin_dir . 'migrations.php';
