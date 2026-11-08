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

error_reporting( E_ALL );
ini_set( 'display_errors', '0' );

require_once __DIR__ . '/whitelist.php';

header( 'Access-Control-Allow-Origin: *' );
header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
header( 'Content-Type: text/plain; charset=UTF-8' );

function last()
{
    $wiki = Whitelist::get_wiki( request_value( 'wp' ) );
    $result = psql::exec( 'SELECT page, revid, score, "user", summary FROM se WHERE wiki=$1 ORDER BY date DESC;', array( $wiki ) );
    header( 'Content-Type: application/xml; charset=UTF-8' );
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo "<results>\n";
    while ( $row = pg_fetch_row( $result ) )
    {
        echo '    <item page="' . xml_escape( $row[0] ) . '" revid="' . xml_escape( $row[1] ) .
            '" score="' . xml_escape( $row[2] ) . '" user="' . xml_escape( $row[3] ) .
            '">Summary: ' . xml_escape( $row[4] ) . "</item>\n";
    }
    echo "</results>\n";
}

function insert()
{
    $user = request_value( 'user' );
    $page = request_value( 'page' );
    $score = request_integer( 'score' );
    $wiki = Whitelist::get_wiki( request_value( 'wiki' ) );
    $summary = request_value( 'summary' );
    $revid = request_integer( 'revid' );
    psql::exec( 'INSERT INTO se (revid, score, wiki, date, summary, page, "user", ip) VALUES ($1, $2, $3, CURRENT_TIMESTAMP, $4, $5, $6, $7);',
        array( $revid, $score, $wiki, $summary, $page, $user, client_ip() ) );
    echo 'done';
}

function remove()
{
    $id = request_integer( 'id' );
    psql::exec( 'UPDATE se SET review=true, review_user=$1 WHERE id=$2;', array( client_ip(), $id ) );
    echo 'Removed';
}

try
{
    $action = request_value( 'action' );
    if ( !in_array( $action, array( 'last', 'remove', 'insert' ), true ) )
        throw new InvalidArgumentException( 'Missing or invalid action' );
    psql::Connect();
    try
    {
        switch ( $action )
        {
            case 'last':
                last();
                break;
            case 'remove':
                remove();
                break;
            case 'insert':
                insert();
                break;
        }
    } finally
    {
        psql::Disconnect();
    }
} catch ( Throwable $error )
{
    respond_error( $error );
}
