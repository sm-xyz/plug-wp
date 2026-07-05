<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Lightweight TOTP & Base32 implementation.
 * Replay protection via transient (90 detik window = 3 TOTP period).
 */
class Illu_Shield_TOTP {

    private static string $base32_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generate_secret( int $length = 16 ): string {
        $secret = '';
        try {
            $bytes = random_bytes( $length );
        } catch ( Exception $e ) {
            $bytes = openssl_random_pseudo_bytes( $length );
        }
        for ( $i = 0; $i < $length; $i++ ) {
            $secret .= self::$base32_chars[ ord( $bytes[ $i ] ) % 32 ];
        }
        return $secret;
    }

    public static function generate_recovery_codes( int $count = 8 ): array {
        $codes = [];
        for ( $i = 0; $i < $count; $i++ ) {
            try {
                $bytes = random_bytes( 4 );
            } catch ( Exception $e ) {
                $bytes = openssl_random_pseudo_bytes( 4 );
            }
            $codes[] = strtoupper( implode( '-', str_split( bin2hex( $bytes ), 4 ) ) );
        }
        return $codes;
    }

    public static function get_totp( string $secret ): string {
        $key        = self::base32_decode( $secret );
        $time       = floor( time() / 30 );
        $time_bytes = pack( 'N', 0 ) . pack( 'N', $time );
        $hash       = hash_hmac( 'sha1', $time_bytes, $key, true );
        $offset     = ord( substr( $hash, -1 ) ) & 0x0F;
        $value      = unpack( 'N', substr( $hash, $offset, 4 ) )[1] & 0x7FFFFFFF;
        return str_pad( $value % 1000000, 6, '0', STR_PAD_LEFT );
    }

    public static function verify_totp( string $secret, string $code, int $discrepancy = 1 ): bool {
        $current_time = floor( time() / 30 );
        $key          = self::base32_decode( $secret );

        for ( $i = -$discrepancy; $i <= $discrepancy; $i++ ) {
            $time       = $current_time + $i;
            $time_bytes = pack( 'N', 0 ) . pack( 'N', $time );
            $hash       = hash_hmac( 'sha1', $time_bytes, $key, true );
            $offset     = ord( substr( $hash, -1 ) ) & 0x0F;
            $value      = unpack( 'N', substr( $hash, $offset, 4 ) )[1] & 0x7FFFFFFF;
            $calculated = str_pad( $value % 1000000, 6, '0', STR_PAD_LEFT );

            if ( hash_equals( $calculated, str_pad( $code, 6, '0', STR_PAD_LEFT ) ) ) {
                // Replay protection
                $used_key = 'illu_2fa_used_' . md5( $secret . $code );
                if ( get_transient( $used_key ) ) return false;
                set_transient( $used_key, true, 90 );
                return true;
            }
        }
        return false;
    }

    private static function base32_decode( string $secret ): string {
        if ( empty( $secret ) ) return '';
        $secret      = strtoupper( $secret );
        $key         = '';
        $buffer      = 0;
        $buffer_size = 0;

        for ( $i = 0; $i < strlen( $secret ); $i++ ) {
            $val = strpos( self::$base32_chars, $secret[ $i ] );
            if ( $val === false ) continue;
            $buffer      = ( $buffer << 5 ) | $val;
            $buffer_size += 5;
            if ( $buffer_size >= 8 ) {
                $buffer_size -= 8;
                $key         .= chr( ( $buffer >> $buffer_size ) & 0xFF );
            }
        }
        return $key;
    }
}
