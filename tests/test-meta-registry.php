<?php
/**
 * Tests for meta-registry.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class MetaRegistryTest extends TestCase {

	// ─── upkeepify_get_meta_field_registry ───────────────────────────────────

	public function test_registry_returns_array() {
		$registry = upkeepify_get_meta_field_registry();
		$this->assertIsArray( $registry );
	}

	public function test_registry_is_not_empty() {
		$registry = upkeepify_get_meta_field_registry();
		$this->assertNotEmpty( $registry );
	}

	public function test_registry_contains_all_expected_meta_keys() {
		$registry     = upkeepify_get_meta_field_registry();
		$expected_keys = [
			UPKEEPIFY_META_KEY_NEAREST_UNIT,
			UPKEEPIFY_META_KEY_ROUGH_ESTIMATE,
			UPKEEPIFY_META_KEY_GPS_LATITUDE,
			UPKEEPIFY_META_KEY_GPS_LONGITUDE,
			UPKEEPIFY_META_KEY_TASK_UPDATE_TOKEN,
			UPKEEPIFY_META_KEY_ASSIGNED_SERVICE_PROVIDER,
			UPKEEPIFY_META_KEY_DUE_DATE,
			UPKEEPIFY_META_KEY_RESPONSE_TASK_ID,
			UPKEEPIFY_META_KEY_PROVIDER_ID,
			UPKEEPIFY_META_KEY_RESPONSE_TOKEN,
		];
		foreach ( $expected_keys as $key ) {
			$this->assertArrayHasKey( $key, $registry, "Registry missing meta key: $key" );
		}
	}

	public function test_each_registry_entry_has_required_fields() {
		$registry        = upkeepify_get_meta_field_registry();
		$required_fields = [ 'constant', 'type', 'default', 'description', 'examples', 'used_by', 'validation' ];

		foreach ( $registry as $meta_key => $definition ) {
			foreach ( $required_fields as $field ) {
				$this->assertArrayHasKey(
					$field,
					$definition,
					"Meta key '$meta_key' is missing field '$field'"
				);
			}
		}
	}

	public function test_each_registry_entry_has_non_empty_description() {
		$registry = upkeepify_get_meta_field_registry();
		foreach ( $registry as $meta_key => $definition ) {
			$this->assertNotEmpty(
				$definition['description'],
				"Meta key '$meta_key' has empty description"
			);
		}
	}

	public function test_each_registry_entry_has_at_least_one_example() {
		$registry = upkeepify_get_meta_field_registry();
		foreach ( $registry as $meta_key => $definition ) {
			$this->assertIsArray( $definition['examples'], "Meta key '$meta_key' examples should be array" );
			$this->assertNotEmpty( $definition['examples'], "Meta key '$meta_key' has no examples" );
		}
	}

	public function test_each_registry_entry_has_used_by_array() {
		$registry = upkeepify_get_meta_field_registry();
		foreach ( $registry as $meta_key => $definition ) {
			$this->assertIsArray( $definition['used_by'], "Meta key '$meta_key' used_by should be array" );
		}
	}

	public function test_nearest_unit_has_integer_type() {
		$registry = upkeepify_get_meta_field_registry();
		$this->assertSame( 'integer', $registry[ UPKEEPIFY_META_KEY_NEAREST_UNIT ]['type'] );
	}

	public function test_gps_fields_have_string_type() {
		$registry = upkeepify_get_meta_field_registry();
		$this->assertSame( 'string', $registry[ UPKEEPIFY_META_KEY_GPS_LATITUDE ]['type'] );
		$this->assertSame( 'string', $registry[ UPKEEPIFY_META_KEY_GPS_LONGITUDE ]['type'] );
	}

	public function test_response_task_id_has_integer_type() {
		$registry = upkeepify_get_meta_field_registry();
		$this->assertSame( 'integer', $registry[ UPKEEPIFY_META_KEY_RESPONSE_TASK_ID ]['type'] );
	}

	// ─── upkeepify_get_meta_field_definition ─────────────────────────────────

	public function test_get_definition_returns_array_for_known_key() {
		$definition = upkeepify_get_meta_field_definition( UPKEEPIFY_META_KEY_NEAREST_UNIT );
		$this->assertIsArray( $definition );
	}

	public function test_get_definition_returns_null_for_unknown_key() {
		$definition = upkeepify_get_meta_field_definition( 'totally_unknown_meta_key' );
		$this->assertNull( $definition );
	}

	public function test_get_definition_returns_correct_entry() {
		$definition = upkeepify_get_meta_field_definition( UPKEEPIFY_META_KEY_GPS_LATITUDE );
		$this->assertSame( 'UPKEEPIFY_META_KEY_GPS_LATITUDE', $definition['constant'] );
		$this->assertSame( 'string', $definition['type'] );
	}

	public function test_get_definition_returns_null_for_empty_string() {
		$definition = upkeepify_get_meta_field_definition( '' );
		$this->assertNull( $definition );
	}

	public function test_all_registry_constants_resolve_to_strings() {
		$registry = upkeepify_get_meta_field_registry();
		foreach ( array_keys( $registry ) as $meta_key ) {
			$this->assertIsString( $meta_key, "Registry key should be a string constant" );
			$this->assertNotEmpty( $meta_key );
		}
	}
}
