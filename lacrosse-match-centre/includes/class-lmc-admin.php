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
        add_action('admin_notices', array($this, 'show_admin_notices'));
        
        // AJAX handlers
        add_action('wp_ajax_lmc_scrape_competition', array($this, 'ajax_scrape_competition'));
        add_action('wp_ajax_lmc_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('wp_ajax_lmc_list_seasons', array($this, 'ajax_list_seasons'));
        add_action('wp_ajax_lmc_list_available_competitions', array($this, 'ajax_list_available_competitions'));
        add_action('wp_ajax_lmc_get_teams', array($this, 'ajax_get_teams'));
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $settings = get_option('lmc_settings', array());
        $current_competition = isset($settings['current_competition']) ? $settings['current_competition'] : '';
        $has_competitions = isset($settings['competitions']) && !empty($settings['competitions']);
        
        // Only show on relevant pages
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->id, array('dashboard', 'settings_page_lacrosse-match-centre', 'edit-page', 'edit-post'))) {
            return;
        }
        
        // Warning if no current competition is set
        if ($has_competitions && empty($current_competition)) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Lacrosse Match Centre:</strong> No current competition is selected. 
                <a href="<?php echo admin_url('options-general.php?page=lacrosse-match-centre'); ?>">Select a competition</a> 
                to display data in blocks and widgets.</p>
            </div>
            <?php
        }
        
        // Info if no competitions configured
        if (!$has_competitions) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p><strong>Lacrosse Match Centre:</strong> No competitions configured yet. 
                <a href="<?php echo admin_url('options-general.php?page=lacrosse-match-centre'); ?>">Add a competition</a> 
                to start displaying match data.</p>
            </div>
            <?php
        }
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
                $comp_data = array(
                    'id' => sanitize_text_field($comp['id']),
                    'name' => sanitize_text_field($comp['name'])
                );
                
                // Add optional fields if present
                if (isset($comp['season'])) {
                    $comp_data['season'] = sanitize_text_field($comp['season']);
                }
                if (isset($comp['primary_team']) && !empty($comp['primary_team'])) {
                    $comp_data['primary_team'] = sanitize_text_field($comp['primary_team']);
                }
                
                $sanitized['competitions'][] = $comp_data;
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
        
        $scraper_nonce = wp_create_nonce('lmc_scraper_nonce');
        $cache_nonce = wp_create_nonce('lmc_cache_nonce');
        $list_competitions_nonce = wp_create_nonce('lmc_list_competitions_nonce');
        $get_teams_nonce = wp_create_nonce('lmc_get_teams_nonce');
        
        $inline_script = <<<JAVASCRIPT
            jQuery(document).ready(function($) {
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
                    var statusDiv = row.find('.scrape-status');
                    
                    if (!compId || !compName) {
                        alert('Please fill in Competition ID and Name before scraping.');
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Scraping...');
                    statusDiv.html('<span style=\"color: blue;\">⏳ Scraping data (auto-detecting rounds)...</span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 300000, // 5 minutes timeout (scraping can take time)
                        data: {
                            action: 'lmc_scrape_competition',
                            nonce: '{$scraper_nonce}',
                            comp_id: compId,
                            comp_name: compName
                        },
                        success: function(response) {
                            if (response.success) {
                                statusDiv.html('<span style=\"color: green;\">✓ ' + response.data.message + '</span>');
                            } else {
                                statusDiv.html('<span style=\"color: red;\">✗ ' + response.data.message + '</span>');
                            }
                            btn.prop('disabled', false).text('Scrape Data');
                        },
                        error: function(xhr, status, error) {
                            console.error('LMC Scraper AJAX Error:', status, error, xhr.responseText);
                            var errorMsg = 'Error occurred';
                            if (status === 'timeout') {
                                errorMsg = 'Request timed out (scraping may still be running - check logs)';
                            } else if (xhr.responseText) {
                                errorMsg = 'Error: ' + error;
                            }
                            statusDiv.html('<span style=\"color: red;\">✗ ' + errorMsg + '</span>');
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
                            nonce: '{$cache_nonce}'
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
                
                // Step 1: Discover seasons
                $('#lmc-discover-seasons-btn').on('click', function() {
                    var btn = $(this);
                    var associationId = $('#lmc-discover-association-id').val().trim();
                    var statusDiv = $('#lmc-discover-status');
                    var seasonsDiv = $('#lmc-seasons-selection');
                    var resultsDiv = $('#lmc-discover-results');
                    
                    if (!associationId) {
                        statusDiv.html('<span style=\"color: red;\">⚠️ Please enter an Association ID</span>');
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Loading Seasons...');
                    statusDiv.html('<span style=\"color: blue;\">🔍 Fetching seasons from GameDay...</span>');
                    seasonsDiv.html('');
                    resultsDiv.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 30000,
                        data: {
                            action: 'lmc_list_seasons',
                            nonce: '{$list_competitions_nonce}',
                            association_id: associationId
                        },
                        success: function(response) {
                            console.log('Season load success:', response);
                            btn.prop('disabled', false).text('Load Seasons');
                            
                            if (response.success && response.data.seasons) {
                                var seasons = response.data.seasons;
                                statusDiv.html('<span style=\"color: green;\">✓ Found ' + seasons.length + ' seasons. Select a season below:</span>');
                                
                                var html = '<div style=\"margin: 15px 0;\">';
                                html += '<select id=\"lmc-season-select\" class=\"regular-text\" style=\"margin-right: 10px;\">';
                                html += '<option value=\"\">-- Select a Season --</option>';
                                seasons.forEach(function(season) {
                                    html += '<option value=\"' + season.id + '\">' + season.name + '</option>';
                                });
                                html += '</select>';
                                html += '<button type=\"button\" id=\"lmc-load-competitions-btn\" class=\"button\">Load Competitions</button>';
                                html += '</div>';
                                
                                seasonsDiv.html(html);
                            } else {
                                statusDiv.html('<span style=\"color: red;\">✗ ' + (response.data ? response.data.message : 'No seasons found') + '</span>');
                            }
                        },
                        error: function(xhr, status, error) {
                            console.log('Season load error:', xhr, status, error);
                            console.log('Response text:', xhr.responseText);
                            btn.prop('disabled', false).text('Load Seasons');
                            statusDiv.html('<span style=\"color: red;\">✗ Request failed</span>');
                        }
                    });
                });
                
                // Step 2: Load competitions for selected season
                $(document).on('click', '#lmc-load-competitions-btn', function() {
                    var btn = $(this);
                    var associationId = $('#lmc-discover-association-id').val().trim();
                    var seasonId = $('#lmc-season-select').val();
                    var seasonName = $('#lmc-season-select option:selected').text();
                    var statusDiv = $('#lmc-discover-status');
                    var resultsDiv = $('#lmc-discover-results');
                    
                    if (!seasonId) {
                        statusDiv.html('<span style=\"color: red;\">⚠️ Please select a season</span>');
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Loading...');
                    statusDiv.html('<span style=\"color: blue;\">🔍 Fetching competitions for selected season...</span>');
                    resultsDiv.html('');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        timeout: 30000,
                        data: {
                            action: 'lmc_list_available_competitions',
                            nonce: '{$list_competitions_nonce}',
                            association_id: associationId,
                            season_id: seasonId,
                            season_name: seasonName
                        },
                        success: function(response) {
                            btn.prop('disabled', false).text('Load Competitions');
                            
                            if (response.success && response.data.competitions) {
                                var comps = response.data.competitions;
                                statusDiv.html('<span style=\"color: green;\">✓ Found ' + comps.length + ' competitions. Select competitions to scrape:</span>');
                                
                                var html = '<div style=\"margin-top: 15px; border: 1px solid #ccc; padding: 15px; background: #f9f9f9;\">';
                                html += '<div style=\"margin-bottom: 10px;\">';
                                html += '<button type=\"button\" id=\"lmc-select-all-comps\" class=\"button button-small\" style=\"margin-right: 5px;\">Select All</button>';
                                html += '<button type=\"button\" id=\"lmc-deselect-all-comps\" class=\"button button-small\" style=\"margin-right: 5px;\">Deselect All</button>';
                                html += '<button type=\"button\" id=\"lmc-add-selected-comps\" class=\"button button-primary\">Add Selected Competitions</button>';
                                html += '</div>';
                                html += '<div style=\"max-height: 400px; overflow-y: auto; background: white; padding: 10px; border: 1px solid #ddd;\">';
                                
                                comps.forEach(function(comp) {
                                    html += '<div style=\"margin: 8px 0; padding: 8px; border-bottom: 1px solid #eee;\">';
                                    html += '<label style=\"display: flex; align-items: center; cursor: pointer;\">';
                                    html += '<input type="checkbox" class="lmc-comp-checkbox" data-id="' + comp.id + '" data-name="' + comp.name + '" data-season="' + seasonName + '" style="margin-right: 10px;">';
                                    html += '<span style="flex: 1;"><strong>' + comp.name + '</strong></span>';
                                    html += '<code style="font-size: 11px; color: #666;">' + comp.id + '</code>';
                                    html += '</label>';
                                    html += '</div>';
                                });  
                                
                                html += '</div></div>';
                                resultsDiv.html(html);
                            } else {
                                statusDiv.html('<span style=\"color: red;\">✗ ' + (response.data ? response.data.message : 'No competitions found') + '</span>');
                            }
                        },
                        error: function(xhr, status, error) {
                            btn.prop('disabled', false).text('Load Competitions');
                            statusDiv.html('<span style=\"color: red;\">✗ Request failed</span>');
                        }
                    });
                });
                
                // Select/deselect all checkboxes
                $(document).on('click', '#lmc-select-all-comps', function() {
                    $('.lmc-comp-checkbox').prop('checked', true);
                });
                
                $(document).on('click', '#lmc-deselect-all-comps', function() {
                    $('.lmc-comp-checkbox').prop('checked', false);
                });
                
                // Add selected competitions to the configuration
                $(document).on('click', '#lmc-add-selected-comps', function() {
                    var checked = $('.lmc-comp-checkbox:checked');
                    
                    if (checked.length === 0) {
                        alert('Please select at least one competition');
                        return;
                    }
                    
                    checked.each(function() {
                        var compId = $(this).data('id');
                        var compName = $(this).data('name');
                        var seasonName = $(this).data('season');
                        
                        // Add a new competition row
                        var template = $('#competition-template').html();
                        var index = $('.lmc-competition-row').length;
                        template = template.replace(/INDEX/g, index);
                        $('#lmc-competitions-list').append(template);
                        
                        // Fill in the values with season prefix
                        var newRow = $('.lmc-competition-row').last();
                        newRow.find('.comp-id').val(compId);
                        newRow.find('.comp-name').val(seasonName + ' - ' + compName);
                        newRow.find('.comp-season').val(seasonName);
                    });
                    
                    // Clear the discovery results
                    $('#lmc-discover-results').html('');
                    $('#lmc-discover-status').html('<span style=\"color: green;\">✓ Added ' + checked.length + ' competitions to configuration</span>');
                });
                
                // Old "Use This" button handler (kept for backwards compatibility if needed)
                $(document).on('click', '.lmc-use-competition', function() {
                    var compId = $(this).data('id');
                    var compName = $(this).data('name');
                    
                    // Add a new competition row
                    var template = $('#competition-template').html();
                    var index = $('.lmc-competition-row').length;
                    template = template.replace(/INDEX/g, index);
                    $('#lmc-competitions-list').append(template);
                    
                    // Fill in the values
                    var newRow = $('.lmc-competition-row').last();
                    newRow.find('.comp-id').val(compId);
                    newRow.find('.comp-name').val(compName);
                    
                    // Scroll to the new row
                    $('html, body').animate({
                        scrollTop: newRow.offset().top - 100
                    }, 500);
                    
                    // Highlight the row briefly
                    newRow.css('background-color', '#ffffcc');
                    setTimeout(function() {
                        newRow.css('background-color', '#fff');
                    }, 2000);
                    
                    alert('Competition added! Remember to save your settings.');
                });
                
                // Get teams for competition
                $(document).on('click', '.lmc-get-teams-btn', function() {
                    var btn = $(this);
                    var row = btn.closest('.lmc-competition-row');
                    var compId = row.find('.comp-id').val() || row.find('input[type=\"hidden\"][name*=\"[id]\"]').val();
                    var teamSelect = row.find('.lmc-team-select');
                    var teamStatus = row.find('.lmc-team-status');
                    
                    if (!compId) {
                        alert('Competition ID not found');
                        return;
                    }
                    
                    btn.prop('disabled', true).text('Loading Teams...');
                    teamStatus.html('<span style=\"color: blue;\">⏳ Fetching teams...</span>');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lmc_get_teams',
                            nonce: '{$get_teams_nonce}',
                            comp_id: compId
                        },
                        success: function(response) {
                            if (response.success && response.data.teams) {
                                // Populate select dropdown
                                teamSelect.empty();
                                teamSelect.append('<option value=\"\">-- Select Primary Team --</option>');
                                response.data.teams.forEach(function(team) {
                                    teamSelect.append('<option value=\"' + team + '\">' + team + '</option>');
                                });
                                teamSelect.show();
                                teamStatus.html('<span style=\"color: green;\">✓ Found ' + response.data.teams.length + ' teams</span>');
                            } else {
                                teamStatus.html('<span style=\"color: red;\">✗ ' + (response.data ? response.data.message : 'No teams found') + '</span>');
                            }
                            btn.prop('disabled', false).text('Refresh Teams');
                        },
                        error: function() {
                            teamStatus.html('<span style=\"color: red;\">✗ Error fetching teams</span>');
                            btn.prop('disabled', false).text('Refresh Teams');
                        }
                    });
                });
            });
