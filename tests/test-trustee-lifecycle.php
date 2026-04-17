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
		$GLOBALS['_upkeepify_test_mail']           = [];
		$_GET                                     = [];
		$_POST                                    = [];
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
}
