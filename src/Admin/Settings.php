<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/edd-truemail
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */
namespace EDD\Truemail\Admin;

use EDD\Truemail\Client;

defined( 'ABSPATH' ) || exit;

class Settings {
	const ACCESS_TOKEN = 'edd_truemail_access_token';

    const APP_URL = 'edd_truemail_app_url';

    /**
     * Settings constructor.
     *
     * @return void
     */
	public function __construct() {
		$this->init();
	}

    /**
     * Initializes the Settings class.
     *
     * @return void
     */
	private function init() {
		add_filter( 'edd_settings_sections_extensions', [ $this, 'add_section' ], 10, 1 );
		add_filter( 'edd_settings_extensions', [ $this, 'add_settings' ] );
	}

	/**
	 * Adds the Truemail settings to the Extensions tab in Easy Digital Downloads > Settings.
	 *
	 * @param mixed $sections
	 * @return mixed
	 */
	public function add_section( $sections ) {
		$sections['edd-truemail'] = __( 'Truemail', 'edd-truemail' );

		return $sections;
	}

    /**
     * Adds settings to the Truemail tab.
     *
     * @param mixed $settings
     * @return mixed
     */
	public function add_settings( $settings ) {
		return array_merge(
			$settings,
            [
				'edd-truemail' => [
                    [
                        'id'   => self::ACCESS_TOKEN,
                        'name' => __( 'Access Token', 'edd-truemail' ),
                        'desc' => __( 'Enter the Access Token (environment variable) you\'ve configured in your Truemail instance here.', 'edd-truemail' ),
                        'type' => 'text',
                        'size' => 'large',
                    ],
                    [
                        'id' => self::APP_URL,
                        'name' => __( 'Application URL', 'edd-truemail' ),
                        'desc' => __( 'Enter the URL of your Truemail instance here.', 'edd-truemail' ),
                        'type' => 'text',
                        'size' => 'large',
                    ],
                ],
            ]
		);
	}
}
