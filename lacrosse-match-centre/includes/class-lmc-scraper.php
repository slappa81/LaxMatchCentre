<?php
/**
 * Scraper class for fetching data from SportsTG
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Scraper class
 */
class LMC_Scraper {
    
    /**
     * Base URL for SportsTG
     */
    private $base_url = 'https://www.sportstg.com';
    
    /**
     * Get ladder data for a competition
     *
     * @param string $comp_id Competition ID
     * @param int $round_num Round number
     * @return array|false Ladder data or false on failure
     */
    public function get_ladder($comp_id, $round_num) {
        $url = "{$this->base_url}/comp_ladder.cgi?c={$comp_id}&round={$round_num}&pool=-1";
        
        error_log('LMC Scraper: Fetching ladder from ' . $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            error_log('LMC Scraper: Failed to fetch ladder - ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('LMC Scraper: Ladder request returned status ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            error_log('LMC Scraper: Empty response body for ladder');
            return false;
        }
        
        $result = $this->parse_ladder($body);
        
        if (empty($result)) {
            error_log('LMC Scraper: Failed to parse ladder data');
        } else {
            error_log('LMC Scraper: Successfully parsed ' . count($result) . ' teams');
        }
        
        return $result;
    }
    
    /**
     * Parse ladder HTML
     *
     * @param string $html HTML content
     * @return array Parsed ladder data
     */
    private function parse_ladder($html) {
        $ladder = array();
        
        // Suppress warnings from HTML parsing
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Find the ladder table
        $rows = $xpath->query("//table[@class='ladder']//tr[position() > 1]");
        
        if ($rows->length === 0) {
            // Try alternative table structure
            $rows = $xpath->query("//table[contains(@class, 'table')]//tbody//tr");
        }
        
        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);
            
            if ($cells->length >= 8) {
                $team_data = array(
                    'position' => (int)trim($cells->item(0)->textContent),
                    'team' => trim($cells->item(1)->textContent),
                    'played' => (int)trim($cells->item(2)->textContent),
                    'won' => (int)trim($cells->item(3)->textContent),
                    'lost' => (int)trim($cells->item(4)->textContent),
                    'drawn' => (int)trim($cells->item(5)->textContent),
                    'for' => (int)trim($cells->item(6)->textContent),
                    'against' => (int)trim($cells->item(7)->textContent),
                    'percentage' => ($cells->length > 8) ? trim($cells->item(8)->textContent) : '0%',
                    'points' => (int)trim($cells->item($cells->length - 1)->textContent)
                );
                
                $ladder[] = $team_data;
            }
        }
        
        libxml_clear_errors();
        
