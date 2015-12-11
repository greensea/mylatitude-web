--
-- Database: `latitude`
--

-- --------------------------------------------------------

--
-- 表的结构 `b_friend`
--

CREATE TABLE IF NOT EXISTS `b_friend` (
  `friend_id` int(11) NOT NULL,
  `friend1_google_uid` varchar(255) NOT NULL,
  `friend2_google_uid` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `dtime` int(11) NOT NULL DEFAULT '0',
  `invite_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `b_invite`
--

CREATE TABLE IF NOT EXISTS `b_invite` (
  `invite_id` int(11) NOT NULL,
  `sender_google_uid` varchar(255) NOT NULL,
  `invited_google_uid` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `dtime` int(11) NOT NULL DEFAULT '0' COMMENT '拒绝时间（deny time)',
  `atime` int(11) NOT NULL DEFAULT '0' COMMENT '同意时间(approve time)',
  `rtime` int(11) NOT NULL DEFAULT '0' COMMENT '撤销时间（revert time）'
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- 表的结构 `b_location`
--

CREATE TABLE IF NOT EXISTS `b_location` (
  `name` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `rtime` double NOT NULL COMMENT '用户报告的位置时间（reported time）',
  `latitude` decimal(20,16) NOT NULL,
  `longitude` decimal(20,16) NOT NULL,
  `altitude` decimal(20,16) NOT NULL,
  `accurateness` decimal(20,16) NOT NULL,
  `google_uid` varchar(255) NOT NULL DEFAULT '',
  `uid` varchar(255) NOT NULL DEFAULT '',
  `src` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 表的结构 `b_stat`
--

CREATE TABLE IF NOT EXISTS `b_stat` (
  `stat_id` int(11) NOT NULL,
  `google_uid` varchar(255) NOT NULL,
  `ctime` int(11) NOT NULL,
  `distance` double NOT NULL COMMENT '移动总距离，单位 m',
  `distance_per_day` float NOT NULL COMMENT '日均移动距离，单位 m',
  `mtime` int(11) NOT NULL,
  `min_time` float NOT NULL COMMENT '已知的最早的位置记录的时间',
  `max_time` float NOT NULL COMMENT '已知的最新的位置记录的时间'
) ENGINE=InnoDB AUTO_INCREMENT=128 DEFAULT CHARSET=latin1 COMMENT='用户移信息统计表';

-- --------------------------------------------------------

--
-- 表的结构 `b_user`
--

CREATE TABLE IF NOT EXISTS `b_user` (
  `user_id` int(11) NOT NULL,
  `uid` varchar(32) NOT NULL,
  `google_uid` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `google_face` varchar(4096) NOT NULL,
  `ctime` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 替换视图以便查看 `v_location`
--
CREATE TABLE IF NOT EXISTS `v_location` (
`name` varchar(255)
,`ctime` int(11)
,`rtime` double
,`latitude` decimal(20,16)
,`longitude` decimal(20,16)
,`altitude` decimal(20,16)
,`accurateness` decimal(20,16)
,`FROM_UNIXTIME(ctime)` datetime
,`FROM_UNIXTIME(rtime)` datetime
);

-- --------------------------------------------------------

--
-- 视图结构 `v_location`
--
DROP TABLE IF EXISTS `v_location`;

CREATE VIEW `v_location` AS select `b_location`.`name` AS `name`,`b_location`.`ctime` AS `ctime`,`b_location`.`rtime` AS `rtime`,`b_location`.`latitude` AS `latitude`,`b_location`.`longitude` AS `longitude`,`b_location`.`altitude` AS `altitude`,`b_location`.`accurateness` AS `accurateness`,from_unixtime(`b_location`.`ctime`) AS `FROM_UNIXTIME(ctime)`,from_unixtime(`b_location`.`rtime`) AS `FROM_UNIXTIME(rtime)` from `b_location` order by `b_location`.`name` desc,`b_location`.`rtime` desc;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `b_friend`
--
ALTER TABLE `b_friend`
  ADD PRIMARY KEY (`friend_id`), ADD KEY `friend1_google_uid` (`friend1_google_uid`,`friend2_google_uid`,`dtime`);

--
-- Indexes for table `b_invite`
--
ALTER TABLE `b_invite`
  ADD PRIMARY KEY (`invite_id`);

--
-- Indexes for table `b_location`
--
ALTER TABLE `b_location`
  ADD PRIMARY KEY (`rtime`,`uid`), ADD KEY `ctime` (`ctime`), ADD KEY `google_uid` (`google_uid`,`rtime`) USING BTREE, ADD KEY `uid` (`uid`);

--
-- Indexes for table `b_stat`
--
ALTER TABLE `b_stat`
  ADD PRIMARY KEY (`stat_id`), ADD UNIQUE KEY `google_uid` (`google_uid`);

--
-- Indexes for table `b_user`
--
ALTER TABLE `b_user`
  ADD PRIMARY KEY (`user_id`), ADD UNIQUE KEY `uid` (`uid`), ADD KEY `google_uid` (`google_uid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `b_friend`
--
ALTER TABLE `b_friend`
  MODIFY `friend_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=20;
--
-- AUTO_INCREMENT for table `b_invite`
--
ALTER TABLE `b_invite`
  MODIFY `invite_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=23;
--
-- AUTO_INCREMENT for table `b_stat`
--
ALTER TABLE `b_stat`
  MODIFY `stat_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=128;
--
-- AUTO_INCREMENT for table `b_user`
--
ALTER TABLE `b_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=77;
