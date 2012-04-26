<?php

class Menu_Rules_Meta_Box extends PB_Meta_Box {

    // Setup fields
    function __construct() {

        parent::__construct();

        // Meta box only used on menu_rules post type
        $this->post_type = Menu_Rules::get_var( 'post_type' );

        // No nav menus defined
        // TODO: Present a notification
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

        // No items in any menus
        if ( ! $nav_menu_dropdown_values) return;

        // Condition fields
        $fields['conditions_adv'] = array(
            'menu-rules-conditional-exp' => array(
                'title' => __('When these conditions match:', 'menu-rules'),
                'type' => 'textarea',
                'name' => 'menu-rules-conditional-exp',
				'extra' => array(
                    'class' => 'code',
                    'rows' => 5,
                    'cols' => 60,
                    'placeholder' => __('eg is_single()', 'menu-rules'),
                )
            ),
        );

        // Rules
        $fields['rules'] = array(
            'menu-rules-rules' => array(
                'title' => __( 'Apply this rule:' , 'menu-rules'),
                'type' => 'radio',
                'name' => 'menu-rules-rules',
                'value' => array_combine(
                    array_keys( Menu_Rules::get_var( 'rules_handlers' ) ),
                    array_map( create_function( '$v', 'return $v->description;' ), Menu_Rules::get_var( 'rules_handlers' ) )
                ),
            ),
        );

        // Nav menus
        $fields['nav_menus'] = array(
            'menu-rules-menu-items' => array(
                'title' => __( 'To these menu items:' , 'menu-rules'),
                'type' => 'select',
                'name' => 'menu-rules-menu-items',
                'value' => $nav_menu_dropdown_values,
                'multiple' => true,
                'text' => __( 'Select menu items' , 'menu-rules'),
                'extra' => array(
                    'class' => 'menu-rules-items-select',
                    'required' => 'required',
                ),
            ),
        );

        $this->fields = $fields;
    }

    // Display meta box
    function display( $post ) {

        // User need to create a menu or before using menu rules
        if ( ! $this->get_fields() ) {
            echo '<p class="error-message">' . sprintf( __('You haven\'t setup any WordPress custom menus. %sCreate one now to start using Menu Rules%s', 'menu-rules'), '<a href="' . admin_url( 'nav-menus.php' ) . '">', '</a>' ) . '</p>';
            return;
        }

        $postmeta = get_post_custom( $post->ID );

        echo PB_Forms::table( $this->get_fields( 'conditions_adv' ), $postmeta );
        echo PB_Forms::table( $this->get_fields( 'rules' ), $postmeta );

        // Backward compatibility notice for 1.0 - 1.1 upgrades
        if ( count( $postmeta['menu-rules-rules'] ) > 1 ) {
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

        echo PB_Forms::table( $this->get_fields( 'nav_menus' ), $postmeta );
        echo '<p>' . sprintf( __('A full list of conditonal tags can be %sfound on the WordPress.org codex%s. Do not include an if statement or a semicolon.', 'menu-rules'), '<a href="http://codex.wordpress.org/Conditional_Tags" target="_blank">', '</a>' ) . '</p>';
        echo '<h4>' . __('Condition Examples', 'menu-rules') . '</h4>';
        echo '<p>' . sprintf( __('%sis_single()%s applies rules when viewing a single post.', 'menu-rules'), '<code>', '</code>' ) . '</p>';
        echo '<p>' . sprintf( __('%sis_singular( \'product\' )%s applies rules when viewing a single product.', 'menu-rules'), '<code>', '</code>' ) . '</p>';
        echo '<p>' . sprintf( __('%s( is_singular( \'book\' ) || is_singular( \'journal\' ) ) && has_tag( \'fiction\' )%s applies rules when showing a single book or journal which is tagged as fiction', 'menu-rules'), '<code>', '</code>' ) . '</p>';
    }


    // When JavaScript is printed on post.php
    static function javascript() {

        // Only for when we're editing this post type
        if ( ! isset( $GLOBALS['post_type_object'] ) || $GLOBALS['post_type_object']->name != Menu_Rules::get_var( 'post_type' ) ) return;
    }
}