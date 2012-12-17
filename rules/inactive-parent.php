<?php

// Register this handler
add_action( 'plugins_loaded', create_function( '', 'Menu_Rules::register( "Menu_Rules_Handler_Inactive_Parent" );' ) );

class Menu_Rules_Handler_Inactive_Parent extends Menu_Rules_Handler {

    // Cache menu rules data
    protected $data;

    function __construct() {
        $this->setup( __('Remove all active states.', 'menu-rules') );
    }

    function handler( $data ) {
        $this->data = $data;
        add_filter( 'wp_nav_menu_objects', array( $this, 'run' ) );
    }

    function run( $menu_items ) {

        foreach ( $menu_items as $order => &$menu_item ) {

            // No rules applied to this menu item
            if ( ! in_array( $menu_item->ID, $this->data['menu-rules-menu-items'] ) ) continue;

            $classes_to_remove = array();
            if ( $classes_to_remove[] = array_search( 'current-menu-parent', $menu_item->classes ) || $classes_to_remove[] = array_search( 'current_page_parent', $menu_item->classes ) ) {
                
                // Search for other ancestor classes
                $classes_to_remove[] = array_search( 'current-menu-ancestor', $menu_item->classes );
                $classes_to_remove[] = array_search( 'current_page_ancestor', $menu_item->classes );

                foreach ( $classes_to_remove as $class_to_remove ) {
                    if ( is_int( $class_to_remove ) ) unset( $menu_item->classes[$class_to_remove] );
                }

                $menu_item->current_item_parent = false;
                $menu_item->current_item_ancestor = false;
            }
        }

        return $menu_items;
    }
}