<?php
/**
 * Plugin Name: Lacrosse Match Centre
 * Plugin URI: https://github.com/slappa81/LaxMatchCentre
 * Description: Display lacrosse league data from GameDay with built-in scraper. Shows ladders, upcoming games, and results.
 * Version: 1.0.0
 * Author: Williamstown Lacrosse Club
 * Author URI: https://williamstownlacrosse.com
 * License: MIT
 * Text Domain: lacrosse-match-centre
 * Requires at least: 5.0
 * Requires PHP: 7.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
if (!defined('LMC_VERSION')) {
    define('LMC_VERSION', '1.0.0');
}
if (!defined('LMC_PLUGIN_DIR')) {
    define('LMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('LMC_PLUGIN_URL')) {
    define('LMC_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('LMC_DATA_DIR')) {
    define('LMC_DATA_DIR', LMC_PLUGIN_DIR . 'data/');
}

/**
 * Main plugin class
 */
class Lacrosse_Match_Centre {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize plugin
        add_action('plugins_loaded', array($this, 'init'));
        
        // Register widgets
        add_action('widgets_init', array($this, 'register_widgets'));
        
        // Setup cron for auto-scraping
        add_action('lmc_hourly_scrape', array($this, 'cron_scrape_competitions'));
        
        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        
        // Enqueue block editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        
        // Register block category
        add_filter('block_categories_all', array($this, 'register_block_category'), 10, 2);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        $files = array(
            'includes/class-lmc-scraper.php',
            'includes/class-lmc-data.php',
            'includes/class-lmc-admin.php',
            'includes/class-lmc-blocks.php',
            'includes/class-lmc-ladder-widget.php',
            'includes/class-lmc-upcoming-widget.php',
            'includes/class-lmc-results-widget.php',
            'includes/class-lmc-cli.php'
        );
        
        foreach ($files as $file) {
            $filepath = LMC_PLUGIN_DIR . $file;
            if (file_exists($filepath)) {
                require_once $filepath;
            }
        }
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Initialize admin interface
        if (is_admin()) {
            new LMC_Admin();
        }
        
        // Initialize blocks
        if (class_exists('LMC_Blocks')) {
            new LMC_Blocks();
            error_log('LMC: Blocks class initialized');
        } else {
            error_log('LMC: Blocks class not found');
        }
    }
    
    /**
     * Register widgets
     */
    public function register_widgets() {
        register_widget('LMC_Ladder_Widget');
        register_widget('LMC_Upcoming_Widget');
        register_widget('LMC_Results_Widget');
    }
    
    /**
     * Enqueue plugin styles
     */
    public function enqueue_styles() {
        wp_enqueue_style(
            'lacrosse-match-centre',
            LMC_PLUGIN_URL . 'assets/style.css',
            array(),
            LMC_VERSION
        );
        
        // Register block styles for front-end
        wp_register_style(
            'lacrosse-match-centre-blocks',
            LMC_PLUGIN_URL . 'assets/blocks.css',
            array(),
            LMC_VERSION
        );
        wp_enqueue_style('lacrosse-match-centre-blocks');
    }
    
    /**
     * Register custom block category
     */
    public function register_block_category($categories, $post) {
        return array_merge(
            array(
                array(
                    'slug'  => 'lacrosse-match-centre',
                    'title' => __('Lacrosse Match Centre', 'lacrosse-match-centre'),
                    'icon'  => 'awards'
                )
            ),
            $categories
        );
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        error_log('LMC: Enqueuing block editor assets (via enqueue_block_editor_assets hook)');
        // Assets are now registered in LMC_Blocks class
        // This hook just logs that it's being called
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Load dependencies if not already loaded
        if (!class_exists('LMC_Data')) {
            $this->load_dependencies();
        }
        
        // Create data directory if it doesn't exist
        if (!file_exists(LMC_DATA_DIR)) {
            wp_mkdir_p(LMC_DATA_DIR);
        }
        
        // Set default options
        $default_options = array(
            'cache_duration' => 3600,
            'competitions' => array(),
            'current_competition' => ''
        );
        
        if (!get_option('lmc_settings')) {
            add_option('lmc_settings', $default_options);
        }
        
        // Create .htaccess to protect data directory
        $htaccess_file = LMC_DATA_DIR . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Protect data directory\nOrder deny,allow\nDeny from all\n<Files ~ \"\\.(json)$\">\n    Allow from all\n</Files>";
            @file_put_contents($htaccess_file, $htaccess_content);
        }
        
        // Schedule hourly cron job for auto-scraping
        if (!wp_next_scheduled('lmc_hourly_scrape')) {
            wp_schedule_event(time(), 'hourly', 'lmc_hourly_scrape');
            error_log('LMC: Scheduled hourly auto-scraping');
        }
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear all cached data
        if (class_exists('LMC_Data')) {
            LMC_Data::clear_all_cache();
        }
        
        // Clear scheduled cron job
        $timestamp = wp_next_scheduled('lmc_hourly_scrape');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'lmc_hourly_scrape');
            error_log('LMC: Unscheduled hourly auto-scraping');
        }
    }
    
    /**
     * Cron handler to scrape all competitions
     */
    public function cron_scrape_competitions() {
        error_log('LMC Cron: Starting hourly scrape of all competitions');
        
        // Get settings
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        
        if (empty($competitions)) {
            error_log('LMC Cron: No competitions configured, skipping scrape');
            return;
        }
        
        // Load scraper if not already loaded
        if (!class_exists('LMC_Scraper')) {
            require_once LMC_PLUGIN_DIR . 'includes/class-lmc-scraper.php';
        }
        
        $scraper = new LMC_Scraper();
        $success_count = 0;
        $error_count = 0;
        
        // Scrape each competition
        foreach ($competitions as $comp) {
            $comp_id = isset($comp['id']) ? $comp['id'] : '';
            $comp_name = isset($comp['name']) ? $comp['name'] : '';
            
            if (empty($comp_id)) {
                continue;
            }
            
            error_log("LMC Cron: Scraping competition: {$comp_name} (ID: {$comp_id})");
            
            try {
                $result = $scraper->scrape_competition($comp_id, $comp_name);
                
                if ($result['success']) {
                    $success_count++;
                    error_log("LMC Cron: Successfully scraped {$comp_name}");
                } else {
                    $error_count++;
                    error_log("LMC Cron: Error scraping {$comp_name}: " . $result['message']);
                }
            } catch (Exception $e) {
                $error_count++;
                error_log("LMC Cron: Exception scraping {$comp_name}: " . $e->getMessage());
            }
        }
        
        error_log("LMC Cron: Completed hourly scrape - Success: {$success_count}, Errors: {$error_count}");
    }
}

// Initialize the plugin
if (!isset($GLOBALS['lacrosse_match_centre'])) {
    $GLOBALS['lacrosse_match_centre'] = new Lacrosse_Match_Centre();
}

// Register WP-CLI commands if WP-CLI is available
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('lmc', 'LMC_CLI');
}
