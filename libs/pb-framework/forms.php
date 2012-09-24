<?php

// Form framework wrapper
// Forked from scb-framework: http://scribu.net/wordpress/scb-framework
// New features include: multiple select boxes, optgroups
// TODO: Make singleton to allow class extensions

// On plugins_loaded
require dirname( __FILE__ ) . '/../scb-framework/scb/load.php';
scb_init( /* create_function( '', 'require_once dirname( __FILE__ ) . "/../libs/pb-framework/forms.php";' ) */ );

class PB_Forms {

	const TOKEN = '%input%';

	protected static $cur_name;

	static function input( $args, $formdata = false ) {
		if ( !empty( $formdata ) ) {
			$form = new PB_Form( $formdata );
			return $form->input( $args );
		}

		if ( empty( $args['name'] ) ) {
			return trigger_error( 'Empty name', E_USER_WARNING );
		}

		$args = wp_parse_args( $args, array(
			'desc' => '',
			'desc_pos' => 'after',
			'wrap' => self::TOKEN,
		) );

		if ( isset( $args['value'] ) && is_array( $args['value'] ) ) {
			$args['values'] = $args['value'];
			unset( $args['value'] );
		}

		if ( isset( $args['extra'] ) && !is_array( $args['extra'] ) )
			$args['extra'] = shortcode_parse_atts( $args['extra'] );

		self::$cur_name = self::get_name( $args['name'] );


		switch ( $args['type'] ) {
			case 'select':
			case 'radio':
				$input = self::_single_choice( $args );
				break;
			case 'checkbox':
				if ( isset( $args['values'] ) )
					$input = self::_multiple_choice( $args );
				else
					$input = self::_checkbox( $args );
				break;
			default:
				$input = self::_input( $args );
		}

		return str_replace( self::TOKEN, $input, $args['wrap'] );
	}


// ____________UTILITIES____________


	// Generates a table wrapped in a form
	static function form_table( $rows, $formdata = NULL ) {
		$output = '';
		foreach ( $rows as $row )
			$output .= self::table_row( $row, $formdata );

		$output = self::form_table_wrap( $output );

		return $output;
	}

	// Generates a form
	static function form( $inputs, $formdata = NULL, $nonce ) {
		$output = '';
		foreach ( $inputs as $input )
			$output .= self::input( $input, $formdata );

		$output = self::form_wrap( $output, $nonce );

		return $output;
	}

	// Generates a table
	static function table( $rows, $formdata = NULL ) {
		$output = '';
		foreach ( $rows as $row )
			$output .= self::table_row( $row, $formdata );

		$output = self::table_wrap( $output );

		return $output;
	}

	// Generates a table row
	static function table_row( $args, $formdata = NULL ) {
		return self::row_wrap( $args, self::input( $args, $formdata ) );
	}


// ____________WRAPPERS____________


	// Wraps the given content in a <form><table>
	static function form_table_wrap( $content, $nonce = 'update_options' ) {
		$output = self::table_wrap( $content );
		$output = self::form_wrap( $output, $nonce );

		return $output;
	}

	// Wraps the given content in a <form> tag
	static function form_wrap( $content, $nonce = 'update_options' ) {
		$output = "\n<form method='post' action=''>\n";
		$output .= $content;
		$output .= wp_nonce_field( $action = $nonce, $name = "_wpnonce", $referer = true , $echo = false );
		$output .= "\n</form>\n";

		return $output;
	}

	// Wraps the given content in a <table>
	static function table_wrap( $content ) {
		$output = "\n<table class='form-table pb-framework-table'>\n" . $content . "\n</table>\n";

		return $output;
	}

	// Wraps the given content in a <tr><td>
	static function row_wrap( $args, $content ) {
		$output = '<tr><th scope="row"><label for="' . $args['name'] . '">' . $args['title'] . '</label>';

        // Description below the label
        if ( isset( $args['description'] ) ) $output .= $args['description'];

        $output .= '</th><td>' . $content;

        // Footer text below the field
        if ( isset( $args['footer'] ) ) $output .= $args['footer'];

        $output .= '</td></tr>';

        return $output;
	}


// ____________PRIVATE METHODS____________


