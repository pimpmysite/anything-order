<?php
/**
 * Base class.
 *
 * @package Anything_Order
 * @since 1.0
 * @access public
 */
abstract class Anything_Order_Base {

    /**
     * ID or name of this class.
     *
     * @since 1.0.0
     * @access protected
     *
     * @var string
     */
    protected $name = '';

    /**
     * Page now (not include '.php').
     *
     * @since 1.0.0
     * @access protected
     *
     * @var string
     */
    protected $pagenow = '';

    /**
     * Global variable name of current screen object.
     *
     * @since 1.0.0
     * @access protected
     *
     * @var string
     */
    protected $objectnow = '';

    /**
     * Name of the inline editor.
     *
     * @since 1.0.0
     * @access protected
     *
     * @var string
     */
    protected $inline_editor = '';

    /**
     * Query variable.
     *
     * @since 1.0.0
     * @access protected
     *
     * @var string
     */
    protected $query_var = '';

    /**
     * Error object.
     *
     * @since 1.0.0
     * @access protected
     *
     * @var WP_Error
     */
    protected $error = null;

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function __construct( $args = array() ) {
        $class = get_class( $this );
        $keys = array_keys( get_class_vars( $class ) );

        foreach ( $keys as $key ) {
            if ( isset( $args[ $key ] ) )
                $this->$key = $args[ $key ];
        }

        $this->name = str_replace( 'Anything_Order_', '', $class );

        if ( ! empty( $this->pagenow ) ) {
            add_action( "admin_print_styles-{$this->pagenow}.php", array( $this, 'admin_print_styles' ) );
            add_action( "admin_print_scripts-{$this->pagenow}.php", array( $this, 'admin_print_scripts' ) );
        }

        add_action( 'admin_init', array( $this, 'set_current_screen' ) );
        add_action( 'current_screen', array( $this, 'current_screen' ) );

        add_action( "wp_ajax_Anything_Order/update/{$this->name}", array( $this, 'update' ) );
    }

    /**
     * Get an ID for the class.
     *
     * @since 1.0.0
     * @access protected
     *
     * @return string
     */
    protected function get_id( $suffix = '' ) {
        $id = strtolower( get_class( $this ) );

        if ( ! empty( $suffix ) ) {
            $id .= "_$suffix";
        }

        return $id;
    }

    /**
     * Hook: Set current screen.
     *
     * @since 1.0.0
     * @access public
     */
    function set_current_screen() {
        if ( defined( 'DOING_AJAX' ) && isset( $_POST['screen_id'] ) ) {
            convert_to_screen( $_POST['screen_id'] )->set_current_screen();
        }
    }

    /**
     * Hook: Add hooks depend on current screen.
     *
     * @since 1.0.0
     * @access public
     *
     * @param object $screen Current screen.
     */
    function current_screen( $screen ) {
        if ( get_current_screen()->base != $this->pagenow )
            return;

        if ( ! current_user_can( apply_filters( "Anything_Order/cap/{$this->name}", $this->cap(), $screen ) ) )
            return;

        $this->manage_column( $screen );
    }

    /**
     * Capability for ordering.
     *
     * @since 1.0.0
     * @access protected
     */
    abstract protected function cap();

    /**
     * Manage a column for ordering.
     *
     * @since 1.0.0
     * @access protected
     *
     * @param object $screen Current screen.
     */
    abstract protected function manage_column( $screen );

    /**
     * Hook: Prepend a column for ordering to columns.
     *
     * @since 1.0.0
     * @access public
     */
    function get_columns( $columns ) {
        $title = sprintf(
            '<a href="%1$s">'.
            '<span class="dashicons dashicons-sort"></span>'.
            '</a>'.
            '<span class="title">%2$s</span>'.
            '<span class="anything-order-actions"><a class="reset">%3$s</a></span>',
            esc_url( $this->get_url() ),
            esc_html__( 'Order', 'anything-order' ),
            esc_html__( 'Reset', 'anything-order' )
        );

        return array( 'anything-order' => $title ) + $columns;
    }

    /**
     * Hook: Render a column for ordering.
     *
     * @since 1.0.0
     * @access public
     */
    abstract function render_column();

    /**
     * Retirive HTML for a column.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function _render_column( $args ) {
        $output = '';

        if ( 'anything-order' == $args[0] ) {
            $output = sprintf(
               '<span class="hidden anything-order-id">%1$s</span>'.
               '<span class="hidden anything-order-order">%2$s</span>',
               absint( $args[1] ),
               absint( $args[2] )
            );
        }

        return $output;
    }

    /**
     * Retrieve the url of an admin page.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function get_url() {
         return add_query_arg( $this->query_var, $GLOBALS[$this->objectnow], admin_url( "{$this->pagenow}.php" ) );
    }

    /**
     * Hook: Enqueue styles.
     *
     * @since 1.0.0
     * @access public
     */
    function admin_print_styles() {
        wp_enqueue_style( $this->get_id( 'style' ), plugin_dir_url( __FILE__ ) . 'style.css', array(), false, 'all' );
    }

    /**
     * Hook: Enqueue scripts.
     *
     * @since 1.0.0
     * @access public
     */
    function admin_print_scripts() {
        wp_enqueue_script( $this->get_id( 'script' ), plugin_dir_url( __FILE__ ) . 'script.js', array( 'jquery-ui-sortable' ), false, true );

        $params = apply_filters( "Anything_Order/ajax_params/{$this->name}", array(
            '_ajax_nonce' => wp_create_nonce( "Anything_Order/update/{$this->name}" ),
            'action'      => "Anything_Order/update/{$this->name}",
            'inline'      => $this->inline_editor,
            'objectnow'   => $GLOBALS[$this->objectnow],
        ) );

        $texts = array(
            'confirmReset' => __( "Are you sure you want to reset order?\n 'Cancel' to stop, 'OK' to reset.", 'anything-order' )
        );

        wp_localize_script( $this->get_id( 'script' ), 'anythingOrder', array(
            'params' => $params,
            'texts'  => $texts,
        ) );
    }

    /**
     * Hook: Update order.
     *
     * @since 1.0.0
     * @access public
     */
    final function update() {
        check_ajax_referer( "Anything_Order/update/{$this->name}" );

        $this->errors = new WP_Error();

        $ids       = isset( $_POST['ids'] )
                   ? array_filter( array_map( 'intval', explode( ',', $_POST['ids'] ) ) )
                   : array();
        $order     = isset( $_POST['order'] ) ? intval( $_POST['order'] ) : 0;
        $objectnow = isset( $_POST['objectnow'] ) ? $_POST['objectnow'] : '';

        if ( ! $order ) {
            $this->errors->add(
                'invalid_order',
                __( 'Invalid ordering number is posted.', 'anything-order' )
            );
        }

        $msgs = $this->errors->get_error_messages();

        if ( empty( $msgs ) ) {
            $redirect = $this->_update( $ids, $order, $objectnow )
                      ? ''
                      : $this->get_url();

            echo json_encode( array(
                'status'   => 'success',
                'redirect' => $redirect,
            ) );

        } else {
            echo json_encode( array(
                'status'  => 'error',
                'message' => implode( '<br>', $msgs ),
            ) );
        }

        wp_die();
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
     * @return bool True if updated. False if reset.
     */
    abstract protected function _update( $ids, $order, $objectnow );
}
