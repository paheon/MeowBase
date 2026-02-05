-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 04, 2025 at 02:53 PM
-- Server version: 10.1.48-MariaDB-0ubuntu0.18.04.1
-- PHP Version: 8.1.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `meow`
--

-- --------------------------------------------------------

--
-- Table structure for table `meow_test`
--

DROP TABLE IF EXISTS `meow_test`;
CREATE TABLE `meow_test` (
  `id` int(11) NOT NULL,
  `name` varchar(64) NOT NULL,
  `description` varchar(128) NOT NULL,
  `value` int(11) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- --------------------------------------------------------

--
-- Table structure for table `meow_users`
--

DROP TABLE IF EXISTS `meow_users`;
CREATE TABLE `meow_users` (
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `user_name` varchar(127) NOT NULL COMMENT 'User Name',
  `login_name` varchar(127) NOT NULL COMMENT 'Login Name',
  `password` varchar(127) NOT NULL COMMENT 'Password',
  `email` varchar(255) NOT NULL COMMENT 'Email Address',
  `status` int(11) NOT NULL DEFAULT '0' COMMENT 'Current Status',
  `login_time` datetime NOT NULL COMMENT 'Login Time (in Sec.)',
  `logout_time` datetime NOT NULL COMMENT 'Logout Time (in Sec.)',
  `last_active` datetime NOT NULL COMMENT 'Last Active Time (in Sec.)',
  `session_id` varchar(63) NOT NULL COMMENT 'Session ID',
  `extra_data` text NOT NULL COMMENT 'Extra Data',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Information';

-- --------------------------------------------------------

--
-- Table structure for table `meow_users_groups`
--

DROP TABLE IF EXISTS `meow_users_groups`;
CREATE TABLE `meow_users_groups` (
  `group_id` int(11) NOT NULL COMMENT 'User ID',
  `group_name` varchar(127) NOT NULL COMMENT 'Group Name',
  `group_desc` varchar(255) NOT NULL COMMENT 'Group Description',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Group Information';

-- --------------------------------------------------------

--
-- Table structure for table `meow_users_groups_link`
--

DROP TABLE IF EXISTS `meow_users_groups_link`;
CREATE TABLE `meow_users_groups_link` (
  `user_group_id` int(11) NOT NULL COMMENT 'User Group ID',
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `group_id` int(11) NOT NULL COMMENT 'Group ID',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Group Link Information';

-- --------------------------------------------------------

--
-- Table structure for table `meow_users_groups_perm`
--

DROP TABLE IF EXISTS `meow_users_groups_perm`;
CREATE TABLE `meow_users_groups_perm` (
  `group_perm_id` int(11) NOT NULL COMMENT 'Group Permission ID',
  `group_id` int(11) NOT NULL COMMENT 'Group ID',
  `item` varchar(127) NOT NULL COMMENT 'Item',
  `permission` varchar(127) NOT NULL COMMENT 'Permission',
  `value` int(11) NOT NULL DEFAULT 0 COMMENT 'Value',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Group Permission Information';

-- --------------------------------------------------------

--
-- Table structure for table `meow_users_perm`
--

DROP TABLE IF EXISTS `meow_users_perm`;
CREATE TABLE `meow_users_perm` (
  `user_perm_id` int(11) NOT NULL COMMENT 'User Permission ID',
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `item` varchar(127) NOT NULL COMMENT 'Item',
  `permission` varchar(127) NOT NULL COMMENT 'Permission',
  `value` int(11) NOT NULL DEFAULT 0 COMMENT 'Value',
  `created_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Created Date',
  `updated_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Updated Date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='User Permission Information';


--
-- Indexes for dumped tables
--

--
-- Indexes for table `meow_test`
--
ALTER TABLE `meow_test`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meow_users`
--
ALTER TABLE `meow_users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `login_name` (`login_name`) USING BTREE COMMENT 'Login Name';

--
-- Indexes for table `meow_users_groups`
--
ALTER TABLE `meow_users_groups`
  ADD PRIMARY KEY (`group_id`),
  ADD UNIQUE KEY `group_name` (`group_name`) USING BTREE COMMENT 'Group Name';

--
-- Indexes for table `meow_users_groups_link`
--
ALTER TABLE `meow_users_groups_link`
  ADD PRIMARY KEY (`user_group_id`),
  ADD KEY `user_id` (`user_id`,`group_id`) USING BTREE COMMENT 'User Group Lookup';

--
-- Indexes for table `meow_users_groups_perm`
--
ALTER TABLE `meow_users_groups_perm`
  ADD PRIMARY KEY (`group_perm_id`),
  ADD KEY `group_id` (`group_id`,`item`,`permission`) USING BTREE COMMENT 'Group Permission Lookup';

--
-- Indexes for table `meow_users_perm`
--
ALTER TABLE `meow_users_perm`
  ADD PRIMARY KEY (`user_perm_id`),
  ADD KEY `user_id` (`user_id`,`item`,`permission`) USING BTREE COMMENT 'User Permission Lookup';

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `meow_test`
--
ALTER TABLE `meow_test`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `meow_users`
--
ALTER TABLE `meow_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User ID', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `meow_users_groups`
--
ALTER TABLE `meow_users_groups`
  MODIFY `group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User ID', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `meow_users_groups_link`
--
ALTER TABLE `meow_users_groups_link`
  MODIFY `user_group_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Group ID', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `meow_users_groups_perm`
--
ALTER TABLE `meow_users_groups_perm`
  MODIFY `group_perm_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Group Permission ID', AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT for table `meow_users_perm`
--
ALTER TABLE `meow_users_perm`
  MODIFY `user_perm_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User Permission ID', AUTO_INCREMENT=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
