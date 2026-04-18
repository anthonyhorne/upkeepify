<?php
/**
 * Tests for trustee lifecycle helpers.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/shortcodes.php';
require_once dirname( __DIR__ ) . '/includes/custom-post-types.php';

class TrusteeLifecycleTest extends TestCase {

	private $tmp_file = null;

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']        = [
			UPKEEPIFY_OPTION_SETTINGS => [
				UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE => 'https://example.com/provider-response',
				UPKEEPIFY_SETTING_CURRENCY               => '$',
			],
		];
		$GLOBALS['_upkeepify_test_cache']          = [];
		$GLOBALS['_upkeepify_test_transients']     = [];
		$GLOBALS['_upkeepify_test_posts']          = [];
		$GLOBALS['_upkeepify_test_post_meta']      = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms'] = [];
		$GLOBALS['_upkeepify_test_term_meta']      = [];
		$GLOBALS['_upkeepify_test_object_terms']   = [];
		$GLOBALS['_upkeepify_test_mail']           = [];
		$GLOBALS['_upkeepify_test_attachment_files'] = [];
		$GLOBALS['_upkeepify_test_attachment_urls']  = [];
		$_GET                                     = [];
		$_POST                                    = [];
	}

	protected function tearDown(): void {
		if ( $this->tmp_file && file_exists( $this->tmp_file ) ) {
			unlink( $this->tmp_file );
			$this->tmp_file = null;
		}
	}

	public function test_lifecycle_panel_shows_approve_estimate_action() {
		$task_id     = 42;
		$response_id = 123;
		$provider_id = 77;

		$task = new WP_Post(
			[
				'ID'        => $task_id,
				'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
			]
		);

		$response = new WP_Post(
			[
				'ID'        => $response_id,
				'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
			]
		);

		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [ $response ];
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]  = $task_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]       = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ]  = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ]  = '250';
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => $provider_id,
					'name'    => 'Fix It Fast',
				]
			),
		];

		ob_start();
		upkeepify_trustee_lifecycle_meta_box_callback( $task );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Fix It Fast', $output );
		$this->assertStringContainsString( 'Approve estimate', $output );
		$this->assertStringContainsString( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_APPROVAL, $output );
	}

	public function test_lifecycle_panel_shows_resident_issue_actions_after_contractor_followup() {
		$task_id     = 42;
		$response_id = 123;
		$provider_id = 77;

		$task = new WP_Post(
			[
				'ID'        => $task_id,
				'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
			]
		);

		$response = new WP_Post(
			[
				'ID'        => $response_id,
				'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
			]
		);

		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [ $response ];
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ] = $task_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ] = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ] = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ] = '250';
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE ] = '300';
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT ] = time() - 3600;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT ] = time();
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_NOTE ] = 'Checked and resealed the leak.';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] = UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] = $response_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE ] = 'Still leaking.';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] = 'resident@example.com';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = 'residenttoken123';
		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] = 'https://example.com/resident-confirmation';
		$GLOBALS['_upkeepify_test_cache'] = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => $provider_id,
					'name'    => 'Fix It Fast',
				]
			),
		];

		ob_start();
		upkeepify_trustee_lifecycle_meta_box_callback( $task );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Resident issue under review', $output );
		$this->assertStringContainsString( 'Still leaking.', $output );
		$this->assertStringContainsString( 'Checked and resealed the leak.', $output );
		$this->assertStringContainsString( 'Resolve issue and close', $output );
		$this->assertStringContainsString( 'Re-request resident confirmation', $output );
		$this->assertStringContainsString( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_FOLLOWUP, $output );
	}

	public function test_resolve_resident_issue_records_resolution_and_clears_followup_state() {
		$task_id = 42;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] = UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] = 123;
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] = [
			new WP_Term(
				[
					'term_id' => 31,
					'name'    => 'Completed',
				]
			),
		];

		upkeepify_resolve_resident_issue( $task_id, 'Reviewed and closed.' );

		$this->assertNotEmpty( $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT ] );
		$this->assertSame( 'Reviewed and closed.', $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertSame( [ 31 ], $GLOBALS['_upkeepify_test_object_terms'][ $task_id ][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] );
	}

	public function test_rerequest_resident_confirmation_reopens_feedback_and_sends_email() {
		$task_id = 42;
		$task    = new WP_Post(
			[
				'ID'         => $task_id,
				'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
				'post_title' => 'Leaking roof',
			]
		);

		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] = 'https://example.com/resident-confirmation';
		$GLOBALS['_upkeepify_test_cache'] = [];
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] = 'resident@example.com';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = 'residenttoken123';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED ] = '0';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT ] = time();
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE ] = 'Still leaking.';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] = UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] = 123;

		$sent = upkeepify_rerequest_resident_confirmation( $task_id, $task );

		$this->assertTrue( $sent );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ] );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'resident@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Confirm or report an issue', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
	}

	public function test_rerequest_resident_confirmation_keeps_state_when_email_cannot_send() {
		$task_id = 42;
		$task    = new WP_Post(
			[
				'ID'         => $task_id,
				'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
				'post_title' => 'Leaking roof',
			]
		);

		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] = 'resident@example.com';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] = 'residenttoken123';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED ] = '0';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT ] = 12345;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE ] = 'Still leaking.';
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] = UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED;
		$GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] = 123;

		$sent = upkeepify_rerequest_resident_confirmation( $task_id, $task );

		$this->assertFalse( $sent );
		$this->assertSame( '0', $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED ] );
		$this->assertSame( 12345, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT ] );
		$this->assertSame( 'Still leaking.', $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE ] );
		$this->assertSame( UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS ] );
		$this->assertSame( 123, $GLOBALS['_upkeepify_test_post_meta'][ $task_id ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID ] );
		$this->assertCount( 0, $GLOBALS['_upkeepify_test_mail'] );
	}

	public function test_trustee_approval_email_notifies_provider() {
		$task_id     = 42;
		$response_id = 123;
		$provider_id = 77;

		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ] = [
			new WP_Post(
				[
					'ID'         => $task_id,
					'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
					'post_title' => 'Leaking roof',
				]
			),
		];
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]    = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ] = 'providertoken123';
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => $provider_id,
					'name'    => 'Fix It Fast',
				]
			),
		];
		$GLOBALS['_upkeepify_test_term_meta'][ $provider_id ][ UPKEEPIFY_TERM_META_PROVIDER_EMAIL ] = 'contractor@example.com';

		$sent = upkeepify_send_trustee_lifecycle_approval_email( $task_id, $response_id, 'estimate' );

		$this->assertTrue( $sent );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'contractor@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Estimate approved', $GLOBALS['_upkeepify_test_mail'][0]['subject'] );
		$this->assertStringContainsString( 'Submit formal quote', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
	}

	public function test_quote_audit_email_includes_approved_quote_documents_and_other_response_references() {
		$task_id              = 42;
		$approved_response_id = 123;
		$other_response_id    = 124;
		$provider_id          = 77;
		$other_provider_id    = 78;
		$attachment_id        = 901;

		$GLOBALS['_upkeepify_test_options'][ UPKEEPIFY_OPTION_SETTINGS ][ UPKEEPIFY_SETTING_AUDIT_EMAIL ] = 'auditor@example.com';

		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ] = [
			new WP_Post(
				[
					'ID'         => $task_id,
					'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
					'post_title' => 'Gate motor repair',
				]
			),
		];
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [
			new WP_Post(
				[
					'ID'        => $approved_response_id,
					'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
				]
			),
			new WP_Post(
				[
					'ID'        => $other_response_id,
					'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
				]
			),
		];
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => $provider_id,
					'name'    => 'Fix It Fast',
				]
			),
			new WP_Term(
				[
					'term_id' => $other_provider_id,
					'name'    => 'Slow But Steady',
				]
			),
		];

		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]            = $task_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]                 = $provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ]            = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ]            = '1000';
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE ]        = '1250';
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_QUOTE_NOTE ]          = 'Includes gate controller replacement.';
		$GLOBALS['_upkeepify_test_post_meta'][ $approved_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_QUOTE_ATTACHMENTS ]   = [ $attachment_id ];

		$GLOBALS['_upkeepify_test_post_meta'][ $other_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]     = $task_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $other_response_id ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]          = $other_provider_id;
		$GLOBALS['_upkeepify_test_post_meta'][ $other_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ]     = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ $other_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ]     = '900';
		$GLOBALS['_upkeepify_test_post_meta'][ $other_response_id ][ UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE ] = '1400';

		$this->tmp_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upkeepify_quote_audit_test.pdf';
		file_put_contents( $this->tmp_file, "%PDF-1.4\n% test quote\n" );
		$GLOBALS['_upkeepify_test_attachment_files'][ $attachment_id ] = $this->tmp_file;
		$GLOBALS['_upkeepify_test_attachment_urls'][ $attachment_id ]  = 'https://example.com/uploads/quote.pdf';

		$sent = upkeepify_send_quote_audit_email( $task_id, $approved_response_id );

		$this->assertTrue( $sent );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'auditor@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Approved quote audit pack', $GLOBALS['_upkeepify_test_mail'][0]['subject'] );
		$this->assertStringContainsString( 'Fix It Fast', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
		$this->assertStringContainsString( '$1,250.00', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
		$this->assertStringContainsString( 'Quote document #901', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
		$this->assertStringContainsString( 'Slow But Steady', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
		$this->assertSame( [ $this->tmp_file ], $GLOBALS['_upkeepify_test_mail'][0]['attachments'] );
	}
}
