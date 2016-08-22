<?php

class FrmCptHooksController{

	public static function load_hooks() {
		add_action( 'plugins_loaded', 'FrmCptController::load_lang' );

		// Add captcha into Formidable form
		add_action( 'frm_entry_form', 'FrmCptController::add_cptch_field', 150, 3 );
		add_filter( 'frm_validate_entry', 'FrmCptController::check_cptch_post', 10, 2 );

		self::load_admin_hooks();
	}

	public static function load_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_init', 'FrmCptController::include_updater', 1 );

		add_action( 'admin_head', 'FrmCptController::save_cptch_opt' );

		// Necessary prior to BWS Captcha version 4.2.3
		add_filter( 'cptch_forms_list', 'FrmCptController::add_option' );

		// for >= v4.2.3
		add_filter( 'cptch_add_form', 'FrmCptController::add_option_tab' );

		add_action( 'frm_additional_form_options', 'FrmCptController::add_cptch_form_opt', 50 );
		add_filter( 'frm_form_options_before_update', 'FrmCptController::update_cptch_form_options', 20, 2 );
	}

}