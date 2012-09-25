<?php

abstract class PB_Meta_Box {

    // Which post types this meta box is attached to
    protected $post_type;

    // For caching
    protected $fields = array();
    protected $fields_flat;

    // On init
    function __construct() {
        add_action( 'save_post', array( &$this, 'save' ), 10, 2 );
    }

    // Display meta box
    function display( $post ) {
        foreach ( $this->get_fields() as $field_group ) echo PB_Forms::table( $field_group, get_post_custom( $post->ID ) );
    }

    // Save data
    function save( $post_id, $post ) {

        // No fields? Don't save
        $fields = $this->get_fields_flat();
        if ( empty( $fields ) ) return;

        // Check post type
        if ( isset( $this->post_type ) && $post->post_type != $this->post_type ) return;

        // Check each field for submitted data
        foreach ( $fields as $field_name => $field_data ) {

            // If the field has a null value assume it's an unticked checkbox
            if ( ! isset( $_POST[$field_name] ) ) {
                delete_post_meta( $post_id, $field_name );
            }
            // If the data is an array, like checkboxes or a multiple select list
            elseif ( is_array( $_POST[$field_name] ) ) {

                // Save new values
                delete_post_meta( $post_id, $field_name );
                foreach ( $_POST[$field_name] as $field_value ) {

                    // Validation
                    if ( $field_data['type'] == 'checkbox' && isset( $field_data['value'] ) && ! in_array( $field_value, array_keys( $field_data['value'] ) ) ) continue;

                    add_post_meta( $post_id, $field_name, $field_value );
                }
            }
            // Regular name/value data
            else {
                add_post_meta( $post_id, $field_name, $_POST[$field_name], true ) or update_post_meta( $post_id, $field_name, $_POST[$field_name] );
            }
        }
    }

    // Register Stylesheets
    function styles() {
        wp_enqueue_style( 'pb-meta-box', plugins_url( '/assets/css/meta-box.css', __FILE__ ) );
    }

    // Register JavaScript
    function scripts() {
    }

    // Get all fields in a grouped hierarchical list
    protected function get_fields( $group = '' ) {
        if ( $group ) {
            return isset( $this->fields[$group] ) ? $this->fields[$group] : array();
        } else {
            return $this->fields;
        }
    }

    // Get all fields in an unstructured flat list
    protected function get_fields_flat() {

        // Check cache
        if ( isset( $this->fields_flat ) ) return $this->fields_flat;

        $fields = array();
        foreach ( $this->get_fields() as $meta_box_fields ) {
            foreach ( $meta_box_fields as $field ) {

                // If fields of the same name are spread across different fields
                // If it's a checkbox or dropdown we need to merge the value sets together
                if ( isset( $fields[$field['name']] ) && is_array( $field['value'] ) ) {
                    $fields[$field['name']]['value'] += $field['value'];
                } else {
                    $fields[$field['name']] = $field;
                }
            }
        }

        // Cache and return
        return $this->fields_flat = $fields;
    }

    // Get a specific field definition data
    protected function get_field_def( $name ) {
        $all_fields = $this->get_fields_flat();
        return isset( $all_fields[$name] ) ? $all_fields[$name] : false;
    }

    // Add a group of fields
    protected function add_field_group( $name, $fields ) {
        $this->fields[$name] = $fields;
    }
}