<?php
/**
 * Blocks Registration
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Blocks class
 */
class LMC_Blocks {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_block_assets'), 5);
        add_action('init', array($this, 'register_blocks'), 10);
    }
    
    /**
     * Register block assets (scripts and styles)
     */
    public function register_block_assets() {
        // Register block editor JavaScript
        wp_register_script(
            'lacrosse-match-centre-blocks',
            plugin_dir_url(dirname(__FILE__)) . 'assets/blocks.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-block-editor', 'wp-components', 'wp-i18n'),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/blocks.js'),
            false
        );
        
        // Register block editor styles
        wp_register_style(
            'lacrosse-match-centre-blocks-editor',
            plugin_dir_url(dirname(__FILE__)) . 'assets/blocks.css',
            array('wp-edit-blocks'),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/blocks.css')
        );
        
        // Register front-end block styles
        wp_register_style(
            'lacrosse-match-centre-blocks',
            plugin_dir_url(dirname(__FILE__)) . 'assets/blocks.css',
            array(),
            filemtime(plugin_dir_path(dirname(__FILE__)) . 'assets/blocks.css')
        );
        
        error_log('LMC Blocks: Assets registered');
    }
    
    /**
     * Register all blocks
     */
    public function register_blocks() {
        // Check if register_block_type exists
        if (!function_exists('register_block_type')) {
            error_log('LMC Blocks: register_block_type function does not exist');
            return;
        }
        
        error_log('LMC Blocks: Registering blocks...');
        
        // Register ladder block
        register_block_type('lacrosse-match-centre/ladder', array(
            'api_version' => 2,
            'editor_script' => 'lacrosse-match-centre-blocks',
            'editor_style' => 'lacrosse-match-centre-blocks-editor',
            'style' => 'lacrosse-match-centre-blocks',
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => 'Competition Ladder'
                ),
                'compId' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_ladder_block')
        ));
        
        error_log('LMC Blocks: Ladder block registered');
        
        // Register upcoming games block
        register_block_type('lacrosse-match-centre/upcoming', array(
            'api_version' => 2,
            'editor_script' => 'lacrosse-match-centre-blocks',
            'editor_style' => 'lacrosse-match-centre-blocks-editor',
            'style' => 'lacrosse-match-centre-blocks',
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => 'Upcoming Games'
                ),
                'compId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 5
                )
            ),
            'render_callback' => array($this, 'render_upcoming_block')
        ));
        
        error_log('LMC Blocks: Upcoming block registered');
        
        // Register results block
        register_block_type('lacrosse-match-centre/results', array(
            'api_version' => 2,
            'editor_script' => 'lacrosse-match-centre-blocks',
            'editor_style' => 'lacrosse-match-centre-blocks-editor',
            'style' => 'lacrosse-match-centre-blocks',
            'attributes' => array(
                'title' => array(
                    'type' => 'string',
                    'default' => 'Recent Results'
                ),
                'compId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'limit' => array(
                    'type' => 'number',
                    'default' => 5
                )
            ),
            'render_callback' => array($this, 'render_results_block')
        ));
        
        error_log('LMC Blocks: Results block registered');
        error_log('LMC Blocks: All blocks registered successfully');
    }
    
    /**
     * Render ladder block
     */
    public function render_ladder_block($attributes) {
        $title = isset($attributes['title']) ? $attributes['title'] : 'Competition Ladder';
        $comp_id = isset($attributes['compId']) && !empty($attributes['compId']) ? $attributes['compId'] : null;
        
        error_log('LMC Blocks: Rendering ladder block with compId: ' . ($comp_id ? $comp_id : 'NULL (will use current)'));
        error_log('LMC Blocks: Block attributes: ' . print_r($attributes, true));
        
        ob_start();
        
        echo '<div class="wp-block-lacrosse-match-centre-ladder lmc-ladder-block">';
        
        if (!empty($title)) {
            echo '<h2 class="lmc-block-title">' . esc_html($title) . '</h2>';
        }
        
        // Get ladder data
        $ladder = LMC_Data::get_ladder($comp_id);
        error_log('LMC Blocks: Ladder data result: ' . ($ladder ? count($ladder) . ' teams' : 'FALSE/NULL'));
        
        if ($ladder && !empty($ladder)) {
            echo '<div class="lmc-ladder">';
            echo '<table class="lmc-ladder-table">';
            echo '<thead>';
            echo '<tr>';
            echo '<th class="pos">Pos</th>';
            echo '<th class="team">Team</th>';
            echo '<th class="played">P</th>';
            echo '<th class="won">W</th>';
            echo '<th class="lost">L</th>';
            echo '<th class="drawn">D</th>';
            echo '<th class="points">Pts</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($ladder as $team) {
                echo '<tr>';
                echo '<td class="pos">' . esc_html($team['position']) . '</td>';
                echo '<td class="team">' . esc_html($team['team']) . '</td>';
                echo '<td class="played">' . esc_html($team['played']) . '</td>';
                echo '<td class="won">' . esc_html($team['won']) . '</td>';
                echo '<td class="lost">' . esc_html($team['lost']) . '</td>';
                echo '<td class="drawn">' . esc_html($team['drawn']) . '</td>';
                echo '<td class="points">' . esc_html($team['points']) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
            echo '</div>';
        } else {
            echo '<p class="lmc-no-data">No ladder data available. Please scrape data from the admin panel.</p>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render upcoming games block
     */
    public function render_upcoming_block($attributes) {
        $title = isset($attributes['title']) ? $attributes['title'] : 'Upcoming Games';
        $comp_id = isset($attributes['compId']) && !empty($attributes['compId']) ? $attributes['compId'] : null;
        $limit = isset($attributes['limit']) ? absint($attributes['limit']) : 5;
        
        error_log('LMC Blocks: Rendering upcoming block with compId: ' . ($comp_id ? $comp_id : 'NULL (will use current)'));
        
        ob_start();
        
        echo '<div class="wp-block-lacrosse-match-centre-upcoming lmc-upcoming-block">';
        
        if (!empty($title)) {
            echo '<h2 class="lmc-block-title">' . esc_html($title) . '</h2>';
        }
        
        // Get upcoming games
        $games = LMC_Data::get_upcoming_games($comp_id, $limit);
        error_log('LMC Blocks: Upcoming games result: ' . ($games ? count($games) . ' games' : 'FALSE/NULL'));
        
        if ($games && !empty($games)) {
            echo '<div class="lmc-upcoming">';
            
            foreach ($games as $game) {
                echo '<div class="lmc-game">';
                echo '<div class="lmc-game-round">Round ' . esc_html($game['round']) . '</div>';
                // Use formatted datetime if available, otherwise fall back to raw date/time
                if (!empty($game['formatted_datetime'])) {
                    echo '<div class="lmc-game-datetime">' . esc_html($game['formatted_datetime']) . '</div>';
                } else {
                    echo '<div class="lmc-game-date">' . esc_html($game['date']) . '</div>';
                    if (!empty($game['time'])) {
                        echo '<div class="lmc-game-time">' . esc_html($game['time']) . '</div>';
                    }
                }
                echo '<div class="lmc-game-teams">';
                echo '<div class="lmc-team-home">' . esc_html($game['home_team']) . '</div>';
                echo '<div class="lmc-vs">vs</div>';
                echo '<div class="lmc-team-away">' . esc_html($game['away_team']) . '</div>';
                echo '</div>';
                if (!empty($game['venue'])) {
                    echo '<div class="lmc-game-venue">' . esc_html($game['venue']) . '</div>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<p class="lmc-no-data">No upcoming games available. Please scrape data from the admin panel.</p>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    /**
     * Render results block
     */
    public function render_results_block($attributes) {
        $title = isset($attributes['title']) ? $attributes['title'] : 'Recent Results';
        $comp_id = isset($attributes['compId']) && !empty($attributes['compId']) ? $attributes['compId'] : null;
        $limit = isset($attributes['limit']) ? absint($attributes['limit']) : 5;
        
        error_log('LMC Blocks: Rendering results block with compId: ' . ($comp_id ? $comp_id : 'NULL (will use current)'));
        
        ob_start();
        
        echo '<div class="wp-block-lacrosse-match-centre-results lmc-results-block">';
        
        if (!empty($title)) {
            echo '<h2 class="lmc-block-title">' . esc_html($title) . '</h2>';
        }
        
        // Get results
        $results = LMC_Data::get_results($comp_id, $limit);
        error_log('LMC Blocks: Results data result: ' . ($results ? count($results) . ' results' : 'FALSE/NULL'));
        
        if ($results && !empty($results)) {
            echo '<div class="lmc-results">';
            
            foreach ($results as $result) {
                echo '<div class="lmc-result">';
                echo '<div class="lmc-result-round">Round ' . esc_html($result['round']) . '</div>';
                // Use formatted datetime if available, otherwise fall back to raw date
                if (!empty($result['formatted_datetime'])) {
                    echo '<div class="lmc-result-datetime">' . esc_html($result['formatted_datetime']) . '</div>';
                } else {
                    echo '<div class="lmc-result-date">' . esc_html($result['date']) . '</div>';
                }
                echo '<div class="lmc-result-teams">';
                echo '<div class="lmc-result-team lmc-result-home">';
                echo '<span class="lmc-result-team-name">' . esc_html($result['home_team']) . '</span>';
                echo '<span class="lmc-result-score">' . esc_html($result['home_score']) . '</span>';
                echo '</div>';
                echo '<div class="lmc-result-team lmc-result-away">';
                echo '<span class="lmc-result-team-name">' . esc_html($result['away_team']) . '</span>';
                echo '<span class="lmc-result-score">' . esc_html($result['away_score']) . '</span>';
                echo '</div>';
                echo '</div>';
                if (!empty($result['venue'])) {
                    echo '<div class="lmc-result-venue">' . esc_html($result['venue']) . '</div>';
                }
                echo '</div>';
            }
            
            echo '</div>';
        } else {
            echo '<p class="lmc-no-data">No results available. Please scrape data from the admin panel.</p>';
        }
        
        echo '</div>';
        
        return ob_get_clean();
    }
}
