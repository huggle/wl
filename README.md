This is a software that is used on servers that are hosting huggle whitelist

Requires PHP 8.2 or newer with the PostgreSQL (`pgsql`) extension.
Set the database connection in `src/config.php`, or override individual settings
in an optional `src/config_prod.php`. The database name is `$global_db`.

Proxy support is disabled by default (`$global_proxy = false`), so client IPs
come from `REMOTE_ADDR` and forwarding headers are ignored. If a reverse proxy
is added later, enable it and list its actual addresses or CIDR ranges:

```php
$global_proxy = true;
$global_trusted_proxies = array( '127.0.0.1', '::1' );
```

Only connections from a trusted proxy can supply `X-Forwarded-For`. The chain
is read from right to left, skipping trusted proxies. Malformed headers fall
back to `REMOTE_ADDR`. Configure the proxy to overwrite or correctly append
this header, and trust only proxy addresses you control.

GitHub Actions runs syntax and PostgreSQL regression checks on PHP 8.2–8.5
for pushes and pull requests. The workflow can also be started manually.

Run PHP syntax checks with `bash scripts/lint-php.sh`.
Run the standalone proxy IP checks with `php tests/proxy.php`.
The regression checks also require SimpleXML and an empty, expendable PostgreSQL
database. They create tables and leave test data in that database:

```sh
createdb wl_test
WL_TEST_HOST=localhost WL_TEST_DB=wl_test WL_TEST_USER="$USER" php tests/regression.php
dropdb wl_test
```

The regression setup adapts the supplied installation scripts to the schema
currently used by the application: `list` is named `whitelist`, and `se` has
`review` and `review_user` columns. These schema differences still need to be
accounted for when installing a new deployment.
