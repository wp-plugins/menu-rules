<?php

// Register this handler
add_action( 'plugins_loaded', create_function( '', 'Menu_Rules::register( "Menu_Rules_Handler_Child_Page" );' ) );

include_once dirname( __FILE__ ) . '/active-parent.php';
class Menu_Rules_Handler_Child_Page extends Menu_Rules_Handler_Active_Parent {

    function __construct() {
        $this->setup( __('Insert the current page into the menu as a child.', 'menu-rules') );
    }

    function handler( $data ) {
        $this->data = $data;
        add_filter( 'wp_nav_menu_objects', array( $this, 'child_page' ) );
    }

    function child_page( $menu_items ) {

        // Make the parent items active
        $menu_items = $this->active_parent( $menu_items );

        // Add the fake page

        // Array keys of the menu items passed in are incremental so we need to traverse them to match the ID against the rule
        foreach ( $menu_items as $order => $menu_item ) {
            if ( ! in_array( $menu_item->ID, $this->data['menu-rules-menu-items'] ) ) continue;

            // Create a fake descendant menu item
            $dummy_menu_item = (object) array(
                'ID' => 'menu-rules-child-page',
                'post_status' => 'publish',
                'menu_item_parent' => $menu_item->ID,
                'object' => 'custom',
                'post_parent' => 0,
                'object_id' => 0,
                'db_id' => 0,
                'type' => 'custom',
                'url' => $current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . untrailingslashit( $_SERVER['REQUEST_URI'] ),
                'title' => $GLOBALS['post']->post_title,
                'classes' => array(),
            );
            array_splice( $menu_items, $order, 0, array( $dummy_menu_item ) );

            _wp_menu_item_classes_by_context( $menu_items );
        }
        return $menu_items;
    }
}