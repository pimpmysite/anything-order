<?php
/**
 * Post order.
 *
 * @package Anything_Order
 * @since 1.0
 * @access public
 */
class Anything_Order_Post extends Anything_Order_Base {

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct( array(
            'pagenow'       => 'edit',
            'objectnow'     => 'typenow',
            'inline_editor' => 'inlineEditPost',
            'query_var'     => 'post_type',
        ) );

        add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
    }

    /**
     * Hook: Modify orderby clause on admin screen and the public site.
     *
     * @since 1.0.0
     * @access public
     *
     * @see WP_Query::get_posts()
     *
     * @param WP_Query &$this The WP_Query instance (passed by reference).
     */
    function posts_orderby( $orderby ) {
        global $wpdb;

        if ( ! is_admin() || ( is_admin() && ! isset( $_GET['orderby'] ) ) ) {
            if ( false === strpos( $orderby, 'menu_order' ) ) {
                $orderby = "$wpdb->posts.menu_order ASC,$orderby";
            }
        }

        return $orderby;
    }

    /**
     * Capability for ordering.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function cap() {
        $post_type_object = get_post_type_object( $GLOBALS[$this->objectnow] );

        if ( ! $post_type_object )
            wp_die( __( 'Invalid post type' ) );

        return $post_type_object->cap->edit_others_posts;
    }

    /**
     * Manage a column for ordering.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param object $screen Current screen.
     */
    protected function manage_column( $screen ) {
        add_filter( "manage_{$screen->post_type}_posts_columns"      , array( $this, 'get_columns' ) );
        add_action( "manage_{$screen->post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
    }

    /**
     * Hook: Render a column for ordering.
     *
     * @since 1.0.0
     * @access public
     */
    function render_column() {
        $args = func_get_args();

        $post = get_post( $args[1] );
        $args[] = $post->menu_order;

        echo $this->_render_column( $args );
    }

    /**
     * Update order.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param array $ids Object IDs to update order.
     * @param int $order The number to start ordering.
     * @param string $objectnow Current screen object name.
     *

     */
    protected function _update( $ids, $order, $objectnow ) {
        global $wpdb;

        if ( empty( $ids ) ) {
            $wpdb->update(
                $wpdb->posts,
                array( 'menu_order' => 0 ),
                array( 'post_type' => $objectnow )
            );

            return false;

        } else {
            foreach ( $ids as $id ) {
                if ( 0 < $id ) {
                    $wpdb->update(
                        $wpdb->posts,
                        array( 'menu_order' => $order++ ),
                        array( 'ID' => $id )
                    );
                }
            }
        }

        return true;
    }
}

new Anything_Order_Post;
