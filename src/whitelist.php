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

require_once __DIR__ . '/pgsql.php';
require_once __DIR__ . '/request.php';

class Whitelist
{
    //! Here we hold the content of whitelist
    //private var $data = array();
    private function usagelog($log_text)
    {
        $file_log = fopen( "logs.txt", 'a' );
        if ( $file_log === false )
            return;
        fwrite ( $file_log, $log_text );
        fclose ( $file_log );
    }

    private function isvalid_action ($id)
    {
        if ($id == "" or $id == null)
            return false;
        switch ( $id )
        {
            case "edit":
            case "save":
            case "display":
            case "read":
                return true;
        }
        return false;
    }

    //! This function exist for compatibility reasons
    private function name($wikiname)
    {
        //check if the wiki is a valid wiki
        //yes it is dumb but it can be improved any time ;)
        switch ($wikiname)
        {
            case "ar.wikipedia":
            case "ar":
                return "ar.wikipedia.org";
            case "en":
            case "en.wikipedia":
                return "en.wikipedia.org";
            case "fr":
            case "fr.wikipedia":
                return "fr.wikipedia.org";
            case "de":
            case "de.wikipedia":
                return "de.wikipedia.org";
            case "es.wikivoyage":
                return "es.wikivoyage.org";
            case "ca":
            case "ca.wikipedia":
                return "ca.wikipedia.org";
            case "pt":
            case "pt.wikipedia":
                return "pt.wikipedia.org";
            case "es":
            case "es.wikipedia":
                return "es.wikipedia.org";
            case "ko":
            case "ko.wikipedia":
                return "ko.wikipedia.org";
            case "nl":
            case "nl.wikipedia":
                return "nl.wikipedia.org";
            case "no":
            case "no.wikipedia":
                return "no.wikipedia.org";
            case "hi":
            case "hi.wikipedia":
                return "hi.wikipedia.org";
            case "ru":
            case "ru.wikipedia":
                return "ru.wikipedia.org";
            case "sv":
            case "sv.wikipedia":
                return "sv.wikipedia.org";
            case "vi":
            case "vi.wikipedia":
                return "vi.wikipedia.org";
            case "ja":
            case "ja.wikipedia":
                return "ja.wikipedia.org";
            case "te":
            case "te.wikipedia":
                return "te.wikipedia.org";
            case "bg":
            case "bg.wikipedia":
                return "bg.wikipedia.org";
            case "vl":
            case "vl.wikipedia":
                return "vl";
            case "km":
            case "km.wikipedia":
                return "km.wikipedia.org";
            case "zh":
            case "zh.wikipedia":
                return "zh.wikipedia.org";
            case "simple.wikipedia":
            case "simple":
                return "simple.wikipedia.org";
            case "or":
            case "or.wikipedia":
                return "or.wikipedia.org";
            case "test wiki":
            case "test.wikipedia":
                return "test.wikipedia.org";
            case "fa.wikipedia":
            case "fa":
                return "fa.wikipedia.org";
            case "wikidata":
                return "wikidata.org";
        }
        return $wikiname;
    }

    public static function get_wiki( $wp )
    {
        $result = psql::exec( 'SELECT id FROM wiki WHERE name = $1;', array( $wp ) );
        if ( pg_num_rows( $result ) != 1 )
        {
            throw new InvalidArgumentException( 'There is no such a wiki' );
        }
        $item = pg_fetch_row( $result );
        return $item[0];
    }

    private function save( $data, $wp )
    {
        //user wants to edit it
        if ( $data == "" or $data == null or strpos( $data, '||EOW||' ) === false )
        {
            echo "Error no data!<!-- failed s4 -->";
            return;
        } else
        {
            $un = request_value( 'user', 'unknown' );
            $data = str_replace( "||EOW||", "", $data );
            $wl = explode( "|", $data );
            $ip = client_ip();
            psql::exec( "BEGIN;" );
            // select a wiki id
            $wiki = self::get_wiki( $wp );
            // we need to insert a new revision here
            $result = psql::exec( 'INSERT INTO revs (date, wiki, "user", ip) VALUES (CURRENT_TIMESTAMP, $1, $2, $3) RETURNING id;', array( $wiki, $un, $ip ) );
            $r = pg_fetch_row( $result );
            $revision = $r[0];
            foreach ( $wl as $user )
            {
               if ( $user == "" )
               {
                   continue;
               }
               // check if user is already in table
               $result = psql::exec( 'SELECT name FROM whitelist WHERE wiki=$1 AND is_deleted=false AND name=$2;', array( $wiki, $user ) );
               if ( pg_num_rows( $result ) == 0 )
               {
                   psql::exec( 'INSERT INTO whitelist (name, wiki, rev_id) VALUES ($1, $2, $3);', array( $user, $wiki, $revision ) );
               }
            }
            psql::exec( "COMMIT;" );
            echo "written";
            $this->usagelog ( "$wp was updated on " . date ( "F j, Y, g:i a" ) .  " size: " . strlen($data) . "\n" );
        }
    }

    private function read( $wp )
    {
        header( 'Content-Type: text/plain; charset=UTF-8' );
        $wiki = self::get_wiki( $wp );
        $list = psql::exec( 'SELECT name FROM whitelist WHERE wiki=$1 AND is_deleted=false;', array( $wiki ) );
        while ( $line = pg_fetch_row( $list ) )
        {
           echo $line[0] . "|";
        }
        echo "<!-- list -->";
    }

    private function display( $wp )
    {
        include __DIR__ . '/header';
        echo "List of all users in the whitelist for wiki:";
        $wiki = self::get_wiki( $wp );
        $list = psql::exec( 'SELECT name, revs.user, revs.date FROM whitelist,revs WHERE whitelist.rev_id = revs.id AND whitelist.wiki=$1 AND is_deleted=false ORDER BY name ASC;', array( $wiki ) );
        echo "<br>Total: " .pg_num_rows( $list );
        echo "\n<table border=\"1\">\n";
        echo "<tr><th>Name</th><th>Inserted by</th><th>Time of insertion</th></tr>\n";
        while ( $line = pg_fetch_row( $list ) )
        {
           echo "  <tr><td>" . html_escape( $line[0] ) . "</td><td>" . html_escape( $line[1] ) . "</td><td>" . html_escape( $line[2] ) . "</td></tr>\n";
        }
        echo "</table>";
        include __DIR__ . '/footer';
    }

    public function init()
    {
        $wp = request_value( 'wp' );
        $action = request_value( 'action' );
        $data = request_value( 'wl' );
        if ( !$this->isvalid_action( $action ) )
        {
            include __DIR__ . '/header';
            echo "Error no action was given, this file is used internally by huggle<!-- failed s1 -->";
            include __DIR__ . '/readme';
            include __DIR__ . '/footer';
            return;
        }
        if ( $wp === '' )
            throw new InvalidArgumentException( 'Missing parameter: wp' );
        $wp = $this->name( $wp );
        psql::Connect();
        try
        {
            switch ( $action )
            {
                case "display":
                    $this->display( $wp );
                    break;
                case "save":
                    $this->save( $data, $wp );
                    break;
                case "read":
                    $this->read( $wp );
                    break;
            }
        } finally
        {
            psql::Disconnect();
        }
    }

    public function load( $wiki )
    {

    }
}
