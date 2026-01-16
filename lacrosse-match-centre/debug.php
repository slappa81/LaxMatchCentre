<?php
/**
 * Debug script to test plugin loading
 * 
 * Access this file directly to see what errors occur during plugin loading
 */

// Simulate WordPress environment
define('ABSPATH', true);
define('WPINC', 'wp-includes');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Plugin Debug Test</h1>";
echo "<pre>";

// Mock WordPress functions
if (!function_exists('plugin_dir_path')) {
    function plugin_dir_path($file) {
        return dirname($file) . '/';
    }
}

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url($file) {
        return 'http://example.com/wp-content/plugins/' . basename(dirname($file)) . '/';
    }
}

if (!function_exists('wp_mkdir_p')) {
    function wp_mkdir_p($dir) {
        return mkdir($dir, 0755, true);
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        return $default;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value) {
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $accepted_args = 1) {
        return true;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook($file, $callback) {
        return true;
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook($file, $callback) {
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin() {
        return false;
    }
}

if (!function_exists('register_widget')) {
    function register_widget($widget_class) {
        return true;
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style($handle, $src = '', $deps = array(), $ver = false, $media = 'all') {
        return true;
    }
}

echo "Testing plugin file loading...\n\n";

try {
    require_once 'lacrosse-match-centre.php';
    echo "✓ Main plugin file loaded successfully!\n";
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n</pre>";
