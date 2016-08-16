<?php

class FrmCptController {

	public static function load_lang() {
		$plugin_folder = FrmCptAppHelper::plugin_folder();
		load_plugin_textdomain( 'frmcptch', false, $plugin_folder . '/languages/' );
	}

	public static function include_updater() {
		if ( class_exists( 'FrmAddon' ) ) {
			FrmCptUpdate::load_hooks();
		}
	}

	/**
	 * Add the Formidable checkbox option in captcha v4.0.5 - 4.2.3 settings
	 */
	public static function add_option( $options ) {
		$cptch_options = get_option( 'cptch_options' );
		$checked = ( isset( $cptch_options[ 'cptch_frm_form' ] ) && $cptch_options[ 'cptch_frm_form' ] != '' ) ? 'checked="checked"' : '';

		$options .= '<label>';
		$options .= '<input type="checkbox" name="cptch_frm_form" value="cptch_frm_form" ' . $checked . ' />';
		$options .= ' Formidable form</label><br/>';

		return $options;
	}

	/**
	 * Checks if this is the captcha settings page, and if the settings need to be saved
	 * Called on the admin_head hook.
	 */
	public static function save_cptch_opt() {
		if ( ! self::is_captcha_page() ) {
			return;
		}

		//save captcha
		if ( isset( $_REQUEST[ 'cptch_form_submit' ] ) ) {
			$cptch_options = self::get_bws_captcha_options();
			$frm_form = isset( $_REQUEST[ 'cptch_frm_form' ] );
			if ( $cptch_options ) {
				$cptch_options[ 'cptch_frm_form' ] = $frm_form;
			} else {
				$cptch_options = get_option( 'cptch_options' ); // get options from the database
				$cptch_options[ 'cptch_frm_form' ] = $frm_form;
				$cptch_options = update_option( 'cptch_options', $cptch_options ); // save options
			}
		} else {
			// insert the default setting for the Formidable checkbox
			$cptch_options = get_option( 'cptch_options' ); // get options from the database
			if ( ! isset( $cptch_options[ 'cptch_frm_form' ] ) || $cptch_options[ 'cptch_frm_form' ] == '' ) {
				$cptch_options[ 'cptch_frm_form' ] = 0;
				$cptch_options = update_option( 'cptch_options', $cptch_options ); // save options
			}
		}
	}

	/**
	 * Adds a checkbox in the form settings to allow the captcha to be excluded
	 */
	public static function add_cptch_form_opt( $values ) { ?>
		<tr>
			<td colspan="2">
				<?php
				if ( ! self::is_cptch_installed() ) {
					echo '<p>' . esc_html( __( 'You are missing the BWS Captcha plugin', 'frmcptch' ) ) . '</p>';
				} else {
					$opt = (array)get_option( 'frm_cptch' ); ?>
					<label for="frm_cptch"><input type="checkbox" value="1" id="frm_cptch"
												  name="frm_cptch" <?php echo in_array( $values[ 'id' ], $opt ) ? 'checked="checked"' : ''; ?> /> <?php echo esc_html( __( 'Do not include the math captcha with this form.', 'frmcptch' ) ) ?>
					</label>
					<?php
				}
				?>
			</td>
		</tr>
		<?php
	}

	public static function update_cptch_form_options( $options, $values ) {
		$opt = (array)get_option( 'frm_cptch' );
		if ( isset( $values[ 'frm_cptch' ] ) && ( ! isset( $values[ 'id' ] ) || ! in_array( $values[ 'id' ], $opt ) ) ) {
			$opt[] = $values[ 'id' ];
			update_option( 'frm_cptch', $opt );
		} else if ( ! isset( $values[ 'frm_cptch' ] ) && isset( $values[ 'id' ] ) && in_array( $values[ 'id' ], $opt ) ) {
			$pos = array_search( $values[ 'id' ], $opt );
			unset( $opt[ $pos ] );
			update_option( 'frm_cptch', $opt );
		}

		return $options;
	}

