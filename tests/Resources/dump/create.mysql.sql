CREATE TABLE IF NOT EXISTS `settings`
(
  `id`        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) unsigned DEFAULT NULL,
  `caption`   varchar(128)     DEFAULT NULL,
  `value`     varchar(255)     DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `site_contents`
(
  `id`        int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_parent` int(11) unsigned DEFAULT NULL,
  `name`      varchar(255)     DEFAULT NULL,
  `cdate`     DATETIME         NULL,
  `mdate`     DATETIME         NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;

CREATE TABLE IF NOT EXISTS `site_contents2settings`
(
  `id`              int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_site_content` int(11) unsigned DEFAULT NULL,
  `id_setting`      int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8;