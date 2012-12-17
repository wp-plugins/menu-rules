<?php

class Menu_Rules_Meta_Box_Reactions extends PB_Meta_Box {

    // Setup meta box
    function __construct() {

        parent::__construct();

        // Meta box only used on menu_rules post type
        $this->post_type = Menu_Rules::get_var( 'post_type' );

        // Check if there are navigation menus defined
        $nav_menus = wp_get_nav_menus( array('orderby' => 'name') );
        if ( ! $nav_menus ) return;

        $nav_menu_dropdown_values = array();
        foreach ( $nav_menus as $nav_menu_obj ) {

            // Check the menu isn't empty
            if ( ! $nav_menu_items = wp_get_nav_menu_items( $nav_menu_obj->term_id ) ) continue;

            $nav_menu_dropdown_values[$nav_menu_obj->name] = array_combine(
                array_map( create_function( '$v', 'return $v->ID;' ), $nav_menu_items ),
                array_map( create_function( '$v', 'return empty( $v->menu_item_parent ) ? $v->title : "-- " . $v->title;' ), $nav_menu_items )
            );
        }

        // Menus are setup but don't contain any items
        if ( ! $nav_menu_dropdown_values) return;

        // Menu Rules
        $this->add_field_group( 'menu-rules', array(
            // Action
            'menu-rules-rules' => array(
                'title' => __( 'Apply this rule:' , 'menu-rules' ),
                'type' => 'radio',
                'name' => 'menu-rules-rules',
                'value' => array_combine(
                    array_keys( Menu_Rules::get_var( 'rules_handlers' ) ),
                    array_map( create_function( '$v', 'return $v->description;' ), Menu_Rules::get_var( 'rules_handlers' ) )
                ),
            ),
            // Nav menus
            'menu-rules-menu-items' => array(
                'title' => __( 'To these menu items:' , 'menu-rules' ),
                'type' => 'select',
                'name' => 'menu-rules-menu-items',
                'value' => $nav_menu_dropdown_values,
                'multiple' => true,
                'text' => __( 'Select menu items' , 'menu-rules' ),
                'extra' => array(
                    'class' => 'menu-rules-items-select',
                    'required' => 'required',
                ),
            ),
        ) );
    }

    // Display meta box
    function display( $post ) {

        // User need to create a menu or before using menu rules
        if ( ! $this->get_fields( 'menu-rules' ) ) {
            echo '<p class="error-message">' . sprintf( __( 'You haven\'t setup any WordPress custom menus. %sCreate one now to start using Menu Rules%s', 'menu-rules' ), '<a href="' . admin_url( 'nav-menus.php' ) . '">', '</a>' ) . '</p>';
            return;
        }

        parent::display( $post );

        // Backward compatibility notice for 1.0 - 1.1 upgrades
        if ( isset( $postmeta['menu-rules-rules'] ) && count( $postmeta['menu-rules-rules'] ) > 1 ) {
            echo '<div class="error">';

            echo '<p>' . __( 'We&apos;ve changed the behaviour of Menu Rules so you can only select one rule per Menu Rule item. You have the following rules selected:', 'menu-rules' ) . '</p>';

            echo '<ul>';
            $menu_rules_rules_field = $this->get_field_def( 'menu-rules-rules' );
            foreach ( $postmeta['menu-rules-rules'] as $rule_value ) {
                echo '<li>' . $menu_rules_rules_field['value'][$rule_value] . '</li>';
            }
            echo '</ul>';

            echo '<p>' . sprintf(
                _n(
                    'Please apply 1 of these rules to this item. %sThen create another menu rule%s, copy these conditions and menu items and apply the other rule.',
                    'Please apply 1 of these rules to this item. %sThen create more menu rules%s, copy these conditions and menu items and apply the other rules.',
                    count( $postmeta['menu-rules-rules'] ) - 1,
                    'menu-rules'
                ),
                '<a href="' . admin_url( 'post-new.php?post_type=menu_rule' ) . '">',
                '</a>'
            ) . '</p>';

            echo '</div>';
        }
    }
}