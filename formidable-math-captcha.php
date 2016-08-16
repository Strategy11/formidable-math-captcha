<?php
/*
Plugin Name: Formidable Math Captcha
Description: Extends Captcha by BestWebSoft to work with Formidable Forms
Version: 1.13
Plugin URI: http://formidablepro.com/
Author URI: http://strategy11.com
Author: Strategy11
Text Domain: frmcptch
*/

function frm_cptch_forms_autoloader($class_name) {
	$path = dirname(__FILE__);

	// Only load Frm classes here
	if ( ! preg_match('/^FrmCpt.+$/', $class_name) ) {
		return;
	}

	if ( preg_match('/^.+Helper$/', $class_name) ) {
		$path .= '/helpers/' . $class_name . '.php';
	} else if ( preg_match('/^.+Controller$/', $class_name) ) {
		$path .= '/controllers/'. $class_name .'.php';
	} else {
		$path .= '/models/'. $class_name .'.php';
	}

	if ( file_exists($path) ) {
		include($path);
	}
}

// Add the autoloader
spl_autoload_register('frm_cptch_forms_autoloader');

// Load hooks
FrmCptHooksController::load_hooks();