<?php

// Admin wrapper
class Menu_Rules_Admin {

    static $meta_box_conditions;
    static $meta_box_reactions;

    // On plugins loaded
    static function load() {

        // Include PB Framework
        // This has to be done at plugins_loaded but ideally it should go in the meta box class
        require_once dirname( __FILE__ ) . '/../libs/pb-framework/meta-box.php';

        add_action( 'admin_init', __CLASS__ . '::init' );

        add_action( 'admin_print_styles-post.php', __CLASS__ . '::styles' );
        add_action( 'admin_print_styles-post-new.php', __CLASS__ . '::styles' );

        add_action( 'admin_print_scripts-post.php', __CLASS__ . '::scripts' );
        add_action( 'admin_print_scripts-post-new.php', __CLASS__ . '::scripts' );

        add_filter( 'post_updated_messages', __CLASS__ . '::post_updated_messages' );
    }

    // On admin_init
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

        add_meta_box( 'menu-rules-conditions', __( 'Conditions', 'menu-rules' ), array( &Menu_Rules_Admin::$meta_box_conditions, 'display' ), Menu_Rules::get_var( 'post_type' ), 'normal' );

        add_meta_box( 'menu-rules-reactions', __( 'Reactions', 'menu-rules' ), array( &Menu_Rules_Admin::$meta_box_reactions, 'display' ), Menu_Rules::get_var( 'post_type' ), 'normal' );
    }

    static function post_updated_messages( $messages ) {
        global $post, $post_ID;

        $messages[ Menu_Rules::get_var( 'post_type' ) ] = array(
            0 => '',
            1 => __( 'Menu rule updated.', 'menu-rules' ),
            2 => __( 'Custom field updated.', 'menu-rules' ),
            3 => __( 'Custom field deleted.', 'menu-rules' ),
            4 => __( 'Menu rule updated.', 'menu-rules' ),
            5 => isset($_GET['revision']) ? sprintf( __( 'Menu rule restored to revision from %s', 'menu-rules' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
            6 => __( 'Menu rule published.', 'menu-rules' ),
            7 => __( 'Menu rule saved.', 'menu-rules' ),
            8 => __( 'Menu rule submitted.', 'menu-rules' ),
            9 => __( 'Menu rule scheduled for.', 'menu-rules' ),
            10 => __( 'Menu rule draft updated.', 'menu-rules' ),
        );

        return $messages;
    }
}