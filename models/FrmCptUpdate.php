<?php

class FrmCptUpdate extends FrmAddon {
	public $plugin_file;
	public $plugin_name = 'Math Captcha';
	public $download_id = 163255;
	public $version = '1.15';

	public function __construct() {
		$this->plugin_file = FrmCptAppHelper::plugin_path() . '/formidable-math-captcha.php';
		parent::__construct();
	}

	public static function load_hooks() {
		add_filter( 'frm_include_addon_page', '__return_true' );
		new FrmCptUpdate();
	}
}