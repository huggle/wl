<?php
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
require_once __DIR__ . '/../src/config.php';
require_once __DIR__ . '/../src/request.php';

function expect_ip( string $expected, string $description ): void
{
    $actual = client_ip();
    if ( $actual !== $expected )
        throw new RuntimeException( "$description: expected '$expected', got '$actual'" );
    if ( strlen( $actual ) > 46 )
        throw new RuntimeException( 'Client IP exceeds the database field length' );
}

$_SERVER = array( 'REMOTE_ADDR' => '192.0.2.10', 'HTTP_X_FORWARDED_FOR' => '198.51.100.1' );
expect_ip( '192.0.2.10', 'Default configuration ignores forwarding headers' );
$global_trusted_proxies = array( '192.0.2.10' );
expect_ip( '192.0.2.10', 'Disabled proxy flag overrides trusted list' );
$global_proxy = true;
$global_trusted_proxies = array();
expect_ip( '192.0.2.10', 'Enabled flag alone grants no trust' );
$global_trusted_proxies = array( '127.0.0.1', '::1' );
expect_ip( '192.0.2.10', 'Untrusted peer cannot spoof client IP' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
expect_ip( '198.51.100.1', 'Trusted peer can forward client IP' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.99, 198.51.100.1';
expect_ip( '198.51.100.1', 'Ignore spoofed leftmost address' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 192.0.2.17, ::1';
$global_trusted_proxies[] = '192.0.2.0/24';
expect_ip( '198.51.100.1', 'Multiple trusted IPv4/IPv6 hops' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 192.0.3.17';
expect_ip( '192.0.3.17', 'CIDR must exclude adjacent networks' );

$global_trusted_proxies = array( '2001:db8:1234::/48' );
$_SERVER = array( 'REMOTE_ADDR' => '2001:db8:1234::1', 'HTTP_X_FORWARDED_FOR' => '2001:db8:ffff::1234, 2001:db8:1234::2' );
expect_ip( '2001:db8:ffff::1234', 'IPv6 CIDR chain' );
$global_trusted_proxies = array( '2001:db8:1234::/49' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1, 2001:db8:1234:8000::1';
expect_ip( '2001:db8:1234:8000::1', 'Partial-byte CIDR boundary' );

$global_trusted_proxies = array( '127.0.0.1' );
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
foreach ( array( '', 'unknown', '198.51.100.1,', '198.51.100.1:123', '[::1]', "198.51.100.1\r\n", array( '198.51.100.1' ) ) as $header )
{
    $_SERVER['HTTP_X_FORWARDED_FOR'] = $header;
    // Whitespace surrounding a valid address is accepted.
    expect_ip( is_string( $header ) && trim( $header ) === '198.51.100.1' ? '198.51.100.1' : '127.0.0.1', 'Malformed header fallback' );
}
unset( $_SERVER['HTTP_X_FORWARDED_FOR'] );
expect_ip( '127.0.0.1', 'Missing forwarded header' );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '127.0.0.1';
expect_ip( '127.0.0.1', 'All hops trusted' );
$global_trusted_proxies = array( 'garbage', '127.0.0.1/33', '127.0.0.1/-1', '127.0.0.1/abc', null );
$_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.1';
expect_ip( '127.0.0.1', 'Invalid configuration grants no trust' );
$global_trusted_proxies = '127.0.0.1';
expect_ip( '127.0.0.1', 'Malformed proxy list grants no trust' );
foreach ( array( '', 'invalid', array( '127.0.0.1' ) ) as $remote )
{
    $_SERVER['REMOTE_ADDR'] = $remote;
    expect_ip( '', 'Invalid peer address' );
}
$_SERVER = array();
expect_ip( '', 'Missing peer address' );
echo 'Proxy checks passed on PHP ' . PHP_VERSION . "\n";
