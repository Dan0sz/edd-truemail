<?php
/**
 * Wizard AJAX Handler
 *
 * @package   daandev/correct-contact
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023-2026 Daan van den Bergh
 */

namespace CorrectContact\Admin\Wizard;

use CorrectContact\Admin\Settings;
use CorrectContact\Options;

defined( 'ABSPATH' ) || exit;

class Ajax {
	/**
	 * Ajax constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cc_wizard_save_do_token', [ $this, 'ajax_wizard_save_do_token' ] );
		add_action( 'wp_ajax_cc_wizard_provision', [ $this, 'ajax_wizard_provision' ] );
		add_action( 'wp_ajax_cc_wizard_save_credentials', [ $this, 'ajax_wizard_save_credentials' ] );
		add_action( 'wp_ajax_cc_wizard_remove_token', [ $this, 'ajax_wizard_remove_token' ] );
		add_action( 'wp_ajax_cc_wizard_complete', [ $this, 'ajax_wizard_complete' ] );
	}
	
	/**
	 * AJAX handler for saving DigitalOcean API token.
	 */
	public function ajax_wizard_save_do_token() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => __( 'API token is required.', 'correct-contact' ) ] );
		}
		
		Options::update( Settings::DO_TOKEN, $token );
		
		wp_send_json_success();
	}
	
	/**
	 * AJAX handler for wizard provisioning steps.
	 */
	public function ajax_wizard_provision() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		$step  = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => __( 'API token is required.', 'correct-contact' ) ] );
		}
		
		// Simulate provisioning steps
		// In a real implementation, this would make actual DigitalOcean API calls
		switch ( $step ) {
			case 'project':
				// Simulate project creation
				sleep( 1 );
				wp_send_json_success( [ 'step' => 'project' ] );
				break;
			
			case 'server':
				// Simulate server creation
				sleep( 2 );
				wp_send_json_success( [ 'step' => 'server' ] );
				break;
			
			case 'install':
				// Simulate Truemail installation
				sleep( 2 );
				wp_send_json_success( [ 'step' => 'install' ] );
				break;
			
			case 'secure':
				// Simulate API access configuration
				sleep( 1 );
				// Generate mock credentials
				$app_url      = 'https://truemail-' . wp_generate_password( 8, false ) . '.example.com';
				$access_token = wp_generate_password( 32, false );
				wp_send_json_success( [
					'step'         => 'secure',
					'app_url'      => $app_url,
					'access_token' => $access_token,
				] );
				break;
			
			case 'done':
				wp_send_json_success( [ 'step' => 'done' ] );
				break;
			
			default:
				wp_send_json_error( [ 'message' => __( 'Invalid step.', 'correct-contact' ) ] );
		}
	}
	
	/**
	 * AJAX handler for saving wizard credentials.
	 */
	public function ajax_wizard_save_credentials() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		$app_url      = isset( $_POST['app_url'] ) ? esc_url_raw( $_POST['app_url'] ) : '';
		$access_token = isset( $_POST['access_token'] ) ? sanitize_text_field( $_POST['access_token'] ) : '';
		$do_token     = isset( $_POST['do_token'] ) ? sanitize_text_field( $_POST['do_token'] ) : '';
		
		if ( empty( $app_url ) || empty( $access_token ) ) {
			wp_send_json_error( [ 'message' => __( 'Missing credentials.', 'correct-contact' ) ] );
		}
		
		// Save credentials
		Options::update( Settings::APP_URL, $app_url );
		Options::update( Settings::ACCESS_TOKEN, $access_token );
		
		// Store DO token (no longer temporary, but still in the main options row)
		Options::update( Settings::DO_TOKEN, $do_token );
		
		wp_send_json_success();
	}
	
	/**
	 * AJAX handler for removing DigitalOcean API token.
	 */
	public function ajax_wizard_remove_token() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		// Remove DO token from options
		$options = get_option( Settings::OPTION_NAME, [] );
		if ( isset( $options[ Settings::DO_TOKEN ] ) ) {
			unset( $options[ Settings::DO_TOKEN ] );
			update_option( Settings::OPTION_NAME, $options );
		}
		
		// Also remove temporary DO token if it exists
		delete_option( 'cc_do_token_temp' );
		
		wp_send_json_success();
	}
	
	/**
	 * AJAX handler for completing the wizard.
	 */
	public function ajax_wizard_complete() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		// Mark setup as completed
		Options::update( Settings::SETUP_COMPLETED, true );
		
		// Clean up temporary token if still exists
		delete_option( 'cc_do_token_temp' );
		
		// Also clean up DO token from main options as it's no longer needed after setup
		$options = get_option( Settings::OPTION_NAME, [] );
		if ( isset( $options[ Settings::DO_TOKEN ] ) ) {
			unset( $options[ Settings::DO_TOKEN ] );
			update_option( Settings::OPTION_NAME, $options );
		}
		
		wp_send_json_success();
	}
}
