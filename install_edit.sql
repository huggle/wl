CREATE TABLE se
(
  "id"            bigserial PRIMARY KEY,
  "revid"         integer NOT NULL,
  "score"         integer NOT NULL,
  "wiki"          integer NOT NULL REFERENCES wiki(id),
  "date"          timestamp NOT NULL,
  "summary"       text,
  "page"          varchar(200) NOT NULL,
  "user"          text NOT NULL,
  "ip"            varchar(46),
  UNIQUE (revid, wiki)
);

CREATE INDEX se_wiki_idx ON se(wiki);
CREATE INDEX se_page_idx ON se(page);
CREATE INDEX se_revid_idx ON se(revid);

CREATE VIEW suspicious_edits AS
  SELECT id, revid, score, wiki, date, summary, page FROM se ORDER BY revid DESC;
