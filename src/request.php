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

function client_ip(): string
{
    global $global_proxy;
    $ip = $global_proxy ? ( $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '' ) : ( $_SERVER['REMOTE_ADDR'] ?? '' );
    return is_string( $ip ) ? $ip : '';
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
