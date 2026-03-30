<?php
/**
 * Tests for data-validation.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class DataValidationTest extends TestCase {

	// ─── upkeepify_validate_task_meta ────────────────────────────────────────

	public function test_validate_task_meta_returns_error_for_unknown_key() {
		$result = upkeepify_validate_task_meta( 'totally_unknown_key', 'value' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_meta_key', $result->get_error_code() );
	}

	// nearest_unit
	public function test_validate_nearest_unit_accepts_positive_integer() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_NEAREST_UNIT, 5 ) );
	}

	public function test_validate_nearest_unit_accepts_numeric_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_NEAREST_UNIT, '12' ) );
	}

	public function test_validate_nearest_unit_rejects_zero() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_NEAREST_UNIT, 0 );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_nearest_unit', $result->get_error_code() );
	}

	public function test_validate_nearest_unit_rejects_non_numeric() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_NEAREST_UNIT, 'abc' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_nearest_unit_rejects_negative() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_NEAREST_UNIT, -1 );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// gps_latitude
	public function test_validate_latitude_accepts_valid_value() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '-33.865143' ) );
	}

	public function test_validate_latitude_accepts_empty_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '' ) );
	}

	public function test_validate_latitude_rejects_non_numeric() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, 'north' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_latitude', $result->get_error_code() );
	}

	public function test_validate_latitude_rejects_out_of_range() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '91' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_latitude_rejects_below_minus_90() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '-91' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_latitude_accepts_boundary_values() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '90' ) );
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LATITUDE, '-90' ) );
	}

	// gps_longitude
	public function test_validate_longitude_accepts_valid_value() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, '151.209900' ) );
	}

	public function test_validate_longitude_accepts_empty_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, '' ) );
	}

	public function test_validate_longitude_rejects_non_numeric() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, 'east' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_longitude', $result->get_error_code() );
	}

	public function test_validate_longitude_rejects_out_of_range() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, '181' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_longitude_accepts_boundary_values() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, '180' ) );
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_GPS_LONGITUDE, '-180' ) );
	}

	// rough_estimate
	public function test_validate_rough_estimate_accepts_valid_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, '150-250' ) );
	}

	public function test_validate_rough_estimate_accepts_empty_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, '' ) );
	}

	public function test_validate_rough_estimate_rejects_too_long() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, str_repeat( 'x', 51 ) );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_rough_estimate', $result->get_error_code() );
	}

	public function test_validate_rough_estimate_accepts_exactly_50_chars() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ROUGH_ESTIMATE, str_repeat( 'x', 50 ) ) );
	}

	// tokens
	public function test_validate_token_accepts_valid_token() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, 'a8B3kLm9pQ2rS7tU1vWx' ) );
	}

	public function test_validate_token_rejects_empty_string() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, '' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_token', $result->get_error_code() );
	}

	public function test_validate_token_rejects_too_short() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, 'abc123' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_token_accepts_exactly_8_chars() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN, 'abcd1234' ) );
	}

	public function test_validate_response_token_same_rules() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_RESPONSE_TOKEN, '' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_RESPONSE_TOKEN, 'validtoken123' ) );
	}

	// due_date
	public function test_validate_due_date_accepts_valid_date() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_DUE_DATE, '2026-12-31' ) );
	}

	public function test_validate_due_date_accepts_empty_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_DUE_DATE, '' ) );
	}

	public function test_validate_due_date_rejects_invalid_date() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_DUE_DATE, 'not-a-date' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_due_date', $result->get_error_code() );
	}

	public function test_validate_due_date_accepts_datetime_string() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_DUE_DATE, '2026-01-01 10:00:00' ) );
	}

	// assigned_service_provider
	public function test_validate_assigned_provider_accepts_empty() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, '' ) );
	}

	public function test_validate_assigned_provider_accepts_positive_id() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, 5 ) );
	}

	public function test_validate_assigned_provider_rejects_zero_id() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER, 0 );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// response_task_id / provider_id
	public function test_validate_response_task_id_accepts_positive_integer() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, 101 ) );
	}

	public function test_validate_response_task_id_rejects_zero() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_RESPONSE_TASK_ID, 0 );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_validate_provider_id_accepts_positive_integer() {
		$this->assertTrue( upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_PROVIDER_ID, 55 ) );
	}

	public function test_validate_provider_id_rejects_non_numeric() {
		$result = upkeepify_validate_task_meta( UPKEEPIFY_META_KEY_PROVIDER_ID, 'abc' );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// ─── upkeepify_validate_maintenance_task ─────────────────────────────────

	public function test_validate_task_accepts_valid_data() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => 'Leaking tap in bathroom',
			'post_content' => 'The tap in unit 5 has been dripping for two days.',
		] );
		$this->assertTrue( $result );
	}

	public function test_validate_task_rejects_non_array() {
		$result = upkeepify_validate_maintenance_task( 'not an array' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_task_data', $result->get_error_code() );
	}

	public function test_validate_task_rejects_empty_title() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => '',
			'post_content' => 'Some description.',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_task_title', $result->get_error_code() );
	}

	public function test_validate_task_rejects_whitespace_only_title() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => '   ',
			'post_content' => 'Some description.',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_task_title', $result->get_error_code() );
	}

	public function test_validate_task_rejects_title_too_long() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => str_repeat( 'x', 201 ),
			'post_content' => 'Some description.',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_task_title', $result->get_error_code() );
	}

	public function test_validate_task_rejects_empty_content() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => 'Valid Title',
			'post_content' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_task_content', $result->get_error_code() );
	}

	public function test_validate_task_validates_meta_fields() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => 'Valid Title',
			'post_content' => 'Valid content.',
			'meta'         => [
				UPKEEPIFY_META_KEY_GPS_LATITUDE => '999', // out of range
			],
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_latitude', $result->get_error_code() );
	}

	public function test_validate_task_accepts_valid_meta() {
		$result = upkeepify_validate_maintenance_task( [
			'post_title'   => 'Valid Title',
			'post_content' => 'Valid content.',
			'meta'         => [
				UPKEEPIFY_META_KEY_GPS_LATITUDE  => '-33.865',
				UPKEEPIFY_META_KEY_GPS_LONGITUDE => '18.423',
				UPKEEPIFY_META_KEY_NEAREST_UNIT  => 3,
			],
		] );
		$this->assertTrue( $result );
	}

	// ─── upkeepify_validate_provider ─────────────────────────────────────────

	public function test_validate_provider_accepts_valid_data() {
		$result = upkeepify_validate_provider( [
			'name'  => 'Handyman Heroes',
			'email' => 'info@handyman.co.za',
			'phone' => '+27 21 555 1234',
		] );
		$this->assertTrue( $result );
	}

	public function test_validate_provider_rejects_non_array() {
		$result = upkeepify_validate_provider( 'not an array' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_provider_data', $result->get_error_code() );
	}

	public function test_validate_provider_rejects_empty_name() {
		$result = upkeepify_validate_provider( [ 'name' => '   ' ] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_provider_name', $result->get_error_code() );
	}

	public function test_validate_provider_rejects_invalid_email() {
		$result = upkeepify_validate_provider( [
			'name'  => 'Handyman Heroes',
			'email' => 'not-an-email',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_provider_email', $result->get_error_code() );
	}

	public function test_validate_provider_accepts_empty_email() {
		$result = upkeepify_validate_provider( [
			'name'  => 'Handyman Heroes',
			'email' => '',
		] );
		$this->assertTrue( $result );
	}

	public function test_validate_provider_rejects_phone_too_long() {
		$result = upkeepify_validate_provider( [
			'name'  => 'Handyman Heroes',
			'phone' => str_repeat( '1', 51 ),
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_provider_phone', $result->get_error_code() );
	}

	public function test_validate_provider_rejects_non_array_categories() {
		$result = upkeepify_validate_provider( [
			'name'                  => 'Handyman Heroes',
			'associated_categories' => 'not-an-array',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_provider_categories', $result->get_error_code() );
	}

	public function test_validate_provider_accepts_valid_category_ids() {
		$result = upkeepify_validate_provider( [
			'name'                  => 'Handyman Heroes',
			'associated_categories' => [ 1, 2, 5 ],
		] );
		$this->assertTrue( $result );
	}

	public function test_validate_provider_rejects_invalid_category_id() {
		$result = upkeepify_validate_provider( [
			'name'                  => 'Handyman Heroes',
			'associated_categories' => [ 1, 0, 5 ], // 0 is invalid
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// ─── upkeepify_validate_settings ─────────────────────────────────────────

	public function test_validate_settings_rejects_non_array() {
		$result = upkeepify_validate_settings( 'not an array' );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_settings', $result->get_error_code() );
	}

	public function test_validate_settings_returns_sanitized_array() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_NOTIFY_OPTION => 1,
			UPKEEPIFY_SETTING_CURRENCY      => '$',
		] );
		$this->assertIsArray( $result );
		$this->assertSame( 1, $result[ UPKEEPIFY_SETTING_NOTIFY_OPTION ] );
		$this->assertSame( '$', $result[ UPKEEPIFY_SETTING_CURRENCY ] );
	}

	public function test_validate_settings_casts_boolean_fields_to_int() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_SMTP_OPTION => true,
		] );
		$this->assertIsArray( $result );
		$this->assertSame( 1, $result[ UPKEEPIFY_SETTING_SMTP_OPTION ] );
	}

	public function test_validate_settings_empty_value_gives_zero_for_boolean_field() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_NOTIFY_OPTION => '',
		] );
		$this->assertIsArray( $result );
		$this->assertSame( 0, $result[ UPKEEPIFY_SETTING_NOTIFY_OPTION ] );
	}

	public function test_validate_settings_rejects_invalid_email() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => 'bad-email',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_override_email', $result->get_error_code() );
	}

	public function test_validate_settings_accepts_empty_override_email() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => '',
		] );
		$this->assertIsArray( $result );
	}

	public function test_validate_settings_accepts_valid_override_email() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_OVERRIDE_EMAIL => 'admin@example.com',
		] );
		$this->assertIsArray( $result );
		$this->assertSame( 'admin@example.com', $result[ UPKEEPIFY_SETTING_OVERRIDE_EMAIL ] );
	}

	public function test_validate_settings_rejects_negative_unit_count() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_NUMBER_OF_UNITS => -1,
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_number_of_units', $result->get_error_code() );
	}

	public function test_validate_settings_accepts_zero_units() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_NUMBER_OF_UNITS => 0,
		] );
		$this->assertIsArray( $result );
		$this->assertSame( 0, $result[ UPKEEPIFY_SETTING_NUMBER_OF_UNITS ] );
	}

	public function test_validate_settings_rejects_currency_too_long() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_CURRENCY => 'TOOLONGVAL',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upkeepify_invalid_currency', $result->get_error_code() );
	}

	public function test_validate_settings_accepts_empty_array() {
		$result = upkeepify_validate_settings( [] );
		$this->assertIsArray( $result );
		$this->assertEmpty( $result );
	}

	public function test_validate_settings_strips_newlines_from_text_fields() {
		$result = upkeepify_validate_settings( [
			UPKEEPIFY_SETTING_SMTP_HOST => "mail.example.com\ninjected",
		] );
		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( "\n", $result[ UPKEEPIFY_SETTING_SMTP_HOST ] );
	}
}
