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

use WpOrg\Requests\Exception\InvalidArgument;

defined( 'ABSPATH' ) || exit;

class Ajax {
    /**
     * Ajax constructor.
     *
     * @return void
     */
    public function __construct() {
	    $this->init();
    }

    /**
     * Initializes the class.
     *
     * @return void
     */
    private function init() {
        add_action( 'wp_ajax_edd_truemail_verify_email', [ $this, 'verify' ] );
    }

    /**
     * Verifies an email address.
     *
     * @return void
     *
     * @throws InvalidArgument
     */
    public function verify() {
        $email    = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : false;
        $client   = new Client();
        $result   = $client->verify( $email );
        $response = [
            'status'  => $result['code'],
            'success' => false,
        ];

        if ( ! $result['success'] && $result['code'] === 200 ) {
            $response['message'] = __( 'We couldn\'t verify your email address. Are you sure it\'s spelled correctly?', 'edd-truemail' );
        }

        if ( ! $result['success'] && $result['code'] === 400 ) {
            $response['message'] = __( 'Please enter a valid email address.', 'edd-truemail' );
        }

        if ( ! $result['success'] && $result['code'] === 408 ) {
            $response['message'] = __( 'Request timed out.', 'edd-truemail' );
        }

        if ( ! $result['success'] ) {
            wp_send_json_error( $response );
        }

        $response['message'] = __( 'Email address verified.', 'edd-truemail' );
        $response['success'] = true;

        wp_send_json_success( $response );
    }
}