	private static function _single_choice( $args ) {
		$args = wp_parse_args( $args, array(
			'numeric' => false,		// use numeric array instead of associative
			'selected' => array( 'foo' ),	// hack to make default blank
		) );

		self::_expand_values( $args );

		if ( 'select' == $args['type'] )
			return self::_select( $args );
		else
			return self::_radio( $args );
	}

	private static function _multiple_choice( $args ) {
		$args = wp_parse_args( $args, array(
			'numeric' => false,		// use numeric array instead of associative
			'checked' => null,
		) );

		self::$cur_name .= '[]';

		self::_expand_values( $args );

		extract( $args );

		if ( !is_array( $checked ) )
			$checked = array();

		$opts = '';
        $counter = 0;
		foreach ( $values as $value => $title ) {
            if ( $counter > 0 ) $opts .= '<br/>';

			if ( empty( $value ) || empty( $title ) )
				continue;

            $checkbox_args = array(
				'type' => 'checkbox',
				'value' => $value,
				'checked' => in_array( $value, $checked ),
				'desc' => $title,
				'desc_pos' => 'after',
			);
            if ( isset( $extra ) ) $checkbox_args['extra'] = $extra;
			$opts .= self::_checkbox( $checkbox_args );

            $counter++;

		}

		return self::add_desc( $opts, $desc, $desc_pos );
	}

	private static function _expand_values( &$args ) {
		$values =& $args['values'];

		if ( !empty( $values ) && !self::is_associative( $values ) ) {
			if ( is_array( $args['desc'] ) ) {
				$values = array_combine( $values, $args['desc'] );	// back-compat
				$args['desc'] = false;
			} elseif ( !$args['numeric'] ) {
				$values = array_combine( $values, $values );
			}
		}
	}

	private static function _radio( $args ) {
		extract( $args );

		if ( array( 'foo' ) == $selected ) {
			// radio buttons should always have one option selected
			$selected = key( $values );
		}

		$opts = '';
        $counter = 0;
		foreach ( $values as $value => $title ) {
            if ( $counter > 0 ) $opts .= '<br/>';

			if ( empty( $value ) || empty( $title ) )
				continue;

			$checkbox_args = array(
				'type' => 'radio',
				'value' => $value,
                'checked' => is_array( $selected )
                    ? in_array( $value, $selected )
                    : ( (string) $value == (string) $selected ),
				'desc' => $title,
				'desc_pos' => 'after'
			);
            if ( isset( $extra ) ) $checkbox_args['extra'] = $extra;
            $opts .= self::_checkbox( $checkbox_args );

            $counter++;
		}

		return self::add_desc( $opts, $desc, $desc_pos );
	}

