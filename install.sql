CREATE TABLE wiki
(
  id varchar(20) PRIMARY KEY
);

CREATE TABLE list
(
  list_id            bigserial PRIMARY KEY,
  name               text NOT NULL UNIQUE,
  wiki               varchar(20) NOT NULL REFERENCES wiki(id),
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

-- Insert some basic data here
BEGIN;
INSERT INTO wiki VALUES('en.wikipedia.org');
INSERT INTO wiki VALUES('de.wikipedia.org');
INSERT INTO wiki VALUES('pt.wikipedia.org');
INSERT INTO wiki VALUES('simple.wikipedia.org');
INSERT INTO wiki VALUES('bg.wikipedia.org');
INSERT INTO wiki VALUES('ca.wikipedia.org');
INSERT INTO wiki VALUES('es.wikipedia.org');
INSERT INTO wiki VALUES('fr.wikipedia.org');
INSERT INTO wiki VALUES('ar.wikipedia.org');
INSERT INTO wiki VALUES('hi.wikipedia.org');
INSERT INTO wiki VALUES('ja.wikipedia.org');
INSERT INTO wiki VALUES('nl.wikipedia.org');
INSERT INTO wiki VALUES('no.wikipedia.org');
INSERT INTO wiki VALUES('or.wikipedia.org');
INSERT INTO wiki VALUES('ru.wikipedia.org');
INSERT INTO wiki VALUES('sv.wikipedia.org');
INSERT INTO wiki VALUES('zh.wikipedia.org');
INSERT INTO wiki VALUES('es.wikivoyage.org');
COMMIT;

