CREATE TABLE wiki
(
  "id" integer PRIMARY KEY UNIQUE,
  "name" varchar(20) UNIQUE
);

CREATE TABLE revs
(
  id         serial PRIMARY KEY,
  "date"     timestamp NOT NULL,
  "wiki"     integer NOT NULL REFERENCES wiki(id),
  "user"     text NOT NULL,
  ip         varchar(46)
);

CREATE TABLE list
(
  "list_id"            bigserial PRIMARY KEY,
  "name"               text NOT NULL,
  "wiki"               integer NOT NULL REFERENCES wiki(id),
  "rev_id"             integer NOT NULL REFERENCES revs(id),
  "modification_date"  timestamp,
  "is_deleted"         boolean DEFAULT(false),
  "deletion_time"      timestamp,
  UNIQUE (name, wiki)
);

CREATE INDEX list_wiki_idx ON list(wiki);
CREATE INDEX list_name_idx ON list(name);
CREATE INDEX list_del_idx ON list(is_deleted);

-- Insert some basic data here
BEGIN;
INSERT INTO wiki VALUES(0, 'en.wikipedia.org');
INSERT INTO wiki VALUES(1, 'de.wikipedia.org');
INSERT INTO wiki VALUES(2, 'pt.wikipedia.org');
INSERT INTO wiki VALUES(3, 'simple.wikipedia.org');
INSERT INTO wiki VALUES(4, 'bg.wikipedia.org');
INSERT INTO wiki VALUES(5, 'ca.wikipedia.org');
INSERT INTO wiki VALUES(6, 'es.wikipedia.org');
INSERT INTO wiki VALUES(7, 'fr.wikipedia.org');
INSERT INTO wiki VALUES(8, 'ar.wikipedia.org');
INSERT INTO wiki VALUES(9, 'hi.wikipedia.org');
INSERT INTO wiki VALUES(10, 'ja.wikipedia.org');
INSERT INTO wiki VALUES(11, 'nl.wikipedia.org');
INSERT INTO wiki VALUES(12, 'no.wikipedia.org');
INSERT INTO wiki VALUES(13, 'or.wikipedia.org');
INSERT INTO wiki VALUES(14, 'ru.wikipedia.org');
INSERT INTO wiki VALUES(15, 'sv.wikipedia.org');
INSERT INTO wiki VALUES(16, 'zh.wikipedia.org');
INSERT INTO wiki VALUES(17, 'es.wikivoyage.org');
INSERT INTO wiki VALUES(18, 'test.wikipedia.org');
COMMIT;

