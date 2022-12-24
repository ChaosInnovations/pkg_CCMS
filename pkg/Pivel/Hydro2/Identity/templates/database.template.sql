CREATE TABLE IF NOT EXISTS `users` (
  `uid` char(32) NOT NULL,UNIQUE KEY `uid` (`uid`),
  `pwd` char(255) NOT NULL,
  `email` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `registered` date NOT NULL,
  `permissions` text NOT NULL,
  `permviewbl` text NOT NULL,
  `permeditbl` text NOT NULL,
  `collab_lastseen` datetime DEFAULT NULL,
  `collab_pageid` tinytext,
  `collab_notifs` text NOT NULL,
  `notify` tinyint(1) NOT NULL DEFAULT '1',
  `last_notif` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores tokens` */
CREATE TABLE IF NOT EXISTS `tokens` (
  `uid` char(32) NOT NULL,
  `tid` char(32) NOT NULL,UNIQUE KEY `tid` (`tid`),
  `source_ip` varchar(45) NOT NULL,
  `start` date NOT NULL,
  `expire` date NOT NULL,
  `forcekill` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `users` VALUES('21232f297a57a5a743894a0e4a801fc3', '$2y$10$aqSNcBhO7vU/B82D7b0zmuO4OTioth34ZI13aNrlyATZ6Xji9Q3Qa', 'admin', 'Administrator', UTC_TIMESTAMP, "owner;", "", "", NULL, "", "", 1, 0);