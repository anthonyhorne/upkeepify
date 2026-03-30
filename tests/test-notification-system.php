<?php
/**
 * Tests for notification-system.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class NotificationSystemTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
	}

	// ─── upkeepify_add_notification ──────────────────────────────────────────

	public function test_add_notification_returns_true_on_success() {
		$result = upkeepify_add_notification( 'Task updated successfully.' );
		$this->assertTrue( $result );
	}

	public function test_add_notification_stores_notification_in_options() {
		upkeepify_add_notification( 'Test message', 'success' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertIsArray( $notifications );
		$this->assertCount( 1, $notifications );
		$this->assertSame( 'success', $notifications[0]['type'] );
	}

	public function test_add_notification_returns_false_for_empty_message() {
		$result = upkeepify_add_notification( '' );
		$this->assertFalse( $result );
	}

	public function test_add_notification_normalizes_invalid_type_to_info() {
		upkeepify_add_notification( 'A message', 'totally_invalid_type' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertSame( 'info', $notifications[0]['type'] );
	}

	public function test_add_notification_accepts_all_valid_types() {
		foreach ( [ 'success', 'error', 'warning', 'info' ] as $type ) {
			$GLOBALS['_upkeepify_test_options']    = [];
			$GLOBALS['_upkeepify_test_cache']      = [];
			$GLOBALS['_upkeepify_test_transients'] = [];

			$result = upkeepify_add_notification( 'Message', $type );
			$this->assertTrue( $result, "Type '$type' should be accepted" );

			$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
			$this->assertSame( $type, $notifications[0]['type'] );
		}
	}

	public function test_add_notification_appends_to_existing_notifications() {
		upkeepify_add_notification( 'First', 'success' );

		// Clear cache so second call reads from option store
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		upkeepify_add_notification( 'Second', 'error' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertCount( 2, $notifications );
	}

	public function test_add_notification_strips_html_from_message() {
		upkeepify_add_notification( '<script>alert("xss")</script>Task done.', 'success' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertStringNotContainsString( '<script>', $notifications[0]['message'] );
	}

	public function test_add_notification_includes_timestamp() {
		upkeepify_add_notification( 'Test', 'info' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertArrayHasKey( 'timestamp', $notifications[0] );
		$this->assertNotEmpty( $notifications[0]['timestamp'] );
	}

	public function test_add_notification_includes_additional_data() {
		$data = [ 'task_id' => 42, 'status' => 'completed' ];
		upkeepify_add_notification( 'Test', 'success', $data );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertSame( $data, $notifications[0]['data'] );
	}

	public function test_add_notification_limits_queue_to_50() {
		// Pre-fill with 55 fake notifications
		$fake = [];
		for ( $i = 0; $i < 55; $i++ ) {
			$fake[] = [ 'message' => "Notification $i", 'type' => 'info', 'data' => [], 'timestamp' => '' ];
		}
		update_option( UPKEEPIFY_OPTION_NOTIFICATIONS, $fake );

		// Clear cache so the next call reads the fake data
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		upkeepify_add_notification( 'New message', 'success' );

		$notifications = get_option( UPKEEPIFY_OPTION_NOTIFICATIONS );
		$this->assertLessThanOrEqual( 50, count( $notifications ) );
	}

	// ─── upkeepify_send_email_notification ───────────────────────────────────

	public function test_send_email_returns_false_for_empty_message() {
		$result = upkeepify_send_email_notification( '', 'success' );
		$this->assertFalse( $result );
	}

	public function test_send_email_returns_false_when_notifications_disabled() {
		// Default settings have notifications disabled
		$result = upkeepify_send_email_notification( 'Task updated.', 'success' );
		$this->assertFalse( $result );
	}

	public function test_send_email_returns_false_with_invalid_recipient() {
		// Enable notifications but set invalid email
		update_option( UPKEEPIFY_OPTION_SETTINGS, [
			UPKEEPIFY_SETTING_NOTIFY_OPTION  => 1,
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => 'not-an-email',
		] );
		// Seed admin_email with an invalid value too
		update_option( 'admin_email', 'invalid' );

		// Clear cache
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		$result = upkeepify_send_email_notification( 'Task updated.', 'success' );
		$this->assertFalse( $result );
	}

	public function test_send_email_returns_true_when_enabled_with_valid_recipient() {
		update_option( UPKEEPIFY_OPTION_SETTINGS, [
			UPKEEPIFY_SETTING_NOTIFY_OPTION  => 1,
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => 'admin@example.com',
		] );

		// Clear cache so settings are read fresh
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		$result = upkeepify_send_email_notification( 'Task updated.', 'success' );
		$this->assertTrue( $result );
	}

	public function test_send_email_uses_admin_email_when_no_override() {
		update_option( UPKEEPIFY_OPTION_SETTINGS, [
			UPKEEPIFY_SETTING_NOTIFY_OPTION  => 1,
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => '',
		] );
		update_option( 'admin_email', 'site-admin@example.com' );

		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];

		$result = upkeepify_send_email_notification( 'Task updated.', 'success' );
		$this->assertTrue( $result );
	}
}
