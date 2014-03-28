<?php

/**
 * AJAX JSON API entry point.
 */

define('WP_USE_THEMES', false);
require_once( __DIR__ . '/AbstractAjaxApi.php' );
try {
	ob_start();
	$apmcom_ob_level = ob_get_level();
	require_once( AbstractAjaxApi::getWpLoad() );
	require_once( __DIR__ . '/ApmComApi.php' );
	$aca = new ApmComApi();
	$aca->execAjax();
} catch ( Exception $e ) {
	$aca->getExceptionOb( $e, $apmcom_ob_level );
	$aca->ajaxResponse( $e );
}