JAVASCRIPT;
        
        wp_add_inline_script('jquery', $inline_script);
        
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
        // Suppress error display and start output buffering
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Clean any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        check_ajax_referer('lmc_scrape_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $comp_id = sanitize_text_field($_POST['comp_id']);
        $comp_name = sanitize_text_field($_POST['comp_name']);
        
        if (empty($comp_id) || empty($comp_name)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }
        
        // Increase execution time for scraping (can take 1-2 minutes)
        set_time_limit(300); // 5 minutes
        
        error_log('LMC Admin: Starting AJAX scrape for ' . $comp_id);
        
        try {
            $scraper = new LMC_Scraper();
            $result = $scraper->scrape_competition($comp_id, $comp_name);
            
            error_log('LMC Admin: Scrape completed, result: ' . json_encode($result));
            
            // Discard any output (PHP notices, warnings, etc.)
            ob_end_clean();
            
            if ($result['success']) {
                // Clear cache for this competition
                LMC_Data::clear_cache($comp_id);
                wp_send_json_success($result);
            } else {
                wp_send_json_error($result);
            }
        } catch (Exception $e) {
            error_log('LMC Admin: Exception during scrape: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Suppress error display and start output buffering
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Clean any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        check_ajax_referer('lmc_cache_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        LMC_Data::clear_all_cache();
        
        // Discard any output
        ob_end_clean();
        
        wp_send_json_success(array('message' => 'Cache cleared'));
    }
    
    /**
     * AJAX handler for listing available seasons from GameDay
     */
    public function ajax_list_seasons() {
        // Suppress error display and start output buffering
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Clean any existing output
        if (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        error_log('LMC Admin: ajax_list_seasons called');
        error_log('LMC Admin: POST data: ' . print_r($_POST, true));
        
        try {
            check_ajax_referer('lmc_list_competitions_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                error_log('LMC Admin: Insufficient permissions');
                ob_end_clean();
                wp_send_json_error(array('message' => 'Insufficient permissions'));
            }
            
            $association_id = isset($_POST['association_id']) ? sanitize_text_field($_POST['association_id']) : '';
            
            if (empty($association_id)) {
                error_log('LMC Admin: Association ID is empty');
                ob_end_clean();
                wp_send_json_error(array('message' => 'Association ID is required'));
            }
            
            error_log('LMC Admin: Fetching seasons for association ' . $association_id);
            $scraper = new LMC_Scraper();
            $seasons = $scraper->list_seasons($association_id);
            
            if ($seasons === false || empty($seasons)) {
                error_log('LMC Admin: No seasons found');
                ob_end_clean();
                wp_send_json_error(array('message' => 'No seasons found'));
            }
            
            error_log('LMC Admin: Found ' . count($seasons) . ' seasons');
            ob_end_clean();
            wp_send_json_success(array('seasons' => $seasons));
        } catch (Exception $e) {
            error_log('LMC Admin: Exception while listing seasons: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for listing available competitions from GameDay
     */
    public function ajax_list_available_competitions() {
        // Suppress error display and start output buffering
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Clean any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        check_ajax_referer('lmc_list_competitions_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $association_id = isset($_POST['association_id']) ? sanitize_text_field($_POST['association_id']) : '';
        $season_id = isset($_POST['season_id']) ? sanitize_text_field($_POST['season_id']) : '';
        
        if (empty($association_id)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Association ID is required'));
        }
        
        if (empty($season_id)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Season ID is required'));
        }
        
        error_log('LMC Admin: Fetching available competitions for association ' . $association_id . ', season ' . $season_id);
        
        try {
            error_log('LMC Admin: Creating scraper instance...');
            $scraper = new LMC_Scraper();
            error_log('LMC Admin: Calling list_competitions...');
            $competitions = $scraper->list_competitions($association_id, $season_id);
            error_log('LMC Admin: list_competitions returned, processing results...');
            
            // Discard any output
            ob_end_clean();
            
            if ($competitions === false || empty($competitions)) {
                $error_msg = 'No competitions found for this season. ';
                $error_msg .= 'Please verify: 1) Association ID is correct, 2) Season has active competitions, 3) Check debug.log for details.';
                
                wp_send_json_error(array('message' => $error_msg));
            } else {
                error_log('LMC Admin: Found ' . count($competitions) . ' competitions');
                wp_send_json_success(array(
                    'message' => 'Found ' . count($competitions) . ' competitions',
                    'competitions' => $competitions
                ));
            }
        } catch (Exception $e) {
            error_log('LMC Admin: Exception while listing competitions: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
    }
    
    /**
     * AJAX handler for getting teams list from fixtures
     */
    public function ajax_get_teams() {
        // Suppress error display and start output buffering
        @ini_set('display_errors', '0');
        error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
        
        // Clean any existing output
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();
        
        check_ajax_referer('lmc_get_teams_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Insufficient permissions'));
        }
        
        $comp_id = isset($_POST['comp_id']) ? sanitize_text_field($_POST['comp_id']) : '';
        
        if (empty($comp_id)) {
            ob_end_clean();
            wp_send_json_error(array('message' => 'Competition ID is required'));
        }
        
        error_log('LMC Admin: Fetching teams for competition ' . $comp_id);
        
        try {
            $teams = LMC_Data::get_teams_list($comp_id);
            
            // Discard any output
            ob_end_clean();
            
            if ($teams === false || empty($teams)) {
                wp_send_json_error(array('message' => 'No teams found. Please scrape the competition data first.'));
            } else {
                error_log('LMC Admin: Found ' . count($teams) . ' teams');
                wp_send_json_success(array(
                    'message' => 'Found ' . count($teams) . ' teams',
                    'teams' => $teams
                ));
            }
        } catch (Exception $e) {
            error_log('LMC Admin: Exception while getting teams: ' . $e->getMessage());
            ob_end_clean();
            wp_send_json_error(array('message' => 'Error: ' . $e->getMessage()));
        }
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
                <p>Add competitions and scrape data from MyGameDay/SportsTG.</p>
                <p><strong>Competition ID Format:</strong> <code>0-&lt;Association&gt;-0-&lt;Competition&gt;-0</code></p>
                <p><strong>Example:</strong> If your Association is <code>1064</code> and Competition is <code>646414</code>, enter: <code>0-1064-0-646414-0</code></p>
                <p>Find these in your GameDay website URL. See <a href="https://helpdesk.mygameday.app/help/adding-and-changing-the-match-centre-ids" target="_blank">MyGameDay Help</a> for details.</p>
                
                <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <h3 style="margin-top: 0;">🔍 Discover Competitions</h3>
                    <p>Enter your Association ID and select a season to see all available competitions from GameDay:</p>
                    <div style="display: flex; gap: 10px; align-items: flex-start; margin-bottom: 10px;">
                        <input type="text" id="lmc-discover-association-id" value="1064" placeholder="e.g., 1064" class="regular-text" style="max-width: 200px;">
                        <button type="button" id="lmc-discover-seasons-btn" class="button">Load Seasons</button>
                    </div>
                    <div id="lmc-seasons-selection" style="margin-bottom: 10px;"></div>
                    <div id="lmc-discover-status" style="margin-top: 10px;"></div>
                    <div id="lmc-discover-results" style="margin-top: 15px; max-height: 400px; overflow-y: auto;"></div>
                </div>
                
                <div id="lmc-competitions-list">
                    <?php foreach ($competitions as $index => $comp): ?>
                    <div class="lmc-competition-row">
                        <h3><?php echo esc_html((isset($comp['season']) && !empty($comp['season']) ? $comp['season'] . ' - ' : '') . $comp['name']); ?></h3>
                        
                        <label>
                            <input type="radio" name="lmc_settings[current_competition]" value="<?php echo esc_attr($comp['id']); ?>" <?php checked($current_competition, $comp['id']); ?>>
                            Use as current competition
                        </label>
                        
                        <!-- Hidden fields to preserve competition data -->
                        <input type="hidden" name="lmc_settings[competitions][<?php echo $index; ?>][id]" value="<?php echo esc_attr($comp['id']); ?>">
                        <input type="hidden" name="lmc_settings[competitions][<?php echo $index; ?>][name]" value="<?php echo esc_attr($comp['name']); ?>">
                        <input type="hidden" name="lmc_settings[competitions][<?php echo $index; ?>][season]" class="comp-season" value="<?php echo esc_attr(isset($comp['season']) ? $comp['season'] : ''); ?>">
                        
                        <p style="color: #666; font-size: 0.9em; margin: 5px 0;">
                            <strong>Competition ID:</strong> <code><?php echo esc_html($comp['id']); ?></code>
                        </p>
                        
                        <div style="margin: 10px 0;">
                            <label><strong>Primary Team:</strong></label><br>
                            <select name="lmc_settings[competitions][<?php echo $index; ?>][primary_team]" class="lmc-team-select regular-text" style="max-width: 400px;">
                                <option value="">-- Select Primary Team --</option>
                                <?php
                                // Load teams if data exists
                                $teams = LMC_Data::get_teams_list($comp['id']);
                                if ($teams && !empty($teams)) {
                                    $current_team = isset($comp['primary_team']) ? $comp['primary_team'] : '';
                                    foreach ($teams as $team) {
                                        echo '<option value="' . esc_attr($team) . '"' . selected($current_team, $team, false) . '>' . esc_html($team) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                            <button type="button" class="button lmc-get-teams-btn" style="margin-left: 5px;">
                                <?php echo ($teams && !empty($teams)) ? 'Refresh Teams' : 'Load Teams'; ?>
                            </button>
                            <div class="lmc-team-status" style="margin-top: 5px;"></div>
                            <p class="description">Select the primary team to display in team-specific blocks</p>
                        </div>
                        
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
                                    ✓ <?php echo ucfirst($type); ?>: Last updated <?php echo human_time_diff($file_info['modified'], current_time('timestamp', true)); ?> ago<br>
                                <?php else: ?>
                                    ✗ <?php echo ucfirst($type); ?>: Not available<br>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <script type="text/template" id="competition-template">
                    <div class="lmc-competition-row">
                        <h3>New Competition</h3>
                        
                        <label>
                            <input type="radio" name="lmc_settings[current_competition]" value="">
                            Use as current competition
                        </label>
                        <br><br>
                        
                        <input type="text" name="lmc_settings[competitions][INDEX][id]" class="comp-id regular-text" placeholder="Competition ID (e.g., 0-1064-0-646414-0)">
                        <input type="text" name="lmc_settings[competitions][INDEX][name]" class="comp-name regular-text" placeholder="Competition Name">
                        <input type="hidden" name="lmc_settings[competitions][INDEX][season]" class="comp-season" value="">
                        <br>
                        
                        <div style="margin: 10px 0;">
                            <label><strong>Primary Team:</strong></label><br>
                            <select name="lmc_settings[competitions][INDEX][primary_team]" class="lmc-team-select regular-text" style="max-width: 400px; display: none;">
                                <option value="">-- Select Primary Team --</option>
                            </select>
                            <button type="button" class="button lmc-get-teams-btn" style="margin-left: 5px;">Load Teams</button>
                            <div class="lmc-team-status" style="margin-top: 5px;"></div>
                            <p class="description">Select the primary team to display in team-specific blocks</p>
                        </div>
                        
                        <br>
                        <button type="button" class="button lmc-scrape-btn">Scrape Data</button>
                        <button type="button" class="button lmc-remove-competition">Remove</button>
                        
                        <div class="scrape-status"></div>
                    </div>
                </script>
                
                <h2>Cache Management</h2>
                <p>Clear all cached data to force fresh data loading.</p>
                <button type="button" id="lmc-clear-cache" class="button">Clear Cache</button>
                
                <h2>Automatic Scraping</h2>
                <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
                    <?php
                    $next_run = wp_next_scheduled('lmc_hourly_scrape');
                    if ($next_run) {
                        $time_until = human_time_diff(time(), $next_run);
                        echo '<p><strong>Status:</strong> ✓ Enabled - runs every 60 minutes</p>';
                        echo '<p><strong>Next Run:</strong> In ' . esc_html($time_until) . ' (' . esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), $next_run)) . ')</p>';
                        echo '<p style="color: #666; font-size: 0.9em;">All configured competitions will be automatically scraped every hour.</p>';
                    } else {
                        echo '<p style="color: #d63638;"><strong>Status:</strong> ✗ Not scheduled</p>';
                        echo '<p>Deactivate and reactivate the plugin to enable automatic scraping.</p>';
                    }
                    ?>
                </div>
                
                <p class="submit">
                    <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                </p>
            </form>
        </div>
        <?php
    }
}
