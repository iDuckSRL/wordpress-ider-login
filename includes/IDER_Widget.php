<?php

/**
 *
 * Widgets
 *
 * @package     WordPress
 * @subpackage  Ider
 * @author      Davide Lattanzio <plugins@jlm.srl>
 * @since       1.0
 *
 */

class IDER_Widget extends WP_Widget {

    static function init()
    {
        add_action( 'widgets_init', function(){
            register_widget( 'IDER_Widget' );
        });
    }

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'ider_widget', // Base ID
            __( 'IDer Login', 'ider' ), // Name
            array( 'description' => __( 'Provide the IDer login button', 'ider' ), 'classname' => "ider-widget") // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {

        //var_dump($instance);
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        unset($args['class']);

        echo IDER_Shortcodes::ider_login_button($args);
        echo $args['after_widget'];
    }


    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {

        $checked = !empty($instance['loginonly']) ? 'checked="checked' : '';

        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'loginonly' ) ); ?>">
                <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'loginonly' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'loginonly' ) ); ?>" type="checkbox" value="1" <?php echo $checked; ?>">
                <?php esc_attr_e( 'Don\'t show logout', 'text_domain' ); ?>
            </label>
        </p>
        <?php
    }


    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;

        //print_r($new_instance);
        $instance[ 'loginonly' ] = strip_tags( $new_instance[ 'loginonly' ] );
        return $instance;
    }

}


