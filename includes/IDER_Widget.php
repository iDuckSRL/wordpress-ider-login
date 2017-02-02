<?php



class IDER_Widget extends WP_Widget {

    static function init()
    {
        add_action( 'widgets_init', function(){
            register_widget( 'IDer_Widget' );
        });
    }

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'ider_widget', // Base ID
            __( 'IDer Box', 'ider' ), // Name
            array( 'description' => __( 'Provide the IDer login/register box', 'ider' ), ) // Args
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
        echo $args['before_widget'];
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }
        echo IDER_Shortcodes::single_sign_on_login_button_shortcode();
        echo $args['after_widget'];
    }

} // class Foo_Widget


