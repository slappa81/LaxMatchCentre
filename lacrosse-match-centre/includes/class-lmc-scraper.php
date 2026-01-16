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
     * Base URL for MyGameDay/SportsTG
     */
    private $base_url = 'https://websites.mygameday.app';
    
    /**
     * Get ladder data for a competition
     *
     * @param string $comp_id Competition ID (format: 0-12060-0-616436-0)
     * @param int $round_num Round number (not used for ladder, keeping for compatibility)
     * @return array|false Ladder data or false on failure
     */
    public function get_ladder($comp_id, $round_num) {
        // MyGameDay URL format for ladder
        $url = "{$this->base_url}/comp_info.cgi?c={$comp_id}&pool=1&a=LADDER";
        
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
     * @param string $comp_id Competition ID (format: 0-12060-0-616436-0)
     * @param int $round_num Round number (0 = all rounds)
     * @param int $pool_num Pool number (default 1)
     * @return array|false Fixtures data or false on failure
     */
    public function get_round_fixtures($comp_id, $round_num, $pool_num = 1) {
        // MyGameDay URL format - use round=0 to get all rounds, or specific round number
        $url = "{$this->base_url}/comp_info.cgi?c={$comp_id}&pool={$pool_num}&round={$round_num}&a=FIXTURE";
        
        error_log('LMC Scraper: Fetching fixtures from ' . $url);
        
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
        
        if (empty($body)) {
            error_log('LMC Scraper: Empty response body for fixtures');
            return false;
        }
        
        error_log('LMC Scraper: Fixtures response body length: ' . strlen($body) . ' bytes');
        
        // Save HTML to file for debugging (first time only)
        $debug_file = LMC_DATA_DIR . 'debug-fixtures-html.txt';
        if (!file_exists($debug_file)) {
            file_put_contents($debug_file, $body);
            error_log('LMC Scraper: Saved HTML to ' . $debug_file . ' for debugging');
        }
        
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
        
        // MyGameDay uses JavaScript to load fixture data
        // Look for: var matches = [{...}];
        if (preg_match('/var matches = (\[.*?\]);/s', $html, $matches)) {
            error_log('LMC Scraper: Found JavaScript matches array');
            
            $json_string = $matches[1];
            $match_data = json_decode($json_string, true);
            
            if (!$match_data || !is_array($match_data)) {
                error_log('LMC Scraper: Failed to parse JSON from JavaScript');
                return $fixtures;
            }
            
            error_log('LMC Scraper: Parsed ' . count($match_data) . ' matches from JavaScript');
            
            foreach ($match_data as $match) {
                // Extract data from the JavaScript object
                $fixture = array(
                    'round' => isset($match['Round']) ? (int)$match['Round'] : $round_num,
                    'date' => isset($match['DateRaw']) ? $match['DateRaw'] : '',
                    'time' => isset($match['TimeRaw']) ? $match['TimeRaw'] : '',
                    'home_team' => isset($match['HomeNameFMT']) ? html_entity_decode($match['HomeNameFMT']) : '',
                    'away_team' => isset($match['AwayNameFMT']) ? html_entity_decode($match['AwayNameFMT']) : '',
                    'venue' => isset($match['VenueName']) ? html_entity_decode($match['VenueName']) : '',
                    'home_score' => null,
                    'away_score' => null,
                    'completed' => false
                );
                
                // Check if game is completed (has scores)
                if (isset($match['HomeScore']) && isset($match['AwayScore']) && 
                    is_numeric($match['HomeScore']) && is_numeric($match['AwayScore'])) {
                    $fixture['home_score'] = (int)$match['HomeScore'];
                    $fixture['away_score'] = (int)$match['AwayScore'];
                    $fixture['completed'] = true;
                }
                
                // Determine if it's a past or future game
                if (isset($match['PastGame'])) {
                    $fixture['completed'] = (bool)$match['PastGame'];
                }
                
                $fixtures[] = $fixture;
            }
            
            error_log('LMC Scraper: Successfully parsed ' . count($fixtures) . ' fixtures from JavaScript data');
            return $fixtures;
        }
        
        error_log('LMC Scraper: No JavaScript matches array found, trying HTML parsing');
        
        // Fallback to HTML parsing if JavaScript isn't found
        libxml_use_internal_errors(true);
        
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        $xpath = new DOMXPath($dom);
        
        // MyGameDay uses div-based layout
        // Try to find the actual game/fixture rows, not navigation
        $fixture_queries = array(
            "//div[contains(@class, 'fixtureDiv')]",
            "//div[contains(@class, 'gameDiv')]",
            "//div[contains(@class, 'matchDiv')]",
            "//div[contains(@class, 'fixture-row')]",
            "//div[contains(@class, 'game-row')]",
            "//tr[contains(@class, 'fixture')]",
            "//tr[contains(@class, 'game')]",
            "//div[contains(@id, 'fixture')]",
            "//div[contains(@id, 'game')]"
        );
        
        $fixture_divs = null;
        foreach ($fixture_queries as $query) {
            $result = $xpath->query($query);
            if ($result && $result->length > 0) {
                error_log('LMC Scraper: Found ' . $result->length . ' elements using: ' . $query);
                // Check if first element looks like it has game data
                if ($result->length > 0) {
                    $first_text = substr(trim($result->item(0)->textContent), 0, 100);
                    error_log('LMC Scraper: First element preview: ' . $first_text);
                    // Skip if it looks like navigation
                    if (!stripos($first_text, 'FixtureResultsLadder') && !stripos($first_text, 'Sync to Calendar')) {
                        $fixture_divs = $result;
                        break;
                    }
                }
            }
        }
        
        if (!$fixture_divs || $fixture_divs->length === 0) {
            error_log('LMC Scraper: No fixture divs found, trying table-based approach');
            // Maybe it's still using tables in some cases
            $fixture_divs = $xpath->query("//table//tr[td and not(th)]");
            if ($fixture_divs && $fixture_divs->length > 0) {
                error_log('LMC Scraper: Found ' . $fixture_divs->length . ' table rows');
            }
        }
        
        if (!$fixture_divs || $fixture_divs->length === 0) {
            error_log('LMC Scraper: No fixture divs found in HTML');
            libxml_clear_errors();
            return $fixtures;
        }
        
        error_log('LMC Scraper: Found ' . $fixture_divs->length . ' fixture divs, parsing...');
        
        $parsed_count = 0;
        
        foreach ($fixture_divs as $index => $div) {
            // Get the full text content for debugging the first few divs
            if ($index < 2) {
                $div_content = trim($div->textContent);
                error_log('LMC Scraper: Div ' . $index . ' content sample: ' . substr($div_content, 0, 200));
            }
            
            // Extract data from the div structure
            // Look for common patterns in GameDay HTML
            
            // Try to find round number
            $round_element = $xpath->query(".//span[contains(@class, 'round') or contains(text(), 'Round')]", $div);
            $round = $round_num;
            if ($round_element && $round_element->length > 0) {
                $round_text = trim($round_element->item(0)->textContent);
                if (preg_match('/Round\s*(\d+)/i', $round_text, $matches)) {
                    $round = (int)$matches[1];
                }
            }
            
            // Try to find date
            $date_element = $xpath->query(".//span[contains(@class, 'date')] | .//div[contains(@class, 'date')] | .//*[contains(@class, 'date')]", $div);
            $date_str = '';
            if ($date_element && $date_element->length > 0) {
                $date_str = trim($date_element->item(0)->textContent);
            }
            
            // Try to find time
            $time_element = $xpath->query(".//span[contains(@class, 'time')] | .//div[contains(@class, 'time')] | .//*[contains(@class, 'time')]", $div);
            $time_str = '';
            if ($time_element && $time_element->length > 0) {
                $time_str = trim($time_element->item(0)->textContent);
            }
            
            // Try to find teams
            $team_elements = $xpath->query(".//*[contains(@class, 'team')]", $div);
            $home_team = '';
            $away_team = '';
            
            if ($team_elements && $team_elements->length >= 2) {
                $home_team = trim($team_elements->item(0)->textContent);
                $away_team = trim($team_elements->item(1)->textContent);
            }
            
            // Try to find venue
            $venue_element = $xpath->query(".//span[contains(@class, 'venue')] | .//div[contains(@class, 'venue')] | .//*[contains(@class, 'venue')]", $div);
            $venue = '';
            if ($venue_element && $venue_element->length > 0) {
                $venue = trim($venue_element->item(0)->textContent);
            }
            
            // Try to find scores
            $score_elements = $xpath->query(".//*[contains(@class, 'score')]", $div);
            $home_score = null;
            $away_score = null;
            $completed = false;
            
            if ($score_elements && $score_elements->length >= 2) {
                $home_score_text = trim($score_elements->item(0)->textContent);
                $away_score_text = trim($score_elements->item(1)->textContent);
                
                if (is_numeric($home_score_text) && is_numeric($away_score_text)) {
                    $home_score = (int)$home_score_text;
                    $away_score = (int)$away_score_text;
                    $completed = true;
                }
            }
            
            // Debug what we found
            if ($index < 2) {
                error_log('LMC Scraper: Div ' . $index . ' - Teams found: home="' . $home_team . '", away="' . $away_team . '"');
                error_log('LMC Scraper: Div ' . $index . ' - Date="' . $date_str . '", Time="' . $time_str . '", Venue="' . $venue . '"');
            }
            
            // Only add if we have at least teams
            if (!empty($home_team) && !empty($away_team)) {
                $fixture = array(
                    'round' => $round,
                    'date' => $date_str,
                    'time' => $time_str,
                    'home_team' => $home_team,
                    'away_team' => $away_team,
                    'venue' => $venue,
                    'home_score' => $home_score,
                    'away_score' => $away_score,
                    'completed' => $completed
                );
                
                $fixtures[] = $fixture;
                $parsed_count++;
            }
        }
        
        error_log('LMC Scraper: Parsed ' . $parsed_count . ' fixtures from ' . $fixture_divs->length . ' divs');
        
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
    public function fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds = 30) {
        error_log('LMC Scraper: Auto-detecting rounds by fetching until empty');
        
        $all_fixtures = array();
        $empty_rounds = 0;
        $last_round_with_data = 0;
        
        // Fetch rounds until we hit 3 consecutive empty rounds (to be safe)
        for ($round = 1; $round <= $max_rounds; $round++) {
            $fixtures = $this->get_round_fixtures($comp_id, $round);
            
            if ($fixtures && !empty($fixtures)) {
                $all_fixtures = array_merge($all_fixtures, $fixtures);
                $last_round_with_data = $round;
                $empty_rounds = 0;
                error_log('LMC Scraper: Round ' . $round . ' returned ' . count($fixtures) . ' fixtures');
            } else {
                $empty_rounds++;
                error_log('LMC Scraper: Round ' . $round . ' is empty');
                
                // If we've found data and now hit 3 empty rounds, we're done
                if ($last_round_with_data > 0 && $empty_rounds >= 3) {
                    error_log('LMC Scraper: Stopping at round ' . $round . ', last data in round ' . $last_round_with_data);
                    break;
                }
            }
            
            // Small delay to avoid overwhelming the server
            usleep(300000); // 0.3 seconds
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
     * @return array Status information
     */
    public function scrape_competition($comp_id, $comp_name) {
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
        
        // Fetch ladder (use round 1, but ladder shows overall standings)
        $ladder = $this->get_ladder($comp_id, 1);
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
        
        // Fetch fixtures (auto-detect number of rounds)
        $fixtures_success = $this->fetch_all_fixtures($comp_id, $comp_name, 1);
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
