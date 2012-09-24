<?php

// Admin wrapper
class Menu_Rules_Admin {

    static $meta_box_conditions;
    static $meta_box_reactions;

    // On plugins loaded
    static function load() {

        // Include PB Framework
        // This has to be done at plugins_loaded but ideally it should go in the meta box class
        require dirname( __FILE__ ) . '/../libs/pb-framework/load.php';
        PB_Framework::load( array( 'meta-box', 'forms' ) );

        add_action( 'init', __CLASS__ . '::init' );

        add_action( 'admin_print_styles-post.php', __CLASS__ . '::styles' );
        add_action( 'admin_print_styles-post-new.php', __CLASS__ . '::styles' );

        add_action( 'admin_print_scripts-post.php', __CLASS__ . '::scripts' );
        add_action( 'admin_print_scripts-post-new.php', __CLASS__ . '::scripts' );
    }

    // On init
    static function init() {

        // Load meta box object here so the save handler is added earlier in the request but after plugins_loaded so menu items are loaded
        require dirname( __FILE__ ) . '/meta-box-conditions.php';
        self::$meta_box_conditions = new Menu_Rules_Meta_Box_Conditions();

        require dirname( __FILE__ ) . '/meta-box-reactions.php';
        self::$meta_box_reactions = new Menu_Rules_Meta_Box_Reactions();
    }

    // When Stylesheets are outputted on post.php
    static function styles() {

        // Only for when we're editing this post type
        if ( ! isset( $GLOBALS['post_type_object'] ) || $GLOBALS['post_type_object']->name != Menu_Rules::get_var( 'post_type' ) ) return;

        self::$meta_box_reactions->styles();
    }

    // When JavaScript is outputted on post.php
    static function scripts() {

        // Only for when we're editing this post type
        if ( ! isset( $GLOBALS['post_type_object'] ) || $GLOBALS['post_type_object']->name != Menu_Rules::get_var( 'post_type' ) ) return;

        // Disable autosave to prevent to unsaved form notice
        if ( wp_script_is( 'autosave', $list = 'queue' ) ) wp_dequeue_script( 'autosave' );
    }

    // When the post type is registered
    // TODO: decouple this from Menu_Rules::init
    static function register_meta_boxes() {

        add_meta_box( 'menu-rules-conditions', __('Conditions'), array( &Menu_Rules_Admin::$meta_box_conditions, 'display' ), Menu_Rules::get_var( 'post_type' ), 'normal' );

        add_meta_box( 'menu-rules-reactions', __('Reactions'), array( &Menu_Rules_Admin::$meta_box_reactions, 'display' ), Menu_Rules::get_var( 'post_type' ), 'normal' );
    }
}