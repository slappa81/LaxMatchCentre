<?php
/**
 * Results Widget
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Results_Widget class
 */
class LMC_Results_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'lmc_results_widget',
            'LMC: Recent Results',
            array(
                'description' => 'Display recent match results',
                'classname' => 'lmc-results-widget'
            )
        );
    }
    
    /**
     * Front-end display of widget
     *
     * @param array $args Widget arguments
     * @param array $instance Saved values from database
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Recent Results';
        $comp_id = !empty($instance['comp_id']) ? $instance['comp_id'] : null;
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // Get results
        $results = LMC_Data::get_results($comp_id, $limit);
        
        if ($results && !empty($results)) {
            echo '<div class="lmc-results">';
            
            foreach ($results as $result) {
                $round_label = !empty($result['round_label']) ? $result['round_label'] : 'Round ' . $result['round'];
                echo '<div class="lmc-result">';
                echo '<div class="lmc-result-round">' . esc_html($round_label) . '</div>';
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
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Recent Results';
        $comp_id = isset($instance['comp_id']) ? $instance['comp_id'] : '';
        $limit = isset($instance['limit']) ? absint($instance['limit']) : 5;
        
        $settings = get_option('lmc_settings', array());
        $competitions = isset($settings['competitions']) ? $settings['competitions'] : array();
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        
        <?php if (!empty($competitions)): ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('comp_id')); ?>">Competition:</label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('comp_id')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('comp_id')); ?>">
                <option value="">Use default competition</option>
                <?php foreach ($competitions as $comp): ?>
                    <option value="<?php echo esc_attr($comp['id']); ?>" <?php selected($comp_id, $comp['id']); ?>>
                        <?php echo esc_html($comp['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php else: ?>
        <p><em>No competitions configured. Please add competitions in the plugin settings.</em></p>
        <?php endif; ?>
        
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Number of results to show:</label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" 
                   type="number" min="1" max="20" value="<?php echo esc_attr($limit); ?>">
        </p>
        <?php
    }
    
    /**
     * Sanitize widget form values as they are saved
     *
     * @param array $new_instance Values just sent to be saved
     * @param array $old_instance Previously saved values from database
     * @return array Updated safe values to be saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['comp_id'] = (!empty($new_instance['comp_id'])) ? sanitize_text_field($new_instance['comp_id']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? absint($new_instance['limit']) : 5;
        
        return $instance;
    }
}
