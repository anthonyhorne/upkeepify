<?php
/**
 * Tests for caching.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class CachingTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
	}

	// ─── upkeepify_get_setting_cached ─────────────────────────────────────────

	public function test_get_setting_cached_returns_default_when_option_missing() {
		$result = upkeepify_get_setting_cached( 'nonexistent_option_key', 'my_default' );
		$this->assertSame( 'my_default', $result );
	}

	public function test_get_setting_cached_returns_stored_option() {
		update_option( 'upkeepify_test_opt', 'hello' );
		// Clear cache so it falls through to option store
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		$result = upkeepify_get_setting_cached( 'upkeepify_test_opt', false );
		$this->assertSame( 'hello', $result );
	}

	public function test_get_setting_cached_populates_object_cache_on_miss() {
		update_option( 'upkeepify_test_opt2', 'world' );
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		upkeepify_get_setting_cached( 'upkeepify_test_opt2' );

		// Object cache should now be populated
		$cache_key    = 'upkeepify_setting_' . md5( 'upkeepify_test_opt2' );
		$cached_value = wp_cache_get( $cache_key, UPKEEPIFY_CACHE_GROUP_SETTINGS );
		$this->assertSame( 'world', $cached_value );
	}

	public function test_get_setting_cached_returns_cached_value_without_hitting_option() {
		// Prime the object cache directly
		$cache_key = 'upkeepify_setting_' . md5( 'upkeepify_test_opt3' );
		wp_cache_set( $cache_key, 'cached_value', UPKEEPIFY_CACHE_GROUP_SETTINGS );

		// Option store has a different (stale) value
		update_option( 'upkeepify_test_opt3', 'stale_value' );

		$result = upkeepify_get_setting_cached( 'upkeepify_test_opt3' );
		$this->assertSame( 'cached_value', $result );
	}

	public function test_get_setting_cached_uses_transient_when_object_cache_empty() {
		$cache_key     = 'upkeepify_setting_' . md5( 'upkeepify_test_opt4' );
		$transient_key = UPKEEPIFY_CACHE_GROUP_SETTINGS . '_' . $cache_key;
		set_transient( $transient_key, 'transient_value' );

		$result = upkeepify_get_setting_cached( 'upkeepify_test_opt4' );
		$this->assertSame( 'transient_value', $result );
	}

	// ─── upkeepify_update_setting_cached ─────────────────────────────────────

	public function test_update_setting_cached_stores_new_value() {
		upkeepify_update_setting_cached( 'upkeepify_update_test', 'new_value' );
		$this->assertSame( 'new_value', get_option( 'upkeepify_update_test' ) );
	}

	public function test_update_setting_cached_clears_object_cache() {
		$cache_key = 'upkeepify_setting_' . md5( 'upkeepify_clear_test' );
		wp_cache_set( $cache_key, 'old_value', UPKEEPIFY_CACHE_GROUP_SETTINGS );

		upkeepify_update_setting_cached( 'upkeepify_clear_test', 'new_value' );

		$cached = wp_cache_get( $cache_key, UPKEEPIFY_CACHE_GROUP_SETTINGS );
		$this->assertFalse( $cached );
	}

	public function test_update_setting_cached_clears_transient() {
		$cache_key     = 'upkeepify_setting_' . md5( 'upkeepify_trans_clear_test' );
		$transient_key = UPKEEPIFY_CACHE_GROUP_SETTINGS . '_' . $cache_key;
		set_transient( $transient_key, 'old_transient' );

		upkeepify_update_setting_cached( 'upkeepify_trans_clear_test', 'new_value' );

		$this->assertFalse( get_transient( $transient_key ) );
	}

	// ─── upkeepify_get_shortcode_output_cached ────────────────────────────────

	public function test_shortcode_cache_calls_callback_on_miss() {
		$called = false;
		$result = upkeepify_get_shortcode_output_cached(
			'test_shortcode',
			[],
			function () use ( &$called ) {
				$called = true;
				return '<p>output</p>';
			}
		);

		$this->assertTrue( $called );
		$this->assertSame( '<p>output</p>', $result );
	}

	public function test_shortcode_cache_does_not_call_callback_on_hit() {
		$cache_key = 'upkeepify_shortcode_' . md5( 'cached_shortcode' . serialize( [] ) );
		wp_cache_set( $cache_key, '<p>cached</p>', UPKEEPIFY_CACHE_GROUP_SHORTCODES );

		$called = false;
		$result = upkeepify_get_shortcode_output_cached(
			'cached_shortcode',
			[],
			function () use ( &$called ) {
				$called = true;
				return '<p>fresh</p>';
			}
		);

		$this->assertFalse( $called );
		$this->assertSame( '<p>cached</p>', $result );
	}

	public function test_shortcode_cache_stores_output_after_miss() {
		upkeepify_get_shortcode_output_cached(
			'stored_shortcode',
			[ 'limit' => 5 ],
			function () {
				return '<ul>tasks</ul>';
			}
		);

		$cache_key    = 'upkeepify_shortcode_' . md5( 'stored_shortcode' . serialize( [ 'limit' => 5 ] ) );
		$cached_value = wp_cache_get( $cache_key, UPKEEPIFY_CACHE_GROUP_SHORTCODES );
		$this->assertSame( '<ul>tasks</ul>', $cached_value );
	}

	public function test_shortcode_cache_different_atts_have_different_keys() {
		$outputs = [];
		foreach ( [ [ 'limit' => 5 ], [ 'limit' => 10 ] ] as $atts ) {
			$outputs[] = upkeepify_get_shortcode_output_cached(
				'multi_att_shortcode',
				$atts,
				function () use ( $atts ) {
					return '<p>limit=' . $atts['limit'] . '</p>';
				}
			);
		}
		$this->assertSame( '<p>limit=5</p>', $outputs[0] );
		$this->assertSame( '<p>limit=10</p>', $outputs[1] );
	}

	// ─── upkeepify_log_query_performance ─────────────────────────────────────

	public function test_log_query_performance_does_not_throw() {
		$start = microtime( true );
		// Should silently do nothing (WP_DEBUG is false)
		upkeepify_log_query_performance( 'test_query', $start );
		$this->assertTrue( true );
	}
}
