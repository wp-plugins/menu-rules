<?php

class Menu_Rules_Meta_Box_Conditions extends PB_Meta_Box {

    // Setup meta box
    function __construct() {

        parent::__construct();

        // Condition fields
        $this->add_field_group( 'conditions', array(
            'menu-rules-conditional-exp' => array(
                'title' => __( 'When these conditions match:', 'menu-rules' ),
                'type' => 'textarea',
                'name' => 'menu-rules-conditional-exp',
				'extra' => array(
                    'class' => 'code',
                    'rows' => 5,
                    'cols' => 60,
                    'placeholder' => __( 'eg is_single()', 'menu-rules' ),
                ),
                'description' => '
                    <p>' . sprintf( __( 'A full list of conditonal tags can be %sfound on the WordPress.org codex%s.', 'menu-rules' ), '<a href="http://codex.wordpress.org/Conditional_Tags" target="_blank">', '</a>' ) . '</p>
                    <p>' . __( 'Do not include an if statement or a semicolon.', 'menu-rules' ) . '</p>
                ',
                'footer' => '
                    <h4>' . __( 'Examples', 'menu-rules' ) . '</h4>
                    <p>' . sprintf( __( '%sis_single()%s applies reactions when viewing a single post.', 'menu-rules' ), '<code>', '</code>' ) . '</p>
                    <p>' . sprintf( __( '%sis_singular( \'product\' )%s applies reactions when viewing a single product.', 'menu-rules' ), '<code>', '</code>' ) . '</p>
                    <p>' . sprintf( __( '%s( is_singular( \'book\' ) || is_singular( \'journal\' ) ) && has_tag( \'fiction\' )%s applies reactions when showing a single book or journal which is tagged as fiction', 'menu-rules' ), '<code>', '</code>' ) . '</p>
                    <p>' . sprintf( __( '%sis_user_logged_in()%s applies reactions if the current user is logged in.', 'menu-rules' ), '<code>', '</code>' ) . '</p>
                ',
            ),
        ) );
    }
}