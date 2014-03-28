<?php

/**
 * Theme-less frontend boot. Content is generated via api templates.
 */

if ( defined( 'ABSPATH' ) ) {
	throw new Exception( 'WordPress is already loaded' );
}

define( 'WP_USE_THEMES', false );

try {
	require_once( 'wp-load.php' );

	// ApiComApi is a child of AbstractAjaxApi.
	require_once( WP_PLUGIN_DIR . '/apmcom/ApmComApi.php' );

	if ( !isset( $aca ) ) {
		$aca = new ApmComApi();
		$aca->obStart();
	}

} catch ( Exception $e ) {
	if ( function_exists( 'sdv_backtrace' ) ) {
		sdv_backtrace();
		sdv_dbg('e',$e);
	} else {
		echo htmlspecialchars( $e->getMessage(), ENT_COMPAT, 'UTF-8' );
	}
	exit();
}

