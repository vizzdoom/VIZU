
--
-- `fields` table structure
--

CREATE TABLE `fields` (
	`num` int NOT NULL AUTO_INCREMENT,
	`template` varchar(50) NOT NULL,
	`language` varchar(2) NOT NULL,
	`id` varchar(50) NOT NULL,
	`content` text,
	`created` timestamp NOT NULL,
	`modified` timestamp NOT NULL,
	`version` int NOT NULL,
	PRIMARY KEY (`num`)
) CHARSET=utf8;


-- --------------------------------------------------------


--
-- `config` table structure
--

CREATE TABLE `users` (
	`id` int NOT NULL AUTO_INCREMENT,
	`email` varchar(250) NOT NULL,
	`password` text,
	PRIMARY KEY (`id`)
) CHARSET=utf8;

INSERT INTO `users` VALUES
	(NULL, 'admin@website.com', '0a5b5d819b8a9d2e90843df535516e41e41330140acb2bb69a29092d3cb49993');


-- --------------------------------------------------------


--
-- `languages` table structure
--

CREATE TABLE `languages` (
	`code` varchar(2) NOT NULL,
	`short_name` varchar(3) NOT NULL,
	`name` varchar(30),
	`country` varchar(30),
	`active` BOOLEAN NOT NULL DEFAULT TRUE,
	PRIMARY KEY (`code`)
) CHARSET=utf8;

--
-- `languages` default data
--

INSERT INTO `languages` VALUES
	('pl', 'PL', 'Polski', 'Polska', TRUE),
	('en', 'EN', 'English', 'Great Britain', TRUE);