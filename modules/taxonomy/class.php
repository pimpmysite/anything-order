<?php
/**
 * Taxonomy order.
 *
 * @package Anything_Order
 * @since 1.0
 * @access public
 */
class Anything_Order_Taxonomy extends Anything_Order_Base {

    /**
     * Constructor.
     *
     * @since 1.0.0
     * @access public
     */
    public function __construct() {
        parent::__construct( array(
            'pagenow'       => 'edit-tags',
            'objectnow'     => 'taxnow',
            'inline_editor' => 'inlineEditTax',
            'query_var'     => 'taxonomy',
        ) );

        add_action( 'create_term', array( $this, 'create_term' ), 10, 3 );
        add_action( 'delete_term_taxonomy', array( $this, 'delete_term_taxonomy' ) );
        add_filter( 'get_term', array( $this, 'get_term' ), 10, 2 );
        add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
        add_filter( 'get_the_terms', array( $this, 'get_the_terms' ), 10, 3 );
        add_filter( 'wp_get_object_terms', array( $this, 'get_object_terms' ), 10, 4 );
    }

    /**
     * Hook: Add term order after a new term is created, before the term cache is cleaned.
     *
     * @since 1.0.0
     * @access public
     *
     * @see wp_insert_term()
     *
     * @param int    $term_id  Term ID.
     * @param int    $tt_id    Term taxonomy ID.
     * @param string $taxonomy Taxonomy slug.
     */
    function create_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        $wpdb->query( $wpdb->prepare(
            "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order)"
          . " VALUES(%d, %d, %d) ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)",
            0, $tt_id, 0
        ) );
    }

    /**
     * Hook: Delete term order before a term taxonomy ID is deleted.
     *
     * @since 1.0.0
     * @access public
     *
     * @see wp_insert_term()
     *
     * @param int $tt_id Term taxonomy ID.
     */
    function delete_term_taxonomy( $tt_id ) {
        global $wpdb;

        $wpdb->delete( $wpdb->term_relationships, array( 'object_id' => 0, 'term_taxonomy_id' => $tt_id ) );
    }

    /**
     * Filter a term. Add term_order property to a term object.
     *
     * @since 1.0.0
     * @access public
     *
     * @see get_term()
     *
     * @param object $term Term object.
     * @param string $taxonomy The taxonomy slug.
     */
    function get_term( $term, $taxonomy ) {
        if ( ! isset( $term->term_order ) ) {
            global $wpdb;

            $query = "SELECT tr.term_order FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id LEFT JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE t.term_id = %d AND tt.taxonomy = %s AND tr.object_id = 0";

            $order = $wpdb->get_var( $wpdb->prepare( $query, $term->term_id, $taxonomy ) );
            $term->term_order = $order ? $order : 0;

            wp_cache_replace( $term->term_id, $term, $taxonomy );
        }

        return $term;
    }

    /**
     * Filter the list of terms attached to the given post.
     *
     * @since 1.0.0
     * @access public
     *
     * @see get_the_terms()
     *
     * @param array  $terms    List of attached terms.
     * @param int    $post_id  Post ID.
     * @param string $taxonomy Name of the taxonomy.
    */
    function get_the_terms( $terms, $post_id, $taxonomy ) {
        $terms = wp_get_object_terms( array( $post_id ), $taxonomy, array( 'orderby' => 'term_order' ) );
        wp_cache_replace( $post_id, $terms, $taxonomy . '_relationships' );

        return $terms;
    }

    /**
     * Filter the terms for a given object or objects.
     *
     * @since 1.0.0
     * @access public
     *
     * @see wp_get_object_terms()
     *
     * @param array        $terms      An array of terms for the given object or objects.
     * @param array|int    $object_ids Object ID or array of IDs.
     * @param array|string $taxonomies A taxonomy or array of taxonomies.
     * @param array        $args       An array of arguments for retrieving terms for
     *                                 the given object(s).
     */
    function get_object_terms( $terms, $object_ids, $taxonomies, $args ) {
        // 'term_order' == $args['orderby'] ... Enable only specified orderby argument.
        $do_term_order = true;

        if ( ! $terms || is_wp_error( $terms ) || ! $do_term_order ) {
            return $terms;
        }

        global $wpdb;

        extract($args, EXTR_SKIP);

        if ( 'term_order' != $args['orderby'] ) {
            if ( 'count' == $orderby )
                $orderby = 'tt.count';
            else if ( 'name' == $orderby )
                $orderby = 't.name';
            else if ( 'slug' == $orderby )
                $orderby = 't.slug';
            else if ( 'term_group' == $orderby )
                $orderby = 't.term_group';
            else if ( 'term_order' == $orderby )
                $orderby = 'tr.term_order';
            else if ( 'none' == $orderby ) {
                $orderby = '';
                $order = '';
            } else {
                $orderby = 't.term_id';
            }

            // tt_ids queries can only be none or tr.term_taxonomy_id
            if ( ('tt_ids' == $fields) && !empty($orderby) )
                $orderby = 'tr.term_taxonomy_id';

            $orderby = implode( ',', array_filter( array( 'tr.term_order ASC', $orderby ) ) );

        }else{
            $orderby = 'tr.term_order';
            $order   = 'ASC';
        }

        $order = strtoupper( $order );
        if ( '' !== $order && ! in_array( $order, array( 'ASC', 'DESC' ) ) )
            $order = 'ASC';

        $field       = '';
        $select_this = '';
        $values      = array();

        switch ( $fields ) {
            case 'all':
                $field = 'term_id';
                $select_this = 't.*, tt.*, tr.term_order';
                $values = wp_list_pluck( $terms, $field );
                break;

            case 'ids':
                $field = 'term_id';
                $select_this = 't.term_id, tt.taxonomy';
                $values = $terms;
                break;

            case 'names':
                $field = 'name';
                $select_this = 't.name, t.term_id, tt.taxonomy';
                $values = array_map( array( $this, 'add_quote' ), $terms );
                break;

            case 'slugs':
                $field ='slug';
                $select_this = 't.slug, t.term_id, tt.taxonomy';
                $values = array_map( array( $this, 'add_quote' ), $terms );
                break;

            case 'all_with_object_id':
                $field = 'term_id';
                $select_this = 't.*, tt.*, tr.term_order, tr.object_id';
                $values = wp_list_pluck( $terms, $field );
                break;

            case 'tt_ids':
                $values = $terms;
                break;
        }

        $values = implode( ',', $values );

        $query = "SELECT $select_this"
               . " FROM $wpdb->terms AS t"
               . " INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id"
               . " LEFT  JOIN $wpdb->term_relationships AS tr ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tr.object_id = 0)"
               . " WHERE t.$field IN ($values)"
               . " ORDER BY $orderby $order";

        if ( 'all' == $fields || 'all_with_object_id' == $fields ) {
            $terms = $wpdb->get_results( $query );
            foreach ( $terms as $key => $term ) {
                $terms[$key] = sanitize_term( $term, $term->taxonomy, 'raw' );
            }
            update_term_cache( $terms );

        } else if ( 'ids' == $fields || 'names' == $fields || 'slugs' == $fields ) {
            $terms = $wpdb->get_results( $query );
            $_field = ( 'ids' == $fields ) ? 'term_id' : 'name';
            foreach ( $terms as $key => $term ) {
                $terms[$key] = sanitize_term_field( $_field, $term->$field, $term->term_id, $term->taxonomy, 'raw' );
            }

        } else if ( 'tt_ids' == $fields ) {
            $terms = $wpdb->get_results(
                "SELECT tt.term_taxonomy_id, tt.taxonomy"
              . " FROM $wpdb->term_taxonomy AS tt"
              . " LEFT JOIN $wpdb->term_relationships AS tr ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tr.object_id = 0)"
              . " WHERE tt.term_taxonomy_id IN ($values)"
              . " ORDER BY $orderby $order"
            );

            foreach ( $terms as $key => $term ) {
                $terms[$key] = sanitize_term_field( 'term_taxonomy_id', $term->term_taxonomy_id, 0, $term->taxonomy, 'raw' );
            }
        }

        return $terms;
    }
    
    /**
     * Surround the string with a single quote
     *
     * @since 1.0.2
     * @access protected
     *
     * @see Anything_Order_Taxonomy::get_object_terms()
     *
     * @param string $str An string.
     * @return string String surrounded with a single quote.
     *
     */
    protected function add_quote( $str ) {
        return "'$str'";
    }

    /**
     * Filter the terms query SQL clauses.
     *
     * @since 1.0.0
     * @access public
     *
     * @see get_terms()
     *
     * @param array        $pieces     Terms query SQL clauses.
     * @param string|array $taxonomies A taxonomy or array of taxonomies.
     */
     function terms_clauses( $pieces, $taxonomies, $args ) {
        global $wpdb;

        // 'term_order' == $args['orderby'] ... Enable only specified orderby argument.
        $do_term_order = ! is_admin();

        if ( $do_term_order || ( is_admin() && ! isset( $_GET['orderby'] ) ) ) {
            if ( 'term_order' != $args['orderby'] ) {
                $orderby = implode( ',', array_filter( array(
                    'tr.term_order ASC',
                    str_replace( 'ORDER BY ', '', $pieces['orderby'] )
                ) ) );

            }else{
                $orderby = 'tr.term_order';

                if ( empty( $pieces['order'] ) ) {
                    $pieces['order'] = 'ASC';
                }
            }

            $pieces['orderby']  = 'ORDER BY ' . $orderby;
            $pieces['fields' ] .= ',tr.term_order';
            $pieces['join'   ] .= " LEFT JOIN $wpdb->term_relationships AS tr ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tr.object_id = 0)";
        }

        return $pieces;
    }

    /**
     * Capability for ordering.
     *
     * @since 1.0.0
     * @access protected
     */
    protected function cap() {
        $tax = get_taxonomy( $GLOBALS[$this->objectnow] );

        if ( ! $tax )
            wp_die( __( 'Invalid taxonomy' ) );

        return $tax->cap->manage_terms;
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
        add_filter( "manage_{$screen->id}_columns", array( $this, 'get_columns' ) );
        add_filter( "manage_{$screen->taxonomy}_custom_column", array( $this, 'render_column' ), 10, 3 );
    }

    /**
     * Hook: Render a column for ordering.
     *
     * @since 1.0.0
     * @access public
     */
    function render_column() {
        $args = func_get_args();
        array_shift( $args );

        $term = get_term( $args[1], $GLOBALS['taxnow'] );
        $args[2] = $term->term_order;

        return $this->_render_column( $args );
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
    protected function _update( $ids, $order, $objectnow ) {
        global $wpdb;

        $update = 1;

        if ( empty( $ids ) ) {
            $ids = get_terms( $objectnow, array(
                'fields'     => 'ids',
                'get'        => 'all',
            ) );

            $update = $order = 0;
        }

        $values = array();

        foreach ( $ids as $id ) {
            if ( 0 < $id ) {
                $term     = get_term( $id, $objectnow );
                $values[] = $wpdb->prepare( "(%d, %d, %d)", 0, $term->term_taxonomy_id, $order );
                $order   += $update;
            }
        }

        if ( $values ) {
            $wpdb->query( "INSERT INTO $wpdb->term_relationships (object_id, term_taxonomy_id, term_order) VALUES " . join( ',', $values ) . " ON DUPLICATE KEY UPDATE term_order = VALUES(term_order)" );
        }

        return $update;
    }
}

new Anything_Order_Taxonomy;
