<?php
/**
 * Debug script to check Lacrosse Match Centre configuration
 * 
 * Place this file in your WordPress root directory and access it via browser
 * Example: https://yoursite.com/lmc-debug.php
 * 
 * IMPORTANT: Delete this file after debugging for security!
 */

// Load WordPress
require_once('wp-load.php');

// Must be admin
if (!current_user_can('manage_options')) {
    die('Access denied - admin only');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>LMC Debug Information</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f0f0f0; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
        h2 { color: #0073aa; margin-top: 30px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { background: #e7f5fe; padding: 10px; border-left: 4px solid #0073aa; margin: 10px 0; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #0073aa; color: white; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🏑 Lacrosse Match Centre - Debug Information</h1>
    <p><strong>Generated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
    
    <?php
    // Constants
    echo '<div class="section">';
    echo '<h2>📋 Plugin Constants</h2>';
    echo '<table>';
    echo '<tr><th>Constant</th><th>Value</th><th>Status</th></tr>';
    
    $constants = ['LMC_VERSION', 'LMC_PLUGIN_DIR', 'LMC_PLUGIN_URL', 'LMC_DATA_DIR'];
    foreach ($constants as $const) {
        $value = defined($const) ? constant($const) : 'NOT DEFINED';
        $status = defined($const) ? '<span class="success">✓ Defined</span>' : '<span class="error">✗ Not Defined</span>';
        echo "<tr><td><code>{$const}</code></td><td>{$value}</td><td>{$status}</td></tr>";
    }
    echo '</table>';
    echo '</div>';
    
    // Settings
    echo '<div class="section">';
    echo '<h2>⚙️ Plugin Settings</h2>';
    $settings = get_option('lmc_settings', array());
    
    echo '<h3>Current Competition</h3>';
    if (isset($settings['current_competition']) && !empty($settings['current_competition'])) {
        echo '<p class="success">✓ Competition ID: <code>' . esc_html($settings['current_competition']) . '</code></p>';
    } else {
        echo '<p class="error">✗ No current competition set!</p>';
        echo '<div class="info">Go to Settings → Match Centre and select a competition as current</div>';
    }
    
    echo '<h3>Cache Duration</h3>';
    $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 3600;
    echo '<p>' . $cache_duration . ' seconds (' . round($cache_duration / 60) . ' minutes)</p>';
    
    echo '<h3>Configured Competitions</h3>';
    if (isset($settings['competitions']) && !empty($settings['competitions'])) {
        echo '<table>';
        echo '<tr><th>Name</th><th>ID</th><th>Current</th></tr>';
        foreach ($settings['competitions'] as $comp) {
            $is_current = ($comp['id'] === $settings['current_competition']) ? '<span class="success">✓ Yes</span>' : 'No';
            echo '<tr><td>' . esc_html($comp['name']) . '</td><td><code>' . esc_html($comp['id']) . '</code></td><td>' . $is_current . '</td></tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="warning">⚠ No competitions configured</p>';
        echo '<div class="info">Go to Settings → Match Centre to add competitions</div>';
    }
    
    echo '<h3>Full Settings Object</h3>';
    echo '<pre>' . print_r($settings, true) . '</pre>';
    echo '</div>';
    
    // Data Directory
    echo '<div class="section">';
    echo '<h2>📁 Data Directory</h2>';
    
    if (defined('LMC_DATA_DIR')) {
        $data_dir = LMC_DATA_DIR;
        echo '<p><strong>Path:</strong> <code>' . $data_dir . '</code></p>';
        
        if (file_exists($data_dir)) {
            echo '<p class="success">✓ Directory exists</p>';
            
            if (is_writable($data_dir)) {
                echo '<p class="success">✓ Directory is writable</p>';
            } else {
                echo '<p class="error">✗ Directory is NOT writable!</p>';
                echo '<div class="info">Fix permissions: chmod 755 or 775 on the data directory</div>';
            }
            
            // List files
            echo '<h3>Files in Data Directory</h3>';
            $files = scandir($data_dir);
            $json_files = array_filter($files, function($f) { return strpos($f, '.json') !== false; });
            
            if (count($json_files) > 0) {
                echo '<table>';
                echo '<tr><th>Filename</th><th>Size</th><th>Modified</th><th>Preview</th></tr>';
                foreach ($json_files as $file) {
                    $filepath = $data_dir . $file;
                    $size = filesize($filepath);
                    $modified = date('Y-m-d H:i:s', filemtime($filepath));
                    
                    // Get first few characters
                    $content = file_get_contents($filepath);
                    $preview = substr($content, 0, 100);
                    if (strlen($content) > 100) $preview .= '...';
                    
                    echo '<tr>';
                    echo '<td><code>' . esc_html($file) . '</code></td>';
                    echo '<td>' . number_format($size) . ' bytes</td>';
                    echo '<td>' . $modified . '</td>';
                    echo '<td><small>' . esc_html($preview) . '</small></td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<p class="warning">⚠ No JSON files found!</p>';
                echo '<div class="info">You need to scrape data from the admin panel:<br>Go to Settings → Match Centre → Click "Scrape Data"</div>';
            }
            
            // List all files
            echo '<h3>All Files</h3>';
            echo '<ul>';
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo '<li><code>' . esc_html($file) . '</code></li>';
                }
            }
            echo '</ul>';
            
        } else {
            echo '<p class="error">✗ Directory does not exist!</p>';
            echo '<div class="info">The plugin should create this on activation. Try deactivating and reactivating the plugin.</div>';
        }
    } else {
        echo '<p class="error">✗ LMC_DATA_DIR constant not defined!</p>';
    }
    echo '</div>';
    
    // Classes
    echo '<div class="section">';
    echo '<h2>🔧 Plugin Classes</h2>';
    $classes = ['LMC_Data', 'LMC_Blocks', 'LMC_Scraper', 'LMC_Admin', 'Lacrosse_Match_Centre'];
    echo '<table>';
    echo '<tr><th>Class</th><th>Status</th></tr>';
    foreach ($classes as $class) {
        $exists = class_exists($class);
        $status = $exists ? '<span class="success">✓ Loaded</span>' : '<span class="error">✗ Not Loaded</span>';
        echo "<tr><td><code>{$class}</code></td><td>{$status}</td></tr>";
    }
    echo '</table>';
    echo '</div>';
    
    // Test data retrieval
    if (class_exists('LMC_Data') && isset($settings['current_competition']) && !empty($settings['current_competition'])) {
        echo '<div class="section">';
        echo '<h2>🔍 Test Data Retrieval</h2>';
        
        $comp_id = $settings['current_competition'];
        echo '<p>Testing with competition: <code>' . esc_html($comp_id) . '</code></p>';
        
        echo '<h3>Ladder Data</h3>';
        $ladder = LMC_Data::get_ladder($comp_id);
        if ($ladder && !empty($ladder)) {
            echo '<p class="success">✓ Ladder data loaded: ' . count($ladder) . ' teams</p>';
            echo '<details><summary>View Data</summary><pre>' . print_r($ladder, true) . '</pre></details>';
        } else {
            echo '<p class="error">✗ No ladder data available</p>';
        }
        
        echo '<h3>Upcoming Games</h3>';
        $upcoming = LMC_Data::get_upcoming_games($comp_id);
        if ($upcoming && !empty($upcoming)) {
            echo '<p class="success">✓ Upcoming games loaded: ' . count($upcoming) . ' games</p>';
            echo '<details><summary>View Data</summary><pre>' . print_r($upcoming, true) . '</pre></details>';
        } else {
            echo '<p class="error">✗ No upcoming games available</p>';
        }
        
        echo '<h3>Results</h3>';
        $results = LMC_Data::get_results($comp_id);
        if ($results && !empty($results)) {
            echo '<p class="success">✓ Results loaded: ' . count($results) . ' results</p>';
            echo '<details><summary>View Data</summary><pre>' . print_r($results, true) . '</pre></details>';
        } else {
            echo '<p class="error">✗ No results available</p>';
        }
        
        echo '</div>';
    }
    
    // Recommendations
    echo '<div class="section">';
    echo '<h2>💡 Recommendations</h2>';
    echo '<ul>';
    
    if (!isset($settings['current_competition']) || empty($settings['current_competition'])) {
        echo '<li class="error">Set a current competition in Settings → Match Centre</li>';
    }
    
    if (!isset($settings['competitions']) || empty($settings['competitions'])) {
        echo '<li class="error">Add at least one competition in Settings → Match Centre</li>';
    }
    
    $json_files_exist = defined('LMC_DATA_DIR') && file_exists(LMC_DATA_DIR) && count(glob(LMC_DATA_DIR . '*.json')) > 0;
    if (!$json_files_exist) {
        echo '<li class="error">Scrape data from Settings → Match Centre</li>';
    }
    
    if (defined('LMC_DATA_DIR') && file_exists(LMC_DATA_DIR) && !is_writable(LMC_DATA_DIR)) {
        echo '<li class="error">Fix data directory permissions</li>';
    }
    
    echo '<li>After scraping data, clear cache if blocks still show no data</li>';
    echo '<li>Check WordPress debug.log for detailed error messages</li>';
    echo '<li><strong>Delete this debug file after use for security!</strong></li>';
    echo '</ul>';
    echo '</div>';
    
    // Debug Log
    echo '<div class="section">';
    echo '<h2>📝 Recent Debug Log Entries</h2>';
    $debug_log = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($debug_log)) {
        $log_content = file_get_contents($debug_log);
        $lines = explode("\n", $log_content);
        $lmc_lines = array_filter($lines, function($line) {
            return strpos($line, 'LMC') !== false;
        });
        
        if (count($lmc_lines) > 0) {
            $recent = array_slice($lmc_lines, -20); // Last 20 entries
            echo '<pre>' . esc_html(implode("\n", $recent)) . '</pre>';
        } else {
            echo '<p>No LMC-related log entries found</p>';
        }
    } else {
        echo '<p>Debug log not enabled. Add to wp-config.php:</p>';
        echo '<pre>define(\'WP_DEBUG\', true);<br>define(\'WP_DEBUG_LOG\', true);<br>define(\'WP_DEBUG_DISPLAY\', false);</pre>';
    }
    echo '</div>';
    ?>
    
    <div class="info" style="margin-top: 30px; background: #ffecec; border-color: #ff0000;">
        <strong>⚠️ SECURITY WARNING:</strong> Delete this file (lmc-debug.php) from your WordPress root directory after debugging!
    </div>
</div>
</body>
</html>
