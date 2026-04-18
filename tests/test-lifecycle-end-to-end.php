<?php
/**
 * End-to-end lifecycle scenario tests.
 *
 * Covers the full two-contractor flow: both contractors submit estimates,
 * trustee selects one, approves the formal quote, contractor completes work,
 * resident confirms or reports an issue, contractor follows up, and the
 * trustee resolves or re-requests confirmation. Also covers the manual-close
 * path when no resident email is available, and token revoke/regenerate.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/shortcodes.php';
require_once dirname( __DIR__ ) . '/includes/custom-post-types.php';

class LifecycleEndToEndTest extends TestCase {

	// ── Fixed IDs ──────────────────────────────────────────────────────────────

	const TASK_ID     = 100;
	const RESPONSE_A  = 201; // contractor A — selected by trustee
	const RESPONSE_B  = 202; // contractor B — not selected
	const PROVIDER_A  = 301;
	const PROVIDER_B  = 302;

	// Status term IDs
	const TERM_OPEN                           = 10;
	const TERM_PENDING_ESTIMATE_APPROVAL      = 11;
	const TERM_PENDING_QUOTE_APPROVAL         = 12;
	const TERM_AWAITING_COMPLETION            = 13;
	const TERM_AWAITING_RESIDENT_CONFIRMATION = 14;
	const TERM_NEEDS_REVIEW                   = 15;
	const TERM_COMPLETED                      = 16;

	// ── Test bootstrap ─────────────────────────────────────────────────────────

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options'] = [
			UPKEEPIFY_OPTION_SETTINGS => [
				UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE     => 'https://example.com/provider-response',
				UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE => 'https://example.com/resident-confirmation',
				UPKEEPIFY_SETTING_CURRENCY                   => '$',
				UPKEEPIFY_SETTING_AUDIT_EMAIL                => 'auditor@example.com',
			],
		];
		$GLOBALS['_upkeepify_test_cache']            = [];
		$GLOBALS['_upkeepify_test_transients']       = [];
		$GLOBALS['_upkeepify_test_posts']            = [];
		$GLOBALS['_upkeepify_test_post_meta']        = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms']   = [];
		$GLOBALS['_upkeepify_test_term_meta']        = [];
		$GLOBALS['_upkeepify_test_object_terms']     = [];
		$GLOBALS['_upkeepify_test_mail']             = [];
		$GLOBALS['_upkeepify_test_attachment_files'] = [];
		$GLOBALS['_upkeepify_test_attachment_urls']  = [];
		$_GET  = [];
		$_POST = [];

		$this->seedTask();
		$this->seedStatusTerms();
		$this->seedProviders();
		$this->seedResponses();
	}

	// ── Seed helpers ───────────────────────────────────────────────────────────

	private function seedTask(): void {
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS ] = [
			new WP_Post( [
				'ID'         => self::TASK_ID,
				'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
				'post_title' => 'Replace pool pump',
			] ),
		];
		$GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] = 'resident@example.com';
		$GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ]  = 'restoken999';
	}

	private function seedStatusTerms(): void {
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] = [
			new WP_Term( [ 'term_id' => self::TERM_OPEN,                           'name' => UPKEEPIFY_TASK_STATUS_OPEN ] ),
			new WP_Term( [ 'term_id' => self::TERM_PENDING_ESTIMATE_APPROVAL,      'name' => UPKEEPIFY_TASK_STATUS_PENDING_ESTIMATE_APPROVAL ] ),
			new WP_Term( [ 'term_id' => self::TERM_PENDING_QUOTE_APPROVAL,         'name' => UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL ] ),
			new WP_Term( [ 'term_id' => self::TERM_AWAITING_COMPLETION,            'name' => UPKEEPIFY_TASK_STATUS_AWAITING_COMPLETION ] ),
			new WP_Term( [ 'term_id' => self::TERM_AWAITING_RESIDENT_CONFIRMATION, 'name' => UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION ] ),
			new WP_Term( [ 'term_id' => self::TERM_NEEDS_REVIEW,                   'name' => UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW ] ),
			new WP_Term( [ 'term_id' => self::TERM_COMPLETED,                      'name' => UPKEEPIFY_TASK_STATUS_COMPLETED ] ),
		];
	}

	private function seedProviders(): void {
		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term( [ 'term_id' => self::PROVIDER_A, 'name' => 'Alpha Plumbing' ] ),
			new WP_Term( [ 'term_id' => self::PROVIDER_B, 'name' => 'Beta Plumbing' ] ),
		];
		$GLOBALS['_upkeepify_test_term_meta'][ self::PROVIDER_A ][ UPKEEPIFY_TERM_META_PROVIDER_EMAIL ] = 'alpha@example.com';
		$GLOBALS['_upkeepify_test_term_meta'][ self::PROVIDER_B ][ UPKEEPIFY_TERM_META_PROVIDER_EMAIL ] = 'beta@example.com';
	}

	private function seedResponses(): void {
		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [
			new WP_Post( [ 'ID' => self::RESPONSE_A, 'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] ),
			new WP_Post( [ 'ID' => self::RESPONSE_B, 'post_type' => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] ),
		];

		// Contractor A: accepted with estimate
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]        = self::TASK_ID;
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]             = self::PROVIDER_A;
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ]       = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ]       = '4500';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ]          = 'token_alpha';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES ]  = time() + DAY_IN_SECONDS;

		// Contractor B: accepted with a lower estimate
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]       = self::TASK_ID;
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_PROVIDER_ID ]            = self::PROVIDER_B;
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_RESPONSE_DECISION ]      = 'accept';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_RESPONSE_ESTIMATE ]      = '3900';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN ]         = 'token_beta';
		$GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_B ][ UPKEEPIFY_META_KEY_RESPONSE_TOKEN_EXPIRES ] = time() + DAY_IN_SECONDS;
	}

	// ── Lifecycle stage helpers ────────────────────────────────────────────────

	private function approveEstimate( int $response_id ): void {
		$provider_id = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, $response_id );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_AT, time() );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_BY, 1 );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, $provider_id );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function submitFormalQuote( int $response_id, string $amount ): void {
		update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FORMAL_QUOTE, $amount );
	}

	private function approveQuote( int $response_id ): void {
		$provider_id = intval( get_post_meta( $response_id, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, $response_id );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_AT, time() );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_BY, 1 );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, $provider_id );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function markWorkComplete( int $response_id ): void {
		update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT, time() );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function residentConfirms(): void {
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, '1' );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, time() );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function residentReportsIssue( int $response_id, string $note = 'Pump still not working.' ): void {
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, '0' );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, $note );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $response_id );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_REPORTED_AT, time() );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function contractorSubmitsFollowup( int $response_id, string $note = 'Replaced the capacitor.' ): void {
		update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT, time() );
		update_post_meta( $response_id, UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_NOTE, $note );
		update_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED );
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
	}

	private function assertLifecycleStatus( string $expected ): void {
		$this->assertSame( $expected, upkeepify_get_task_lifecycle_status_name( self::TASK_ID ) );

		$term_id = null;
		foreach ( $GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] as $term ) {
			if ( $term->name === $expected ) {
				$term_id = $term->term_id;
				break;
			}
		}
		if ( $term_id !== null ) {
			$this->assertSame(
				[ $term_id ],
				$GLOBALS['_upkeepify_test_object_terms'][ self::TASK_ID ][ UPKEEPIFY_TAXONOMY_TASK_STATUS ] ?? []
			);
		}
	}

	// ── Tests ──────────────────────────────────────────────────────────────────

	/**
	 * Full happy path: two contractors → approve A → quote → completion → resident confirms.
	 */
	public function test_full_happy_path_with_resident_confirmation(): void {
		// Both contractors have submitted estimates.
		upkeepify_sync_task_lifecycle_status( self::TASK_ID );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_PENDING_ESTIMATE_APPROVAL );

		// Trustee approves contractor A's estimate.
		$this->approveEstimate( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_PENDING_QUOTE_APPROVAL );

		// Contractor A submits formal quote; trustee approves it.
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_COMPLETION );

		// Contractor A marks work complete.
		$this->markWorkComplete( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION );

		// Resident confirms satisfaction.
		$this->residentConfirms();
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_COMPLETED );
		$this->assertSame( '1', get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, true ) );
	}

	/**
	 * Approving contractor A's estimate does not assign or approve contractor B.
	 */
	public function test_trustee_selects_contractor_a_not_contractor_b(): void {
		$this->approveEstimate( self::RESPONSE_A );

		$this->assertSame(
			self::RESPONSE_A,
			intval( get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true ) )
		);
		$this->assertNotSame(
			self::RESPONSE_B,
			intval( get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID, true ) )
		);
		$this->assertSame(
			self::PROVIDER_A,
			intval( get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, true ) )
		);

		// Contractor B also submits a formal quote — trustee still approves A.
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->submitFormalQuote( self::RESPONSE_B, '3700' );
		$this->approveQuote( self::RESPONSE_A );

		$this->assertSame(
			self::RESPONSE_A,
			intval( get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID, true ) )
		);
		$this->assertSame(
			self::PROVIDER_A,
			intval( get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, true ) )
		);
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_COMPLETION );
	}

	/**
	 * Resident reports issue → contractor follows up → trustee resolves → Completed.
	 */
	public function test_resident_dissatisfaction_then_contractor_followup_then_trustee_resolve(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION );

		$this->residentReportsIssue( self::RESPONSE_A, 'Pump starts but trips breaker.' );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW );
		$this->assertSame(
			UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_ISSUE,
			get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true )
		);

		$this->contractorSubmitsFollowup( self::RESPONSE_A, 'Replaced faulty capacitor, tested at full load.' );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW );
		$this->assertSame(
			UPKEEPIFY_RESIDENT_FOLLOWUP_STATUS_SUBMITTED,
			get_post_meta( self::TASK_ID, UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, true )
		);

		upkeepify_resolve_resident_issue( self::TASK_ID, 'Reviewed follow-up. Issue addressed.' );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_COMPLETED );

		$meta = $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ];
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT ] );
		$this->assertSame( 'Reviewed follow-up. Issue addressed.', $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $meta );
	}

	/**
	 * After contractor follow-up, trustee opts to re-request resident confirmation.
	 */
	public function test_resident_dissatisfaction_then_trustee_rerequests_confirmation(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->residentReportsIssue( self::RESPONSE_A, 'Still not working.' );
		$this->contractorSubmitsFollowup( self::RESPONSE_A, 'Cleaned the impeller.' );

		$task = new WP_Post( [
			'ID'         => self::TASK_ID,
			'post_type'  => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
			'post_title' => 'Replace pool pump',
		] );

		$sent = upkeepify_rerequest_resident_confirmation( self::TASK_ID, $task );

		$this->assertTrue( $sent );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'resident@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );

		$meta = $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ];
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $meta );
	}

	/**
	 * When no resident email is on file, the trustee can manually close the lifecycle.
	 */
	public function test_manual_close_path_without_resident_email(): void {
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] );
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] );

		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION );

		$this->assertTrue( upkeepify_can_manual_close_task_lifecycle( self::TASK_ID ) );

		upkeepify_manual_close_task_lifecycle(
			self::TASK_ID,
			UPKEEPIFY_MANUAL_CLOSE_MODE_CLOSED_WITHOUT_CONFIRMATION,
			'No resident contact details on file.'
		);

		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_COMPLETED );

		$meta = $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ];
		$this->assertSame( UPKEEPIFY_MANUAL_CLOSE_MODE_CLOSED_WITHOUT_CONFIRMATION, $meta[ UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_MODE ] );
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSED_AT ] );
		$this->assertSame( 'No resident contact details on file.', $meta[ UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_NOTE ] );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED, $meta );
	}

	/**
	 * Manual close in resident_confirmed mode sets the confirmed flag.
	 */
	public function test_manual_close_resident_confirmed_mode_sets_confirmed_flag(): void {
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] );
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] );

		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );

		upkeepify_manual_close_task_lifecycle(
			self::TASK_ID,
			UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED,
			'Confirmed by phone.'
		);

		$meta = $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ];
		$this->assertSame( UPKEEPIFY_MANUAL_CLOSE_MODE_RESIDENT_CONFIRMED, $meta[ UPKEEPIFY_META_KEY_TASK_MANUAL_CLOSE_MODE ] );
		$this->assertSame( '1', $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED ] );
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRMED_AT ] );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_COMPLETED );
	}

	/**
	 * Manual close is unavailable when a resident email and confirmation URL exist.
	 */
	public function test_manual_close_unavailable_when_resident_email_present(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_AWAITING_RESIDENT_CONFIRMATION );

		// Resident email is still present from seedTask().
		$this->assertFalse( upkeepify_can_manual_close_task_lifecycle( self::TASK_ID ) );
	}

	/**
	 * Manual close is unavailable once the resident has already confirmed.
	 */
	public function test_manual_close_unavailable_after_resident_confirmed(): void {
		// Remove resident email so this test isolates the confirmed-at guard.
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] );

		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->residentConfirms();

		$this->assertFalse( upkeepify_can_manual_close_task_lifecycle( self::TASK_ID ) );
	}

	/**
	 * Manual close is unavailable when a resident issue follow-up is pending.
	 */
	public function test_manual_close_unavailable_during_resident_issue_followup(): void {
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] );

		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->residentReportsIssue( self::RESPONSE_A );

		$this->assertFalse( upkeepify_can_manual_close_task_lifecycle( self::TASK_ID ) );
	}

	/**
	 * Revoking contractor A's token blocks access; regen restores it with a new token.
	 */
	public function test_token_revoke_blocks_access_then_regenerate_restores_it(): void {
		$this->assertTrue( upkeepify_provider_response_token_matches( self::RESPONSE_A, 'token_alpha' ) );

		upkeepify_revoke_provider_response_token( self::RESPONSE_A );
		$this->assertSame( 'revoked', upkeepify_get_provider_response_token_state( self::RESPONSE_A ) );
		$this->assertFalse( upkeepify_provider_response_token_matches( self::RESPONSE_A, 'token_alpha' ) );

		$new_token = upkeepify_regenerate_provider_response_token( self::RESPONSE_A );
		$this->assertSame( 'active', upkeepify_get_provider_response_token_state( self::RESPONSE_A ) );
		$this->assertTrue( upkeepify_provider_response_token_matches( self::RESPONSE_A, $new_token ) );
		$this->assertFalse( upkeepify_provider_response_token_matches( self::RESPONSE_A, 'token_alpha' ) );
	}

	/**
	 * Revoking contractor B's token leaves contractor A's token unaffected.
	 */
	public function test_token_revoke_for_contractor_b_does_not_affect_contractor_a(): void {
		upkeepify_revoke_provider_response_token( self::RESPONSE_B );

		$this->assertSame( 'revoked', upkeepify_get_provider_response_token_state( self::RESPONSE_B ) );
		$this->assertSame( 'active', upkeepify_get_provider_response_token_state( self::RESPONSE_A ) );
		$this->assertTrue( upkeepify_provider_response_token_matches( self::RESPONSE_A, 'token_alpha' ) );
	}

	/**
	 * Regenerating a token sends the new link to the correct contractor's email address.
	 */
	public function test_token_regenerate_sends_email_to_correct_provider(): void {
		$new_token = upkeepify_regenerate_provider_response_token( self::RESPONSE_A );
		$sent      = upkeepify_send_regenerated_provider_token_email( self::TASK_ID, self::RESPONSE_A, $new_token );

		$this->assertTrue( $sent );
		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'alpha@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Replace pool pump', $GLOBALS['_upkeepify_test_mail'][0]['subject'] );
		$this->assertStringContainsString( 'Open job response', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
	}

	/**
	 * Estimate approval email is sent only to the selected contractor.
	 */
	public function test_estimate_approval_email_sent_to_selected_contractor_only(): void {
		$this->approveEstimate( self::RESPONSE_A );
		upkeepify_send_trustee_lifecycle_approval_email( self::TASK_ID, self::RESPONSE_A, 'estimate' );

		$this->assertCount( 1, $GLOBALS['_upkeepify_test_mail'] );
		$this->assertSame( 'alpha@example.com', $GLOBALS['_upkeepify_test_mail'][0]['to'] );
		$this->assertStringContainsString( 'Estimate approved', $GLOBALS['_upkeepify_test_mail'][0]['subject'] );
		$this->assertStringContainsString( 'Submit formal quote', $GLOBALS['_upkeepify_test_mail'][0]['message'] );
	}

	/**
	 * Quote approval notifies the contractor and sends the audit pack to the auditor.
	 */
	public function test_quote_approval_notifies_contractor_and_auditor(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );

		upkeepify_send_trustee_lifecycle_approval_email( self::TASK_ID, self::RESPONSE_A, 'quote' );
		upkeepify_send_quote_audit_email( self::TASK_ID, self::RESPONSE_A );

		$recipients = array_column( $GLOBALS['_upkeepify_test_mail'], 'to' );
		$this->assertContains( 'alpha@example.com', $recipients );
		$this->assertContains( 'auditor@example.com', $recipients );

		$quote_email = array_values( array_filter( $GLOBALS['_upkeepify_test_mail'], fn( $m ) => $m['to'] === 'alpha@example.com' ) )[0];
		$this->assertStringContainsString( 'Quote approved', $quote_email['subject'] );

		$audit_email = array_values( array_filter( $GLOBALS['_upkeepify_test_mail'], fn( $m ) => $m['to'] === 'auditor@example.com' ) )[0];
		$this->assertStringContainsString( 'Approved quote audit pack', $audit_email['subject'] );
		$this->assertStringContainsString( 'Alpha Plumbing', $audit_email['message'] );
		$this->assertStringContainsString( '$4,800.00', $audit_email['message'] );
		$this->assertStringContainsString( 'Beta Plumbing', $audit_email['message'] );
	}

	/**
	 * Full audit trail is intact after completing the entire lifecycle with a resident issue.
	 */
	public function test_complete_audit_trail_after_lifecycle_with_resident_issue(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->residentReportsIssue( self::RESPONSE_A, 'Pump vibrates excessively.' );
		$this->contractorSubmitsFollowup( self::RESPONSE_A, 'Balanced the impeller and tightened mounts.' );
		upkeepify_resolve_resident_issue( self::TASK_ID, 'Inspected on-site. Satisfied with follow-up.' );

		$meta = $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ];
		$response_a_meta = $GLOBALS['_upkeepify_test_post_meta'][ self::RESPONSE_A ];

		// Estimate approval trail
		$this->assertSame( self::RESPONSE_A, intval( $meta[ UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_RESPONSE_ID ] ) );
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_APPROVED_ESTIMATE_AT ] );
		$this->assertSame( self::PROVIDER_A, intval( $meta[ UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER ] ) );

		// Quote approval trail
		$this->assertSame( self::RESPONSE_A, intval( $meta[ UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_RESPONSE_ID ] ) );
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_APPROVED_QUOTE_AT ] );

		// Completion trail
		$this->assertNotEmpty( $response_a_meta[ UPKEEPIFY_META_KEY_RESPONSE_COMPLETED_AT ] );

		// Resident issue trail
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_REPORTED_AT ] );
		$this->assertSame( 'Pump vibrates excessively.', $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_CONFIRM_NOTE ] );

		// Contractor follow-up trail
		$this->assertNotEmpty( $response_a_meta[ UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_COMPLETED_AT ] );
		$this->assertSame( 'Balanced the impeller and tightened mounts.', $response_a_meta[ UPKEEPIFY_META_KEY_RESPONSE_FOLLOWUP_NOTE ] );

		// Trustee resolution trail
		$this->assertNotEmpty( $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLVED_AT ] );
		$this->assertSame( 'Inspected on-site. Satisfied with follow-up.', $meta[ UPKEEPIFY_META_KEY_TASK_RESIDENT_ISSUE_RESOLUTION_NOTE ] );

		// Follow-up state fully cleared
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_STATUS, $meta );
		$this->assertArrayNotHasKey( UPKEEPIFY_META_KEY_TASK_RESIDENT_FOLLOWUP_RESPONSE_ID, $meta );

		$this->assertLifecycleStatus( UPKEEPIFY_TASK_STATUS_COMPLETED );
	}

	/**
	 * Admin panel renders both contractor names and estimate approve buttons in the initial state.
	 */
	public function test_lifecycle_panel_shows_both_contractors_at_initial_state(): void {
		$task = new WP_Post( [
			'ID'        => self::TASK_ID,
			'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
		] );

		ob_start();
		upkeepify_trustee_lifecycle_meta_box_callback( $task );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Alpha Plumbing', $output );
		$this->assertStringContainsString( 'Beta Plumbing', $output );
		$this->assertStringContainsString( 'Approve estimate', $output );
		$this->assertStringContainsString( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_APPROVAL, $output );
	}

	/**
	 * Admin panel shows the manual close controls once work is complete and no resident email exists.
	 */
	public function test_lifecycle_panel_shows_manual_close_when_resident_email_absent(): void {
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_SUBMITTER_EMAIL ] );
		unset( $GLOBALS['_upkeepify_test_post_meta'][ self::TASK_ID ][ UPKEEPIFY_META_KEY_TASK_RESIDENT_TOKEN ] );

		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );

		$task = new WP_Post( [
			'ID'        => self::TASK_ID,
			'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
		] );

		ob_start();
		upkeepify_trustee_lifecycle_meta_box_callback( $task );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Resident confirmation unavailable', $output );
		$this->assertStringContainsString( 'Mark resident confirmed and close', $output );
		$this->assertStringContainsString( 'Close without resident confirmation', $output );
		$this->assertStringContainsString( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_MANUAL_CLOSE, $output );
	}

	/**
	 * Admin panel shows the resident issue review panel while contractor follow-up is pending.
	 */
	public function test_lifecycle_panel_shows_resident_issue_review_during_followup(): void {
		$this->approveEstimate( self::RESPONSE_A );
		$this->submitFormalQuote( self::RESPONSE_A, '4800' );
		$this->approveQuote( self::RESPONSE_A );
		$this->markWorkComplete( self::RESPONSE_A );
		$this->residentReportsIssue( self::RESPONSE_A, 'Loud grinding noise.' );
		$this->contractorSubmitsFollowup( self::RESPONSE_A, 'Replaced worn bearings.' );

		$task = new WP_Post( [
			'ID'        => self::TASK_ID,
			'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
		] );

		ob_start();
		upkeepify_trustee_lifecycle_meta_box_callback( $task );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Resident issue under review', $output );
		$this->assertStringContainsString( 'Loud grinding noise.', $output );
		$this->assertStringContainsString( 'Replaced worn bearings.', $output );
		$this->assertStringContainsString( 'Resolve issue and close', $output );
		$this->assertStringContainsString( UPKEEPIFY_ADMIN_ACTION_TRUSTEE_LIFECYCLE_FOLLOWUP, $output );
	}
}