	/**
	 * Insert captcha
	 */
	public static function add_cptch_field( $form, $action, $errors = array() ) {
		if ( self::skip_captcha() ) {
			return;
		}

		$cptch_error = ( ! empty( $errors ) && isset( $errors[ 'cptch_number' ] ) );

		//skip if there are more pages for this form
		$more_pages = self::more_form_pages( $form->id );
		if ( $more_pages ) {
			wp_nonce_field( 'frmcptch-nonce', 'frmcptch' );
			return;
		}

		if ( ! self::is_cptch_installed() ) {
			_e( 'You are missing the BWS Captcha plugin', 'frmcptch' );
			return;
		}

		$opt = get_option( 'frm_cptch' );
		if ( $opt && in_array( $form->id, (array)$opt ) ) {
			// insert a nonce field instead of the captcha for later validation
			wp_nonce_field( 'frmcptch-nonce', 'frmcptch' );
			return;
		}
		unset( $opt );

		$error_message = $cptch_error ? $errors[ 'cptch_number' ] : false;
		self::show_cptch_field( $form, $error_message );

		$cptch_options = self::get_bws_captcha_options();
		if ( ! isset( $cptch_options[ 'cptch_str_key' ] ) ) {
			global $str_key;
			update_option( 'frmcpt_str_key', $str_key );
		}
	}

	/**
	 * The HTML for the captcha
	 *
	 * @param object $form
	 * @param string $cptch_error
	 */
	private static function show_cptch_field( $form, $cptch_error ) {
		wp_enqueue_style( 'math_cptch_stylesheet', FrmCptAppHelper::plugin_url() . '/css/math-captcha.css' );

		// captcha html
		$classes = apply_filters( 'frm_cpt_field_classes', array( 'form-field', 'frm_top_container', 'auto_width' ), $form );
		if ( $cptch_error ) {
			$classes[] = 'frm_blank_field';
		}
		echo '<div id="frm_field_cptch_number_container" class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		$cptch_options = self::get_bws_captcha_options();
		if ( ! empty( $cptch_options[ 'cptch_label_form' ] ) ) {
			echo '<label class="frm_primary_label">' . wp_kses_post( $cptch_options[ 'cptch_label_form' ] );
			echo ' <span class="frm_required">' . wp_kses_post( $cptch_options[ 'cptch_required_symbol' ] ) . '</span>';
			echo '</label>';
		}

		if ( function_exists( 'cptch_display_captcha' ) ) {
			echo cptch_display_captcha();
		} else if ( function_exists( 'cptchpr_display_captcha' ) ) {
			echo cptchpr_display_captcha();
		} else {
			return;
		}

		if ( $cptch_error ) {
			echo '<div class="frm_error">' . esc_html( $cptch_error ) . '</div>';
		}

		echo '</div>';
	}

	public static function check_cptch_post( $errors, $values ) {
		if ( ! self::maybe_check_errors( $values ) ) {
			return $errors;
		}

		$number = isset( $_POST[ 'cptch_number' ] ) ? sanitize_text_field( $_POST[ 'cptch_number' ] ) : false;

		//if the captcha wasn't incuded on the page
		if ( $number === false ) {
			// if captcha is turned off for this form, there will be a nonce instead
			$check_nonce = ( isset( $_REQUEST[ 'frmcptch' ] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST[ 'frmcptch' ] ), 'frmcptch-nonce' ) );
			if ( ! $check_nonce ) {
				$errors[ 'cptch_number' ] = __( 'The captcha is missing from your form.', 'frmcptch' );
			}
		} else if ( $number == '' ) {
			// If captcha not complete, return error
			$errors[ 'cptch_number' ] = __( 'Please complete the CAPTCHA.', 'frmcptch' );
		} else if ( ! self::is_cptch_correct( $number ) ) {
			// captcha was not matched
			$errors[ 'cptch_number' ] = __( 'That CAPTCHA was incorrect.', 'frmcptch' );
		}

