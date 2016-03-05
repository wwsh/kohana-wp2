<?php defined('KWP_DOCROOT') or die('No direct script access.');

/**
 * KohanaWidget Class
 */
class KWP_NonAdmin_Widget extends WP_Widget
{
    /** constructor */
    function __construct()
    {
        parent::WP_Widget('kohana-widget', 'KohanaWidget');
    }

    /**
     * @param $args
     * @param $instance
     */
    function widget($args, $instance)
    {
        extract($args);
        ?>
        <?php echo $before_widget; ?>
        <?php
        if ($instance['title']) {
            echo $before_title
                . $instance['title']
                . $after_title;
        }
        ?>
        <?php echo kohana($instance['kohana_request']) ?>
        <?php echo $after_widget; ?>
        <?php
    }

    /**
     * @param $new_instance
     * @param $old_instance
     * @return mixed
     */
    function update($new_instance, $old_instance)
    {
        return $new_instance;
    }

    /**
     * @param $instance
     */
    function form($instance)
    {
        $title = esc_attr($instance['title']);
        $req   = esc_attr($instance['kohana_request']);
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Title:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                       name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>"/>
            </label>
            <label for="<?php echo $this->get_field_id('kohana_request'); ?>">
                <?php _e('Kohana Request:'); ?>
                <input class="widefat" id="<?php echo $this->get_field_id('kohana_request'); ?>"
                       name="<?php echo $this->get_field_name('kohana_request'); ?>" type="text"
                       value="<?php echo $req; ?>"/>
            </label>
        </p>
        <?php
    }

    /**
     * 
     */
    static public function register()
    {
        register_widget("KWP_NonAdmin_Widget");
    }
} // class KohanaWidget
