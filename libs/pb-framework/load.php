<?php

// Framework loading utility to ensure classes are only loaded once
if ( ! class_exists( 'PB_Framework' ) ) {
    class PB_Framework {
        public static function load( $libs ) {
            foreach ( $libs as $lib ) {
                if ( ! class_exists( $lib ) ) {
                    require_once dirname( __FILE__ ) . '/' . $lib . '.php';
                }
            }
        }
    }
}