<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Illu_Shield_Cloudflare {

    private string $email   = '';
    private string $api_key = '';
    private string $zone_id = '';
    private bool   $is_active = false;

    public function __construct() {
        $settings = get_option( 'illu_shield_settings', [] );

        $this->email   = $settings['cloudflare_email']   ?? '';
        $this->api_key = illu_decrypt_secret( $settings['cloudflare_api_key'] ?? '' );
        $this->zone_id = $settings['cloudflare_zone_id'] ?? '';

        if ( ! empty( $this->email ) && ! empty( $this->api_key ) && ! empty( $this->zone_id ) ) {
            $this->is_active = true;
            add_action( 'illu_shield_ip_blacklisted',  [ $this, 'sync_blacklisted_ip' ] );
            add_action( 'illu_shield_purge_cloudflare', [ $this, 'purge_cache' ] );
        }
    }

    public function purge_cache() {
        if ( ! $this->is_active ) return;

        wp_remote_request(
            "https://api.cloudflare.com/client/v4/zones/{$this->zone_id}/purge_cache",
            [
                'method'  => 'POST',
                'headers' => $this->headers(),
                'body'    => wp_json_encode( [ 'purge_everything' => true ] ),
                'timeout' => 15,
            ]
        );
    }

    public function sync_blacklisted_ip( string $ip ) {
        if ( ! $this->is_active || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) return;

        $response = wp_remote_request(
            "https://api.cloudflare.com/client/v4/zones/{$this->zone_id}/firewall/access_rules/rules",
            [
                'method'  => 'POST',
                'headers' => $this->headers(),
                'body'    => wp_json_encode( [
                    'mode'          => 'block',
                    'configuration' => [ 'target' => 'ip', 'value' => $ip ],
                    'notes'         => 'Blocked by Illu Shield WAF Sync',
                ] ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            Illu_Shield_DB::log( 'Cloudflare Sync Error', 'Gagal sync: ' . $response->get_error_message() );
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( in_array( $code, [ 200, 201 ], true ) ) {
            Illu_Shield_DB::log( 'Cloudflare Sync', "IP {$ip} berhasil diblokir di Cloudflare WAF." );
        } else {
            $err = $body['errors'][0]['message'] ?? 'Unknown Error';
            Illu_Shield_DB::log( 'Cloudflare Sync Failed', "Gagal block IP {$ip}: $err" );
        }
    }

    private function headers(): array {
        return [
            'X-Auth-Email'  => $this->email,
            'X-Auth-Key'    => $this->api_key,
            'Content-Type'  => 'application/json',
        ];
    }
}
