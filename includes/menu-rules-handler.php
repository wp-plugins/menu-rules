<?php

// Base class for rules handlers
abstract class Menu_Rules_Handler {

    public $description = '';

    function __construct() {
        $this->setup();
    }

    protected function setup( $description = '' ) {
        $this->description = empty($description) ? __CLASS__ : $description;
    }

    abstract function handler( $data );
}