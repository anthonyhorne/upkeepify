<?php
/**
 * Tests for uninstall cleanup.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class UninstallTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
		$GLOBALS['_upkeepify_test_posts']      = [];
		$GLOBALS['_upkeepify_test_post_thumbnails'] = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms']  = [];
		$GLOBALS['_upkeepify_test_deleted_attachments'] = [];
		$GLOBALS['_upkeepify_test_deleted_posts']   = [];
		$GLOBALS['_upkeepify_test_deleted_terms']   = [];
	}

	public function test_uninstall_deletes_options_posts_terms_and_task_photo_attachments() {
		update_option( 'upkeepify_settings', [ 'foo' => 'bar' ] );
		update_option( 'upkeepify_db_version', '1.0.0' );

		$GLOBALS['_upkeepify_test_posts'] = [
			'maintenance_tasks'   => [ 101 ],
			'upkeepify_responses' => [ 202 ],
			'provider_responses'  => [ 303 ],
		];
		$GLOBALS['_upkeepify_test_post_thumbnails'] = [
			101 => 501,
		];
		$GLOBALS['_upkeepify_test_taxonomy_terms'] = [
			'service_provider' => [ 11 ],
			'task_category'    => [ 12 ],
			'task_type'        => [ 13 ],
			'task_status'      => [ 14 ],
			'unit'             => [ 15 ],
		];

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', true );
		}

		include dirname( __DIR__ ) . '/uninstall.php';

		$this->assertFalse( get_option( 'upkeepify_settings', false ) );
		$this->assertFalse( get_option( 'upkeepify_db_version', false ) );
		$this->assertSame(
			[
				[
					'attachment_id' => 501,
					'force_delete'  => true,
				],
			],
			$GLOBALS['_upkeepify_test_deleted_attachments']
		);
		$this->assertCount( 3, $GLOBALS['_upkeepify_test_deleted_posts'] );
		$this->assertSame( 101, $GLOBALS['_upkeepify_test_deleted_posts'][0]['post_id'] );
		$this->assertTrue( $GLOBALS['_upkeepify_test_deleted_posts'][0]['force_delete'] );
		$this->assertCount( 5, $GLOBALS['_upkeepify_test_deleted_terms'] );
	}
}
