CREATE TABLE ps_block (
  id int(11) NOT NULL default 0,
  pid int(11) NOT NULL default 0,
  btype varchar(10) NOT NULL,
  type_id int(11) NOT NULL default 0,
  tag varchar(20) NOT NULL,
  PRIMARY KEY  (id)
);

CREATE INDEX psblock_idx on ps_block(pid);

CREATE TABLE ps_page (
  id int(11) NOT NULL default 0,
  key_id int(11) NOT NULL default 0,
  title varchar(255) NOT NULL,
  template varchar(20) NOT NULL,
  PRIMARY KEY  (id)
);

CREATE INDEX pspage_idx on ps_page(key_id);


CREATE TABLE ps_text (
  id int(11) NOT NULL default 0,
  pid int(11) NOT NULL default 0,
  content text NOT NULL,
  tag varchar(30) NOT NULL,
  PRIMARY KEY  (id)
);

CREATE INDEX pstext_idx on ps_text(pid);
