This is a software that is used on servers that are hosting huggle whitelist

Requires PHP 8.2 or newer with the PostgreSQL (`pgsql`) extension.
Set the database connection in `src/config.php`, or override individual settings
in an optional `src/config_prod.php`. The database name is `$global_db`.

Run PHP syntax checks with `bash .travis.phplint.sh`.
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
