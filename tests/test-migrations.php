<?php
/**
 * Tests for migrations.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class MigrationsTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
	}

	// ─── upkeepify_get_default_settings ──────────────────────────────────────

	public function test_get_default_settings_returns_array() {
		$defaults = upkeepify_get_default_settings();
		$this->assertIsArray( $defaults );
	}

	public function test_get_default_settings_contains_all_expected_keys() {
		$defaults      = upkeepify_get_default_settings();
		$expected_keys = [
			UPKEEPIFY_SETTING_SMTP_OPTION,
			UPKEEPIFY_SETTING_SMTP_HOST,
			UPKEEPIFY_SETTING_NOTIFY_OPTION,
			UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK,
			UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING,
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL,
			UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE,
			UPKEEPIFY_SETTING_NUMBER_OF_UNITS,
			UPKEEPIFY_SETTING_CURRENCY,
			UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE,
			UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL,
			UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE,
			UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE,
			UPKEEPIFY_SETTING_NOTIFY_CONTRACTOR_ON_RESIDENT_ISSUE,
		];
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $defaults, "Missing default setting: $key" );
		}
	}

	public function test_get_default_settings_smtp_is_disabled_by_default() {
		$defaults = upkeepify_get_default_settings();
		$this->assertSame( 0, $defaults[ UPKEEPIFY_SETTING_SMTP_OPTION ] );
	}

	public function test_get_default_settings_notifications_enabled_by_default() {
		$defaults = upkeepify_get_default_settings();
		$this->assertSame( 1, $defaults[ UPKEEPIFY_SETTING_NOTIFY_OPTION ] );
	}

	public function test_get_default_settings_currency_is_dollar() {
		$defaults = upkeepify_get_default_settings();
		$this->assertSame( '$', $defaults[ UPKEEPIFY_SETTING_CURRENCY ] );
	}

	public function test_get_default_settings_number_of_units_is_10() {
		$defaults = upkeepify_get_default_settings();
		$this->assertSame( 10, $defaults[ UPKEEPIFY_SETTING_NUMBER_OF_UNITS ] );
	}

	public function test_get_default_settings_string_fields_are_empty_strings() {
		$defaults      = upkeepify_get_default_settings();
		$string_fields = [
			UPKEEPIFY_SETTING_SMTP_HOST,
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL,
			UPKEEPIFY_SETTING_THANK_YOU_PAGE_URL,
			UPKEEPIFY_SETTING_PROVIDER_RESPONSE_PAGE,
			UPKEEPIFY_SETTING_RESIDENT_CONFIRMATION_PAGE,
		];
		foreach ( $string_fields as $field ) {
			$this->assertSame( '', $defaults[ $field ], "Field $field should default to empty string" );
		}
	}

	public function test_get_default_settings_boolean_fields_default_to_zero() {
		$defaults       = upkeepify_get_default_settings();
		$boolean_fields = [
			UPKEEPIFY_SETTING_SMTP_OPTION,
			UPKEEPIFY_SETTING_PROVIDER_DELETE_TASK,
			UPKEEPIFY_SETTING_PUBLIC_TASK_LOGGING,
			UPKEEPIFY_SETTING_ENABLE_TOKEN_UPDATE,
			UPKEEPIFY_SETTING_ENABLE_THANK_YOU_PAGE,
		];
		foreach ( $boolean_fields as $field ) {
			$this->assertSame( 0, $defaults[ $field ], "Field $field should default to 0" );
		}
	}

	public function test_get_default_settings_contractor_issue_notifications_enabled_by_default() {
		$defaults = upkeepify_get_default_settings();
		$this->assertSame( 1, $defaults[ UPKEEPIFY_SETTING_NOTIFY_CONTRACTOR_ON_RESIDENT_ISSUE ] );
	}

	// ─── upkeepify_get_current_db_version ────────────────────────────────────

	public function test_get_current_db_version_returns_1_when_not_set() {
		// No DB version stored
		$version = upkeepify_get_current_db_version();
		$this->assertSame( 1, $version );
	}

	public function test_get_current_db_version_returns_stored_version() {
		update_option( UPKEEPIFY_OPTION_DB_VERSION, 2 );
		$version = upkeepify_get_current_db_version();
		$this->assertSame( 2, $version );
	}

	public function test_get_current_db_version_returns_integer() {
		update_option( UPKEEPIFY_OPTION_DB_VERSION, '2' );
		$version = upkeepify_get_current_db_version();
		$this->assertIsInt( $version );
	}

	public function test_get_current_db_version_returns_0_for_negative_stored_value() {
		update_option( UPKEEPIFY_OPTION_DB_VERSION, -5 );
		$version = upkeepify_get_current_db_version();
		$this->assertSame( 0, $version );
	}

	// ─── upkeepify_set_current_db_version ────────────────────────────────────

	public function test_set_current_db_version_returns_true() {
		$result = upkeepify_set_current_db_version( 2 );
		$this->assertTrue( $result );
	}

	public function test_set_current_db_version_stores_the_version() {
		upkeepify_set_current_db_version( 2 );
		$this->assertSame( 2, upkeepify_get_current_db_version() );
	}

	public function test_set_current_db_version_casts_to_integer() {
		upkeepify_set_current_db_version( '3' );
		$stored = get_option( UPKEEPIFY_OPTION_DB_VERSION );
		$this->assertSame( 3, $stored );
	}

	// ─── upkeepify_migration_log ─────────────────────────────────────────────

	public function test_migration_log_stores_message() {
		upkeepify_migration_log( 'Test migration message' );

		$log = get_option( UPKEEPIFY_OPTION_MIGRATION_LOG, [] );
		$this->assertIsArray( $log );
		$this->assertNotEmpty( $log );

		$last_entry = end( $log );
		$this->assertStringContainsString( 'Test migration message', $last_entry );
	}

	public function test_migration_log_includes_timestamp() {
		upkeepify_migration_log( 'Timestamped entry' );

		$log        = get_option( UPKEEPIFY_OPTION_MIGRATION_LOG, [] );
		$last_entry = end( $log );
		// Timestamp format is [YYYY-MM-DD HH:MM:SS]
		$this->assertMatchesRegularExpression( '/\[\d{4}-\d{2}-\d{2}/', $last_entry );
	}

	public function test_migration_log_includes_context_as_json() {
		upkeepify_migration_log( 'Message with context', [ 'key' => 'value' ] );

		$log        = get_option( UPKEEPIFY_OPTION_MIGRATION_LOG, [] );
		$last_entry = end( $log );
		$this->assertStringContainsString( '"key"', $last_entry );
		$this->assertStringContainsString( '"value"', $last_entry );
	}

	public function test_migration_log_trims_to_200_entries() {
		// Pre-fill with 205 entries
		$log = [];
		for ( $i = 0; $i < 205; $i++ ) {
			$log[] = "[2026-01-01 00:00:00] Entry $i";
		}
		update_option( UPKEEPIFY_OPTION_MIGRATION_LOG, $log );

		upkeepify_migration_log( 'Overflow entry' );

		$stored = get_option( UPKEEPIFY_OPTION_MIGRATION_LOG );
		$this->assertLessThanOrEqual( 200, count( $stored ) );
	}

	// ─── upkeepify_append_migration_history ──────────────────────────────────

	public function test_append_migration_history_adds_entry() {
		$entry = [ 'version' => 2, 'applied_at' => '2026-01-01' ];
		upkeepify_append_migration_history( $entry );

		$history = get_option( UPKEEPIFY_OPTION_MIGRATION_HISTORY, [] );
		$this->assertIsArray( $history );
		$this->assertCount( 1, $history );
		$this->assertSame( $entry, $history[0] );
	}

	public function test_append_migration_history_appends_to_existing() {
		update_option( UPKEEPIFY_OPTION_MIGRATION_HISTORY, [
			[ 'version' => 1, 'applied_at' => '2025-01-01' ],
		] );

		upkeepify_append_migration_history( [ 'version' => 2, 'applied_at' => '2026-01-01' ] );

		$history = get_option( UPKEEPIFY_OPTION_MIGRATION_HISTORY );
		$this->assertCount( 2, $history );
	}
}
