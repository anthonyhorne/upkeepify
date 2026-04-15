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
		$GLOBALS['_upkeepify_test_cache']          = [];
		$GLOBALS['_upkeepify_test_transients']     = [];
		$GLOBALS['_upkeepify_test_posts']          = [];
		$GLOBALS['_upkeepify_test_post_meta']      = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms'] = [];
		$GLOBALS['_upkeepify_test_object_terms']   = [];
		$GLOBALS['_upkeepify_test_inserted_posts'] = [];
		$GLOBALS['_upkeepify_test_mail']           = [];
		$_SERVER['REQUEST_URI']                    = '/report-an-issue';
		$_SERVER['REQUEST_METHOD']                 = 'GET';
		$_GET                                      = [];
		$_POST                                     = [];
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

	public function test_task_form_submission_creates_pending_task() {
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_SESSION[ UPKEEPIFY_SESSION_MATH_RESULT ] = 4;

		$_POST = [
			'upkeepify_task_submit'          => '1',
			'upkeepify_upload'               => '1',
			UPKEEPIFY_NONCE_TASK_SUBMIT      => 'test-nonce',
			'math'                           => '4',
			'task_title'                     => 'Broken gate',
			'task_description'               => 'The pedestrian gate does not close properly.',
			'nearest_unit'                   => '3',
			UPKEEPIFY_TAXONOMY_TASK_CATEGORY => '12',
			UPKEEPIFY_TAXONOMY_TASK_TYPE     => '22',
		];

		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] = [
			new WP_Term(
				[
					'term_id' => 31,
					'name'    => 'Open',
				]
			),
		];

		upkeepify_handle_task_form_submission();

		$this->assertSame( 'pending', $GLOBALS['_upkeepify_test_inserted_posts'][1000]['post_status'] );
		$this->assertSame( [ 12 ], $GLOBALS['_upkeepify_test_object_terms'][1000][ UPKEEPIFY_TAXONOMY_TASK_CATEGORY ] );
		$this->assertSame( [ 22 ], $GLOBALS['_upkeepify_test_object_terms'][1000][ UPKEEPIFY_TAXONOMY_TASK_TYPE ] );
		$this->assertSame( [ 31 ], $GLOBALS['_upkeepify_test_object_terms'][1000][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] );
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

	// ─── upkeepify_get_provider_response_url ───────────────────────────────────

	public function test_get_provider_response_url_uses_configured_response_page() {
		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] = 'https://example.com/provider-response';
		$GLOBALS['_upkeepify_test_cache'] = [];
		$GLOBALS['_upkeepify_test_post_meta'][123][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ] = 'providertoken123';

		$url = upkeepify_get_provider_response_url( 123 );

		$this->assertSame( 'https://example.com/provider-response?upkeepify_token=providertoken123', $url );
	}

	public function test_send_contractor_resident_issue_email_notifies_provider_and_marks_task() {
		$task_id     = 42;
		$response_id = 123;
		$provider_id = 77;
		$task        = new WP_Post( [
			'ID'           => $task_id,
			'post_title'   => 'Leaking roof',
			'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
			'post_content' => 'Leak above the garage.',
		] );

		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] = 'https://example.com/provider-response';
		$GLOBALS['_upkeepify_test_cache'] = [];
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ] = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ] = 'providertoken123';
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term( [
				'term_id' => $provider_id,
				'name'    => 'Fix It Fast',
			] ),
		];
		$GLOBALS['_upkeepify_test_term_meta'][ $provider_id ][ UPKEEPIFY_TERM_META_PROVIDER_EMAIL ] = 'contractor@example.com';

		$sent = upkeepify_send_contractor_resident_issue_email( $task_id, $task, $response_id, 'Still leaking.' );

		$this->assertTrue( $sent );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'contractor@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Resident reported an issue', $GLOBALS['_upkeepify_test_mail'][0]['subject'] );
		$this->assertStringContainsString( 'Still leaking.', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
		$this->assertNotEmpty( $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_CONTRACTOR_NOTIFIED_AT ] );
	}

	public function test_open_resident_issue_followup_sets_review_state_and_notifies_contractor() {
		$task_id     = 42;
		$response_id = 123;
		$provider_id = 77;
		$task        = new WP_Post( [
			'ID'           => $task_id,
			'post_title'   => 'Leaking roof',
			'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
			'post_content' => 'Leak above the garage.',
		] );
		$response    = new WP_Post( [
			'ID'        => $response_id,
			'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
		] );

		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] = 'https://example.com/provider-response';
		$GLOBALS['_upkeepify_test_cache'] = [];
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [ $response ];
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ] = $task_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT ] = time();
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ] = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ] = 'providertoken123';
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term( [
				'term_id' => $provider_id,
				'name'    => 'Fix It Fast',
			] ),
		];
		$GLOBALS['_upkeepify_test_term_meta'][ $provider_id ][ UPKEEPIFY_TERM_META_PROVIDER_EMAIL ] = 'contractor@example.com';

		$completed_response = upkeepify_open_resident_issue_followup(
			$task_id,
			$task,
			[
				UPKEEPIFY_SETTING_NOTIFY_CONTRACTOR_ON_RESIDENT_ISSUE => 1,
			],
			'Still leaking.'
		);

		$this->assertSame( $response_id, $completed_response->ID );
		$this->assertSame( UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] );
		$this->assertSame( $response_id, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] );
		$this->assertNotEmpty( $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_REPORTED_AT ] );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'contractor@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
	}

	public function test_clear_resident_issue_followup_deletes_review_state() {
		$task_id = 42;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] = UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] = 123;

		upkeepify_clear_resident_issue_followup( $task_id );

		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
	}

	// ─── upkeepify_send_resident_confirmation_email ──────────────────────────────

	public function test_send_resident_confirmation_email_does_nothing_without_email() {
		// No email in meta → function returns early; wp_mail never called.
		// We just ensure no exception is thrown.
		upkeepify_send_resident_confirmation_email( 42, (object) [ 'ID' => 42, 'post_title' => 'Test' ] );
		$this->assertTrue( true ); // Reached without error.
	}
}
