<?php
/*
Plugin Name: Formidable Math Captcha
Description: Extends Captcha by BestWebSoft to work with Formidable
Version: 1.10.01
Plugin URI: http://formidablepro.com/
Author URI: http://strategy11.com
Author: Strategy11
Text Domain: cptch
*/

new FrmCptController();

class FrmCptController {
	function __construct() {
		add_action( 'init', 'FrmCptController::load_hooks' );
		add_action( 'admin_init', 'FrmCptController::include_updater', 1 );
		add_action( 'plugins_loaded', 'FrmCptController::load_lang' );
		add_action( 'admin_head', 'FrmCptController::add_cptch_opt' );
		add_action( 'admin_footer', 'FrmCptController::add_cptch_check' );

		// for Captcha v4.0.5+
		add_filter( 'cptchpr_display_captcha_custom', 'FrmCptController::add_option' ); // add to pro version
		add_filter( 'cptch_forms_list', 'FrmCptController::add_option' ); // add to free version

		add_action( 'frm_additional_form_options', 'FrmCptController::add_cptch_form_opt', 50 );
		add_filter( 'frm_form_options_before_update', 'FrmCptController::update_cptch_form_options', 20, 2 );
	}

	public static function load_hooks() {
		$cptch_options = get_option( 'cptch_options' ); // get the options from the database

		// Add captcha into Formidable form
		if ( isset( $cptch_options['cptch_frm_form'] ) && $cptch_options['cptch_frm_form'] ) {
			add_action('frm_entry_form', 'FrmCptController::add_cptch_field', 150, 3);
			add_filter('frm_validate_entry', 'FrmCptController::check_cptch_post', 10, 2 );
		}
	}


	public static function load_lang() {
		load_plugin_textdomain( 'cptch', false, basename( dirname( __FILE__ ) ) .'/languages/' );
	}


	public static function include_updater() {
		include_once( dirname(__FILE__) .'/FrmCptUpdate.php' );
		new FrmCptUpdate();
	}


	public static function add_cptch_opt() {

		if ( ! isset($_GET) || ! isset($_GET['page']) || $_GET['page'] != 'captcha.php' ) {
			return;
		}

		// for Captcha < v3.9.8
		global $cptch_admin_fields_enable;
		if ( $cptch_admin_fields_enable ) {
			$cptch_admin_fields_enable[] = array( 'cptch_frm_form', 'Formidable form', 'Formidable form' );
		}

		//save captcha
		if ( isset( $_REQUEST['cptch_form_submit'] ) ) {
			global $cptch_options;
			$frm_form = isset( $_REQUEST['cptch_frm_form'] ) ? 1 : 0;
			if ( $cptch_options ) {
				$cptch_options['cptch_frm_form'] = $frm_form;
			} else {
				$cptch_options = get_option( 'cptch_options' ); // get options from the database
				$cptch_options['cptch_frm_form'] = $frm_form;
				$cptch_options = update_option( 'cptch_options', $cptch_options ); // save options
			}
		} else {
			$cptch_options = get_option( 'cptch_options' ); // get options from the database
			if ( ! isset( $cptch_options['cptch_frm_form'] ) || $cptch_options['cptch_frm_form'] == '' ) {
				$cptch_options['cptch_frm_form'] = 0;

				$cptch_options = update_option( 'cptch_options', $cptch_options ); // save options
			}
		}
	}

	public static function add_option( $options ) {
		remove_action( 'admin_footer', 'FrmCptController::add_cptch_check' );

		$cptch_options = get_option( 'cptch_options' );
		$checked = ( isset($cptch_options['cptch_frm_form']) && $cptch_options['cptch_frm_form'] != '' ) ? 'checked="checked"' : '';

		$options .= '<label>';
		$options .= '<input type="checkbox" name="cptch_frm_form" value="cptch_frm_form" '. $checked .' />';
		$options .= ' Formidable form</label><br/>';

		return $options;
	}

