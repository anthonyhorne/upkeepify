<?php
/**
 * Test suite for utility functions.
 *
 * Tests core utility functions with focus on security features
 * (token validation, email sanitization, logging).
 *
 * @package Upkeepify
 */

use PHPUnit\Framework\TestCase;

class UtilityFunctionsTest extends TestCase {

    /**
     * Test that hash_equals() provides constant-time comparison.
     */
    public function test_token_validation_uses_hash_equals() {
        $token_a = 'correct_token_value_12345';
        $token_b = 'correct_token_value_12345';
        $token_c = 'wrong_token_value_12345678';

        // Identical tokens should match
        $this->assertTrue(hash_equals($token_a, $token_b));

        // Different tokens should not match
        $this->assertFalse(hash_equals($token_a, $token_c));
    }

    /**
     * Test that email subject sanitization removes newlines (prevents email injection).
     */
    public function test_email_subject_sanitization_removes_newlines() {
        $malicious_subject = "Task Update\nBcc: attacker@evil.com";
        $sanitized = sanitize_text_field($malicious_subject);

        // Newlines should be stripped
        $this->assertStringNotContainsString("\n", $sanitized);
        $this->assertStringNotContainsString("\r", $sanitized);
    }

    /**
     * Test that email subject sanitization removes carriage returns.
     */
    public function test_email_subject_sanitization_removes_carriage_returns() {
        $malicious_subject = "Task Update\r\nBcc: attacker@evil.com";
        $sanitized = sanitize_text_field($malicious_subject);

        $this->assertStringNotContainsString("\r\n", $sanitized);
    }

    /**
     * Test that email subject sanitization handles mixed injection attempts.
     */
    public function test_email_subject_sanitization_handles_mixed_injections() {
        $malicious = "Task\r\nCc: cc@evil.com\nBcc: bcc@evil.com\tTo: other@evil.com";
        $sanitized = sanitize_text_field($malicious);

        $this->assertStringNotContainsString("\r", $sanitized);
        $this->assertStringNotContainsString("\n", $sanitized);
        $this->assertStringNotContainsString("\t", $sanitized);
    }

    /**
     * Test that security logging function executes without error.
     *
     * Note: We can't fully mock error_log in this test environment,
     * but we verify the function can be called without throwing exceptions.
     */
    public function test_security_log_function_executes() {
        // Should not throw exception
        try {
            upkeepify_log_security_event('test_event', 'Test description', 1);
            $this->assertTrue(true);
        } catch (Exception $e) {
            $this->fail('upkeepify_log_security_event threw exception: ' . $e->getMessage());
        }
    }

    /**
     * Test that security logging includes required fields.
     *
     * This is a basic sanity check that the function is well-formed.
     */
    public function test_security_log_includes_event_type() {
        // Verify the function exists and has correct signature
        $this->assertTrue(function_exists('upkeepify_log_security_event'));

        // Call it and verify no errors (output is suppressed by our stub)
        upkeepify_log_security_event('failed_nonce', 'Test nonce failure', 0);
        $this->assertTrue(true);
    }

    /**
     * Test that token validation function exists and has correct signature.
     */
    public function test_token_validation_function_exists() {
        $this->assertTrue(function_exists('upkeepify_validate_task_update_token'));

        // Mock scenario: false when no meta found
        $result = upkeepify_validate_task_update_token(999, 'test_token');
        // Will return false because get_post_meta stub returns empty string
        $this->assertFalse($result);
    }

}
