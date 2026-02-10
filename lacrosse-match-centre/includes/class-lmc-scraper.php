<?php
/**
 * Scraper class for fetching data from GameDay
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
    * Base URL for GameDay
     */
    private $base_url = 'https://websites.mygameday.app';

    /**
     * Pool number for regular season fixtures
     */
    private $regular_pool = 1;

    /**
     * Pool number for finals fixtures
     */
    private $finals_pool = 1001;

    /**
     * Maximum rounds to check for finals
     */
    private $finals_max_rounds = 5;

    /**
     * Cached finals round names keyed by round number
     */
    private $finals_round_names = null;

    /**
     * Normalize team name for consistent logo matching
     *
     * @param string $team_name Team name
     * @return string Normalized key
     */
    private function normalize_team_key($team_name) {
        $team_name = trim((string)$team_name);
        if ($team_name === '') {
            return '';
        }

        $team_name = strtolower($team_name);
        $team_name = preg_replace('/\s+/', ' ', $team_name);
        $team_name = str_replace('&', 'and', $team_name);
        $team_name = preg_replace('/[^a-z0-9 ]/', '', $team_name);
        $team_name = preg_replace('/\s+/', '-', trim($team_name));

        return $team_name;
    }

    /**
     * Get stage label based on pool number
     *
     * @param int $pool_num Pool number
     * @return string Stage label
     */
    private function get_stage_label($pool_num) {
        return ((int)$pool_num === $this->finals_pool) ? 'Finals' : 'Regular Season';
    }

    /**
     * Build a round label for display
     *
     * @param int $round_num Round number
     * @param int $pool_num Pool number
     * @return string Round label
     */
    private function get_round_label($round_num, $pool_num) {
        if ((int)$pool_num === $this->finals_pool) {
            return 'Finals Week ' . (int)$round_num;
        }

        return 'Round ' . (int)$round_num;
    }

    /**
     * Add stage metadata to fixtures
     *
     * @param array $fixtures Fixtures to enrich
     * @param int $pool_num Pool number
     * @return array Fixtures with stage metadata
     */
    private function add_stage_metadata($fixtures, $pool_num) {
        if (empty($fixtures) || !is_array($fixtures)) {
            return $fixtures;
        }

        $stage_label = $this->get_stage_label($pool_num);
        foreach ($fixtures as &$fixture) {
            $fixture['pool'] = (int)$pool_num;
            $fixture['stage'] = $stage_label;
            if ((int)$pool_num === $this->finals_pool && !empty($fixture['match_name'])) {
                $fixture['round_label'] = $fixture['match_name'];
            } elseif ((int)$pool_num === $this->finals_pool && !empty($fixture['round_name'])) {
                $fixture['round_label'] = $fixture['round_name'];
            } elseif ((int)$pool_num === $this->finals_pool && isset($this->finals_round_names[(int)$fixture['round']])) {
                $fixture['round_label'] = $this->finals_round_names[(int)$fixture['round']];
            } elseif ((int)$pool_num === $this->finals_pool) {
                $fixture['round_label'] = $this->get_default_finals_label(isset($fixture['round']) ? (int)$fixture['round'] : 0);
            } else {
                $fixture['round_label'] = $this->get_round_label(isset($fixture['round']) ? $fixture['round'] : 0, $pool_num);
            }
        }
        unset($fixture);

        return $fixtures;
    }

    /**
     * Get a timestamp from a fixture date/time
     *
     * @param array $fixture Fixture data
     * @return int|null Unix timestamp or null if unavailable
     */
    private function get_fixture_timestamp($fixture) {
        $date = isset($fixture['date']) ? trim((string)$fixture['date']) : '';
        $time = isset($fixture['time']) ? trim((string)$fixture['time']) : '';

        if ($date === '') {
            return null;
        }

        $normalized_date = str_replace('/', ' ', $date);
        $normalized_date = preg_replace('/\s+/', ' ', trim($normalized_date));
        if (!preg_match('/\b\d{4}\b/', $normalized_date)) {
            $normalized_date .= ' ' . current_time('Y');
        }
        $datetime = trim($normalized_date . ' ' . $time);
        $timestamp = strtotime($datetime);

        return ($timestamp === false) ? null : $timestamp;
    }
    
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
                // Try to extract team logo from img tag in first or second cell
                $logo_url = '';
                $logo_img = $xpath->query(".//img", $cells->item(1));
                if ($logo_img->length > 0) {
                    $logo_url = $logo_img->item(0)->getAttribute('src');
                }
                
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
                    'points' => (int)trim($cells->item($cells->length - 1)->textContent),
                    'logo' => $logo_url
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

        if ((int)$pool_num === $this->finals_pool) {
            $this->load_finals_round_names($body);
        }
        
        error_log('LMC Scraper: Fixtures response body length: ' . strlen($body) . ' bytes');
        
        // Save HTML to file for debugging (first time only)
        $debug_file = LMC_DATA_DIR . 'debug-fixtures-html.txt';
        if (!file_exists($debug_file)) {
            file_put_contents($debug_file, $body);
            error_log('LMC Scraper: Saved HTML to ' . $debug_file . ' for debugging');
        }
        
        $fixtures = $this->parse_fixtures($body, $round_num);
        $fixtures = $this->add_stage_metadata($fixtures, $pool_num);

        if (!empty($fixtures)) {
            return $fixtures;
        }

        $fallback_url = "{$this->base_url}/comp_info.cgi?client={$comp_id}&pool={$pool_num}&action=FIXTURE&round={$round_num}";
        error_log('LMC Scraper: No fixtures found, trying fallback URL ' . $fallback_url);

        $fallback_response = wp_remote_get($fallback_url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));

        if (is_wp_error($fallback_response)) {
            error_log('LMC Scraper: Fallback fetch failed for round ' . $round_num . ' - ' . $fallback_response->get_error_message());
            return $fixtures;
        }

        $fallback_status = wp_remote_retrieve_response_code($fallback_response);
        if ($fallback_status !== 200) {
            error_log('LMC Scraper: Fallback fixtures round ' . $round_num . ' returned status ' . $fallback_status);
            return $fixtures;
        }

        $fallback_body = wp_remote_retrieve_body($fallback_response);
        if (empty($fallback_body)) {
            error_log('LMC Scraper: Empty response body for fallback fixtures');
            return $fixtures;
        }

        if ((int)$pool_num === $this->finals_pool) {
            $this->load_finals_round_names($fallback_body);
        }

        $fallback_fixtures = $this->parse_fixtures($fallback_body, $round_num);
        return $this->add_stage_metadata($fallback_fixtures, $pool_num);
    }

    /**
     * Load finals round names from the competition API
     *
     * @param string $html HTML content
     * @return void
     */
    private function load_finals_round_names($html) {
        if ($this->finals_round_names !== null) {
            return;
        }

        $this->finals_round_names = array();

        if (!preg_match('/onlineCompID\s*=\s*(\d+)/', $html, $matches)) {
            return;
        }

        $online_comp_id = $matches[1];
        $api_key = '';

        if (preg_match('/setRequestHeader\(\s*\'x-api-key\'\s*,\s*\'([^\']+)\'\s*\)/', $html, $key_matches)) {
            $api_key = $key_matches[1];
        }

        $headers = array();
        if ($api_key !== '') {
            $headers['x-api-key'] = $api_key;
        }

        $url = "https://awsapi.foxsportspulse.com/v2/compdata/competitions/{$online_comp_id}";
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'headers' => $headers,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));

        if (is_wp_error($response)) {
            error_log('LMC Scraper: Finals round names fetch failed - ' . $response->get_error_message());
            return;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('LMC Scraper: Finals round names request returned status ' . $status_code);
            return;
        }

        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return;
        }

        $data = json_decode($body, true);
        if (!$data || !isset($data['resultList'][0]['round_list'])) {
            return;
        }

        foreach ($data['resultList'][0]['round_list'] as $round) {
            if (!isset($round['number'])) {
                continue;
            }

            if (isset($round['is_final']) && !$round['is_final']) {
                continue;
            }

            $label = $this->get_round_display_name($round);
            if ($label === '') {
                continue;
            }

            $this->finals_round_names[(int)$round['number']] = $label;
        }
    }

    /**
     * Resolve a display label for a round
     *
     * @param array $round Round data
     * @return string
     */
    private function get_round_display_name($round) {
        $keys = array(
            'abbr',
            'short_name',
            'shortName',
            'name',
            'round_name',
            'roundName',
            'label',
            'title'
        );

        foreach ($keys as $key) {
            if (!empty($round[$key])) {
                $label = trim((string)$round[$key]);
                return $this->normalize_finals_label($label);
            }
        }

        if (!empty($round['notes'])) {
            $label = trim((string)$round['notes']);
            return $this->normalize_finals_label($label);
        }

        return '';
    }

    /**
     * Normalize finals labels to short forms where possible
     *
     * @param string $label Label to normalize
     * @return string
     */
    private function normalize_finals_label($label) {
        $clean = trim($label);
        if ($clean === '') {
            return '';
        }

        $lower = strtolower($clean);
        if (strpos($lower, 'semi') !== false) {
            return 'Semi Final';
        }
        if (strpos($lower, 'prelim') !== false) {
            return 'Preliminary Final';
        }
        if (strpos($lower, 'grand') !== false) {
            return 'Grand Final';
        }
        if (strpos($lower, 'elimin') !== false) {
            return 'Elimination Final';
        }
        if (strpos($lower, 'qualif') !== false) {
            return 'Qualifying Final';
        }

        if (strlen($clean) <= 4) {
            return strtoupper($clean);
        }

        return $clean;
    }

    /**
     * Default finals label mapping when round names are not provided
     *
     * @param int $round_num Round number
     * @return string
     */
    private function get_default_finals_label($round_num) {
        $mapping = array(
            1 => 'Semi Final',
            2 => 'Preliminary Final',
            3 => 'Grand Final'
        );

        if (isset($mapping[$round_num])) {
            return $mapping[$round_num];
        }

        return 'Finals Week ' . (int)$round_num;
    }

    /**
     * Validate fixture data to avoid placeholders without teams
     *
     * @param array $fixture Fixture data
     * @return bool
     */
    private function is_valid_fixture($fixture) {
        $home_team = isset($fixture['home_team']) ? trim((string)$fixture['home_team']) : '';
        $away_team = isset($fixture['away_team']) ? trim((string)$fixture['away_team']) : '';

        if ($home_team === '' && $away_team === '') {
            return false;
        }

        if (strtolower($home_team) === 'undecided' && $away_team === '') {
            return false;
        }

        return true;
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
                    'home_team' => isset($match['HomeNameFMT']) ? trim(strip_tags(html_entity_decode($match['HomeNameFMT']))) : '',
                    'away_team' => isset($match['AwayNameFMT']) ? trim(strip_tags(html_entity_decode($match['AwayNameFMT']))) : '',
                    'venue' => isset($match['VenueName']) ? html_entity_decode($match['VenueName']) : '',
                    'home_score' => null,
                    'away_score' => null,
                    'completed' => false,
                    'home_logo' => isset($match['HomeTeamLogo']) ? $match['HomeTeamLogo'] : (isset($match['HomeClubLogo']) ? $match['HomeClubLogo'] : ''),
                    'away_logo' => isset($match['AwayTeamLogo']) ? $match['AwayTeamLogo'] : (isset($match['AwayClubLogo']) ? $match['AwayClubLogo'] : ''),
                    'match_name' => isset($match['MatchName']) ? trim((string)$match['MatchName']) : '',
                    'round_name' => isset($match['RoundName']) ? trim((string)$match['RoundName']) : ''
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
                
                if ($this->is_valid_fixture($fixture)) {
                    $fixtures[] = $fixture;
                }
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
    public function fetch_all_fixtures($comp_id, $comp_name, $current_round, $max_rounds = 18) {
        error_log('LMC Scraper: Auto-detecting rounds by fetching until empty (max: ' . $max_rounds . ')');

        $all_fixtures = array();

        $regular_fixtures = $this->fetch_fixtures_for_pool($comp_id, $this->regular_pool, $max_rounds);
        if (!empty($regular_fixtures)) {
            $all_fixtures = array_merge($all_fixtures, $regular_fixtures);
        }

        $finals_fixtures = $this->fetch_fixtures_for_pool($comp_id, $this->finals_pool, $this->finals_max_rounds);
        if (!empty($finals_fixtures)) {
            $all_fixtures = array_merge($all_fixtures, $finals_fixtures);
        }

        if (empty($all_fixtures)) {
            error_log('LMC Scraper: No fixtures found in any pool');
            return false;
        }

        $deduped_fixtures = array();
        $seen_fixture_keys = array();
        foreach ($all_fixtures as $fixture) {
            $pool = isset($fixture['pool']) ? $fixture['pool'] : 0;
            $key = $pool . '_' . $fixture['round'] . '_' . $fixture['home_team'] . '_' . $fixture['away_team'] . '_' . $fixture['date'];
            if (!isset($seen_fixture_keys[$key])) {
                $seen_fixture_keys[$key] = true;
                $deduped_fixtures[] = $fixture;
            }
        }

        $all_fixtures = $deduped_fixtures;

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

        if (class_exists('LMC_Data')) {
            LMC_Data::clear_cache($comp_id);
        }
        
        return true;
    }

    /**
     * Fetch fixtures for a specific pool
     *
     * @param string $comp_id Competition ID
     * @param int $pool_num Pool number
     * @param int $max_rounds Maximum rounds to check
     * @return array Fixtures for the pool
     */
    private function fetch_fixtures_for_pool($comp_id, $pool_num, $max_rounds) {
        $stage_label = $this->get_stage_label($pool_num);
        error_log('LMC Scraper: Fetching ' . $stage_label . ' fixtures (pool ' . $pool_num . ')');

        $all_fixtures = array();
        $empty_rounds = 0;
        $last_round_with_data = 0;
        $seen_fixture_keys = array();

        if ((int)$pool_num === $this->finals_pool) {
            $finals_fixtures = $this->get_round_fixtures($comp_id, 0, $pool_num);
            if (!empty($finals_fixtures)) {
                error_log('LMC Scraper: Pool ' . $pool_num . ' returned fixtures with round=0');
                foreach ($finals_fixtures as $fixture) {
                    $key = $pool_num . '_' . $fixture['round'] . '_' . $fixture['home_team'] . '_' . $fixture['away_team'] . '_' . $fixture['date'];
                    if (!isset($seen_fixture_keys[$key])) {
                        $all_fixtures[] = $fixture;
                        $seen_fixture_keys[$key] = true;
                    }
                }
            }
        }

        for ($round = 1; $round <= $max_rounds; $round++) {
            $fixtures = $this->get_round_fixtures($comp_id, $round, $pool_num);

            if ($fixtures && !empty($fixtures)) {
                $new_fixtures_count = 0;
                foreach ($fixtures as $fixture) {
                    $key = $pool_num . '_' . $fixture['round'] . '_' . $fixture['home_team'] . '_' . $fixture['away_team'] . '_' . $fixture['date'];
                    if (!isset($seen_fixture_keys[$key])) {
                        $all_fixtures[] = $fixture;
                        $seen_fixture_keys[$key] = true;
                        $new_fixtures_count++;
                    }
                }

                if ($new_fixtures_count > 0) {
                    $last_round_with_data = $round;
                    $empty_rounds = 0;
                    error_log('LMC Scraper: Pool ' . $pool_num . ' round ' . $round . ' returned ' . $new_fixtures_count . ' new fixtures');
                } else {
                    $empty_rounds++;
                    error_log('LMC Scraper: Pool ' . $pool_num . ' round ' . $round . ' returned only duplicate fixtures (empty)');
                }
            } else {
                $empty_rounds++;
                error_log('LMC Scraper: Pool ' . $pool_num . ' round ' . $round . ' is empty');
            }

            if ($last_round_with_data > 0 && $empty_rounds >= 2) {
                error_log('LMC Scraper: Stopping pool ' . $pool_num . ' at round ' . $round . ', last data in round ' . $last_round_with_data);
                break;
            }

            usleep(300000);
        }

        if (empty($all_fixtures)) {
            error_log('LMC Scraper: No fixtures found for pool ' . $pool_num);
        }

        return $all_fixtures;
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
        
        // Sort by date/time if possible, otherwise by round
        usort($upcoming, function($a, $b) {
            $timestamp_a = $this->get_fixture_timestamp($a);
            $timestamp_b = $this->get_fixture_timestamp($b);

            if ($timestamp_a !== null && $timestamp_b !== null) {
                return $timestamp_a <=> $timestamp_b;
            }
            if ($timestamp_a !== null) {
                return -1;
            }
            if ($timestamp_b !== null) {
                return 1;
            }

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
        
        // Sort by date/time (descending), fall back to round
        usort($results, function($a, $b) {
            $timestamp_a = $this->get_fixture_timestamp($a);
            $timestamp_b = $this->get_fixture_timestamp($b);

            if ($timestamp_a !== null && $timestamp_b !== null) {
                return $timestamp_b <=> $timestamp_a;
            }
            if ($timestamp_a !== null) {
                return -1;
            }
            if ($timestamp_b !== null) {
                return 1;
            }

            return $b['round'] - $a['round'];
        });
        
        // Return last 10 results
        return array_slice($results, 0, 10);
    }
    
    /**
     * Get list of all available seasons for an association
     * 
     * @param string $association_id Association ID (e.g., "1064" for Lacrosse Victoria)
     * @return array|false Array of seasons with IDs and names, or false on failure
     */
    public function list_seasons($association_id) {
        $url = "{$this->base_url}/assoc_page.cgi?c=0-{$association_id}-0-0-0&a=COMPS";
        
        error_log('LMC Scraper: Fetching seasons from ' . $url);
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            error_log('LMC Scraper: Failed to fetch seasons - ' . $response->get_error_message());
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return false;
        }
        
        return $this->parse_seasons($body);
    }
    
    /**
     * Parse seasons from HTML
     * 
     * @param string $html HTML content
     * @return array Array of seasons
     */
    private function parse_seasons($html) {
        $seasons = array();
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Find season selector: <select name="seasonID">
        $select = $xpath->query("//select[@name='seasonID']");
        
        if ($select->length > 0) {
            $options = $xpath->query(".//option", $select->item(0));
            
            foreach ($options as $option) {
                $value = $option->getAttribute('value');
                $name = trim($option->textContent);
                
                // Extract season ID from URL
                if (preg_match('/seasonID=(\d+)/', $value, $matches)) {
                    $seasons[] = array(
                        'id' => $matches[1],
                        'name' => $name
                    );
                }
            }
        }
        
        libxml_clear_errors();
        return $seasons;
    }
    
    /**
     * Get list of all available competitions for an association/season
     * 
     * @param string $association_id Association ID (e.g., "1064" for Lacrosse Victoria)
     * @param string $season_id Season ID (e.g., "6042193" for 2025 season)
     * @return array|false Array of competitions with IDs and names, or false on failure
     */
    public function list_competitions($association_id, $season_id) {
        error_log('LMC Scraper: list_competitions() called - START');
        // Use the competitions listing page with season
        $url = "{$this->base_url}/assoc_page.cgi?c=0-{$association_id}-0-0-0&a=COMPS&seasonID={$season_id}";
        
        error_log('LMC Scraper: Fetching competitions from ' . $url);
        error_log('LMC Scraper: About to call wp_remote_get...');
        
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        error_log('LMC Scraper: wp_remote_get completed, checking response...');
        
        if (is_wp_error($response)) {
            error_log('LMC Scraper: Failed to fetch competitions - ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('LMC Scraper: Competitions request returned status ' . $status_code);
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            error_log('LMC Scraper: Empty response body for competitions');
            return false;
        }
        
        error_log('LMC Scraper: Response body length: ' . strlen($body) . ' bytes');
        
        $result = $this->parse_competitions($body);
        
        if (empty($result)) {
            error_log('LMC Scraper: No competitions found in HTML');
            return false;
        }
        
        error_log('LMC Scraper: Successfully parsed ' . count($result) . ' competitions');
        return $result;
    }
    
    /**
     * Parse competitions from HTML (competitions listing page)
     *
     * @param string $html HTML content
     * @return array Parsed competitions data
     */
    private function parse_competitions($html) {
        $competitions = array();
        
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new DOMXPath($dom);
        
        // Find all competition links: <a href="comp_info.cgi?compID=...">
        $links = $xpath->query("//a[contains(@href, 'comp_info.cgi')]");
        
        error_log('LMC Scraper: Found ' . $links->length . ' competition links');
        
        $seen_comp_ids = array(); // Track unique compIDs
        
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            
            // Extract compID from URL: compID=646425
            if (preg_match('/compID=(\d+)/', $href, $matches)) {
                $comp_id_number = $matches[1];
                
                // Build the full competition ID format: 0-1064-0-COMPID-0
                if (preg_match('/c=0-(\d+)-/', $href, $assoc_matches)) {
                    $association_id = $assoc_matches[1];
                    $comp_id = "0-{$association_id}-0-{$comp_id_number}-0";
                    
                    // Skip if we've already seen this compID
                    if (isset($seen_comp_ids[$comp_id_number])) {
                        continue;
                    }
                    $seen_comp_ids[$comp_id_number] = true;
                    
                    // Get the competition name from the parent row
                    // Navigate up to find the competition name (usually in a heading or title before the links)
                    $parent = $link->parentNode;
                    $name = '';
                    
                    // Try to find the competition name in previous siblings or parent elements
                    while ($parent && empty($name)) {
                        // Look for text nodes or heading elements
                        $xpath_name = new DOMXPath($dom);
                        $name_nodes = $xpath_name->query('.//preceding::text()[normalize-space()][1]', $link);
                        if ($name_nodes->length > 0) {
                            $name = trim($name_nodes->item(0)->textContent);
                            break;
                        }
                        $parent = $parent->parentNode;
                    }
                    
                    // If we still don't have a name, try to get it from the row structure
                    if (empty($name) || in_array($name, array('Fixture', 'Results', 'Ladder', 'Stats', 'View'))) {
                        // Try finding a heading or strong tag in the same table row
                        $row = $link;
                        while ($row && $row->nodeName !== 'tr') {
                            $row = $row->parentNode;
                        }
                        
                        if ($row) {
                            $headings = $xpath->query('.//strong | .//b | .//h3 | .//h4', $row);
                            if ($headings->length > 0) {
                                $name = trim($headings->item(0)->textContent);
                            }
                        }
                    }
                    
                    // Skip if we still don't have a proper name
                    if (empty($name) || in_array($name, array('Fixture', 'Results', 'Ladder', 'Stats', 'View'))) {
                        error_log("LMC Scraper: Skipping - no valid name found for compID {$comp_id_number}");
                        continue;
                    }
                    
                    $competitions[] = array(
                        'id' => $comp_id,
                        'name' => $name
                    );
                    error_log("LMC Scraper: Found competition - ID: {$comp_id}, Name: {$name}");
                }
            }
        }
        
        libxml_clear_errors();
        return $competitions;
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
        
        // Fetch fixtures first to get logo data (auto-detect number of rounds)
        $fixtures_success = $this->fetch_all_fixtures($comp_id, $comp_name, 1);
        if ($fixtures_success) {
            error_log('LMC Scraper: Fixtures saved successfully');
            $status['fixtures'] = true;
        } else {
            error_log('LMC Scraper: Failed to fetch fixtures');
        }
        
        // If we have both ladder and fixtures, merge logo data from fixtures into ladder
        if ($ladder && !empty($ladder) && $fixtures_success) {
            // Load fixtures to get logo data
            $fixtures_file = LMC_DATA_DIR . "fixtures-{$comp_id}.json";
            if (file_exists($fixtures_file)) {
                $fixtures_data = json_decode(file_get_contents($fixtures_file), true);
                
                // Build team name to logo mapping from fixtures
                $team_logos = array();
                if (is_array($fixtures_data)) {
                    foreach ($fixtures_data as $fixture) {
                        if (!empty($fixture['home_team']) && !empty($fixture['home_logo'])) {
                            $team_key = $this->normalize_team_key($fixture['home_team']);
                            if ($team_key !== '') {
                                $team_logos[$team_key] = $fixture['home_logo'];
                            }
                        }
                        if (!empty($fixture['away_team']) && !empty($fixture['away_logo'])) {
                            $team_key = $this->normalize_team_key($fixture['away_team']);
                            if ($team_key !== '') {
                                $team_logos[$team_key] = $fixture['away_logo'];
                            }
                        }
                    }
                }
                
                // Merge logos into ladder data
                foreach ($ladder as &$team) {
                    $team_key = $this->normalize_team_key($team['team']);
                    if ($team_key !== '' && isset($team_logos[$team_key])) {
                        $team['logo'] = $team_logos[$team_key];
                        error_log('LMC Scraper: Added logo to ladder for ' . $team['team']);
                    }
                }
                unset($team);
            }
        }
        
        // Save ladder with logo data
        if ($ladder && !empty($ladder)) {
            $ladder_file = LMC_DATA_DIR . "ladder-{$comp_id}.json";
            $result = file_put_contents($ladder_file, json_encode($ladder, JSON_PRETTY_PRINT));
            if ($result === false) {
                error_log('LMC Scraper: Failed to write ladder file: ' . $ladder_file);
            } else {
                error_log('LMC Scraper: Ladder saved successfully with logo data');
                $status['ladder'] = true;
            }
        } else {
            error_log('LMC Scraper: Failed to fetch or parse ladder');
        }

        if (class_exists('LMC_Data')) {
            LMC_Data::clear_cache($comp_id);
        }
        
        // Download and cache team logos if scraping was successful
        if ($status['ladder']) {
            error_log('LMC Scraper: Starting logo download and caching...');
            $this->download_team_logos($comp_id, $ladder);
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
    
    /**
     * Download and cache team logos locally
     *
     * @param string $comp_id Competition ID
     * @param array $ladder Ladder data containing team logos
     * @return void
     */
    private function download_team_logos($comp_id, $ladder) {
        if (!function_exists('wp_upload_dir')) {
            error_log('LMC Scraper: wp_upload_dir not available');
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $lmc_upload_dir = $upload_dir['basedir'] . '/lmc-team-logos';
        $lmc_upload_url = $upload_dir['baseurl'] . '/lmc-team-logos';
        
        // Create directory if it doesn't exist
        if (!file_exists($lmc_upload_dir)) {
            wp_mkdir_p($lmc_upload_dir);
            error_log('LMC Scraper: Created logos directory: ' . $lmc_upload_dir);
        }
        
        $cached_logos = get_option('lmc_cached_logos', array());
        $updated_count = 0;
        
        foreach ($ladder as $team) {
            if (empty($team['logo']) || empty($team['team'])) {
                continue;
            }
            
            $team_key = sanitize_title($team['team']);
            $logo_url = $team['logo'];
            
            // Skip if we already have a cached version and it exists
            if (isset($cached_logos[$team_key]) && file_exists($cached_logos[$team_key]['file'])) {
                continue;
            }
            
            // Download the image
            $downloaded = $this->download_image($logo_url, $lmc_upload_dir, $team_key);
            
            if ($downloaded) {
                $cached_logos[$team_key] = array(
                    'team_name' => $team['team'],
                    'original_url' => $logo_url,
                    'file' => $downloaded['file'],
                    'url' => $lmc_upload_url . '/' . basename($downloaded['file']),
                    'downloaded_at' => current_time('mysql')
                );
                $updated_count++;
                error_log('LMC Scraper: Cached logo for ' . $team['team']);
            }
        }
        
        // Save the cached logos option
        update_option('lmc_cached_logos', $cached_logos);
        
        error_log('LMC Scraper: Logo caching complete. Downloaded ' . $updated_count . ' new logos.');
    }
    
    /**
     * Download an image from a URL and save it locally
     *
     * @param string $url Image URL
     * @param string $target_dir Target directory
     * @param string $filename_prefix Prefix for the filename
     * @return array|false Array with file path and name, or false on failure
     */
    private function download_image($url, $target_dir, $filename_prefix) {
        if (empty($url)) {
            return false;
        }
        
        // Handle relative URLs
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        } elseif (strpos($url, '/') === 0) {
            $url = 'https://websites.mygameday.app' . $url;
        }
        
        // Get the image
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ));
        
        if (is_wp_error($response)) {
            error_log('LMC Scraper: Failed to download image: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('LMC Scraper: Image download returned status ' . $status_code);
            return false;
        }
        
        $image_data = wp_remote_retrieve_body($response);
        if (empty($image_data)) {
            error_log('LMC Scraper: Empty image data received');
            return false;
        }
        
        // Determine file extension from content type or URL
        $content_type = wp_remote_retrieve_header($response, 'content-type');
        $extension = 'png'; // default
        
        if (strpos($content_type, 'jpeg') !== false || strpos($content_type, 'jpg') !== false) {
            $extension = 'jpg';
        } elseif (strpos($content_type, 'png') !== false) {
            $extension = 'png';
        } elseif (strpos($content_type, 'gif') !== false) {
            $extension = 'gif';
        } elseif (strpos($content_type, 'webp') !== false) {
            $extension = 'webp';
        } elseif (strpos($content_type, 'svg') !== false) {
            $extension = 'svg';
        } else {
            // Try to get extension from URL
            $path = parse_url($url, PHP_URL_PATH);
            if ($path) {
                $url_ext = pathinfo($path, PATHINFO_EXTENSION);
                if (in_array(strtolower($url_ext), array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'))) {
                    $extension = strtolower($url_ext);
                }
            }
        }
        
        // Generate filename
        $filename = $filename_prefix . '-' . md5($url) . '.' . $extension;
        $filepath = $target_dir . '/' . $filename;
        
        // Save the file
        $result = file_put_contents($filepath, $image_data);
        
        if ($result === false) {
            error_log('LMC Scraper: Failed to save image to ' . $filepath);
            return false;
        }
        
        return array(
            'file' => $filepath,
            'filename' => $filename
        );
    }
}
