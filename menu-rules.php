<?php
/*
Plugin Name: Menu rules
Description: An extension of the menu system with context-based rules and a flexible framework to write your own.
Author: Phill Brown
Author URI: http://pbweb.co.uk
Version: 1.2.1

Copyright 2012 Phill Brown (email: wp@pbweb.co.uk)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


// Triggered at plugins_loaded
Menu_Rules::load();

class Menu_Rules {

    protected static $vars = array(
        'post_type' => 'menu_rule',
        'rules_handlers' => array(),
    );

    // On plugins_loaded
	static function load() {

        // Include libraries
        require_once dirname( __FILE__ ) . '/includes/menu-rules-handler.php';

        // Setup built-in rules handers
        require_once dirname( __FILE__ ) . '/rules/active-parent.php';
        require_once dirname( __FILE__ ) . '/rules/inactive-parent.php';
        require_once dirname( __FILE__ ) . '/rules/child-page.php';

        // Load admin
        if ( is_admin() ) {
            require_once dirname( __FILE__ ) . '/admin/admin.php';
            Menu_Rules_Admin::load();
        }

        // Load common functionality
        add_action( 'init', __CLASS__ . '::init' );

        // Where the magic happens
        add_action( 'wp', __CLASS__ . '::apply_menu_rules' );

        // Internationalise
        load_plugin_textdomain( 'menu_rules', false, basename( dirname( __FILE__ ) ) . '/languages' );
	}

    // When WordPress initialises
    static function init() {

        // Setup the post type that stores menu rules
        register_post_type( self::get_var( 'post_type' ), array(
            'labels' => array(
                'name' => _x('Menu Rules', 'post type general name', 'menu-rules'),
                'singular_name' => _x('Menu Rule', 'post type singular name', 'menu-rules'),
                'add_new' => _x('Add New', 'Menu Rule', 'menu-rules'),
                'add_new_item' => __('Add New Menu Rule', 'menu-rules'),
                'edit_item' => __('Edit Menu Rule', 'menu-rules'),
                'new_item' => __('New Menu Rule', 'menu-rules'),
                'view_item' => __('View Menu Rule', 'menu-rules'),
                'search_items' => __('Search Menu Rules', 'menu-rules'),
                'not_found' =>  __('No Menu Rules found', 'menu-rules'),
                'not_found_in_trash' => __('No Menu Rules found in Trash', 'menu-rules'),
            ),
            'public' => false,
            'publicly_queryable' => false,
            'show_ui' => true,
            'show_in_menu' => 'themes.php',
            'capabilities' =>  array(
                'edit_posts' => 'edit_theme_options',
                'edit_others_posts' => 'edit_theme_options',
                'publish_posts' => 'edit_theme_options',
            ),
            'map_meta_cap' => true,
            'supports' => array( 'title' ),
            'register_meta_box_cb' => 'Menu_Rules_Admin::register_meta_boxes',
        ) );
    }

    // On wp
    static function apply_menu_rules() {

        // Get active menu rules
        $menu_rules = get_posts( array(
            'post_type' => self::get_var( 'post_type' ),
            'numberposts' => -1,
        ) );

        if ( empty( $menu_rules ) ) return;
        foreach ( $menu_rules as $menu_rule ) {
            $menu_rule_data = get_post_custom( $menu_rule->ID );

            // Revalidate. This should have been done on entry just in case the data was imported or something...
            // TODO: Use a standard meta box validation handler
            if ( empty( $menu_rule_data['menu-rules-menu-items'] ) ) return;
            if ( empty( $menu_rule_data['menu-rules-rules'] ) ) return;
            if ( empty( $menu_rule_data['menu-rules-conditional-exp'] ) ) return;

            $condtion_met = eval( 'return ' . $menu_rule_data['menu-rules-conditional-exp'][0] . ';' );
            if ($condtion_met) {

                // Apply rules
                $rules_handlers = self::get_var( 'rules_handlers' );
                foreach( $menu_rule_data['menu-rules-rules'] as $applied_rules_handler ) {

                    // Check the rules handler exists
                    if ( ! isset( $rules_handlers[$applied_rules_handler] ) ) return;

                    // Let the handler do the magic
                    call_user_func( array( $rules_handlers[$applied_rules_handler], 'handler' ), $menu_rule_data );
                }
            }
        }
    }

    // Register a new rules handler
    static function register( $class ) {
        self::$vars['rules_handlers'][$class] = new $class;
    }

    // Get a filterable variable
    static function get_var( $name ) {
        return apply_filters( 'menu_rules_' . $name, self::$vars[$name] );
    }
}