CREATE TABLE IF NOT EXISTS settings
(
  id serial NOT NULL,
  id_parent int4 DEFAULT NULL,
  caption text,
  value text,
  CONSTRAINT settings_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS site_contents
(
  id serial NOT NULL,
  id_parent integer,
  name text,
  cdate timestamp DEFAULT NULL,
  udate timestamp DEFAULT NULL,
  CONSTRAINT site_contents_pkey PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS site_contents2settings
(
  id serial NOT NULL,
  id_site_content integer,
  id_setting integer,
  CONSTRAINT site_contents2settings_pkey PRIMARY KEY (id)
);