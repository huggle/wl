<?

require ("config_prod.php");
class psql
{
    public static $db;

    public static function Connect()
    {
        global $global_name, $global_pw;
        self::$db = pg_connect("host=localhost dbname=wl user=". $global_name ." password=". $global_pw)
                   or die('Could not connect: ' . pg_last_error());
    } 

    public static function exec($q)
    {
        $result = pg_query($q);
        if (!$result)
            die ('Query failed: ' . pg_last_error());
        return $result;
        
    }

    public static function Disconnect()
    {
        pg_close($db);
    }
}
