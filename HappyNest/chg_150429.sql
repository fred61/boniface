DROP TABLE IF EXISTS `session`;
CREATE TABLE `session` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `cost` decimal(10,0) DEFAULT NULL,
  `available_on` varchar(16) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `session`
--

INSERT INTO `session` (`id`, `name`, `cost`, `available_on`) VALUES
(1, 'Playgroup', '46', '1,2,3,4,5'),
(2, 'English Club', '40', '2,3'),
(3, 'Music', '35', '2,3'),
(4, 'Waiting List', NULL, '1,2,3,4,5');

-- --------------------------------------------------------

--
-- Table structure for table `session_occurence`
--

DROP TABLE IF EXISTS `session_occurence`;
CREATE TABLE `session_occurence` (
  `session_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  `weekdays` varchar(16) DEFAULT NULL,
  `valid_from` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `parent`
  DROP `is_active`,
  DROP `playgroup`,
  DROP `phonics`,
  DROP `music`;