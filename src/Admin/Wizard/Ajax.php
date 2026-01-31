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
		add_action( 'wp_ajax_cc_wizard_fetch_regions', [ $this, 'ajax_wizard_fetch_regions' ] );
		add_action( 'wp_ajax_cc_wizard_provision', [ $this, 'ajax_wizard_provision' ] );
		add_action( 'wp_ajax_cc_wizard_save_credentials', [ $this, 'ajax_wizard_save_credentials' ] );
		add_action( 'wp_ajax_cc_wizard_remove_token', [ $this, 'ajax_wizard_remove_token' ] );
		add_action( 'wp_ajax_cc_wizard_complete', [ $this, 'ajax_wizard_complete' ] );
	}
	
	/**
	 * AJAX handler for fetching DigitalOcean regions.
	 */
	public function ajax_wizard_fetch_regions() {
		check_ajax_referer( 'cc_wizard_nonce', 'nonce' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'correct-contact' ) ] );
		}
		
		$token = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => __( 'API token is required.', 'correct-contact' ) ] );
		}
		
		$response = wp_remote_get( 'https://api.digitalocean.com/v2/regions', [
			'headers' => [
				'Authorization' => 'Bearer ' . $token,
			],
		] );
		
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}
		
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			wp_send_json_error( [ 'message' => $body['message'] ?? __( 'Failed to fetch regions.', 'correct-contact' ) ] );
		}
		
		$regions = [];
		
		if ( ! empty( $body['regions'] ) ) {
			foreach ( $body['regions'] as $region ) {
				// Filter regions that support App Platform (features might include 'apps')
				if ( in_array( 'install_images', $region['features'] ) || in_array( 'apps', $region['features'] ) ) {
					$regions[] = [
						'slug' => $region['slug'],
						'name' => $region['name'],
					];
				}
			}
		}
		
		// Fallback to common regions if none found
		if ( empty( $regions ) ) {
			$regions = [
				[ 'slug' => 'ams3', 'name' => 'Amsterdam 3' ],
				[ 'slug' => 'fra1', 'name' => 'Frankfurt 1' ],
				[ 'slug' => 'nyc3', 'name' => 'New York 3' ],
				[ 'slug' => 'sfo3', 'name' => 'San Francisco 3' ],
				[ 'slug' => 'lon1', 'name' => 'London 1' ],
			];
		}
		
		wp_send_json_success( [ 'regions' => $regions ] );
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
		
		$step       = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
		$token      = isset( $_POST['token'] ) ? sanitize_text_field( $_POST['token'] ) : '';
		$region     = isset( $_POST['region'] ) ? sanitize_text_field( $_POST['region'] ) : 'ams3';
		$project_id = isset( $_POST['project_id'] ) ? sanitize_text_field( $_POST['project_id'] ) : '';
		$app_id     = isset( $_POST['app_id'] ) ? sanitize_text_field( $_POST['app_id'] ) : '';
		
		if ( empty( $token ) ) {
			wp_send_json_error( [ 'message' => __( 'API token is required.', 'correct-contact' ) ] );
		}
		
		switch ( $step ) {
			case 'project':
				$domain       = str_replace( [ 'http://', 'https://' ], '', get_home_url() );
				$project_name = str_replace( '.', '-', $domain );
				
				$response = wp_remote_post( 'https://api.digitalocean.com/v2/projects', [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					],
					'body'    => json_encode( [
						'name'    => $project_name,
						'purpose' => 'Web Application',
					] ),
				] );
				
				$code = wp_remote_retrieve_response_code( $response );
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				
				// If project already exists, we should find its ID and continue.
				if ( $code === 422 ) {
					$projects_response = wp_remote_get( 'https://api.digitalocean.com/v2/projects', [
						'headers' => [
							'Authorization' => 'Bearer ' . $token,
						],
					] );
					
					if ( ! is_wp_error( $projects_response ) ) {
						$projects_body = json_decode( wp_remote_retrieve_body( $projects_response ), true );
						if ( ! empty( $projects_body['projects'] ) ) {
							foreach ( $projects_body['projects'] as $project ) {
								if ( $project['name'] === $project_name ) {
									wp_send_json_success( [ 'step' => 'project', 'project_id' => $project['id'] ] );
									exit;
								}
							}
						}
					}
				}
				
				$this->handle_api_response( $response, 'project' );
				break;
			
			case 'server':
				// Step "server" is repurposed for "Create App" as per instructions
				$domain = str_replace( [ 'http://', 'https://' ], '', get_home_url() );
				$name   = str_replace( '.', '-', $domain );
				// App names must be lowercase and only contain alphanumeric characters and dashes
				$name = strtolower( preg_replace( '/[^a-zA-Z0-9-]/', '', $name ) );
				
				$access_token = wp_generate_password( 32, false, false );
				$admin_email  = get_option( 'admin_email' );
				
				$response = wp_remote_post( 'https://api.digitalocean.com/v2/apps', [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					],
					'body'    => json_encode( [
						'spec' => [
							'name'     => $name,
							'region'   => $region,
							'services' => [
								[
									'name'               => 'truemail',
									'http_port'          => 8080,
									'instance_count'     => 1,
									'instance_size_slug' => 'basic-s', // $10/month, 1GB RAM, 1 vCPU
									'git'                => [
										'repo_clone_url' => 'https://github.com/Dan0sz/truemail-rack-docker-image',
										'branch'         => 'master',
									],
									'envs'               => [
										[
											'key'   => 'ACCESS_TOKENS',
											'value' => $access_token,
											'scope' => 'RUN_AND_BUILD_TIME',
										],
										[
											'key'   => 'VERIFIER_EMAIL',
											'value' => $admin_email,
											'scope' => 'RUN_AND_BUILD_TIME',
										],
										[
											'key'   => 'SMTP_SAFE_CHECK',
											'value' => 'true',
											'scope' => 'RUN_AND_BUILD_TIME',
										],
									],
								],
							],
						],
					] ),
				] );
				
				$code = wp_remote_retrieve_response_code( $response );
				$body = json_decode( wp_remote_retrieve_body( $response ), true );
				
				// App names must be unique. If it already exists, find its ID.
				if ( $code === 422 ) {
					$apps_response = wp_remote_get( 'https://api.digitalocean.com/v2/apps', [
						'headers' => [
							'Authorization' => 'Bearer ' . $token,
						],
					] );
					
					if ( ! is_wp_error( $apps_response ) ) {
						$apps_body = json_decode( wp_remote_retrieve_body( $apps_response ), true );
						if ( ! empty( $apps_body['apps'] ) ) {
							foreach ( $apps_body['apps'] as $app ) {
								if ( $app['spec']['name'] === $name ) {
									wp_send_json_success( [ 'step' => 'server', 'app_id' => $app['id'] ] );
									exit;
								}
							}
						}
					}
				}
				
				// Store the generated access token temporarily to retrieve later
				Options::update( 'cc_temp_access_token', $access_token );
				Options::update( Settings::REGION, $region );
				
				$this->handle_api_response( $response, 'server' );
				break;
			
			case 'install':
				// Step "install" checks deployment status
				if ( empty( $app_id ) ) {
					wp_send_json_error( [ 'message' => __( 'App ID is missing.', 'correct-contact' ) ] );
				}
				
				$response = wp_remote_get( "https://api.digitalocean.com/v2/apps/$app_id/deployments", [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
					],
				] );
				
				if ( is_wp_error( $response ) ) {
					wp_send_json_error( [ 'message' => $response->get_error_message() ] );
				}
				
				$body       = json_decode( wp_remote_retrieve_body( $response ), true );
				$deployment = $body['deployments'][0] ?? null;
				
				if ( $deployment && $deployment['phase'] === 'ACTIVE' ) {
					wp_send_json_success( [ 'step' => 'install' ] );
				} elseif ( $deployment && in_array( $deployment['phase'], [ 'ERROR', 'CANCELED' ] ) ) {
					wp_send_json_error( [ 'message' => __( 'Deployment failed.', 'correct-contact' ) ] );
				} else {
					// Still deploying, keep the UI waiting
					sleep( 2 );
					wp_send_json_success( [ 'step' => 'install', 'retry' => true ] );
				}
				break;
			
			case 'secure':
				// Step "secure" retrieves the URL and attaches to project
				if ( empty( $app_id ) || empty( $project_id ) ) {
					wp_send_json_error( [ 'message' => __( 'App ID or Project ID is missing.', 'correct-contact' ) ] );
				}
				
				// Attach to project
				wp_remote_post( "https://api.digitalocean.com/v2/projects/$project_id/resources", [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
						'Content-Type'  => 'application/json',
					],
					'body'    => json_encode( [
						'resources' => [ "do:app:$app_id" ],
					] ),
				] );
				
				// Get App URL
				$response = wp_remote_get( "https://api.digitalocean.com/v2/apps/$app_id", [
					'headers' => [
						'Authorization' => 'Bearer ' . $token,
					],
				] );
				
				if ( is_wp_error( $response ) ) {
					wp_send_json_error( [ 'message' => $response->get_error_message() ] );
				}
				
				$body    = json_decode( wp_remote_retrieve_body( $response ), true );
				$app_url = $body['app']['live_url'] ?? '';
				
				if ( empty( $app_url ) ) {
					wp_send_json_error( [ 'message' => __( 'Could not retrieve app URL.', 'correct-contact' ) ] );
				}
				
				$access_token = Options::get( 'cc_temp_access_token' );
				
				if ( ! empty( $access_token ) ) {
					delete_option( 'cc_temp_access_token' );
				} else {
					// Fallback: If it's an existing app, we can't retrieve the generated token.
					// We'll have to rely on the one already stored in the database if available.
					$access_token = Options::get( Settings::ACCESS_TOKEN );
				}
				
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
	 * Handle DigitalOcean API response.
	 *
	 * @param mixed $response
	 * @param string $step
	 */
	private function handle_api_response( $response, $step ) {
		if ( is_wp_error( $response ) ) {
			wp_send_json_error( [ 'message' => $response->get_error_message() ] );
		}
		
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		
		if ( $code === 402 || ( isset( $body['id'] ) && $body['id'] === 'payment_required' ) ) {
			wp_send_json_error( [
				'message' => $body['message'] ?? __( 'Payment required.', 'correct-contact' ),
				'code'    => 'payment_method_required',
			] );
		}
		
		if ( ! in_array( $code, [ 200, 201, 202 ] ) ) {
			wp_send_json_error( [ 'message' => $body['message'] ?? __( 'API error.', 'correct-contact' ) ] );
		}
		
		if ( $step === 'project' ) {
			wp_send_json_success( [ 'step' => $step, 'project_id' => $body['project']['id'] ] );
			
			return;
		}
		
		if ( $step === 'server' ) {
			wp_send_json_success( [ 'step' => $step, 'app_id' => $body['app']['id'] ] );
			
			return;
		}
		
		wp_send_json_success( [ 'step' => $step ] );
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
