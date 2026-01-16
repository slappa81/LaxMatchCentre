<?php
/**
 * Ladder Widget
 *
 * @package Lacrosse_Match_Centre
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * LMC_Ladder_Widget class
 */
class LMC_Ladder_Widget extends WP_Widget {
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'lmc_ladder_widget',
            'LMC: Competition Ladder',
            array(
                'description' => 'Display the competition ladder standings',
                'classname' => 'lmc-ladder-widget'
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
        $title = !empty($instance['title']) ? $instance['title'] : 'Competition Ladder';
        $comp_id = !empty($instance['comp_id']) ? $instance['comp_id'] : null;
        
        echo $args['before_widget'];
        
        if (!empty($title)) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }
        
        // Get ladder data
        $ladder = LMC_Data::get_ladder($comp_id);
        
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
        
        echo $args['after_widget'];
    }
    
    /**
     * Back-end widget form
     *
     * @param array $instance Previously saved values from database
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : 'Competition Ladder';
        $comp_id = isset($instance['comp_id']) ? $instance['comp_id'] : '';
        
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
        
        return $instance;
    }
}
