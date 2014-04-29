CREATE TABLE list
(
  list_id            bigserial PRIMARY KEY,
  name               text NOT NULL,
  wiki               varchar(10) NOT NULL,
  insertion_date     timestamp NOT NULL,
  modification_date  timestamp,
  is_deleted         boolean DEFAULT(false),
  deletion_time      timestamp,
  creator_name       text NOT NULL,
  creator_ip         varchar(46) NOT NULL,
  modifier_name      text,
  modifier_ip        varchar(46)
);

CREATE UNIQUE INDEX list_id_idx ON list(list_id);
CREATE INDEX list_wiki_idx ON list(wiki);
CREATE INDEX list_name_idx ON list(name);
CREATE INDEX list_del_idx ON list(is_deleted);