		return $errors;
	}

	/**
	 * Don't check the captcha if editing or if there are more pages in the form.
	 *
	 * @param array $values the posted values
	 * @return bool true if captcha should be checked
	 */
	private static function maybe_check_errors( $values ) {
		// skip captcha if user is logged in and the settings allow
		if ( self::skip_captcha() ) {
			return false;
		}

		//don't require if editing
		$action_var = isset( $_REQUEST[ 'frm_action' ] ) ? 'frm_action' : 'action';
		$editing = ( isset( $values[ $action_var ] ) && $values[ $action_var ] == 'update' );
		if ( $editing ) {
			return false;
		}
		unset( $action_var, $editing );

		//don't require if not on the last page
		return ! self::more_form_pages( $values[ 'form_id' ] );
	}

	/**
	 * Check if there are more pages in this form
	 *
	 * @param int $form_id
	 * @return bool
	 */
	private static function more_form_pages( $form_id ) {
		global $frm_vars, $frm_next_page;
		return ( ( is_array( $frm_vars ) && isset( $frm_vars[ 'next_page' ] ) && isset( $frm_vars[ 'next_page' ][ $form_id ] ) ) || ( is_array( $frm_next_page ) && isset( $frm_next_page[ $form_id ] ) ) );
	}

	/**
	 * Check the value of the captcha
	 *
	 * @param string $number
	 * @return bool True if correct, false if incorrect
	 */
	private static function is_cptch_correct( $number ) {
		$result = isset( $_POST[ 'cptch_result' ] ) ? sanitize_text_field( $_POST[ 'cptch_result' ] ) : '';
		$time = isset( $_REQUEST[ 'cptch_time' ] ) ? sanitize_text_field( $_REQUEST[ 'cptch_time' ] ) : null;

		$str_key = self::get_compare_key();
		if ( function_exists( 'cptch_decode' ) ) {
			$decoded = cptch_decode( $result, $str_key, $time );
		} else if ( function_exists( 'decode' ) ) {
			$decoded = decode( $result, $str_key, $time );
		} else {
			// we don't know how to check it, so don't
			return true;
		}

		return ( 0 == strcasecmp( trim( $decoded ), $number ) );
	}

	/**
	 * The key in the captcha plugin changes regular to help prevent spam.
	 * We need to make sure we're comparing against the correct key, or the result will be wrong.
	 *
	 * @return string
	 */
	private static function get_compare_key() {
		$cptch_options = self::get_bws_captcha_options();

		if ( isset( $cptch_options['str_key'] ) ) {
			$str_key = $cptch_options['str_key']['key'];
		} else if ( isset( $cptch_options['cptch_str_key'] ) ) {
			$str_key = $cptch_options[ 'cptch_str_key' ][ 'key' ];
		} else {
			global $str_key;
			$str_key = get_option( 'frmcpt_str_key' );
		}

		return $str_key;
	}

	/**
	 * Skip the captcha if we are on the back-end,
	 * if logged-in users don't need to see it, or if it's not turned on in the Captcha settings
	 *
	 * @return bool
	 */
	private static function skip_captcha() {
		$cptch_options = self::get_bws_captcha_options();
		$hide_from_registered = isset( $cptch_options[ 'cptch_hide_register' ] ) && $cptch_options[ 'cptch_hide_register' ] ? true : false;
		return ( is_admin() && ! defined( 'DOING_AJAX' ) ) || ( is_user_logged_in() && $hide_from_registered ) || ( ! isset( $cptch_options[ 'cptch_frm_form' ] ) || ! $cptch_options[ 'cptch_frm_form' ] );
	}

	private static function get_bws_captcha_options() {
		global $cptch_options;
		if ( empty( $cptch_options ) ) {
			$cptch_options = get_option( 'cptch_options' );
		}

		return $cptch_options;
	}

	/**
	 * Check if we are on the captcha settings page in the admin
	 * @return bool
	 */
	private static function is_captcha_page() {
		return ( $_GET && isset( $_GET['page'] ) && sanitize_text_field( $_GET['page'] ) == 'captcha.php' );
	}

	/**
	 * Check if the BWS captcha is installed
	 * @return bool true if the plugin is installed
	 */
	private static function is_cptch_installed() {
		return ( function_exists( 'cptch_display_captcha' ) || function_exists( 'cptchpr_display_captcha' ) );
	}
}