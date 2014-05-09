<?php
/*
Plugin Name: Anything Order
Plugin URI: http://wordpress.org/plugins/anything-order/
Description: This plugin allows you to arrange any post types and taxonomies with drag and drop.
Author: pmwp
Author URI: http://pimpmysite.net/
Text Domain: anything-order
Domain Path: /languages/
Version: 1.0.3
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Anything_Order' ) ) {

/**
 * Reorder any post types and taxonomies with drag and drop.
 *
 * @package Anything_Order
 * @since 1.0
 * @access public
 */
class Anything_Order {

    /**
     * Holds the singleton instance of this class.
     *
     * @since 1.0.0
     * @access private
     *
     * @var object
     */
    static private $instance = null;

    /**
     * Singleton.
     *
     * @since 1.0.0
     * @access public
     *
     * @return object
     */
    static public final function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor. Includes Anythig Order modules.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function __construct() {
        load_plugin_textdomain( 'anything-order', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

        include_once( 'modules/base/class.php' );
        include_once( 'modules/taxonomy/class.php' );
        include_once( 'modules/post/class.php' );
    }
}

add_action( 'plugins_loaded', array( 'Anything_Order', 'get_instance' ) );

}