        return $ladder;
    }
    
    /**
     * Get fixtures for a specific round
     *
     * @param string $comp_id Competition ID
     * @param int $round_num Round number
     * @param int $pool_num Pool number
     * @return array|false Fixtures data or false on failure
     */
    public function get_round_fixtures($comp_id, $round_num, $pool_num = -1) {
        $url = "{$this->base_url}/comp_display_round.cgi?c={$comp_id}&round={$round_num}&pool={$pool_num}";
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            error_log('LMC Scraper: Failed to fetch fixtures round ' . $round_num . ' - ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('LMC Scraper: Fixtures round ' . $round_num . ' returned status ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        return $this->parse_fixtures($body, $round_num);
    }
    
    /**
     * Parse fixtures HTML
     *
     * @param string $html HTML content
     * @param int $round_num Round number
     * @return array Parsed fixtures data
     */
    private function parse_fixtures($html, $round_num) {
        $fixtures = array();
        
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // Find fixture rows
        $rows = $xpath->query("//table[contains(@class, 'table')]//tbody//tr | //table[@class='fixtures']//tr[position() > 1]");
        
        foreach ($rows as $row) {
            $cells = $xpath->query(".//td", $row);
            
            if ($cells->length >= 5) {
                // Extract date and time
                $date_str = trim($cells->item(0)->textContent);
                $time_str = ($cells->length > 1) ? trim($cells->item(1)->textContent) : '';
                
                // Extract teams
                $home_team = trim($cells->item(2)->textContent);
                $away_team = trim($cells->item(3)->textContent);
                
                // Extract venue
                $venue = ($cells->length > 4) ? trim($cells->item(4)->textContent) : '';
                
                // Extract score if available (completed games)
                $score_cell = ($cells->length > 5) ? trim($cells->item(5)->textContent) : '';
                $home_score = null;
                $away_score = null;
                $completed = false;
                
                if (!empty($score_cell) && preg_match('/(\d+)\s*-\s*(\d+)/', $score_cell, $matches)) {
                    $home_score = (int)$matches[1];
                    $away_score = (int)$matches[2];
                    $completed = true;
                }
                
                $fixture = array(
                    'round' => $round_num,
                    'date' => $date_str,
                    'time' => $time_str,
                    'home_team' => $home_team,
                    'away_team' => $away_team,
                    'venue' => $venue,
                    'completed' => $completed
                );
                
                if ($completed) {
                    $fixture['home_score'] = $home_score;
                    $fixture['away_score'] = $away_score;
                }
                
                $fixtures[] = $fixture;
            }
        }
        
        libxml_clear_errors();
        
        return $fixtures;
    }
    
    /**
     * Fetch all fixtures for a competition
     *
     * @param string $comp_id Competition ID
     * @param string $comp_name Competition name
     * @param int $current_round Current round number
     * @param int $max_rounds Maximum rounds in competition
     * @return bool Success status
     */
    public function fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds = 18) {
        $all_fixtures = array();
        
        error_log('LMC Scraper: Fetching fixtures for ' . $max_rounds . ' rounds');
        
        // Fetch fixtures for all rounds
        for ($round = 1; $round <= $max_rounds; $round++) {
            $fixtures = $this->get_round_fixtures($comp_id, $round);
            
            if ($fixtures && !empty($fixtures)) {
                $all_fixtures = array_merge($all_fixtures, $fixtures);
            }
            
            // Small delay to avoid overwhelming the server
            usleep(500000); // 0.5 seconds
        }
        
        if (empty($all_fixtures)) {
            error_log('LMC Scraper: No fixtures found in any round');
            return false;
        }
        
        error_log('LMC Scraper: Total fixtures found: ' . count($all_fixtures));
        
        // Save all fixtures
        $fixtures_file = LMC_DATA_DIR . "fixtures-{$comp_id}.json";
        $result = file_put_contents($fixtures_file, json_encode($all_fixtures, JSON_PRETTY_PRINT));
        if ($result === false) {
            error_log('LMC Scraper: Failed to write fixtures file');
            return false;
        }
        
        // Separate upcoming games and results
        $upcoming = $this->get_upcoming_games($all_fixtures);
        $results = $this->get_recent_results($all_fixtures);
        
        error_log('LMC Scraper: Upcoming games: ' . count($upcoming) . ', Results: ' . count($results));
        
        // Save upcoming games
        $upcoming_file = LMC_DATA_DIR . "upcoming-{$comp_id}.json";
        file_put_contents($upcoming_file, json_encode($upcoming, JSON_PRETTY_PRINT));
        
        // Save results
        $results_file = LMC_DATA_DIR . "results-{$comp_id}.json";
        file_put_contents($results_file, json_encode($results, JSON_PRETTY_PRINT));
        
        return true;
    }
    
    /**
     * Get upcoming games from fixtures
     *
     * @param array $fixtures All fixtures
     * @return array Upcoming games
     */
    public function get_upcoming_games($fixtures) {
        $upcoming = array();
        $current_date = current_time('timestamp');
        
        foreach ($fixtures as $fixture) {
            if (!$fixture['completed']) {
                // Try to parse the date
                $fixture_date = strtotime($fixture['date']);
                
                // If date is in the future or couldn't be parsed (show it anyway)
                if ($fixture_date === false || $fixture_date >= $current_date) {
                    $upcoming[] = $fixture;
                }
            }
        }
        
        // Sort by round
        usort($upcoming, function($a, $b) {
            return $a['round'] - $b['round'];
        });
        
        return $upcoming;
    }
    
    /**
     * Get recent results from fixtures
     *
     * @param array $fixtures All fixtures
     * @return array Recent results (last 10)
     */
    public function get_recent_results($fixtures) {
        $results = array();
        
        foreach ($fixtures as $fixture) {
            if ($fixture['completed']) {
                $results[] = $fixture;
            }
        }
        
        // Sort by round (descending)
        usort($results, function($a, $b) {
            return $b['round'] - $a['round'];
        });
        
        // Return last 10 results
        return array_slice($results, 0, 10);
    }
    
    /**
     * Scrape all data for a competition
     *
     * @param string $comp_id Competition ID
     * @param string $comp_name Competition name
     * @param int $current_round Current round number
     * @param int $max_rounds Maximum rounds
     * @return array Status information
     */
    public function scrape_competition($comp_id, $comp_name, $current_round, $max_rounds = 18) {
        $status = array(
            'success' => false,
            'ladder' => false,
            'fixtures' => false,
            'message' => ''
        );
        
        error_log('LMC Scraper: Starting scrape for competition ' . $comp_id . ' (' . $comp_name . ')');
        
        // Check if data directory exists and is writable
        if (!file_exists(LMC_DATA_DIR)) {
            error_log('LMC Scraper: Data directory does not exist: ' . LMC_DATA_DIR);
            $status['message'] = 'Data directory does not exist';
            return $status;
        }
        
        if (!is_writable(LMC_DATA_DIR)) {
            error_log('LMC Scraper: Data directory is not writable: ' . LMC_DATA_DIR);
            $status['message'] = 'Data directory is not writable';
            return $status;
        }
        
        // Fetch ladder
        $ladder = $this->get_ladder($comp_id, $current_round);
        if ($ladder && !empty($ladder)) {
            $ladder_file = LMC_DATA_DIR . "ladder-{$comp_id}.json";
            $result = file_put_contents($ladder_file, json_encode($ladder, JSON_PRETTY_PRINT));
            if ($result === false) {
                error_log('LMC Scraper: Failed to write ladder file: ' . $ladder_file);
            } else {
                error_log('LMC Scraper: Ladder saved successfully');
                $status['ladder'] = true;
            }
        } else {
            error_log('LMC Scraper: Failed to fetch or parse ladder');
        }
        
        // Fetch fixtures
        $fixtures_success = $this->fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds);
        if ($fixtures_success) {
            error_log('LMC Scraper: Fixtures saved successfully');
            $status['fixtures'] = true;
        } else {
            error_log('LMC Scraper: Failed to fetch fixtures');
        }
        
        // Set overall status
        if ($status['ladder'] && $status['fixtures']) {
            $status['success'] = true;
            $status['message'] = 'Successfully scraped all data';
        } elseif ($status['ladder'] || $status['fixtures']) {
            $status['success'] = true;
            $status['message'] = 'Partially scraped data - check error log for details';
        } else {
            $status['message'] = 'Failed to scrape data - check error log for details';
        }
        
        error_log('LMC Scraper: Scrape completed with status: ' . $status['message']);
        
        return $status;
    }
}