	private static function _select( $args ) {
		extract( wp_parse_args( $args, array(
			'text' => '',
			'extra' => array(),
            'multiple' => false,
            'use_js' => true,
		) ) );

        // Options for multiple selects
        if ( $multiple ) {

            // Convert name into array
    		self::$cur_name .= '[]';

            // Add multiple and class attr to select box
            $extra['multiple'] = 'multiple';
        }

        // Fancy JavaScript form library
        if ( $use_js ) {
            wp_enqueue_style( 'pb-vendor-chosen', plugins_url( '/assets/vendor/chosen/chosen.css', __FILE__ ) );
            wp_enqueue_script( 'pb-vendor-chosen', plugins_url( '/assets/vendor/chosen/chosen.jquery.min.js', __FILE__ ), array( 'jquery' ), false, true );
            wp_enqueue_script( 'pb-chosen-init', plugins_url( '/assets/js/init-chosen.js', __FILE__ ), array( 'pb-vendor-chosen' ), false, true );

            // Class JS will pickup on
            $extra_classes[] = 'js-chosen';

            // Placeholder handler
            if ( ! empty( $text ) ) $extra['data-placeholder'] = $text;

            // Required attribute isn't yet supported by chosen so remove it for now
            if ( isset( $extra['required'] ) ) unset( $extra['required'] );
        }

        // String containing options list
		$opts = '';

        // First blank option, often a placeholder
		if ( $text !== false && ! $multiple ) {

            // Change box to a deselecter and make the first option blank
            if ( $use_js ) {

                $opts .= html( 'option', array(
                    'value' => '',
                    'selected' => false
                ), '' );

                $js_chosen_key = array_search( 'js-chosen', $extra_classes );
                if ( $js_chosen_key !== false ) {
                    $extra_classes[$js_chosen_key] = 'js-chosen-deselect';
                }
            } else {
                $placeholder = $text == '' ? $text : '&ndash; ' . $text . ' &ndash;';
                $opts .= html( 'option', array(
                    'value' => '',
                    'selected' => false
                ), $placeholder );
            }
		}

		foreach ( $values as $value => $title ) {
			if ( empty( $value ) || empty( $title ) ) continue;

            // Optgroup support
            if ( is_array( $title ) ) {
                $optgroup = '';
                foreach ( $title as $value2 => $title2 ) {
                    $optgroup .= html( 'option', array(
                        'value' => $value2,
                        'selected' => is_array( $selected )
                            ? in_array( $value2, $selected )
                            : ( (string) $value2 == (string) $selected )
                    ), $title2 );
                }
		        $opts .= html( 'optgroup', array( 'label' => $value ), $optgroup );

            } else {
                $opts .= html( 'option', array(
                    'value' => $value,
                    'selected' => is_array( $selected )
                        ? in_array( $value, $selected )
                        : ( (string) $value == (string) $selected )
                ), $title );
            }
		}

        // Add extra classes
        if ( ! empty( $extra_classes ) ) {
            $extra_classes = implode( ' ', $extra_classes );
            $extra['class'] = empty( $extra['class'] ) ? $extra_classes : $extra['class'] . ' ' . $extra_classes;
        }

		$extra['name'] = self::$cur_name;

		$input = html( 'select', $extra, $opts );

		return self::add_label( $input, $desc, $desc_pos );
	}

	// Handle args for a single checkbox or radio input
	private static function _checkbox( $args ) {
		$args = wp_parse_args( $args, array(
			'value' => true,
			'desc' => NULL,
			'checked' => false,
			'extra' => array(),
		) );


		foreach ( $args as $key => &$val )
			$$key = &$val;
		unset( $val );

		$extra['checked'] = $checked;

		if ( is_null( $desc ) && !is_bool( $value ) )
			$desc = str_replace( '[]', '', $value );

		return self::_input_gen( $args );
	}

	// Handle args for text inputs
	private static function _input( $args ) {
		$args = wp_parse_args( $args, array(
			'value' => '',
			'desc_pos' => 'after',
			'extra' => array( 'class' => 'regular-text' ),
		) );

		foreach ( $args as $key => &$val )
			$$key = &$val;
		unset( $val );

		if ( !isset( $extra['id'] ) && !is_array( $name ) && false === strpos( $name, '[' ) )
			$extra['id'] = $name;

		return self::_input_gen( $args );
	}

	// Generate html with the final args
	private static function _input_gen( $args ) {
		extract( wp_parse_args( $args, array(
			'value' => NULL,
			'desc' => NULL,
			'extra' => array()
		) ) );

		$extra['name'] = self::$cur_name;

		if ( 'textarea' == $type ) {
			$input = html( 'textarea', $extra, esc_textarea( $value ) );
		} else {
			$extra['value'] = $value;
			$extra['type'] = $type;
			$input = html( 'input', $extra );
		}

		return self::add_label( $input, $desc, $desc_pos );
	}

