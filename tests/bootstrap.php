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

// ─── In-memory stores (reset in test setUp) ───────────────────────────────────

$GLOBALS['_upkeepify_test_options']    = [];
$GLOBALS['_upkeepify_test_cache']      = [];
$GLOBALS['_upkeepify_test_transients'] = [];

// ─── Load plugin constants ────────────────────────────────────────────────────

require_once dirname( __DIR__ ) . '/includes/constants.php';

// ─── WordPress function stubs ─────────────────────────────────────────────────

function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

function get_post_meta( $post_id, $key, $single = false ) {
	return $single ? '' : [];
}

function update_post_meta( $post_id, $key, $value ) {
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

function _e( $text, $domain = 'default' ) {
	echo $text;
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	// no-op in tests
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	// no-op in tests
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
	return [];
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
