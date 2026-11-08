<?php

// POST takes precedence, matching the historical whitelist API.
function request_value( string $name, string $default = '' ): string
{
    $value = $_POST[$name] ?? $_GET[$name] ?? $default;
    if ( !is_string( $value ) || str_contains( $value, "\0" ) )
        throw new InvalidArgumentException( 'Invalid parameter: ' . $name );
    return $value;
}

function request_integer( string $name ): string
{
    $value = request_value( $name );
    if ( !preg_match( '/^-?[0-9]+$/D', $value ) )
        throw new InvalidArgumentException( 'Invalid integer parameter: ' . $name );
    return $value;
}

function is_trusted_proxy( string $ip, array $trusted_proxies ): bool
{
    $address = inet_pton( $ip );
    foreach ( $trusted_proxies as $proxy )
    {
        if ( !is_string( $proxy ) )
            continue;
        $parts = explode( '/', $proxy, 2 );
        if ( filter_var( $parts[0], FILTER_VALIDATE_IP ) === false )
            continue;
        $network = inet_pton( $parts[0] );
        if ( $address === false || strlen( $address ) !== strlen( $network ) )
            continue;
        $bits = strlen( $network ) * 8;
        if ( isset( $parts[1] ) )
        {
            if ( !ctype_digit( $parts[1] ) || (int) $parts[1] > $bits )
                continue;
            $bits = (int) $parts[1];
        }
        $bytes = intdiv( $bits, 8 );
        $remaining = $bits % 8;
        if ( substr( $address, 0, $bytes ) === substr( $network, 0, $bytes ) &&
            ( $remaining === 0 ||
                ( ord( $address[$bytes] ) & ( 0xff << ( 8 - $remaining ) ) ) ===
                ( ord( $network[$bytes] ) & ( 0xff << ( 8 - $remaining ) ) ) ) )
            return true;
    }
    return false;
}

function client_ip(): string
{
    global $global_proxy, $global_trusted_proxies;
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ( !is_string( $remote ) || filter_var( $remote, FILTER_VALIDATE_IP ) === false )
        return '';
    $remote = inet_ntop( inet_pton( $remote ) );
    $trusted = is_array( $global_trusted_proxies ) ? $global_trusted_proxies : array();
    if ( !$global_proxy || !is_trusted_proxy( $remote, $trusted ) )
        return $remote;

    $header = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ( !is_string( $header ) || $header === '' )
        return $remote;
    $hops = array_map( 'trim', explode( ',', $header ) );
    foreach ( $hops as $hop )
    {
        if ( filter_var( $hop, FILTER_VALIDATE_IP ) === false )
            return $remote;
    }
    // Stop at the first untrusted hop; earlier entries may be client-supplied.
    foreach ( array_reverse( $hops ) as $hop )
    {
        if ( !is_trusted_proxy( $hop, $trusted ) )
            return inet_ntop( inet_pton( $hop ) );
    }
    return inet_ntop( inet_pton( $hops[0] ) );
}

function html_escape( $value ): string
{
    return htmlspecialchars( $value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function xml_escape( $value ): string
{
    return htmlspecialchars( $value ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_DISALLOWED | ENT_XML1, 'UTF-8' );
}

function respond_error( Throwable $error ): void
{
    header( 'Content-Type: text/plain; charset=UTF-8' );
    if ( $error instanceof InvalidArgumentException )
    {
        http_response_code( 400 );
        echo $error->getMessage();
    } else
    {
        error_log( (string) $error );
        http_response_code( 500 );
        echo 'Internal server error';
    }
}
