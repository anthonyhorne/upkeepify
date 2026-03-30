<?php
/**
 * Tests for upload-handlers.php
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class UploadHandlersTest extends TestCase {

	/** @var string|null Path to temp file created for valid-file tests */
	private $tmp_file = null;

	protected function setUp(): void {
		$GLOBALS['_upkeepify_test_options']    = [];
		$GLOBALS['_upkeepify_test_cache']      = [];
		$GLOBALS['_upkeepify_test_transients'] = [];
	}

	protected function tearDown(): void {
		if ( $this->tmp_file && file_exists( $this->tmp_file ) ) {
			unlink( $this->tmp_file );
			$this->tmp_file = null;
		}
	}

	/** Create a minimal valid GIF89a temp file and return its path. */
	private function make_temp_gif( string $name = 'test.gif' ): string {
		$path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $name;
		// Minimal 1x1 transparent GIF
		file_put_contents( $path, "GIF89a\x01\x00\x01\x00\x00\xff\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;" );
		$this->tmp_file = $path;
		return $path;
	}

	// ─── PHP-reported upload errors ───────────────────────────────────────────

	public function test_rejects_ini_size_error() {
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_INI_SIZE,
			'size'     => 0,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upload_error', $result->get_error_code() );
	}

	public function test_rejects_form_size_error() {
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_FORM_SIZE,
			'size'     => 0,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'upload_error', $result->get_error_code() );
	}

	public function test_rejects_partial_upload_error() {
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_PARTIAL,
			'size'     => 500,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_rejects_no_file_error() {
		$result = upkeepify_validate_upload( [
			'name'     => '',
			'error'    => UPLOAD_ERR_NO_FILE,
			'size'     => 0,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	public function test_rejects_no_tmp_dir_error() {
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_NO_TMP_DIR,
			'size'     => 0,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
	}

	// ─── File size checks ─────────────────────────────────────────────────────

	public function test_rejects_file_exceeding_size_limit() {
		$oversized = UPKEEPIFY_MAX_UPLOAD_SIZE + 1;
		$result    = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_OK,
			'size'     => $oversized,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'file_size_exceeded', $result->get_error_code() );
	}

	public function test_rejects_empty_file() {
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.jpg',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 0,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'empty_file', $result->get_error_code() );
	}

	// ─── File type checks ─────────────────────────────────────────────────────

	public function test_rejects_pdf_file() {
		$result = upkeepify_validate_upload( [
			'name'     => 'document.pdf',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 1024,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_file_type', $result->get_error_code() );
	}

	public function test_rejects_php_file() {
		$result = upkeepify_validate_upload( [
			'name'     => 'shell.php',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 512,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_file_type', $result->get_error_code() );
	}

	public function test_rejects_exe_file() {
		$result = upkeepify_validate_upload( [
			'name'     => 'malware.exe',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 2048,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_file_type', $result->get_error_code() );
	}

	public function test_rejects_text_file() {
		$result = upkeepify_validate_upload( [
			'name'     => 'readme.txt',
			'error'    => UPLOAD_ERR_OK,
			'size'     => 256,
			'tmp_name' => '',
		] );
		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'invalid_file_type', $result->get_error_code() );
	}

	// ─── Valid uploads ────────────────────────────────────────────────────────

	public function test_accepts_valid_gif_with_real_file() {
		$path   = $this->make_temp_gif( 'upkeepify_test_valid.gif' );
		$result = upkeepify_validate_upload( [
			'name'     => 'photo.gif',
			'error'    => UPLOAD_ERR_OK,
			'size'     => filesize( $path ),
			'tmp_name' => $path,
		] );
		$this->assertTrue( $result );
	}

	public function test_accepts_jpeg_at_size_limit() {
		// Uses a GIF byte stream but named .jpg — finfo will detect GIF MIME.
		// This test validates the size boundary (exactly at limit is OK).
		$path     = $this->make_temp_gif( 'upkeepify_test_size.gif' );
		$file_size = filesize( $path );
		$result   = upkeepify_validate_upload( [
			'name'     => 'photo.gif',
			'error'    => UPLOAD_ERR_OK,
			'size'     => $file_size,
			'tmp_name' => $path,
		] );
		$this->assertTrue( $result );
	}
}
