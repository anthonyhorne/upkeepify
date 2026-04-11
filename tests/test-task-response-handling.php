<?php
/**
 * Tests for provider response generation.
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/includes/task-response-handling.php';

class TaskResponseHandlingTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']        = [];
		$GLOBALS['_upkeepify_test_cache']          = [];
		$GLOBALS['_upkeepify_test_transients']     = [];
		$GLOBALS['_upkeepify_test_posts']          = [];
		$GLOBALS['_upkeepify_test_post_meta']      = [];
		$GLOBALS['_upkeepify_test_taxonomy_terms'] = [];
		$GLOBALS['_upkeepify_test_term_meta']      = [];
		$GLOBALS['_upkeepify_test_object_terms']   = [];
		$GLOBALS['_upkeepify_test_inserted_posts'] = [];
	}

	public function test_generates_provider_response_when_pending_task_is_later_published() {
		$task_id = 42;
		$task    = new WP_Post(
			[
				'ID'           => $task_id,
				'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
				'post_status'  => 'publish',
				'post_title'   => 'Leaking roof',
				'post_content' => 'Water is coming through the ceiling.',
			]
		);

		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => 7,
					'name'    => 'Roof Crew',
				]
			),
		];

		upkeepify_generate_provider_tokens( $task_id, $task, true );

		$this->assertCount( 1, $GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] );
		$this->assertSame( $task_id, get_post_meta( 1000, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, true ) );
		$this->assertSame( 7, get_post_meta( 1000, UPKEEPIFY_META_KEY_PROVIDER_ID, true ) );
	}

	public function test_does_not_duplicate_provider_response_on_later_published_task_update() {
		$task_id = 42;
		$task    = new WP_Post(
			[
				'ID'           => $task_id,
				'post_type'    => UPKEEPIFY_POST_TYPE_MAINTENANCE_TASKS,
				'post_status'  => 'publish',
				'post_title'   => 'Leaking roof',
				'post_content' => 'Water is coming through the ceiling.',
			]
		);

		$existing_response = new WP_Post(
			[
				'ID'          => 501,
				'post_type'   => UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES,
				'post_status' => 'draft',
			]
		);

		$GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] = [ $existing_response ];
		update_post_meta( 501, UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, $task_id );

		$GLOBALS['_upkeepify_test_taxonomy_terms'][ UPKEEPIFY_TAXONOMY_SERVICE_PROVIDER ] = [
			new WP_Term(
				[
					'term_id' => 7,
					'name'    => 'Roof Crew',
				]
			),
		];

		upkeepify_generate_provider_tokens( $task_id, $task, true );

		$this->assertCount( 1, $GLOBALS['_upkeepify_test_posts'][ UPKEEPIFY_POST_TYPE_PROVIDER_RESPONSES ] );
		$this->assertSame( [], $GLOBALS['_upkeepify_test_inserted_posts'] );
	}
}
