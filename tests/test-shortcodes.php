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
		$GLOBALS['_upkeepify_test_options']    = [
			// Enable public task submission so task-form tests can render the form.
			UPKEEPIFY_OPTION_SETTINGS => [
				UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING => '1',
				UPKEEPIFY_SETTING_NUMBER_OF_UNITS     => 10,
			],
		];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
		$GLOBALS['_upkeepify_test_posts']      = [];
		$GLOBALS['_upkeepify_test_post_meta']  = [];
		$_SERVER['REQUEST_URI']                = '/report-an-issue';
		$_GET                                  = [];
		$_POST                                 = [];
	}

	// ─── Task form ───────────────────────────────────────────────────────────────

	public function test_task_form_renders_optional_gps_fields_without_required_attribute() {
		$output = upkeepify_task_form_shortcode();

		$this->assertStringContainsString( 'Latitude (optional):', $output );
		$this->assertStringContainsString( 'Longitude (optional):', $output );
		$this->assertStringContainsString( 'id="gps_latitude" name="gps_latitude" class="upkeepify-input"', $output );
		$this->assertStringContainsString( 'id="gps_longitude" name="gps_longitude" class="upkeepify-input"', $output );
		$this->assertStringNotContainsString( 'id="gps_latitude" name="gps_latitude" required', $output );
		$this->assertStringNotContainsString( 'id="gps_longitude" name="gps_longitude" required', $output );
	}

	public function test_task_form_renders_optional_email_field() {
		$output = upkeepify_task_form_shortcode();

		$this->assertStringContainsString( 'id="submitter_email"', $output );
		$this->assertStringContainsString( 'name="submitter_email"', $output );
		$this->assertStringContainsString( 'type="email"', $output );
	}

	public function test_task_form_email_field_is_not_required() {
		$output = upkeepify_task_form_shortcode();

		// The email field must not carry a required attribute.
		$this->assertStringNotContainsString( 'name="submitter_email" required', $output );
		$this->assertStringNotContainsString( 'id="submitter_email" required', $output );
	}

	public function test_task_form_returns_disabled_message_when_public_logging_off() {
		// Override the setting: public task logging disabled.
		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ] = [
			UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING => '',
		];
		$GLOBALS['_upkeepify_test_cache'] = []; // flush cached setting.

		$output = upkeepify_task_form_shortcode();

		$this->assertStringContainsString( 'not available', $output );
		$this->assertStringNotContainsString( '<form', $output );
	}

	// ─── Resident confirmation form ──────────────────────────────────────────────

	public function test_resident_confirmation_form_shows_error_when_no_token_provided() {
		$_GET = [];
		$output = upkeepify_resident_confirmation_form_shortcode();

		$this->assertStringContainsString( 'No confirmation token', $output );
	}

	public function test_resident_confirmation_form_shows_error_for_invalid_token() {
		$_GET[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] = 'nonexistent_token_xyz';
		// get_posts() returns [] by default — no task matches.
		$output = upkeepify_resident_confirmation_form_shortcode();

		$this->assertStringContainsString( 'Invalid or expired', $output );
	}

	public function test_resident_confirmation_form_renders_choice_for_valid_unconfirmed_token() {
		$token   = 'validtoken12345678ab';
		$task_id = 99;

		$_GET[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] = $token;

		// Stub get_posts() to return a matching task.
		$task             = new stdClass();
		$task->ID         = $task_id;
		$task->post_title = 'Leaking tap';
		$task->post_type  = UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS;
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ] = [ $task ];
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = $token;

		// get_post_meta returns '' (falsy) for confirmed_at — task not yet confirmed.
		$output = upkeepify_resident_confirmation_form_shortcode();

		$this->assertStringContainsString( 'Leaking tap', $output );
		$this->assertStringContainsString( 'resident_satisfied', $output );
		$this->assertStringContainsString( 'Submit feedback', $output );
	}

	public function test_resident_confirmation_form_shows_already_submitted_when_confirmed() {
		$token   = 'validtoken12345678ab';
		$task_id = 99;

		$_GET[ UPKEEPIFY_QUERY_VAR_RESIDENT_TOKEN ] = $token;

		$task             = new stdClass();
		$task->ID         = $task_id;
		$task->post_title = 'Leaking tap';
		$task->post_type  = UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS;
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ] = [ $task ];

		// Simulate already-confirmed: store a timestamp in the meta stub.
		// We override get_post_meta for this test using a simple in-memory store.
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = $token;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT ] = time();
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED ]    = '1';

		$output = upkeepify_resident_confirmation_form_shortcode();

		$this->assertStringContainsString( 'already submitted', $output );
	}

	// ─── upkeepify_get_resident_confirmation_url ─────────────────────────────────

	public function test_get_resident_confirmation_url_returns_null_without_token() {
		// Default get_post_meta stub returns '' — no token stored.
		$url = upkeepify_get_resident_confirmation_url( 42 );
		$this->assertNull( $url );
	}

	public function test_get_resident_confirmation_url_returns_null_without_page_setting() {
		$GLOBALS['_upkeepify_test_post_meta'][42][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = 'sometoken';
		// Settings have no confirmation page URL — should return null.
		$url = upkeepify_get_resident_confirmation_url( 42 );
		$this->assertNull( $url );
	}

	// ─── upkeepify_send_resident_confirmation_email ──────────────────────────────

	public function test_send_resident_confirmation_email_does_nothing_without_email() {
		// No email in meta → function returns early; wp_mail never called.
		// We just ensure no exception is thrown.
		upkeepify_send_resident_confirmation_email( 42, (object) [ 'ID' => 42, 'post_title' => 'Test' ] );
		$this->assertTrue( true ); // Reached without error.
	}
}
