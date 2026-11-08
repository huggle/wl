<?php
/**
* Copyright (c) 2014, Huggle Corporation
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* * Redistributions of source code must retain the above copyright notice, this
*   list of conditions and the following disclaimer.
*
* * Redistributions in binary form must reproduce the above copyright notice,
*   this list of conditions and the following disclaimer in the documentation
*   and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
* AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
* IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
* FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
* DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
* SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
* OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
* OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once __DIR__ . '/config.php';
if ( is_file( __DIR__ . '/config_prod.php' ) )
    require_once __DIR__ . '/config_prod.php';
class psql
{
    public static $db;

    public static function Connect()
    {
        global $global_name, $global_pw, $global_sv, $global_db;
        $quote = static function ( $value ) {
            return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), $value ) . "'";
        };
        self::$db = pg_connect( 'host=' . $quote( $global_sv ) . ' dbname=' . $quote( $global_db ) .
            ' user=' . $quote( $global_name ) . ' password=' . $quote( $global_pw ) );
        if ( self::$db === false )
            throw new RuntimeException( 'Could not connect to PostgreSQL' );
    }

    public static function exec( $q, array $params = array() )
    {
        $result = pg_query_params( self::$db, $q, $params );
        if( !$result )
            throw new RuntimeException( 'Query failed: ' . pg_last_error( self::$db ) );
        return $result;

    }

    public static function Disconnect()
    {
        pg_close( self::$db );
        self::$db = null;
    }
}