	// for Captcha v3.9.8+
	public static function add_cptch_check() {
		if ( ! $_GET || ! isset( $_GET['page'] ) || $_GET['page'] != 'captcha.php' ) {
			return;
		}

		global $cptch_admin_fields_enable;
		if ( $cptch_admin_fields_enable ) {
			// if this global is used, then the checkbox has already been added (Captcha < v3.9.8)
			return;
		}

		$cptch_options = get_option( 'cptch_options' );
		$checked = ( isset($cptch_options['cptch_frm_form']) && $cptch_options['cptch_frm_form'] != '' ) ? 'checked="checked"' : '';
?>
<script type="text/javascript">
jQuery(document).ready(function($){
$('input[name="cptch_comments_form"]').closest('label').after('<br/><label><input type="checkbox" name="cptch_frm_form" value="cptch_frm_form" <?php echo $checked ?> /> Formidable form</label>');
});
</script>
<?php
	}

	public static function add_cptch_form_opt( $values ) { ?>
<tr><td colspan="2">
<?php
	if ( ! function_exists('cptch_display_captcha') && ! function_exists('cptchpr_display_captcha') ) {
		echo '<p>'. __( 'You are missing the BWS Captcha plugin', 'cptch' ) .'</p>';
	} else {
		$opt = (array) get_option( 'frm_cptch' ); ?>
<label for="frm_cptch"><input type="checkbox" value="1" id="frm_cptch" name="frm_cptch" <?php echo in_array( $values['id'], $opt ) ? 'checked="checked"' : ''; ?> /> <?php _e( 'Do not include the math captcha with this form.', 'cptch' ) ?></label>
<?php
} ?>
</td></tr>
<?php
	}

	public static function update_cptch_form_options( $options, $values ) {
		$opt = (array) get_option( 'frm_cptch' );
		if ( isset($values['frm_cptch']) && ( ! isset( $values['id'] ) || ! in_array( $values['id'], $opt ) ) ) {
			$opt[] = $values['id'];
			update_option( 'frm_cptch', $opt );
		} else if ( ! isset( $values['frm_cptch'] ) && isset( $values['id'] ) && in_array( $values['id'], $opt ) ) {
			$pos = array_search( $values['id'], $opt );
			unset( $opt[ $pos ] );
			update_option( 'frm_cptch', $opt );
		}

		return $options;
	}

	/**
	 * Insert captcha
	 */
	public static function add_cptch_field( $form, $action, $errors = '' ) {
		global $cptch_options, $frm_next_page, $frm_vars;

		// skip captcha if user is logged in and the settings allow
		if ( ( is_admin() && ! defined('DOING_AJAX') ) || ( is_user_logged_in() && 1 == $cptch_options['cptch_hide_register'] ) ) {
			return;
		}

		//skip if there are more pages for this form
		if ( ( is_array( $errors ) && ! isset( $errors['cptch_number'] ) ) || ( is_array( $frm_vars ) && isset( $frm_vars['next_page']) && isset( $frm_vars['next_page'][ $form->id ] ) ) || ( is_array( $frm_next_page ) && isset( $frm_next_page[ $form->id ] ) ) ) {
			return;
		}

		if ( ! function_exists('cptch_display_captcha') && ! function_exists('cptchpr_display_captcha') ) {
			_e( 'You are missing the BWS Captcha plugin', 'cptch' );
			return;
		}

		$opt = get_option( 'frm_cptch' );
		if ( $opt && in_array( $form->id, (array) $opt ) ) {
			// insert a nonce field instead of the captcha for later validation
			wp_nonce_field('frmcptch-nonce', 'frmcptch');
			return;
		}
		unset( $opt );

		// captcha html
		$classes = apply_filters( 'frm_cpt_field_classes', array('form-field', 'frm_top_container', 'auto_width'), $form );
		if ( is_array( $errors ) && isset( $errors['cptch_number'] ) ) {
			$classes[] = 'frm_blank_field';
		}
		echo '<div id="frm_field_cptch_number_container" class="'. implode(' ', $classes) .'">';
		unset( $classes );

		if ( ! empty( $cptch_options['cptch_label_form'] ) ) {
			echo '<label class="frm_primary_label">'. $cptch_options['cptch_label_form'];
			echo ' <span class="frm_required">'. $cptch_options['cptch_required_symbol'] .'</span>';
			echo '</label>';
		}

		if ( function_exists( 'cptch_display_captcha' ) ) {
			cptch_display_captcha();
		} else if ( function_exists( 'cptchpr_display_captcha' ) ) {
			cptchpr_display_captcha();
		} else {
			return;
		}

		if ( ! isset( $cptch_options['cptch_str_key'] ) ) {
			global $str_key;
			update_option( 'frmcpt_str_key', $str_key );
		}

		if ( is_array($errors) && isset( $errors['cptch_number'] ) ) {
			echo '<div class="frm_error">'. $errors['cptch_number'] .'</div>';
		}

		echo '</div>';
	}

