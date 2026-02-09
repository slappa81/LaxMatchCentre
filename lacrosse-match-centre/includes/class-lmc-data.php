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
        $comp_id = isset($settings['current_competition']) ? $settings['current_competition'] : false;
        error_log('LMC Data: get_current_competition() returning: ' . ($comp_id ? $comp_id : 'false'));
        return $comp_id;
    }
    
    /**
     * Get competition name by ID
     *
     * @param string $comp_id Competition ID
     * @return string Competition name or empty string if not found
     */
    public static function get_competition_name($comp_id) {
        if (empty($comp_id)) {
            return '';
        }
        
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        
        foreach ($competitions as $comp) {
            if ($comp['id'] === $comp_id) {
                return $comp['name'];
            }
        }
        
        return '';
    }
    
    /**
     * Get all configured competitions
     *
     * @return array Array of competitions with id and name
     */
    public static function get_all_competitions() {
        $settings = get_option('lmc_settings', array());
        return isset($settings['competitions']) ? $settings['competitions'] : array();
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
     * Convert a fixture date/time into a sortable timestamp
     *
     * @param array $fixture Fixture data
     * @return int|false Unix timestamp or false on failure
     */
    private static function get_fixture_timestamp($fixture) {
        $date = isset($fixture['date']) ? trim((string)$fixture['date']) : '';
        $time = isset($fixture['time']) ? trim((string)$fixture['time']) : '';

        if ($date === '') {
            return false;
        }

        $normalized_date = str_replace('/', ' ', $date);
        $normalized_date = preg_replace('/\s+/', ' ', trim($normalized_date));
        if (!preg_match('/\b\d{4}\b/', $normalized_date)) {
            $normalized_date .= ' ' . current_time('Y');
        }
        $datetime = trim($normalized_date . ' ' . $time);

        $timestamp = strtotime($datetime);
        return ($timestamp === false) ? false : $timestamp;
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
            error_log('LMC Data: No comp_id provided, using current competition: ' . ($comp_id ? $comp_id : 'NONE'));
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for ladder');
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_ladder_data_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            error_log('LMC Data: Returning cached ladder data for ' . $comp_id);
            return $cached_data;
        }
        
        // Read from file
        $filename = "ladder-{$comp_id}.json";
        error_log('LMC Data: Reading ladder from file: ' . $filename);
        $data = self::read_json_file($filename);
        
        if ($data === false) {
            error_log('LMC Data: Failed to read ladder file: ' . LMC_DATA_DIR . $filename);
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        error_log('LMC Data: Successfully loaded ladder with ' . count($data) . ' teams');
        
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
            error_log('LMC Data: No comp_id provided for upcoming games, using current: ' . ($comp_id ? $comp_id : 'NONE'));
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for upcoming games');
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_upcoming_games_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            error_log('LMC Data: Returning cached upcoming games for ' . $comp_id);
            return $limit ? array_slice($cached_data, 0, $limit) : $cached_data;
        }
        
        // Read from file
        $filename = "upcoming-{$comp_id}.json";
        error_log('LMC Data: Reading upcoming games from file: ' . $filename);
        $data = self::read_json_file($filename);
        
        if ($data === false) {
            error_log('LMC Data: Failed to read upcoming games file: ' . LMC_DATA_DIR . $filename);
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        error_log('LMC Data: Successfully loaded ' . count($data) . ' upcoming games');
        
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
            error_log('LMC Data: No comp_id provided for results, using current: ' . ($comp_id ? $comp_id : 'NONE'));
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for results');
            return false;
        }
        
        // Try to get from cache
        $cache_key = "lmc_results_{$comp_id}";
        $cached_data = get_transient($cache_key);
        
        if ($cached_data !== false) {
            error_log('LMC Data: Returning cached results for ' . $comp_id);
            return $limit ? array_slice($cached_data, 0, $limit) : $cached_data;
        }
        
        // Read from file
        $filename = "results-{$comp_id}.json";
        error_log('LMC Data: Reading results from file: ' . $filename);
        $data = self::read_json_file($filename);
        
        if ($data === false) {
            error_log('LMC Data: Failed to read results file: ' . LMC_DATA_DIR . $filename);
            return false;
        }
        
        // Cache the data
        set_transient($cache_key, $data, self::get_cache_duration());
        error_log('LMC Data: Successfully loaded ' . count($data) . ' results');
        
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
    
    /**
     * Get current competition ID
     *
     * @return string|false Competition ID or false if none set
     */
    public static function get_current_competition_id() {
        return self::get_current_competition();
    }
    
    /**
     * Get primary team for a competition
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @return string|false Primary team name or false if not set
     */
    public static function get_primary_team($comp_id = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            return false;
        }
        
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        
        foreach ($competitions as $comp) {
            if ($comp['id'] === $comp_id) {
                return isset($comp['primary_team']) && !empty($comp['primary_team']) ? $comp['primary_team'] : false;
            }
        }
        
        return false;
    }
    
    /**
     * Get list of all teams in a competition
     *
     * @param string $comp_id Competition ID
     * @return array|false Array of team names or false on failure
     */
    public static function get_teams_list($comp_id) {
        if (!$comp_id) {
            return false;
        }
        
        // Get fixtures to extract teams
        $fixtures = self::get_fixtures($comp_id);
        
        if ($fixtures === false || empty($fixtures)) {
            error_log('LMC Data: No fixtures found for competition ' . $comp_id);
            return false;
        }
        
        $teams = array();
        
        foreach ($fixtures as $fixture) {
            if (isset($fixture['home_team']) && !empty($fixture['home_team'])) {
                $teams[$fixture['home_team']] = true;
            }
            if (isset($fixture['away_team']) && !empty($fixture['away_team'])) {
                $teams[$fixture['away_team']] = true;
            }
        }
        
        // Return sorted unique team names
        $team_list = array_keys($teams);
        sort($team_list);
        
        error_log('LMC Data: Found ' . count($team_list) . ' unique teams');
        return $team_list;
    }
    
    /**
     * Get results for a specific team
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @param string $team_name Team name (optional, uses primary team if not specified)
     * @param int $limit Number of results to return (default: all)
     * @return array|false Team results or false on failure
     */
    public static function get_team_results($comp_id = null, $team_name = null, $limit = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for team results');
            return false;
        }
        
        if (!$team_name) {
            $team_name = self::get_primary_team($comp_id);
        }
        
        if (!$team_name) {
            error_log('LMC Data: No team name available for team results');
            return false;
        }
        
        // Prefer fixtures so we include finals data, then filter completed games
        $fixtures = self::get_fixtures($comp_id);
        if ($fixtures === false) {
            $fixtures = array();
        }

        $completed_results = array_filter($fixtures, function($fixture) {
            return isset($fixture['completed']) && $fixture['completed'];
        });

        // Filter results where team is home or away
        $team_results = array_filter($completed_results, function($result) use ($team_name) {
            return (isset($result['home_team']) && $result['home_team'] === $team_name) ||
                   (isset($result['away_team']) && $result['away_team'] === $team_name);
        });

        // Re-index array
        $team_results = array_values($team_results);

        // Sort by date/time (descending), fall back to round
        usort($team_results, function($a, $b) {
            $timestamp_a = self::get_fixture_timestamp($a);
            $timestamp_b = self::get_fixture_timestamp($b);

            if ($timestamp_a !== false && $timestamp_b !== false) {
                return $timestamp_b <=> $timestamp_a;
            }
            if ($timestamp_a !== false) {
                return -1;
            }
            if ($timestamp_b !== false) {
                return 1;
            }

            $round_a = isset($a['round']) ? (int)$a['round'] : 0;
            $round_b = isset($b['round']) ? (int)$b['round'] : 0;
            return $round_b <=> $round_a;
        });

        error_log('LMC Data: Found ' . count($team_results) . ' results for team ' . $team_name);

        return $limit ? array_slice($team_results, 0, $limit) : $team_results;
    }
    
    /**
     * Get upcoming games for a specific team
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @param string $team_name Team name (optional, uses primary team if not specified)
     * @param int $limit Number of games to return (default: all)
     * @return array|false Team upcoming games or false on failure
     */
    public static function get_team_upcoming($comp_id = null, $team_name = null, $limit = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for team upcoming');
            return false;
        }
        
        if (!$team_name) {
            $team_name = self::get_primary_team($comp_id);
        }
        
        if (!$team_name) {
            error_log('LMC Data: No team name available for team upcoming');
            return false;
        }
        
        // Get all upcoming games
        $all_upcoming = self::get_upcoming_games($comp_id);
        
        if ($all_upcoming === false) {
            return false;
        }
        
        // Filter games where team is home or away
        $team_upcoming = array_filter($all_upcoming, function($game) use ($team_name) {
            return (isset($game['home_team']) && $game['home_team'] === $team_name) ||
                   (isset($game['away_team']) && $game['away_team'] === $team_name);
        });
        
        // Re-index array
        $team_upcoming = array_values($team_upcoming);
        
        error_log('LMC Data: Found ' . count($team_upcoming) . ' upcoming games for team ' . $team_name);
        
        return $limit ? array_slice($team_upcoming, 0, $limit) : $team_upcoming;
    }
    
    /**
     * Get all teams from ladder data
     *
     * @param string $comp_id Competition ID (optional, uses current if not specified)
     * @return array Array of team data with logos
     */
    public static function get_all_teams($comp_id = null) {
        if (!$comp_id) {
            $comp_id = self::get_current_competition();
        }
        
        if (!$comp_id) {
            error_log('LMC Data: No competition ID available for get_all_teams');
            return array();
        }
        
        $ladder = self::get_ladder($comp_id);
        
        if ($ladder === false || empty($ladder)) {
            return array();
        }
        
        // Remove any duplicates based on team name (shouldn't happen, but safeguard)
        $unique_teams = array();
        $seen_teams = array();
        foreach ($ladder as $team) {
            $team_name = $team['team'];
            if (!isset($seen_teams[$team_name])) {
                $unique_teams[] = $team;
                $seen_teams[$team_name] = true;
            }
        }
        $ladder = $unique_teams;
        
        // Get custom team logos
        $team_logos = get_option('lmc_team_logos', array());
        
        // Merge custom logos with scraped data
        foreach ($ladder as &$team) {
            $team_key = sanitize_title($team['team']);
            if (isset($team_logos[$team_key])) {
                $team['logo'] = $team_logos[$team_key];
            }
        }
        
        return $ladder;
    }
}
