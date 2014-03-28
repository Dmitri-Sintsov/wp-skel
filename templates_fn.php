<?php

/**
 * @todo: Use namespaces for all template-related functions.
 * @note: These template functions are used for non-themed frontend;
 * See boot_sample.php / non-themed-sample.php for non-themed frontend.
 *
 * To build custom theme use undescores theme and 960gs / unsemantic:
 * http://notebook.gaslampmedia.com/adding-960-grid-system-960-gs-to-underscores-wordpress-theme/
 * At approximately line 126 of a fresh Underscores theme’s functions.php, you’ll find “function mytheme_scripts()”. Add a line at the top of the function to include 960.css:

function mytheme_scripts() {
	wp_enqueue_style( 'mytheme-960', get_template_directory_uri() . '/960.css' );
	# OR to include style.css
	wp_enqueue_style( 'mytheme-style', get_stylesheet_uri() );
	...
}
	add_action( 'wp_enqueue_scripts', 'mytheme_scripts' );
*/

$a2o_key_path = array();

/**
 * Recursively converts associative arrays to stdClass while keeping integer keys subarrays as arrays
 * (lists of scalar values or collection of objects).
 *
 * Used to make php templates easier readable.
 * Associative object syntax is less obtrusive than associative arrays syntax, especially in html/xml attributes.
 */
function a2o( array $array, $denyNullValues = true ) {
	global $a2o_key_path;
	$a2o_key_path = array();
	return _a2o( $array, $denyNullValues );
}

function _a2o( array $array, $denyNullValues = true ) {
	global $a2o_key_path;
	$resultObj = new stdClass;
	$resultArr = array();
	$hasIntKeys = false;
	$hasStrKeys = false;
	foreach ( $array as $k => $v ) {
		if ( $denyNullValues ) {
			$a2o_key_path[] = $k;
		}
		if ( !$hasIntKeys ) {
			$hasIntKeys = is_int( $k );
		}
		if ( !$hasStrKeys ) {
			$hasStrKeys = is_string( $k );
		}
		if ( $hasIntKeys && $hasStrKeys ) {
			$e = new Exception( 'Current level has both integer and string keys, thus it is impossible to keep array or convert to object' );
			$e->vars = array( 'level' => $array );
			throw $e;
		}
		if ( is_array( $v ) ) {
			if ( $hasStrKeys ) {
				$resultObj->{$k} = a2o( $v );
			} else {
				$resultArr[$k] = a2o( $v );
			}
		} else {
			if ( $denyNullValues && $v === null ) {
				$e = new Exception( 'Please set non-null value for key path' );
				$e->vars = array( 'key_path' => $a2o_key_path, 'argument' => $array );
				throw $e;
			}
			if ( $hasStrKeys ) {
				$resultObj->{$k} = $v;
			} else {
				$resultArr[$k] = $v;
			}
		}
		if ( $denyNullValues ) {
			array_pop( $a2o_key_path );
		}
	}
	return ($hasStrKeys) ? $resultObj : $resultArr;
}

/**
 * Get a random element from consequitve integer keys array.
 */
function get_random_element( array &$list ) {
	$randKey = mt_rand( 0, count( $list ) - 1 );
	if ( array_key_exists( $randKey, $list ) ) {
		return $list[$randKey];
	} else {
		$e = new Exception( 'Non-consequtive or non-integer keys array' );
		$e->vars = array( 'list' => $list );
		throw $e;
	}
}

function attr( $str, $disallowEmpty = true ) {
	if ( !is_int( $str ) && !is_string( $str ) ) {
		if ( function_exists( 'sdv_backtrace' ) ) {
			sdv_backtrace();
		}
		throw new Exception( 'Attribute value should be int or string' );
	}
	if ( $disallowEmpty && is_string( $str ) && trim( $str ) === '' ) {
		if ( function_exists( 'sdv_backtrace' ) ) {
			sdv_backtrace();
		}
		throw new Exception( 'Attribute value cannot be empty string by default' );
	}
	echo htmlspecialchars( $str, ENT_COMPAT, 'UTF-8' );
}

/**
 * @param $str string
 *   html substring to display
 * @param $checkDisbalancedTags boolean
 *   when debugging layouts or AJAX calls, change to true
 */
function ____( $str, $checkDisbalancedTags = false ) {
	if ( !is_int( $str ) && !is_string( $str ) ) {
		if ( function_exists( 'sdv_backtrace' ) ) {
			sdv_backtrace();
		}
		throw new Exception( 'HTML value should be int or string' );
	}
	if ( $checkDisbalancedTags &&
			function_exists( 'force_balance_tags' )
			&& $str !== force_balance_tags( $str ) ) {
		if ( function_exists( 'sdv_backtrace' ) ) {
			sdv_backtrace();
		}
		$e = new Exception( 'Disbalanced tags' );
		$e->vars = array( 'html' => $str );
		throw $e;
	}
	echo $str;
}

