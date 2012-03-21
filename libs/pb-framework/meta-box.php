<?php

// TODO: if and when WordPress moves to PHP5.3 requirement, implent the singleton pattern
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
    abstract function display( $post );

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

    // Get all fields in a grouped hierarchical list
    protected function get_fields( $group = '' ) {
        return $group && isset( $this->fields[$group] ) ? $this->fields[$group] : $this->fields;
    }

    // Get all fields in an unstructured flat list
    protected function get_fields_flat() {

        // Check cache
        if ( isset( $this->fields_flat ) ) return $this->fields_flat;

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
}