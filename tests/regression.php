<?php
// Run against an expendable PostgreSQL database, never a production database.
// WL_TEST_HOST=/path/to/socket WL_TEST_DB=wl_test php tests/regression.php
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );
require_once __DIR__ . '/../src/whitelist.php';

function check( bool $ok, string $message ): void
{
    if ( !$ok )
        throw new RuntimeException( $message );
}

function capture( callable $call ): string
{
    ob_start();
    try { $call(); return ob_get_contents(); }
    finally { ob_end_clean(); }
}

$_GET = $_POST = $_SERVER = array();
$output = capture( static function () { (new Whitelist)->init(); } );
check( str_contains( $output, '<!-- failed s1 -->' ), 'Missing action must show the help page without warnings' );
$_GET = array( 'action' => 'read' );
try {
    (new Whitelist)->init();
    throw new RuntimeException( 'Missing wiki accepted' );
} catch ( InvalidArgumentException $expected ) {}
$_GET = array( 'wp' => array( 'bad' ) );
try {
    request_value( 'wp' );
    throw new RuntimeException( 'Array parameter accepted' );
} catch ( InvalidArgumentException $expected ) {}
$_GET = array( 'score' => '0); DROP TABLE se;--' );
try {
    request_integer( 'score' );
    throw new RuntimeException( 'SQL expression accepted as a number' );
} catch ( InvalidArgumentException $expected ) {}
$_GET = array( 'wp' => 'get' );
$_POST = array( 'wp' => 'post' );
check( request_value( 'wp' ) === 'post', 'POST precedence changed' );
check( client_ip() === '', 'Missing IP must be safe' );

$global_sv = getenv( 'WL_TEST_HOST' );
$global_db = getenv( 'WL_TEST_DB' );
$global_name = getenv( 'WL_TEST_USER' ) ?: get_current_user();
$global_pw = '';
check( is_string( $global_sv ) && $global_sv !== '' && is_string( $global_db ) && $global_db !== '',
    'Set WL_TEST_HOST and WL_TEST_DB to an expendable database' );
psql::Connect();
// Fail rather than touch an existing application database.
check( pg_num_rows( psql::exec( "SELECT tablename FROM pg_tables WHERE schemaname='public';" ) ) === 0,
    'Test database must be empty' );
foreach ( array( 'install.sql', 'install_edit.sql' ) as $file )
{
    // The installation scripts contain multiple statements, unlike application queries.
    check( pg_query( psql::$db, file_get_contents( __DIR__ . '/../' . $file ) ) !== false, 'Schema setup failed' );
}
// Match the deployed schema expected by the existing application.
psql::exec( 'ALTER TABLE list RENAME TO whitelist;' );
psql::exec( 'ALTER TABLE se ADD review boolean DEFAULT false;' );
psql::exec( 'ALTER TABLE se ADD review_user text;' );
psql::Disconnect();

$payload = "O'Reilly <script>alert(1)</script> & \"quoted\"; DROP TABLE wiki;--";
$_SERVER = array( 'REMOTE_ADDR' => '127.0.0.1' );
$_POST = array( 'action' => 'save', 'wp' => 'en', 'user' => $payload, 'wl' => $payload . '||EOW||' );
$_GET = array();
$workdir = sys_get_temp_dir() . '/wl-regression-' . bin2hex( random_bytes( 8 ) );
mkdir( $workdir );
$previous = getcwd();
chdir( $workdir );
try {
    $output = capture( static function () { (new Whitelist)->init(); } );
    check( $output === 'written', 'Save protocol changed' );
} finally {
    chdir( $previous );
    if ( is_file( $workdir . '/logs.txt' ) ) unlink( $workdir . '/logs.txt' );
    rmdir( $workdir );
}
psql::Connect();
try {
    Whitelist::get_wiki( "en.wikipedia.org' OR 1=1;--" );
    throw new RuntimeException( 'Wiki lookup accepted SQL injection' );
} catch ( InvalidArgumentException $expected ) {}
$row = pg_fetch_row( psql::exec( 'SELECT name, revs.user FROM whitelist JOIN revs ON rev_id=revs.id;' ) );
check( $row === array( $payload, $payload ), 'SQL payload was not stored literally' );
psql::Disconnect();
$_POST = array( 'action' => 'display', 'wp' => 'en' );
$output = capture( static function () { (new Whitelist)->init(); } );
check( !str_contains( $output, '<script>' ) && str_contains( $output, '&lt;script&gt;' ), 'HTML output is unsafe' );
$_POST['action'] = 'read';
$output = capture( static function () { (new Whitelist)->init(); } );
check( $output === $payload . '|<!-- list -->', 'Raw whitelist protocol changed' );

$_POST = $_GET = array();
capture( static function () { require __DIR__ . '/../src/susp.php'; } );
check( http_response_code() === 400, 'Suspicious edits must reject a missing action' );
psql::Connect();
$_POST = array( 'user' => $payload, 'page' => $payload, 'summary' => $payload . "\x01", 'wiki' => 'en.wikipedia.org', 'score' => '-12', 'revid' => '123' );
check( capture( 'insert' ) === 'done', 'Suspicious edit insertion failed' );
$_POST = array( 'wp' => 'en.wikipedia.org' );
$output = capture( 'last' );
$xml = simplexml_load_string( $output );
check( $xml !== false && (string) $xml->item['page'] === $payload && (string) $xml->item['user'] === $payload,
    'XML attributes must round trip safely and use the user field' );
check( (string) $xml->item === 'Summary: ' . $payload . "\xEF\xBF\xBD", 'XML must replace disallowed control characters' );
$_POST = array( 'id' => '1' );
check( capture( 'remove' ) === 'Removed', 'Suspicious edit review failed' );
check( pg_fetch_result( psql::exec( 'SELECT review FROM se WHERE id=1;' ), 0, 0 ) === 't', 'Review not recorded' );
psql::Disconnect();
echo 'Regression checks passed on PHP ' . PHP_VERSION . "\n";
