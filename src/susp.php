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

error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
ini_set('display_errors','1');

require("whitelist.php");

$starttime = microtime( true );
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');


function last()
{
    // display a list of last edits we have in db
    $wiki = Whitelist::get_wiki($_GET['wp']);
    $result = psql::exec("SELECT * FROM se WHERE wiki = ".$wiki." ORDER BY date DESC;");
    header('Content-type: application/xml');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<results>\n";
    while ($row = pg_fetch_row($result)) {
        echo "    <item page=\"".$row[2]."\" revid=\"".$row[3]."\" score=\"".$row[4]."\" user=\"".$row[5]."\"></item>\n";
    }
    echo "</results>\n";
}

function insert()
{
   $user = pg_escape_string($_GET['user']);
   $page = pg_escape_string($_GET['page']);
   $score = pg_escape_string($_GET['score']);
   $wiki = Whitelist::get_wiki($_GET['wiki']);
   $summary = pg_escape_string($_GET['summary']);
   $revid = pg_escape_string($_GET['revid']);
   psql::exec("INSERT INTO se (revid, score, wiki, date, summary, page, \"user\", ip) VALUES (".$revid.", ".$score.", ".$wiki.", 'now', '".$summary."', '".$page."', '".$user."', '".pg_escape_string($_SERVER['HTTP_X_FORWARDED_FOR'])."');");
   echo "done";
}

function remove()
{

}

if (!isset($_GET['action']))
  throw new Exception("No action");
psql::Connect();
switch ($_GET['action'])
{
    case "last":
      last();
      break;
    case "remove":
      remove();
      break;
    case "insert":
      insert();
      break;
      
}
psql::Disconnect();




