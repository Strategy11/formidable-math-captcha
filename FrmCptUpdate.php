<?php

class FrmCptUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'Math Captcha';
	public $version = '1.12';

	public function __construct() {
		$this->plugin_file = dirname( __FILE__ ) . '/formidable-math-captcha.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmCptUpdate();
	}
}