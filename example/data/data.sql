/* ©2024 Yuichiro Nakada */

CREATE TABLE users (
  id           INTEGER         PRIMARY KEY AUTOINCREMENT,
  name         TEXT,
  email        TEXT,
  password     TEXT,
  detail       TEXT,
	edit_date timestamp NOT NULL default (DATETIME(CURRENT_TIMESTAMP, 'LOCALTIME'))
);
INSERT INTO users (email, password, detail) VALUES ('admin', 'admin', 'Administrator');

CREATE TABLE history (
  id           INTEGER         PRIMARY KEY AUTOINCREMENT,
  item         TEXT,
  num          INTEGER,
  name         TEXT,
  memo         TEXT,
	edit_date timestamp NOT NULL default (DATETIME(CURRENT_TIMESTAMP, 'LOCALTIME'))
);
