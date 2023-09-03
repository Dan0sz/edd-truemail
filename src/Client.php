<?php
/**
 * Truemail for Easy Digital Downloads
 *
 * @package   daandev/edd-truemail
 * @author    Daan van den Bergh
 *            https://daan.dev
 * @copyright © 2023 Daan van den Bergh
 */
namespace EDD\Truemail;

use EDD\Truemail\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Client {
    /**
     * @var string Access Token
     */
    private $token = '';

    /**
     * @var string API URL
     */
    private $api_url = '';

    /**
     * Client constructor.
     *
     * @param string $token
     */
    public function __construct() {
        $this->token   = edd_get_option( Settings::ACCESS_TOKEN );
        $this->api_url = edd_get_option( Settings::APP_URL );
    }

    /**
     * Verifies an email address.
     *
     * @param string $email
     * @return array [ 'success' => bool, 'code' => int ]
     */
    public function verify( $email ) {
        $email = sanitize_email( $email );

        if ( ! $email ) {
            return [
				'success' => false,
				'code'    => 200,
            ];
        }

        $url      = $this->api_url . '?' . http_build_query( [ 'email' => $email ] );
        $response = wp_remote_get(
            $url,
            [
                'timeout' => 15,
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                    'Authorization' => $this->token,
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return [
				'success' => false,
				'code'    => 408,
            ];
        }

        $body = json_decode( wp_remote_retrieve_body( $response ) );

        return [
			'success' => $body->success,
			'code'    => 200,
        ];
    }
}
