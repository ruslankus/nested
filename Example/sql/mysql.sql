-- phpMyAdmin SQL Dump
-- version 3.5.7
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Июн 04 2013 г., 20:11
-- Версия сервера: 5.6.10-log
-- Версия PHP: 5.4.8

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- База данных: `cats`
--

-- --------------------------------------------------------

--
-- Структура таблицы `test_categories`
--

CREATE TABLE IF NOT EXISTS `test_categories` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `left_key` int(10) unsigned NOT NULL,
  `right_key` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `left_right` (`left_key`,`right_key`),
  KEY `left` (`left_key`),
  KEY `right` (`right_key`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=4 ;

--
-- Дамп данных таблицы `test_categories`
--

INSERT INTO `test_categories` (`id`, `name`, `left_key`, `right_key`) VALUES
(1, 'How Much Is The Fish?', 1, 4),
(2, 'anonymous', 5, 6),
(3, 'Child', 2, 3);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
