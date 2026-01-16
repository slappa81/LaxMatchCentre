<?php
/**
 * WP-CLI commands for Lacrosse Match Centre
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manage Lacrosse Match Centre competitions via WP-CLI
 */
class LMC_CLI {
    
    /**
     * List all configured competitions with their IDs and friendly names.
     *
     * ## EXAMPLES
     *
     *     # List all competitions
     *     $ wp lmc list-competitions
     *     +----------------------+----------------------------------+
     *     | Competition ID       | Competition Name                 |
     *     +----------------------+----------------------------------+
     *     | 0-1064-96359-646414-0| Men's Premier League 2024        |
     *     | 0-1064-96359-646415-0| Women's Premier League 2024      |
     *     +----------------------+----------------------------------+
     *
     * @when after_wp_load
     */
    public function list_competitions($args, $assoc_args) {
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        $current_competition = isset($settings['current_competition']) ? $settings['current_competition'] : '';
        
        if (empty($competitions)) {
            WP_CLI::warning('No competitions configured.');
            WP_CLI::log('Add competitions via: Settings → Match Centre in WordPress admin.');
            return;
        }
        
        // Prepare data for table
        $table_data = array();
        foreach ($competitions as $comp) {
            $is_current = ($comp['id'] === $current_competition) ? ' (current)' : '';
            $table_data[] = array(
                'competition_id' => $comp['id'],
                'competition_name' => $comp['name'] . $is_current
            );
        }
        
        // Display as formatted table
        WP_CLI\Utils\format_items('table', $table_data, array('competition_id', 'competition_name'));
        
        WP_CLI::success(sprintf('Found %d competition(s).', count($competitions)));
    }
    
    /**
     * Get details about a specific competition.
     *
     * ## OPTIONS
     *
     * <competition-id>
     * : The competition ID to get details for.
     *
     * ## EXAMPLES
     *
     *     # Get details for a specific competition
     *     $ wp lmc get-competition 0-1064-96359-646414-0
     *
     * @when after_wp_load
     */
    public function get_competition($args, $assoc_args) {
        if (empty($args[0])) {
            WP_CLI::error('Competition ID is required.');
        }
        
        $comp_id = $args[0];
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        $current_competition = isset($settings['current_competition']) ? $settings['current_competition'] : '';
        
        // Find the competition
        $found = false;
        $comp_name = '';
        foreach ($competitions as $comp) {
            if ($comp['id'] === $comp_id) {
                $found = true;
                $comp_name = $comp['name'];
                break;
            }
        }
        
        if (!$found) {
            WP_CLI::error(sprintf('Competition ID "%s" not found.', $comp_id));
        }
        
        // Get data file information
        $data_info = LMC_Data::get_data_info($comp_id);
        
        WP_CLI::log('Competition Details:');
        WP_CLI::log('-------------------');
        WP_CLI::log(sprintf('ID: %s', $comp_id));
        WP_CLI::log(sprintf('Name: %s', $comp_name));
        WP_CLI::log(sprintf('Is Current: %s', ($comp_id === $current_competition) ? 'Yes' : 'No'));
        WP_CLI::log(sprintf('Data Available: %s', $data_info['data_available'] ? 'Yes' : 'No'));
        WP_CLI::log('');
        WP_CLI::log('Data Files:');
        
        foreach ($data_info['files'] as $type => $file_info) {
            if ($file_info['exists']) {
                $modified = human_time_diff($file_info['modified'], current_time('timestamp', true));
                WP_CLI::log(sprintf('  ✓ %s: Last updated %s ago (%s)', 
                    ucfirst($type), 
                    $modified,
                    size_format($file_info['size'])
                ));
            } else {
                WP_CLI::log(sprintf('  ✗ %s: Not available', ucfirst($type)));
            }
        }
        
        WP_CLI::success('Competition details retrieved.');
    }
}
