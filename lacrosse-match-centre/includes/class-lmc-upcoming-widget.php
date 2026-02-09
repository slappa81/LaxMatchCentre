<?php
/**
 * Upcoming Games Widget
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Upcoming_Widget class
 */
class LMC_Upcoming_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'lmc_upcoming_widget',
            'LMC: Upcoming Games',
            array(
                'description' => 'Display upcoming matches',
                'classname' => 'lmc-upcoming-widget'
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
        $title = !empty($instance['title']) ? $instance['title'] : 'Upcoming Games';
        $comp_id = !empty($instance['comp_id']) ? $instance['comp_id'] : null;
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // Get upcoming games
        $games = LMC_Data::get_upcoming_games($comp_id, $limit);
        
        if ($games && !empty($games)) {
            echo '<div class="lmc-upcoming">';
            
            foreach ($games as $game) {
                $round_label = !empty($game['round_label']) ? $game['round_label'] : 'Round ' . $game['round'];
                echo '<div class="lmc-game">';
                echo '<div class="lmc-game-round">' . esc_html($round_label) . '</div>';
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
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Upcoming Games';
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
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Number of games to show:</label>
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
