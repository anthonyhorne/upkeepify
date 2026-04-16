<?php
/**
 * Tests for settings helpers.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/settings.php';

class SettingsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']        = [];
		$GLOBALS['_upkeepify_test_cache']          = [];
		$GLOBALS['_upkeepify_test_transients']     = [];
		$GLOBALS['_upkeepify_test_posts']          = [];
		$GLOBALS['_upkeepify_test_inserted_posts'] = [];
	}

	public function test_create_default_pages_creates_frontend_pages_and_saves_urls() {
		$result = upkeepify_create_default_pages();

		$this->assertIsArray( $result );
		$this->assertCount( 4, $GLOBALS['_upkeepify_test_inserted_posts'] );
		$this->assertSame( '[upkeepify_task_form]', $GLOBALS['_upkeepify_test_inserted_posts'][1000]['post_content'] );
		$this->assertSame( '[upkeepify_list_tasks]', $GLOBALS['_upkeepify_test_inserted_posts'][1001]['post_content'] );
		$this->assertSame( '[upkeepify_provider_response_form]', $GLOBALS['_upkeepify_test_inserted_posts'][1002]['post_content'] );
		$this->assertSame( '[upkeepify_resident_confirmation_form]', $GLOBALS['_upkeepify_test_inserted_posts'][1003]['post_content'] );

		$settings = get_option( UPKEEPIFY_OPTION_SETTINGS );
		$this->assertSame( 1, $settings[ UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING ] );
		$this->assertSame( 'http://localhost/contractor-response/', $settings[ UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE ] );
		$this->assertSame( 'http://localhost/resident-confirmation/', $settings[ UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE ] );
	}

	public function test_create_default_pages_reuses_existing_pages() {
		upkeepify_create_default_pages();
		$first_count = count( $GLOBALS['_upkeepify_test_inserted_posts'] );

		$result = upkeepify_create_default_pages();

		$this->assertIsArray( $result );
		$this->assertSame( $first_count, count( $GLOBALS['_upkeepify_test_inserted_posts'] ) );
		$this->assertSame( 'reused', $result['request']['status'] );
		$this->assertSame( 'reused', $result['contractor_response']['status'] );
	}
}
