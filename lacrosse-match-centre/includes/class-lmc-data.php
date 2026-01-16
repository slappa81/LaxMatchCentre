<?php
/**
 * Data handler class for reading and caching JSON data
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Data class
 */
class LMC_Data {
    
    /**
     * Get cache duration from settings
     *
     * @return int Cache duration in seconds
     */
    private static function get_cache_duration() {
        $settings = get_option('lmc_settings', array());
        return isset($settings['cache_duration']) ? (int)$settings['cache_duration'] : 3600;
    }
    
    /**
     * Get current competition ID
     *
     * @return string|false Competition ID or false
     */
    private static function get_current_competition() {
        $settings = get_option('lmc_settings', array());
        return isset($settings['current_competition']) ? $settings['current_competition'] : false;
    }
    
    /**
     * Read JSON file
     *
     * @param string $filename Filename to read
     * @return array|false Data array or false on failure
     */
    private static function read_json_file($filename) {
        $filepath = LMC_DATA_DIR . $filename;
        
        if (!file_exists($filepath)) {
            return false;
        }
        
        $json = file_get_contents($filepath);
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('LMC Data: JSON decode error - ' . json_last_error_msg());
            return false;
        }
        
        return $data;
    }
    
    /**
     * Format date/time with timezone awareness
     *
     * @param string $date_string Date string from API
     * @param string $time_string Time string from API
     * @param string $format Output format (default: WordPress date format)
     * @return string Formatted date/time in site timezone
     */
    public static function format_datetime($date_string, $time_string = '', $format = '') {
        if (empty($date_string)) {
            return '';
        }
        
        if (empty($format)) {
            $format = get_option('date_format') . ' ' . get_option('time_format');
        }
        
        // Combine date and time
        $datetime_string = $date_string;
        if (!empty($time_string)) {
            $datetime_string .= ' ' . $time_string;
        }
        
        // Try to parse the datetime
        try {
            // Assume input is in Australian Eastern Time (Melbourne timezone)
            $source_timezone = new DateTimeZone('Australia/Melbourne');
            $datetime = new DateTime($datetime_string, $source_timezone);
            
            // Convert to site timezone
            $site_timezone = new DateTimeZone(wp_timezone_string());
            $datetime->setTimezone($site_timezone);
            
            return $datetime->format($format);
        } catch (Exception $e) {
            // If parsing fails, return original string
            error_log('LMC Data: Failed to parse datetime: ' . $datetime_string . ' - ' . $e->getMessage());
            return $datetime_string . ($time_string ? ' ' . $time_string : '');
        }
    }
    
    /**
     * Get ladder data
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @return array|false Ladder data or false on failure
     */
    public static function get_ladder($comp_id = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_ladder_data_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Read from file
        $data = self::read_json_file("ladder-{$comp_id}.json");
        
        if ($data === false) {
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        
        return $data;
    }
    
    /**
     * Get upcoming games
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @param int $limit Number of games to return (default: all)
     * @return array|false Upcoming games or false on failure
     */
    public static function get_upcoming_games($comp_id = null, $limit = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_upcoming_games_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $limit ? array_slice($cached_data, 0, $limit) : $cached_data;
        }
        
        // Read from file
        $data = self::read_json_file("upcoming-{$comp_id}.json");
        
        if ($data === false) {
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        
        return $limit ? array_slice($data, 0, $limit) : $data;
    }
    
    /**
     * Get results
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @param int $limit Number of results to return (default: all)
     * @return array|false Results or false on failure
     */
    public static function get_results($comp_id = null, $limit = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_results_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $limit ? array_slice($cached_data, 0, $limit) : $cached_data;
        }
        
        // Read from file
        $data = self::read_json_file("results-{$comp_id}.json");
        
        if ($data === false) {
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        
        return $limit ? array_slice($data, 0, $limit) : $data;
    }
    
    /**
     * Get all fixtures
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @return array|false All fixtures or false on failure
     */
    public static function get_fixtures($comp_id = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_fixtures_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Read from file
        $data = self::read_json_file("fixtures-{$comp_id}.json");
        
        if ($data === false) {
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        
        return $data;
    }
    
    /**
     * Clear cache for a specific competition
     *
     * @param string $comp_id Competition ID (optional, clears current if not specified)
     * @return bool Success status
     */
    public static function clear_cache($comp_id = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        delete_transient("lmc_ladder_data_{$comp_id}");
        delete_transient("lmc_upcoming_games_{$comp_id}");
        delete_transient("lmc_results_{$comp_id}");
        delete_transient("lmc_fixtures_{$comp_id}");
        
        return true;
    }
    
    /**
     * Clear all cached data
     *
     * @return bool Success status
     */
    public static function clear_all_cache() {
        global $wpdb;
        
        if (!isset($wpdb) || !is_object($wpdb)) {
            return false;
        }
        
        // Delete all transients with our prefix
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_lmc_%' OR option_name LIKE '_transient_timeout_lmc_%'");
        
        return true;
    }
    
    /**
     * Check if data files exist for a competition
     *
     * @param string $comp_id Competition ID
     * @return array Status of each data file
     */
    public static function check_data_files($comp_id) {
        $files = array(
            'ladder' => "ladder-{$comp_id}.json",
            'fixtures' => "fixtures-{$comp_id}.json",
            'upcoming' => "upcoming-{$comp_id}.json",
            'results' => "results-{$comp_id}.json"
        );
        
        $status = array();
        
        foreach ($files as $type => $filename) {
            $filepath = LMC_DATA_DIR . $filename;
            $status[$type] = array(
                'exists' => file_exists($filepath),
                'modified' => file_exists($filepath) ? filemtime($filepath) : null,
                'size' => file_exists($filepath) ? filesize($filepath) : 0
            );
        }
        
        return $status;
    }
    
    /**
     * Get data file information
     *
     * @param string $comp_id Competition ID
     * @return array File information
     */
    public static function get_data_info($comp_id) {
        $files_status = self::check_data_files($comp_id);
        
        $info = array(
            'competition_id' => $comp_id,
            'files' => $files_status,
            'cache_duration' => self::get_cache_duration(),
            'data_available' => false
        );
        
        // Check if at least ladder and one other file exists
        if ($files_status['ladder']['exists'] && 
            ($files_status['fixtures']['exists'] || $files_status['upcoming']['exists'] || $files_status['results']['exists'])) {
            $info['data_available'] = true;
        }
        
        return $info;
    }
}