	private static function add_label( $input, $desc, $desc_pos ) {
		return html( 'label', self::add_desc( $input, $desc, $desc_pos ) ) . "\n";
	}

	private static function add_desc( $input, $desc, $desc_pos ) {
		if ( empty( $desc ) )
			return $input;

		if ( 'before' == $desc_pos )
			return $desc . ' ' . $input;
		else
			return $input . ' ' . $desc;
	}


// Utilities


	/**
	 * Generates the proper string for a name attribute.
	 *
	 * @param array|string $name The raw name
	 *
	 * @return string
	 */
	static function get_name( $name ) {
		$name = (array) $name;

		$name_str = array_shift( $name );

		foreach ( $name as $key ) {
			$name_str .= '[' . esc_attr( $key ) . ']';
		}

		return $name_str;
	}

	/**
	 * Traverses the formdata and retrieves the correct value.
	 *
	 * @param array|string $name The name of the value
	 * @param array $value The data that will be traversed
	 * @param mixed $fallback The value returned when the key is not found
	 *
	 * @return mixed
	 */
	static function get_value( $name, $value, $fallback = null ) {
		foreach ( (array) $name as $key ) {
			if ( !isset( $value[ $key ] ) )
				return $fallback;

			$value = $value[$key];
		}

		return $value;
	}

	/**
	 * Given a list of fields, extract the appropriate POST data and return it.
	 *
	 * @param array $fields List of args that would be sent to PB_Forms::input()
	 * @param array $to_update Existing data to update
	 *
	 * @return array
	 */
	static function validate_post_data( $fields, $to_update = array() ) {
		foreach ( $fields as $field ) {
			$value = PB_Forms::get_value( $field['name'], $_POST );

			$value = stripslashes_deep( $value );

			switch ( $field['type'] ) {
			case 'checkbox':
				if ( isset( $field['values'] ) && is_array( $field['values'] ) )
					$value = array_intersect( $field['values'], (array) $value );
				else
					$value = (bool) $value;

				break;
			case 'radio':
			case 'select':
				if ( !isset( $field['values'][ $value ] ) )
					continue 2;
			}

			self::set_value( $to_update, $field['name'], $value );
		}

		return $to_update;
	}

	private static function set_value( &$arr, $name, $value ) {
		$name = (array) $name;

		$final_key = array_pop( $name );

		while ( !empty( $name ) ) {
			$key = array_shift( $name );

			if ( !isset( $arr[ $key ] ) )
				$arr[ $key ] = array();

			$arr =& $arr[ $key ];
		}

		$arr[ $final_key ] = $value;
	}

	private static function is_associative( $array ) {
		$keys = array_keys( $array );
		return array_keys( $keys ) !== $keys;
	}
}

/**
 * A wrapper for PB_Forms, containing the formdata
 */
class PB_Form {
	protected $data = array();
	protected $prefix = array();

	function __construct( $data, $prefix = false ) {
		if ( is_array( $data ) )
			$this->data = $data;

		if ( $prefix )
			$this->prefix = (array) $prefix;
	}

	function traverse_to( $path ) {
		$data = PB_Forms::get_value( $path, $this->data );

		$prefix = array_merge( $this->prefix, (array) $path );

		return new PB_Form( $data, $prefix );
	}

	function input( $args ) {
		$value = PB_Forms::get_value( $args['name'], $this->data );

		if ( !is_null( $value ) ) {
			switch ( $args['type'] ) {
			case 'select':
			case 'radio':
				$args['selected'] = $value;
				break;
			case 'checkbox':
				if ( is_array( $value ) )
					$args['checked'] = $value;
				else
					$args['checked'] = ( $value || ( isset( $args['value'] ) && $value == $args['value'] ) );
				break;
			default:
				$args['value'] = is_array( $value ) ? reset( $value ) : $value;
			}
		}

		if ( !empty( $this->prefix ) ) {
			$args['name'] = array_merge( $this->prefix, (array) $args['name'] );
		}

		return PB_Forms::input( $args );
	}
}