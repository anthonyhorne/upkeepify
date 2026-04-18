<?php
/**
 * Tests for admin task list helpers.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return isset( $GLOBALS['_upkeepify_test_is_admin'] ) ? (bool) $GLOBALS['_upkeepify_test_is_admin'] : true;
	}
}

if ( ! function_exists( 'get_current_screen' ) ) {
	function get_current_screen() {
		return isset( $GLOBALS['_upkeepify_test_current_screen'] ) ? $GLOBALS['_upkeepify_test_current_screen'] : null;
	}
}

require_once dirname( __DIR__ ) . '/includes/custom-post-types.php';
require_once dirname( __DIR__ ) . '/includes/admin-functions.php';

class UpkeepifyTestAdminQuery {
	public $vars = [];

	public function is_main_query() {
		return true;
	}

	public function set( $key, $value ) {
		$this->vars[ $key ] = $value;
	}
}

class AdminFunctionsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_is_admin']       = true;
		$GLOBALS['_upkeepify_test_current_screen'] = (object) [
			'post_type' => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
		];
		$_GET = [];
	}

	public function test_add_lifecycle_column_after_title() {
		$columns = upkeepify_add_lifecycle_admin_columns(
			[
				'cb'    => '<input type="checkbox" />',
				'title' => 'Title',
				'date'  => 'Date',
			]
		);

		$this->assertSame(
			[ 'cb', 'title', 'upkeepify_lifecycle_status', 'date' ],
			array_keys( $columns )
		);
		$this->assertSame( 'Lifecycle', $columns['upkeepify_lifecycle_status'] );
	}

	public function test_render_lifecycle_status_filter_outputs_selected_option() {
		$_GET['upkeepify_lifecycle_status'] = UPKEEPIFY_TASK_STATUS_SLUG_NEEDS_REVIEW;

		ob_start();
		upkeepify_render_lifecycle_status_filter( UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'All lifecycle statuses', $output );
		$this->assertStringContainsString( UPKEEPIFY_TASK_STATUS_NEEDS_REVIEW, $output );
		$this->assertStringContainsString( 'value="' . UPKEEPIFY_TASK_STATUS_SLUG_NEEDS_REVIEW . '" selected="selected"', $output );
	}

	public function test_adjust_admin_view_filters_by_lifecycle_status() {
		$_GET['upkeepify_lifecycle_status'] = UPKEEPIFY_TASK_STATUS_SLUG_AWAITING_COMPLETION;
		$query = new UpkeepifyTestAdminQuery();

		upkeepify_adjust_admin_view( $query );

		$this->assertSame( [ 'publish', 'pending', 'draft' ], $query->vars['post_status'] );
		$this->assertSame(
			[
				[
					'taxonomy' => UPKEEPIFY_TAXONOMY_TASK_STATUS,
					'field'    => 'slug',
					'terms'    => UPKEEPIFY_TASK_STATUS_SLUG_AWAITING_COMPLETION,
				],
			],
			$query->vars['tax_query']
		);
	}
}
