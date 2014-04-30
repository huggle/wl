<?
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

require ("pgsql.php");

class Whitelist
{
    //! Here we hold the content of whitelist
    //private var $data = array();
    private function usagelog($log_text)
    {
        $file_log = fopen( "logs.txt", 'a' );
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
                return "vi,wikipedia.org";
            case "ja":
            case "ja.wikipedia":
                return "ja,wikipedia.org";
            case "sv":
            case "sv.wikipedia":
                return "sv.wikipedia.org";
            case "te":
            case "te.wikipedia":
                return "te.wikipedia.org";
            case "bg":
            case "bg.wikipedia":
                return "bg,wikipedia.org";
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

    private function save($data, $wp)
    {
        //user wants to edit it
        if ($data == "" or $data == null or strpos($data,'||EOW||') === false)
        {
            echo "Error no data!<!-- failed s4 -->";
            die (1);
        } else
        {
            $data = str_replace("||EOW||", "", $data);
            $wl = explode("|", $data);
            psql::exec("BEGIN;LOCK TABLE list IN SHARE MODE;");
            foreach ($wl as $user)
            {
               if ($user == "")
               {
                   continue;
               }
               // check if user is already in table
               $result = psql::exec("SELECT name FROM list WHERE wiki='".$wp."' AND is_deleted=false AND name='".$user."';");
               if (pg_num_rows($result) == 0)
               {
                   psql::exec("INSERT INTO list (name, wiki, insertion_date, creator_name, creator_ip) VALUES ('".pg_escape_string($user)."', '".pg_escape_string($wp)."', 'now', 'unknown', '".pg_escape_string($_SERVER['REMOTE_ADDR'])."');");
               }
            }
            echo "written";
            psql::exec("COMMIT;");
            $this->usagelog ("$wp was updated on " . date ( "F j, Y, g:i a" ) .  " size: " . strlen($data) . " by " . $_SERVER['REMOTE_ADDR'] . "\n");
        }
    }

    private function read($wp)
    {
        $list = psql::exec("SELECT name FROM list WHERE wiki='".$wp."' AND is_deleted=false;");
        while ($line = pg_fetch_row($list))
        {
           echo $line[0] . "|"; 
        }
        
    }

    private function display($wp)
    {
        include ("header");
        echo "List of all users in the whitelist for wiki:";
        $list = psql::exec("SELECT name FROM list WHERE wiki='".$wp."' AND is_deleted=false ORDER BY name ASC;");
        echo "<br>Total: " .pg_num_rows( $list );
        echo '<table border="1">';
        while ($line = pg_fetch_row($list))
        {
           echo "<tr><td>".$line[0]."</td></tr>"; 
        }
        echo "</table>";
        include ("footer");
    }

    public function init()
    {
        $wp=$_GET['wp'];
        if (isset($_POST['action']))
        {
            // attempt to get it over post
            $wp=$_POST['wp'];
            $action=$_POST['action'];
        }
        if (isset($_POST['wl']))
        {
            $data=$_POST['wl'];
        }else
        {
            $data=$_GET['wl'];
        }
        $action=$_GET['action'];
        if (!$this->isvalid_action ($action))
        {
            include ("header");
            echo "Error no action was given, this file is used internally by huggle<!-- failed s1 -->";
            include ("readme");
            include ("footer");
            die (1);
        }
        $wp = $this->name($wp);
        psql::Connect();
        switch ($action)
        {
            case "display":
                $this->display($wp);
                return;
            case "save":
                $this->save($data, $wp);
                return;
            case "read":
                $this->read($wp);
                return;
        }
        psql::Disconnect();
    }

    public function load($wiki)
    {

    }
}
