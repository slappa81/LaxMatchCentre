<?php
/**
 * Debug script for blocks
 * Add this code temporarily to wp-config.php to enable debugging:
 * define('WP_DEBUG', true);
 * define('WP_DEBUG_LOG', true);
 * define('WP_DEBUG_DISPLAY', false);
 */

// Add this to class-lmc-blocks.php register_blocks method to debug:
error_log('LMC Blocks: register_blocks called');
error_log('LMC Blocks: Function register_block_type exists: ' . (function_exists('register_block_type') ? 'yes' : 'no'));

// Check if blocks are registered (add to admin page):
if (function_exists('get_dynamic_block_names')) {
    $blocks = get_dynamic_block_names();
    error_log('All registered blocks: ' . print_r($blocks, true));
}

// Check registered scripts (add to admin page):
global $wp_scripts;
if (isset($wp_scripts->registered['lacrosse-match-centre-blocks'])) {
    error_log('Block script registered: yes');
    error_log('Script src: ' . $wp_scripts->registered['lacrosse-match-centre-blocks']->src);
} else {
    error_log('Block script registered: no');
}