	public static function check_cptch_post( $errors, $values ) {
		global $cptch_options;

		// skip captcha if user is logged in and the settings allow
		if ( ( is_admin() && ! defined('DOING_AJAX') ) || ( is_user_logged_in() && 1 == $cptch_options['cptch_hide_register'] ) ) {
			return $errors;
		}

		//don't require if editing
		$action_var = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
	  	if ( isset( $values[ $action_var ] ) && $values[ $action_var ] == 'update' ) {
	  		return $errors;
		}
	  	unset( $action_var );

	  	//don't require if not on the last page
		global $frm_next_page, $frm_vars;
		if ( ( is_array( $frm_vars ) && isset( $frm_vars['next_page'] ) && isset( $frm_vars['next_page'][ $values['form_id'] ] ) ) || ( is_array( $frm_next_page ) && isset( $frm_next_page[ $values['form_id'] ] ) ) ) {
			return $errors;
		}

	  	//if the captcha wasn't incuded on the page
		if ( ! isset( $_POST['cptch_number'] ) ) {
			// if captcha is turned off for this form, there will be a nonce instead
			if ( ! isset( $_REQUEST['frmcptch'] ) || ! wp_verify_nonce( $_REQUEST['frmcptch'], 'frmcptch-nonce' ) ) {
				$errors['cptch_number'] = __( 'The captcha is missing from your form.', 'cptch' );
			}
			return $errors;
		}

	  	if ( ! isset( $cptch_options['cptch_str_key'] ) ) {
			global $str_key;
			$str_key = get_option('frmcpt_str_key');
		} else {
			$str_key = $cptch_options['cptch_str_key']['key'];
		}

	  	// If captcha not complete, return error
	  	if ( $_POST['cptch_number'] == '' ) {
	  		$errors['cptch_number'] = __( 'Please complete the CAPTCHA.', 'cptch' );
		}

		if ( function_exists( 'cptch_decode' ) ) {
			$decoded = cptch_decode( $_POST['cptch_result'], $str_key, ( isset( $_REQUEST['cptch_time'] ) ? $_REQUEST['cptch_time'] : null ) );
		} else if ( function_exists( 'decode' ) ) {
			$decoded = decode( $_POST['cptch_result'], $str_key, ( isset( $_REQUEST['cptch_time'] ) ? $_REQUEST['cptch_time'] : null) );
		} else {
			// we don't know how to check it, so don't
			return $errors;
		}

	  	if ( ! isset( $_POST['cptch_result'] ) || 0 !== strcasecmp( trim( $decoded ), $_POST['cptch_number'] ) ) {
	  		// captcha was not matched
	  		$errors['cptch_number'] = __( 'That CAPTCHA was incorrect.', 'cptch' );
	  	}

		return $errors;
	}
}