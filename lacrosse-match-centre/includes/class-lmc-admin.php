<?php
/**
 * Admin interface class
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Admin class
 */
class LMC_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_lmc_scrape_competition', array($this, 'ajax_scrape_competition'));
        add_action('wp_ajax_lmc_clear_cache', array($this, 'ajax_clear_cache'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Lacrosse Match Centre Settings',
            'Match Centre',
            'manage_options',
            'lacrosse-match-centre',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('lmc_settings_group', 'lmc_settings', array($this, 'sanitize_settings'));
        
        // General Settings Section
        add_settings_section(
            'lmc_general_section',
            'General Settings',
            array($this, 'render_general_section'),
            'lacrosse-match-centre'
        );
        
        // Cache Duration Field
        add_settings_field(
            'cache_duration',
            'Cache Duration (seconds)',
            array($this, 'render_cache_duration_field'),
            'lacrosse-match-centre',
            'lmc_general_section'
        );
        
        // Competitions Section
        add_settings_section(
            'lmc_competitions_section',
            'Competitions',
            array($this, 'render_competitions_section'),
            'lacrosse-match-centre'
        );
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Input values
     * @return array Sanitized values
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Sanitize cache duration
        if (isset($input['cache_duration'])) {
            $sanitized['cache_duration'] = absint($input['cache_duration']);
            if ($sanitized['cache_duration'] < 60) {
                $sanitized['cache_duration'] = 60; // Minimum 1 minute
            }
        } else {
            $sanitized['cache_duration'] = 3600;
        }
        
        // Sanitize competitions
        if (isset($input['competitions']) && is_array($input['competitions'])) {
            $sanitized['competitions'] = array();
            foreach ($input['competitions'] as $comp) {
                $sanitized['competitions'][] = array(
                    'id' => sanitize_text_field($comp['id']),
                    'name' => sanitize_text_field($comp['name']),
                    'current_round' => absint($comp['current_round']),
                    'max_rounds' => absint($comp['max_rounds'])
                );
            }
        } else {
            $sanitized['competitions'] = array();
        }
        
        // Sanitize current competition
        if (isset($input['current_competition'])) {
            $sanitized['current_competition'] = sanitize_text_field($input['current_competition']);
        } else {
            $sanitized['current_competition'] = '';
        }
        
        return $sanitized;
    }
    
    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>Configure general plugin settings.</p>';
    }
    
    /**
     * Render competitions section
     */
    public function render_competitions_section() {
        echo '<p>Manage your competitions and scrape data from SportsTG.</p>';
    }
    
    /**
     * Render cache duration field
     */
    public function render_cache_duration_field() {
        $settings = get_option('lmc_settings', array());
        $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
        ?>
        <input type="number" name="lmc_settings[cache_duration]" value="<?php echo esc_attr($cache_duration); ?>" min="60" step="60" class="regular-text">
        <p class="description">How long to cache data before refreshing (minimum 60 seconds).</p>
        <?php
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_lacrosse-match-centre') {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_add_inline_script('jquery', "
            jQuery(document).ready(function($) {
                // Add competition
                $('#lmc-add-competition').on('click', function() {
                    var template = $('#competition-template').html();
                    var index = $('.lmc-competition-row').length;
                    template = template.replace(/INDEX/g, index);
                    $('#lmc-competitions-list').append(template);
                });
                
                // Remove competition
                $(document).on('click', '.lmc-remove-competition', function() {
                    $(this).closest('.lmc-competition-row').remove();
                });
                
                // Scrape competition
                $(document).on('click', '.lmc-scrape-btn', function() {
                    var btn = $(this);
                    var row = btn.closest('.lmc-competition-row');
                    var compId = row.find('.comp-id').val();
                    var compName = row.find('.comp-name').val();
                    var currentRound = row.find('.current-round').val();
                    var maxRounds = row.find('.max-rounds').val();
                    var statusDiv = row.find('.scrape-status');
                    
                    if (!compId || !compName || !currentRound || !maxRounds) {
                        alert('Please fill in all competition fields before scraping.');
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Scraping...');
                    statusDiv.html('<span style=\"color: blue;\">⏳ Scraping data...</span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lmc_scrape_competition',
                            nonce: '" . wp_create_nonce('lmc_scrape_nonce') . "',
                            comp_id: compId,
                            comp_name: compName,
                            current_round: currentRound,
                            max_rounds: maxRounds
                        },
                        success: function(response) {
                            if (response.success) {
                                statusDiv.html('<span style=\"color: green;\">✓ ' + response.data.message + '</span>');
                            } else {
                                statusDiv.html('<span style=\"color: red;\">✗ ' + response.data.message + '</span>');
                            }
                            btn.prop('disabled', false).text('Scrape Data');
                        },
                        error: function() {
                            statusDiv.html('<span style=\"color: red;\">✗ Error occurred</span>');
                            btn.prop('disabled', false).text('Scrape Data');
                        }
                    });
                });
                
                // Clear cache
                $('#lmc-clear-cache').on('click', function() {
                    var btn = $(this);
                    
                    if (!confirm('Are you sure you want to clear all cached data?')) {
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Clearing...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lmc_clear_cache',
                            nonce: '" . wp_create_nonce('lmc_cache_nonce') . "'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Cache cleared successfully!');
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                            btn.prop('disabled', false).text('Clear Cache');
                        },
                        error: function() {
                            alert('Error occurred while clearing cache.');
                            btn.prop('disabled', false).text('Clear Cache');
                        }
                    });
                });
            });
        ");
        
        wp_add_inline_style('wp-admin', "
            .lmc-competition-row {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 15px;
                margin-bottom: 15px;
                border-radius: 4px;
            }
            .lmc-competition-row input {
                margin-right: 10px;
                margin-bottom: 10px;
            }
            .lmc-scrape-btn {
                margin-top: 10px;
            }
            .scrape-status {
                margin-top: 10px;
                padding: 5px;
            }
            .lmc-data-status {
                background: #f0f0f1;
                padding: 15px;
                border-radius: 4px;
                margin-top: 20px;
            }
        ");
    }
    
    /**
     * AJAX handler for scraping competition
     */
    public function ajax_scrape_competition() {
        check_ajax_referer('lmc_scrape_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $comp_id = sanitize_text_field($_POST['comp_id']);
        $comp_name = sanitize_text_field($_POST['comp_name']);
        $current_round = absint($_POST['current_round']);
        $max_rounds = absint($_POST['max_rounds']);
        
        if (empty($comp_id) || empty($comp_name) || !$current_round || !$max_rounds) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }
        
        $scraper = new LMC_Scraper();
        $result = $scraper->scrape_competition($comp_id, $comp_name, $current_round, $max_rounds);
        
        if ($result['success']) {
            // Clear cache for this competition
            LMC_Data::clear_cache($comp_id);
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        check_ajax_referer('lmc_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        LMC_Data::clear_all_cache();
        wp_send_json_success(array('message' => 'Cache cleared'));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        $current_competition = isset($settings['current_competition']) ? $settings['current_competition'] : '';
        ?>
        <div class="wrap">
            <h1>Lacrosse Match Centre Settings</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('lmc_settings_group');
                do_settings_sections('lacrosse-match-centre');
                ?>
                
                <h2>Manage Competitions</h2>
                <p>Add competitions and scrape data from SportsTG. Competition ID can be found in the SportsTG URL.</p>
                
                <div id="lmc-competitions-list">
                    <?php foreach ($competitions as $index => $comp): ?>
                    <div class="lmc-competition-row">
                        <h3>Competition <?php echo esc_html($index + 1); ?></h3>
                        
                        <label>
                            <input type="radio" name="lmc_settings[current_competition]" value="<?php echo esc_attr($comp['id']); ?>" <?php checked($current_competition, $comp['id']); ?>>
                            Use as current competition
                        </label>
                        <br><br>
                        
                        <input type="text" name="lmc_settings[competitions][<?php echo $index; ?>][id]" class="comp-id regular-text" placeholder="Competition ID (e.g., 140768)" value="<?php echo esc_attr($comp['id']); ?>">
                        <input type="text" name="lmc_settings[competitions][<?php echo $index; ?>][name]" class="comp-name regular-text" placeholder="Competition Name" value="<?php echo esc_attr($comp['name']); ?>">
                        <br>
                        <input type="number" name="lmc_settings[competitions][<?php echo $index; ?>][current_round]" class="current-round" placeholder="Current Round" value="<?php echo esc_attr($comp['current_round']); ?>" min="1">
                        <input type="number" name="lmc_settings[competitions][<?php echo $index; ?>][max_rounds]" class="max-rounds" placeholder="Max Rounds" value="<?php echo esc_attr($comp['max_rounds']); ?>" min="1">
                        
                        <br>
                        <button type="button" class="button lmc-scrape-btn">Scrape Data</button>
                        <button type="button" class="button lmc-remove-competition">Remove</button>
                        
                        <div class="scrape-status"></div>
                        
                        <?php
                        $data_info = LMC_Data::get_data_info($comp['id']);
                        if ($data_info['data_available']):
                        ?>
                        <div class="lmc-data-status">
                            <strong>Data Status:</strong><br>
                            <?php foreach ($data_info['files'] as $type => $file_info): ?>
                                <?php if ($file_info['exists']): ?>
                                    ✓ <?php echo ucfirst($type); ?>: Last updated <?php echo human_time_diff($file_info['modified'], current_time('timestamp')); ?> ago<br>
                                <?php else: ?>
                                    ✗ <?php echo ucfirst($type); ?>: Not available<br>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="button" id="lmc-add-competition" class="button">Add Competition</button>
                
                <script type="text/template" id="competition-template">
                    <div class="lmc-competition-row">
                        <h3>New Competition</h3>
                        
                        <label>
                            <input type="radio" name="lmc_settings[current_competition]" value="">
                            Use as current competition
                        </label>
                        <br><br>
                        
                        <input type="text" name="lmc_settings[competitions][INDEX][id]" class="comp-id regular-text" placeholder="Competition ID (e.g., 140768)">
                        <input type="text" name="lmc_settings[competitions][INDEX][name]" class="comp-name regular-text" placeholder="Competition Name">
                        <br>
                        <input type="number" name="lmc_settings[competitions][INDEX][current_round]" class="current-round" placeholder="Current Round" min="1">
                        <input type="number" name="lmc_settings[competitions][INDEX][max_rounds]" class="max-rounds" placeholder="Max Rounds" min="1">
                        
                        <br>
                        <button type="button" class="button lmc-scrape-btn">Scrape Data</button>
                        <button type="button" class="button lmc-remove-competition">Remove</button>
                        
                        <div class="scrape-status"></div>
                    </div>
                </script>
                
                <h2>Cache Management</h2>
                <p>Clear all cached data to force fresh data loading.</p>
                <button type="button" id="lmc-clear-cache" class="button">Clear Cache</button>
                
                <p class="submit">
                    <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>
        <?php
    }
}
