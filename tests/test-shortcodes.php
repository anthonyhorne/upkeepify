<?php
/**
 * Tests for shortcode rendering.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/shortcodes.php';

class ShortcodesTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
		$_SERVER['REQUEST_URI']                = '/report-an-issue';
		if ( session_status() === PHP_SESSION_NONE ) {
			session_save_path( sys_get_temp_dir() );
		}
	}

	public function test_task_form_renders_optional_gps_fields_without_required_attribute() {
		$output = upkeepify_task_form_shortcode();

		$this->assertStringContainsString( 'Latitude (optional):', $output );
		$this->assertStringContainsString( 'Longitude (optional):', $output );
		$this->assertStringContainsString( 'id="gps_latitude" name="gps_latitude" class="upkeepify-input"', $output );
		$this->assertStringContainsString( 'id="gps_longitude" name="gps_longitude" class="upkeepify-input"', $output );
		$this->assertStringNotContainsString( 'id="gps_latitude" name="gps_latitude" required', $output );
		$this->assertStringNotContainsString( 'id="gps_longitude" name="gps_longitude" required', $output );
	}
}
