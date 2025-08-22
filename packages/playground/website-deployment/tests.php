<?php
/**
 * Playground IP Utilities - Test Suite
 *
 * This script tests the key helper functions for IP manipulation:
 *  - playground_ip_to_a_64_subnet()
 *  - playground_get_ipv6_block()
 *
 * Why this is necessary:
 *  - Ensures IPv6 addresses are normalized correctly to /64 subnets.
 *  - Ensures IPv4 addresses are correctly converted to IPv6-mapped addresses when needed.
 *  - Validates error handling for invalid block sizes, preventing runtime issues.
 *
 * Usage: Run this script via CLI: `php test_playground_ip.php`
 */

require __DIR__ . '/cors-proxy-config.php'; // Import configuration and helper functions

/**
 * Assert that two values are strictly equal.
 *
 * This is necessary to verify that the function output matches expected results.
 *
 * @param mixed  $expected Expected value.
 * @param mixed  $actual   Actual value returned by the function.
 * @param string $message  Optional message to provide context in case of failure.
 */
function assert_equal($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        $message = $message ?: 'Test failed.';
        echo "$message\nExpected: $expected\nActual:   $actual\n";
        exit(1); // Exit with error code to indicate test failure
    }
}

/**
 * Assert that a callback throws an exception with the expected message.
 *
 * This is necessary to test that our functions handle invalid inputs correctly.
 *
 * @param string   $expected_message The exception message expected.
 * @param callable $callback         The function call expected to throw an exception.
 */
function assert_throws($expected_message, callable $callback) {
    try {
        $callback();
    } catch (Exception $e) {
        if ($e->getMessage() !== $expected_message) {
            echo "Test failed.\nExpected: $expected_message\nActual:   {$e->getMessage()}\n";
            exit(1);
        }
        return; // Test passed because the correct exception was thrown
    }

    echo "Test failed.\nExpected: $expected_message\nActual:   No exception was thrown\n";
    exit(1);
}

/**
 * Run all test cases.
 *
 * It is necessary to run tests systematically to ensure the reliability
 * of IP-related helper functions before deploying them in production.
 */
function run_tests() {
    echo "Running Playground IP helper tests...\n";

    // ------------------------------
    // Test 1: IPv6 /64 subnet normalization
    // ------------------------------
    assert_equal(
        '2607:B4C0:0000:0000:0000:0000:0000:0000',
        playground_ip_to_a_64_subnet('2607:B4C0:0000:0000:0000:0000:0000:0001'),
        'IPv6 was not correctly transformed into a /64 subnet'
    );

    assert_equal(
        '2607:B4C0:AAAA:BBBB:0000:0000:0000:0000',
        playground_ip_to_a_64_subnet('2607:B4C0:AAAA:BBBB:CCCC:DDDD:EEEE:FFFF'),
        'IPv6 was not correctly transformed into a /64 subnet'
    );

    // ------------------------------
    // Test 2: IPv4 compatibility (IPv4-mapped IPv6)
    // ------------------------------
    assert_equal(
        '::ffff:127.0.0.1',
        playground_ip_to_a_64_subnet('127.0.0.1', 64),
        'IPv4 address was not preserved correctly in IPv6-mapped form'
    );

    // ------------------------------
    // Test 3: Invalid block size - not multiple of 8
    // ------------------------------
    assert_throws(
        'Block size must be a multiple of 8.',
        function () {
            playground_get_ipv6_block(
                '2607:B4C0:AAAA:BBBB:CCCC:DDDD:EEEE:FFFF',
                7 // Invalid block size
            );
        }
    );

    // ------------------------------
    // Test 4: Invalid block size - greater than 128
    // ------------------------------
    assert_throws(
        'Block size must be less than or equal to 128.',
        function () {
            playground_get_ipv6_block(
                '2607:B4C0:AAAA:BBBB:CCCC:DDDD:EEEE:FFFF',
                136 // Invalid block size
            );
        }
    );

    echo "✅ All tests passed successfully.\n";
}

// Execute tests
run_tests();
