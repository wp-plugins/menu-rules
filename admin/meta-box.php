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

        foreach ( $nav_menus as $nav_menu_obj ) {

            // Check the menu isn't empty
            if ( ! $nav_menu_items = wp_get_nav_menu_items( $nav_menu_obj->term_id ) ) continue;

            $nav_menu_dropdown_values[$nav_menu_obj->name] = array_combine(
                array_map( create_function( '$v', 'return $v->ID;' ), $nav_menu_items ),
                array_map( create_function( '$v', 'return empty( $v->menu_item_parent ) ? $v->title : "-- " . $v->title;' ), $nav_menu_items )
            );
        }

        // Condition fields
        $fields['conditions_adv'] = array(
            array(
                'title' => __('When these conditions match:'),
                'type' => 'textarea',
                'name' => 'menu-rules-conditional-exp',
				'extra' => array(
                    'class' => 'code',
                    'rows' => 5,
                    'cols' => 60,
                    'placeholder' => __('eg is_single()'),
                )
            ),
        );

        // Rules
        $fields['rules'] = array(
            array(
                'title' => __( 'Apply these rules:' ),
                'type' => 'checkbox',
                'name' => 'menu-rules-rules',
                'text' => __( 'Choose rules' ),
                'value' => array_combine(
                    array_keys( Menu_Rules::get_var( 'rules_handlers' ) ),
                    array_map( create_function( '$v', 'return $v->description;' ), Menu_Rules::get_var( 'rules_handlers' ) )
                ),
            ),
        );

        // Nav menus
        $fields['nav_menus'] = array(
            array(
                'title' => __( 'To these menu items:' ),
                'type' => 'select',
                'name' => 'menu-rules-menu-items',
                'value' => $nav_menu_dropdown_values,
                'multiple' => true,
                'text' => __( 'Select menu items' ),
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

        $nav_menus = wp_get_nav_menus( array('orderby' => 'name') );

        // User need to create a menu before using menu rules
        if ( ! $nav_menus ) {
            echo '<p class="error-message">' . sprintf( __('You aren\'t using WordPress custom menus. %sCreate one now to start using Menu Rules%s'), '<a href="' . admin_url( 'nav-menus.php' ) . '">', '</a>' ) . '</p>';
            return;
        }

        $postmeta = get_post_custom( $post->ID );

        echo PB_Forms::table( $this->get_fields( 'conditions_adv' ), $postmeta );
        echo PB_Forms::table( $this->get_fields( 'rules' ), $postmeta );
        echo PB_Forms::table( $this->get_fields( 'nav_menus' ), $postmeta );
        echo '<p>' . sprintf( __('A full list of conditonal tags can be %sfound on the WordPress.org codex%s. Do not include an if statement or a semicolon.'), '<a href="http://codex.wordpress.org/Conditional_Tags" target="_blank">', '</a>' ) . '</p>';
        echo '<h4>' . __('Condition Examples') . '</h4>';
        echo '<p>' . sprintf( __('%sis_single()%s applies rules when viewing a single post.'), '<code>', '</code>' ) . '</p>';
        echo '<p>' . sprintf( __('%sis_singular( \'product\' )%s applies rules when viewing a single product.'), '<code>', '</code>' ) . '</p>';
        echo '<p>' . sprintf( __('%s( is_singular( \'book\' ) || is_singular( \'journal\' ) ) && has_tag( \'fiction\' )%s applies rules when showing a single book or journal which is tagged as fiction'), '<code>', '</code>' ) . '</p>';
    }


    // When JavaScript is printed on post.php
    static function javascript() {

        // Only for when we're editing this post type
        if ( ! isset( $GLOBALS['post_type_object'] ) || $GLOBALS['post_type_object']->name != Menu_Rules::get_var( 'post_type' ) ) return;
    }
}