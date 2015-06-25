-- phpMyAdmin SQL Dump
-- version 4.0.10.10
-- http://www.phpmyadmin.net
--
-- ホスト: localhost
-- 生成日時: 2015 年 6 月 04 日 18:57
-- サーバのバージョン: 5.1.73
-- PHP のバージョン: 5.3.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+09:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- データベース: `TFTbot`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `tumblr_post`
--

CREATE TABLE IF NOT EXISTS `tumblr_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_name` varchar(64) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `photo_url` varchar(256) NOT NULL,
  `twitter_post_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `blog_name` (`blog_name`,`photo_url`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルの構造 `twitter_post`
--

CREATE TABLE IF NOT EXISTS `twitter_post` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) DEFAULT NULL,
  `post_text` varchar(128) DEFAULT NULL,
  `image_url` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `post_text` (`post_text`,`image_url`),
  UNIQUE KEY `post_id` (`post_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルの構造 `twitter_post_log`
--

CREATE TABLE IF NOT EXISTS `twitter_post_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_name` varchar(64) DEFAULT NULL,
  `tumblr_post_id` bigint(20) DEFAULT NULL,
  `error_msg` varchar(256) DEFAULT NULL,
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルの構造 `auto_reply_log`
--

CREATE TABLE IF NOT EXISTS `auto_reply_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `blog_name` varchar(64) NOT NULL,
  `error_msg` varchar(256) NULL,
  `posted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- テーブルの構造 `twitter_search_word`
--

CREATE TABLE IF NOT EXISTS `twitter_search_word` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `word` varchar(128) NOT NULL,
  `notice_user` varchar(32) NOT NULL,
  `latest_tweet_id` bigint(20) DEFAULT NULL,
  `disable_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
