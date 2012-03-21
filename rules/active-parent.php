<?php

// Register this handler
add_action( 'plugins_loaded', create_function( '', 'Menu_Rules::register( "Menu_Rules_Handler_Active_Parent" );' ) );

class Menu_Rules_Handler_Active_Parent extends Menu_Rules_Handler {

    // Cache menu rules data
    protected $data;

    function __construct() {
        $this->setup( __('Emulate current page as a child but do not create a menu item.') );
    }

    function handler( $data ) {
        $this->data = $data;
        add_filter( 'wp_nav_menu_objects', array( $this, 'active_parent' ) );
    }

    function active_parent( $menu_items ) {

        // Ancestory IDs
        $active_ancestor_item_ids = array();

        // Array keys of the menu items passed in are incremental so we need to traverse them to match the ID against the rule
        foreach ( $menu_items as $order => &$menu_item ) {
            if ( ! in_array( $menu_item->ID, $this->data['menu-rules-menu-items'] ) ) continue;

            // Copied from _wp_menu_item_classes_by_context() in wp-includes/nav-menu-template.php
            // Direct parent class
            if ( ! in_array( 'current-menu-parent', $menu_item->classes ) ) {
                $menu_item->classes[] = 'current-menu-parent';
                $menu_item->classes[] = 'current_page_parent';
                $menu_item->current_item_parent = true;

                $menu_item->classes[] = 'current-menu-ancestor';
                $menu_item->classes[] = 'current_page_ancestor';
                $menu_item->current_item_ancestor = true;
            }

            // Get ancestors
            $_anc_id = (int) $menu_item->db_id;
            while(
                ( $_anc_id = get_post_meta( $_anc_id, '_menu_item_menu_item_parent', true ) ) &&
                ! in_array( $_anc_id, $active_ancestor_item_ids )
            ) {
                $active_ancestor_item_ids[] = $_anc_id;
            }
        }

        // Loop through once more to setup all ancestor classes
        if ( ! empty( $active_ancestor_item_ids ) ) {
            $this->setup_ancestor_classes( &$menu_items, $active_ancestor_item_ids );
        }

        return $menu_items;
    }

    protected function setup_parent_classes( $menu_item ) {
    }

    protected function setup_ancestor_classes( &$menu_items, $active_ancestor_item_ids ) {
        $active_ancestor_item_ids = array_filter( array_unique( $active_ancestor_item_ids ) );
        foreach ( $menu_items as &$menu_item ) {
            if (
                in_array( intval( $menu_item->db_id ), $active_ancestor_item_ids ) &&
                ! in_array( 'current-menu-ancestor', $menu_item->classes )
            ) {
                $menu_item->classes[] = 'current-menu-ancestor';
                $menu_item->classes[] = 'current_page_ancestor';
                $menu_item->current_item_ancestor = true;
            }
        }
    }
}