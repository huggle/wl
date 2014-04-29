<?

require ("config.php");
class psql
{
    public static $db;

    public static function Connect()
    {
        global $global_user, $global_pw;
        self::$db = pg_connect("host=localhost dbname=wl user=". $global_user ." password=". $global_pw)
                   or die('Could not connect: ' . pg_last_error());
    } 
}
