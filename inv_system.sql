-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Oct 20, 2025 at 07:19 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inv_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_title`
--

CREATE TABLE `account_title` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `account_title`
--

INSERT INTO `account_title` (`id`, `category_name`) VALUES
(4, 'Machinery and Equipment'),
(5, 'Transportation Equipment'),
(6, 'Furniture, Fixtures and Books'),
(7, 'Buildings and Other Structures'),
(8, 'Infrastructure Assets');

-- --------------------------------------------------------

--
-- Table structure for table `archive`
--

CREATE TABLE `archive` (
  `id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `classification` varchar(50) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `archived_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `archive`
--

INSERT INTO `archive` (`id`, `record_id`, `classification`, `data`, `archived_at`, `archived_by`) VALUES
(63, 3, 'categories', '{\"id\":\"3\",\"name\":\"Janitorial Supplies\"}', '2025-10-05 02:28:18', 0),
(64, 61, 'items', '{\"id\":\"61\",\"fund_cluster\":\"Gaarra\",\"stock_card\":\"30\",\"name\":\"Coffee\",\"UOM\":\"pc\",\"quantity\":\"0\",\"unit_cost\":\"21.00\",\"categorie_id\":\"1\",\"media_id\":\"47\",\"description\":\"for bsit students\",\"date_added\":\"2025-10-07 12:55:43\",\"last_edited\":\"2025-10-07 12:57:17\"}', '2025-10-07 04:58:45', 0),
(65, 1, 'employees', '{\"id\":\"1\",\"first_name\":\"John\",\"last_name\":\"Doe\",\"middle_name\":\"A.\",\"position\":\"Software Engineer\",\"image\":\"1758712163_pfp1.jpg\",\"office\":\"CAT\",\"status\":\"Active\",\"created_at\":\"2025-09-20 19:21:57\",\"updated_at\":\"2025-09-27 23:58:42\",\"user_id\":null}', '2025-10-07 05:08:31', 0),
(66, 65, 'requests', '{\"id\":\"65\",\"ris_no\":\"2025-10-\",\"requested_by\":\"1\",\"date\":\"2025-10-07 12:09:36\",\"status\":\"Approved\",\"date_approved\":\"2025-10-07 12:32:50\",\"remarks\":\"\",\"created_at\":\"2025-10-07 12:32:50\"}', '2025-10-07 06:53:58', 0),
(68, 84, 'requests', '{\"id\":\"84\",\"ris_no\":\"2025-10-\",\"requested_by\":\"10\",\"date\":\"2025-10-15 00:26:44\",\"status\":\"Pending\",\"date_issued\":null,\"date_completed\":null,\"remarks\":\"\",\"created_at\":\"2025-10-15 00:26:44\",\"school_year_id\":\"0\"}', '2025-10-14 16:26:56', 0),
(69, 94, 'requests', '{\"id\":\"94\",\"ris_no\":\"2025-10-0001\",\"requested_by\":\"10\",\"date\":\"2025-10-16 20:48:16\",\"status\":\"Pending\",\"date_issued\":null,\"date_completed\":null,\"remarks\":\"\",\"created_at\":\"2025-10-16 20:48:16\"}', '2025-10-16 12:49:08', 0),
(73, 93, 'requests', '{\"id\":\"93\",\"ris_no\":\"2025-10-0001\",\"requested_by\":\"11\",\"date\":\"2025-10-16 20:15:28\",\"status\":\"Completed\",\"date_issued\":\"0000-00-00 00:00:00\",\"date_completed\":\"2025-10-16 21:18:15\",\"remarks\":\"\",\"created_at\":\"2025-10-16 21:18:15\"}', '2025-10-19 14:19:43', 0),
(78, 95, 'requests', '{\"id\":\"95\",\"ris_no\":\"2025-10-0002\",\"requested_by\":\"10\",\"date\":\"2025-10-16 21:20:57\",\"status\":\"Completed\",\"date_issued\":\"0000-00-00 00:00:00\",\"date_completed\":\"2025-10-19 22:41:55\",\"remarks\":\"\",\"created_at\":\"2025-10-19 22:41:55\"}', '2025-10-19 18:41:06', 0),
(79, 101, 'requests', '{\"id\":\"101\",\"ris_no\":\"2025-10-0045\",\"requested_by\":\"12\",\"date\":\"2025-10-20 02:09:49\",\"status\":\"Pending\",\"date_issued\":null,\"date_completed\":null,\"remarks\":\"\",\"created_at\":\"2025-10-20 02:09:49\"}', '2025-10-19 19:06:29', 0),
(80, 100, 'requests', '{\"id\":\"100\",\"ris_no\":\"2025-10-0022\",\"requested_by\":\"11\",\"date\":\"2025-10-20 01:46:37\",\"status\":\"Pending\",\"date_issued\":null,\"date_completed\":null,\"remarks\":\"\",\"created_at\":\"2025-10-20 01:46:37\"}', '2025-10-19 19:06:33', 0),
(81, 98, 'requests', '{\"id\":\"98\",\"ris_no\":\"2025-10-0007\",\"requested_by\":\"11\",\"date\":\"2025-10-20 00:39:14\",\"status\":\"Pending\",\"date_issued\":null,\"date_completed\":null,\"remarks\":\"\",\"created_at\":\"2025-10-20 00:39:14\"}', '2025-10-19 19:06:37', 0),
(82, 96, 'requests', '{\"id\":\"96\",\"ris_no\":\"2025-10-0003\",\"requested_by\":\"12\",\"date\":\"2025-10-18 09:27:36\",\"status\":\"Pending\",\"date_issued\":\"0000-00-00 00:00:00\",\"date_completed\":\"0000-00-00 00:00:00\",\"remarks\":\"\",\"created_at\":\"2025-10-18 09:27:36\"}', '2025-10-19 19:06:42', 0);

-- --------------------------------------------------------

--
-- Table structure for table `base_units`
--

CREATE TABLE `base_units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `base_units`
--

INSERT INTO `base_units` (`id`, `name`, `symbol`) VALUES
(1, 'Not Applicable', 'N/A'),
(2, 'Box', 'box'),
(3, 'Pack', 'pk'),
(4, 'Ream', 'ream'),
(5, 'Meter', 'm'),
(6, 'Centimeter', 'cm'),
(7, 'Millimeter', 'mm'),
(8, 'Kilogram', 'kg'),
(9, 'Gram', 'g'),
(10, 'Milligram', 'mg'),
(11, 'Liter', 'L'),
(12, 'Milliliter', 'mL'),
(13, 'Gallon', 'gal'),
(14, 'Can', 'can'),
(15, 'Bottle', 'btl'),
(16, 'Roll', 'roll'),
(17, 'Set', 'set'),
(18, 'Pair', 'pair'),
(19, 'Dozen', 'dz'),
(20, 'Sack', 'sack'),
(21, 'Piece', 'pc');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Common Supplies'),
(2, 'Electrical Supplies '),
(3, 'GSO Supplies'),
(4, 'Janitorial Supplies'),
(5, 'Motorpool Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `divisions`
--

CREATE TABLE `divisions` (
  `id` int(11) NOT NULL,
  `division_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `divisions`
--

INSERT INTO `divisions` (`id`, `division_name`, `created_at`, `updated_at`) VALUES
(1, 'ADMINISTRATIVE', '2025-10-01 14:53:33', '2025-10-01 14:53:33'),
(3, 'CTE', '2025-10-01 14:59:13', '2025-10-01 14:59:13'),
(4, 'OSS', '2025-10-01 15:03:55', '2025-10-01 15:03:55');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `division` varchar(25) NOT NULL,
  `office` varchar(100) DEFAULT NULL,
  `designation` varchar(25) NOT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `middle_name`, `position`, `image`, `division`, `office`, `designation`, `status`, `created_at`, `updated_at`, `user_id`) VALUES
(2, 'Jane', 'Smith', 'B.', 'HR Manager', '1758712274_pfp1.jpg', '3', '5', '0', 'Active', '2025-09-20 11:21:57', '2025-10-14 15:10:32', NULL),
(5, 'Dave ', 'Nabaysan', 'A.', 'Taga Kain', '1760155369_Cats.jpg', '4', '8', '0', 'Active', '2025-10-11 04:02:38', '2025-10-14 15:11:03', 12),
(6, 'Jhoy', 'Bangleg', 'L.', 'Taga Kain', '1760155448_Cats.jpg', '1', '3', '0', 'Active', '2025-10-11 04:04:08', '2025-10-14 15:11:16', 11);

-- --------------------------------------------------------

--
-- Table structure for table `fund_clusters`
--

CREATE TABLE `fund_clusters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `updated_at` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fund_clusters`
--

INSERT INTO `fund_clusters` (`id`, `name`, `description`, `updated_at`) VALUES
(1, 'GAA', 'General', '2025-10-02'),
(2, 'IGI', 'Internal', '2025-10-02');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) UNSIGNED NOT NULL,
  `fund_cluster` varchar(255) NOT NULL,
  `stock_card` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(25,2) DEFAULT NULL,
  `categorie_id` int(11) UNSIGNED NOT NULL,
  `media_id` int(11) DEFAULT 0,
  `description` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `unit_id` int(11) DEFAULT NULL,
  `base_unit_id` int(11) DEFAULT NULL,
  `archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `fund_cluster`, `stock_card`, `name`, `quantity`, `unit_cost`, `categorie_id`, `media_id`, `description`, `date_added`, `last_edited`, `unit_id`, `base_unit_id`, `archived`) VALUES
(64, 'GAA', '0001', 'Ballpen - RED', '0', 200.00, 1, 55, 'Okie', '2025-10-14 21:15:49', '2025-10-20 04:11:05', 3, 21, 0),
(65, 'IGI', '0002', 'Ballpen - BLACK', '7.8333333333336', 30.00, 1, 58, '', '2025-10-14 21:26:38', '2025-10-20 11:04:49', 3, 21, 0),
(66, 'GAA', '0003', 'Lollipop', '14.6', 300.00, 2, 60, '', '2025-10-14 21:38:40', '2025-10-20 13:11:59', 3, 16, 0),
(67, 'GAA', '0004', 'Fish', '0.4285714285714399', 600.00, 3, 61, '', '2025-10-14 21:48:34', '2025-10-20 03:16:02', 3, 14, 0),
(68, 'GAA', '0005', 'Cat', '0', 550.00, 3, 63, '', '2025-10-14 21:56:00', '2025-10-20 02:52:19', 6, 9, 0),
(69, 'IGI', '0008', 'Mouse', '4', 500.00, 1, 64, '', '2025-10-19 17:56:22', NULL, 4, 21, 0),
(70, 'IGI', '00067', 'Alcohol-600ml', '5', 600.00, 4, 65, '', '2025-10-19 18:05:58', '0000-00-00 00:00:00', 6, 1, 0);

-- --------------------------------------------------------

--
-- Table structure for table `item_stocks_per_year`
--

CREATE TABLE `item_stocks_per_year` (
  `id` int(10) UNSIGNED NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `stock` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `item_stocks_per_year`
--

INSERT INTO `item_stocks_per_year` (`id`, `item_id`, `school_year_id`, `stock`, `updated_at`) VALUES
(4, 67, 1, 1, '2025-10-19 19:16:02'),
(5, 65, 1, 8, '2025-10-20 03:04:49'),
(6, 64, 1, 0, '2025-10-19 20:11:05'),
(7, 66, 1, 15, '2025-10-20 05:11:59'),
(8, 68, 1, 0, '2025-10-19 18:52:19'),
(9, 69, 1, 4, '2025-10-19 09:56:22');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `id` int(11) UNSIGNED NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`id`, `file_name`, `file_type`) VALUES
(1, 'bsulogo.png', 'image/png'),
(2, 'muriatic.jpg', 'image/jpeg'),
(3, 'puncher.jpg', 'image/jpeg'),
(4, 'wire.jpg', 'image/jpeg'),
(5, 'teflon.jpg', 'image/jpeg'),
(6, 'default.jpg', 'image/jpeg'),
(7, 'Milogo.png', ''),
(8, 'Milogo.png', ''),
(9, '2.0.jpg', ''),
(10, '2.0.jpg', ''),
(11, 'bean.jpg', ''),
(12, 'no_image.png', ''),
(13, 'no_image.png', ''),
(14, 'no_image.png', ''),
(15, 'no_image.png', ''),
(16, 'no_image.png', ''),
(17, 'no_image.png', ''),
(18, 'no_image.png', ''),
(19, 'no_image.png', ''),
(20, 'no_image.png', ''),
(21, 'no_image.png', ''),
(22, 'no_image.png', ''),
(23, 'no_image.png', ''),
(24, 'no_image.png', ''),
(25, 'no_image.png', ''),
(26, 'no_image.png', ''),
(27, 'no_image.png', ''),
(28, 'no_image.png', ''),
(29, 'no_image.png', ''),
(30, 'Milogo.png', ''),
(31, 'teflon.jpg', ''),
(32, 'no_image.png', ''),
(33, 'no_image.png', ''),
(34, 'no_image.png', ''),
(35, 'no_image.png', ''),
(36, 'no_image.png', ''),
(37, 'no_image.png', ''),
(38, 'no_image.png', ''),
(39, 'no_image.png', ''),
(40, 'no_image.png', ''),
(41, 'no_image.png', ''),
(42, 'no_image.png', ''),
(43, 'no_image.png', ''),
(44, 'no_image.png', ''),
(45, 'no_image.png', ''),
(46, 'no_image.png', ''),
(47, 'no_image.png', ''),
(48, 'no_image.png', ''),
(49, 'no_image.png', ''),
(50, 'no_image.png', ''),
(51, 'no_image.png', ''),
(52, 'no_image.png', ''),
(53, 'no_image.png', ''),
(54, 'no_image.png', ''),
(55, 'no_image.png', ''),
(56, 'no_image.png', ''),
(57, 'no_image.png', ''),
(58, 'no_image.png', ''),
(59, 'no_image.png', ''),
(60, 'no_image.png', ''),
(61, 'no_image.png', ''),
(62, 'no_image.png', ''),
(63, 'puncher.jpg', ''),
(64, 'no_image.png', ''),
(65, 'no_image.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(11) NOT NULL,
  `division_id` int(11) NOT NULL,
  `office_name` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `division_id`, `office_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'GSO', '2025-10-01 14:59:27', '2025-10-01 15:00:01'),
(3, 1, 'BUDGET', '2025-10-01 14:59:27', '2025-10-01 14:59:51'),
(4, 1, 'MOTORPOOL', '2025-10-01 15:00:16', '2025-10-01 15:00:16'),
(5, 3, 'CED', '2025-10-01 15:00:37', '2025-10-01 15:00:37'),
(7, 4, 'LIBRARY', '2025-10-01 15:19:52', '2025-10-01 15:19:52'),
(8, 4, 'GUIDANCE', '2025-10-01 15:19:52', '2025-10-01 15:19:52'),
(9, 4, 'CLINIC', '2025-10-01 15:19:52', '2025-10-01 15:19:52'),
(10, 3, 'CAT', '2025-10-04 05:03:24', '2025-10-04 05:03:24'),
(11, 3, 'CCJE-PA', '2025-10-04 05:03:45', '2025-10-04 05:03:45'),
(12, 1, 'SPMO', '2025-10-19 17:16:08', '2025-10-19 17:16:08');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `fund_cluster` varchar(50) NOT NULL,
  `property_no` varchar(100) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `article` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL,
  `qty` int(11) NOT NULL DEFAULT 0,
  `qty_left` int(11) NOT NULL,
  `date_acquired` date NOT NULL,
  `remarks` text DEFAULT NULL,
  `date_added` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `condition` enum('damaged','functional') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `fund_cluster`, `property_no`, `subcategory_id`, `article`, `description`, `unit_cost`, `unit`, `qty`, `qty_left`, `date_acquired`, `remarks`, `date_added`, `date_updated`, `condition`) VALUES
(1, 'IGI', 'HY-465451', 9, 'Helicopter', 'wiiiiiiiiiiiiiiiiiiiiwwwwwwwwwwwwwwweeeeeeeeeeeeeee', 500000.00, 'Unit', 11, 9, '2025-10-16', '                                          WITWEW', '2025-10-04 06:59:07', '2025-10-19 13:34:05', 'damaged'),
(2, 'GAA', 'PR-uyr123', 5, 'Wheel Chair', 'For mentals', 89000.00, 'Unit', 22, 22, '2025-10-01', '                                          ', '2025-10-05 02:07:43', '2025-10-11 05:55:33', 'damaged'),
(3, 'GAA', 'PR-7865345', 12, 'RBC', 'Research Building', 2000000.00, 'Lot', 1, 1, '2025-10-16', '                                          oooooff', '2025-10-07 08:59:05', '2025-10-11 05:55:33', 'damaged');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) UNSIGNED NOT NULL,
  `ris_no` varchar(25) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Completed','Archived','Issued') DEFAULT 'Pending',
  `date_issued` datetime DEFAULT NULL,
  `date_completed` datetime DEFAULT NULL,
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `ris_no`, `requested_by`, `date`, `status`, `date_issued`, `date_completed`, `remarks`, `created_at`) VALUES
(97, '2025-10-0004', 10, '2025-10-18 01:28:03', 'Completed', NULL, '2025-10-20 02:40:39', '', '2025-10-19 18:40:39'),
(99, '2025-10-0005', 5, '2025-10-19 17:24:33', 'Issued', '2025-10-20 02:40:48', '2025-10-20 01:24:51', '', '2025-10-19 18:40:48'),
(102, '2025-10-0074', 10, '2025-10-19 18:16:50', 'Completed', NULL, '2025-10-20 02:52:53', '', '2025-10-19 18:52:53'),
(103, '2025-10-0235', 2, '2025-10-19 18:50:10', 'Issued', '2025-10-20 02:52:19', '2025-10-20 02:52:13', '', '2025-10-19 18:52:19'),
(104, '2025-10-0009', 11, '2025-10-19 19:07:31', 'Completed', '2025-10-20 03:09:01', '2025-10-20 03:47:07', '', '2025-10-19 19:47:07'),
(105, '2025-10-0084', 6, '2025-10-19 19:16:02', 'Pending', NULL, NULL, '', '2025-10-19 19:16:02'),
(106, '2025-10-0002', 2, '2025-10-19 19:45:00', 'Approved', NULL, '2025-10-20 03:45:15', '', '2025-10-19 19:45:15'),
(107, '2025-10-', 12, '2025-10-19 20:08:08', 'Pending', '2025-10-20 04:11:05', '2025-10-20 04:10:47', '', '2025-10-19 20:11:50'),
(108, '2025-10-7485', 11, '2025-10-20 02:40:06', 'Issued', '2025-10-20 13:11:59', '2025-10-20 13:11:50', '', '2025-10-20 05:11:59'),
(109, '2025-10-5000', 11, '2025-10-20 02:59:50', 'Completed', '2025-10-20 11:04:49', '2025-10-20 11:04:58', '', '2025-10-20 03:04:58');

-- --------------------------------------------------------

--
-- Table structure for table `request_items`
--

CREATE TABLE `request_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `req_id` int(11) UNSIGNED NOT NULL,
  `item_id` int(11) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `remarks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_items`
--

INSERT INTO `request_items` (`id`, `req_id`, `item_id`, `qty`, `unit`, `price`, `remarks`) VALUES
(110, 97, 65, 1, 'Piece', 2.50, ''),
(113, 99, 65, 1, 'Piece', 2.50, ''),
(116, 102, 66, 5, 'Roll', 150.00, ''),
(117, 103, 68, 4, 'Box', 2200.00, ''),
(118, 104, 67, 5, 'Can', 428.57, ''),
(119, 105, 67, 1, 'Can', 85.71, ''),
(120, 106, 64, 1, 'Box', 200.00, ''),
(123, 108, 66, 5, 'Roll', 150.00, ''),
(124, 109, 65, 5, 'Piece', 12.50, '');

-- --------------------------------------------------------

--
-- Table structure for table `school_years`
--

CREATE TABLE `school_years` (
  `id` int(11) UNSIGNED NOT NULL,
  `school_year` varchar(9) NOT NULL,
  `semester` enum('1st','2nd','summer') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `school_years`
--

INSERT INTO `school_years` (`id`, `school_year`, `semester`, `start_date`, `end_date`, `is_current`, `created_at`, `updated_at`) VALUES
(1, '2024-2025', '1st', '2025-07-31', '2025-12-19', 1, '2025-10-13 05:04:54', '2025-10-13 05:04:54');

-- --------------------------------------------------------

--
-- Table structure for table `semicategories`
--

CREATE TABLE `semicategories` (
  `id` int(11) NOT NULL,
  `semicategory_name` varchar(255) NOT NULL,
  `uacs` int(25) NOT NULL,
  `date_added` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semicategories`
--

INSERT INTO `semicategories` (`id`, `semicategory_name`, `uacs`, `date_added`, `date_updated`) VALUES
(1, ' Machinery', 1040501000, '2025-10-02 23:53:59', '2025-10-05 10:21:03'),
(2, ' Office Equipment', 1040502000, '2025-10-02 23:54:34', '2025-10-05 10:21:17'),
(3, ' Medical Equipment', 1040510000, '2025-10-02 23:55:04', '2025-10-05 10:21:29'),
(4, 'Sports Equipment', 1040512000, '2025-10-02 23:55:35', '2025-10-05 10:21:47'),
(5, ' Printing Equipment', 1040511000, '2025-10-02 23:56:01', '2025-10-05 10:59:16'),
(6, ' Airport Equipment', 1040506000, '2025-10-02 23:58:44', '2025-10-05 10:20:26');

-- --------------------------------------------------------

--
-- Table structure for table `semi_exp_prop`
--

CREATE TABLE `semi_exp_prop` (
  `id` int(10) UNSIGNED NOT NULL,
  `fund_cluster` varchar(50) NOT NULL,
  `inv_item_no` varchar(255) DEFAULT NULL,
  `item` varchar(255) NOT NULL,
  `item_description` varchar(255) NOT NULL,
  `semicategory_id` int(25) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_qty` int(25) NOT NULL,
  `qty_left` int(25) NOT NULL,
  `estimated_use` varchar(50) DEFAULT NULL,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('available','issued','lost','returned','disposed','archived') DEFAULT 'available',
  `condition` enum('damaged','functional') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semi_exp_prop`
--

INSERT INTO `semi_exp_prop` (`id`, `fund_cluster`, `inv_item_no`, `item`, `item_description`, `semicategory_id`, `unit`, `unit_cost`, `total_qty`, `qty_left`, `estimated_use`, `date_added`, `last_edited`, `status`, `condition`) VALUES
(1, 'GAA', '908453-3487', 'ACER LAPTOP', 'Laptop ACER', 4, 'Unit', 39000.00, 1, 0, '', '2025-09-20 18:29:42', '2025-10-20 11:06:50', 'issued', 'damaged'),
(2, 'IGI', 'JHGFUIG8764', 'FIRE EXTINGUISHER', 'Fire Extinguisher', 1, 'Bottle', 5000.00, 1, 1, '5 Years', '2025-09-21 23:12:30', '2025-10-07 12:26:23', 'available', 'damaged'),
(11, 'GAA', 'GAA-908453-3487', 'CHAIR', 'Chair', 4, 'Unit', 39000.00, 17, 16, '', '2025-09-22 11:40:04', '2025-10-18 14:06:21', 'available', 'damaged'),
(12, 'GAA', '12345-678', 'PENCIL', 'Lapis', 2, 'Box', 17.00, 20, 19, '', '2025-09-22 12:43:15', '2025-10-18 23:27:43', 'available', 'damaged'),
(13, 'GAA', 'INV-984', 'TISSUE', 'Tissue huhu', 1, 'roll', 75.00, 12, 12, '', '2025-09-28 18:41:46', '2025-10-15 10:49:40', 'available', 'damaged');

-- --------------------------------------------------------

--
-- Table structure for table `signatories`
--

CREATE TABLE `signatories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `position` varchar(150) NOT NULL,
  `agency` varchar(150) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `signatories`
--

INSERT INTO `signatories` (`id`, `name`, `position`, `agency`, `created_at`, `updated_at`) VALUES
(1, 'FREDALYN JOY V. FINMARA', 'Accounting Staff', 'ACCOUNTING', '2025-10-01 06:51:08', '2025-10-01 06:51:08'),
(2, 'BRIGIDA A. BENSOSAN', 'AO I / Supply Officer', 'SPMO', '2025-10-01 06:53:12', '2025-10-01 06:53:12'),
(3, 'JHOY L. BANGLEG', 'COA REPRESENTATIVE', 'COA', '2025-10-01 06:54:58', '2025-10-01 06:54:58'),
(4, 'DAVE A. NABAYSAN', 'AUDIT TEAM LEADER', 'COA', '2025-10-01 06:55:46', '2025-10-01 06:55:46'),
(5, 'KENNETH A. LARUAN', 'UNIVERSITY PRESIDENT', 'ADMIN', '2025-10-01 06:56:18', '2025-10-01 06:56:18'),
(6, 'FLORANTE B. VALDEZ', 'Administratice Aied VI , SPMO', 'Inventory  Committee Member', '2025-10-01 06:57:32', '2025-10-01 06:57:32'),
(7, 'RICKY S. POLILEN', 'Administrative Officer I , SPMO', 'Inventory  Committee Member', '2025-10-01 06:58:45', '2025-10-01 06:58:45');

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `previous_qty` int(11) NOT NULL,
  `new_qty` int(11) NOT NULL,
  `change_type` enum('stock_in','adjustment','correction','initial') DEFAULT 'adjustment',
  `changed_by` varchar(100) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `date_changed` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `item_id`, `previous_qty`, `new_qty`, `change_type`, `changed_by`, `remarks`, `date_changed`) VALUES
(2, 64, 23, 22, 'adjustment', 'Administrator', 'Quantity changed from 23 to 22.', '2025-10-14 21:49:19'),
(3, 66, 5, 11, 'stock_in', 'Administrator', 'Quantity changed from 5 to 11.', '2025-10-14 21:49:39'),
(4, 66, 6, 6, '', 'System', 'Request #86 issued', '2025-10-15 00:44:35'),
(5, 66, 6, 6, '', 'System', 'Request #86 issued', '2025-10-15 00:45:14'),
(6, 66, 6, 6, '', 'System', 'Request #86 issued', '2025-10-15 00:45:28'),
(7, 66, 6, 6, '', 'System', 'Request #86 issued', '2025-10-15 00:45:59'),
(8, 66, 6, 4, '', 'System', 'Request #86 issued', '2025-10-15 00:47:19'),
(9, 66, 4, 2, '', 'System', 'Request #86 issued', '2025-10-15 00:47:27'),
(10, 66, 2, 0, '', 'System', 'Request #86 issued', '2025-10-15 00:52:06'),
(11, 67, 4, 3, '', 'System', 'Request #87 issued', '2025-10-15 01:37:19'),
(12, 65, 11, 4, '', 'System', 'Request #88 issued', '2025-10-15 01:52:40'),
(13, 65, 4, 2, '', 'System', 'Request #85 issued', '2025-10-15 10:30:09'),
(14, 65, 2, 16, 'stock_in', 'Administrator', 'Quantity changed from 2 to 16.', '2025-10-15 10:30:45'),
(15, 66, 0, 20, 'stock_in', 'Administrator', 'Quantity changed from 0 to 20.', '2025-10-15 10:31:00'),
(16, 68, 2, 14, 'stock_in', 'Administrator', 'Quantity changed from 2 to 14.', '2025-10-15 10:32:23'),
(17, 65, 16, 14, '', 'System', 'Request #85 issued', '2025-10-15 19:11:13'),
(18, 64, 0, 3, 'stock_in', 'Administrator', 'Quantity changed from 0 to 3.', '2025-10-15 20:50:17'),
(19, 68, 12, 11, '', '10', 'Request #93 issued', '2025-10-16 21:18:15'),
(20, 66, 19, 18, '', '10', 'Request #95 issued', '2025-10-19 22:41:55'),
(21, 65, 11, 11, '', '10', 'Request #97 issued', '2025-10-20 02:40:39'),
(22, 65, 11, 11, '', '10', 'Request #99 issued', '2025-10-20 02:40:48'),
(23, 68, 7, 0, '', '10', 'Request #103 issued', '2025-10-20 02:52:19'),
(24, 66, 17, 16, '', '10', 'Request #102 issued', '2025-10-20 02:52:53'),
(25, 67, 1, 1, '', '10', 'Request #104 issued', '2025-10-20 03:09:01'),
(26, 64, 1, 0, '', '10', 'Request #107 issued', '2025-10-20 04:11:05'),
(27, 65, 10, 9, '', '10', 'Request #107 issued', '2025-10-20 04:11:05'),
(28, 66, 16, 15, '', '10', 'Request #108 issued', '2025-10-20 10:54:47'),
(29, 65, 8, 8, '', '10', 'Request #109 issued', '2025-10-20 11:04:49'),
(30, 66, 15, 15, '', '10', 'Request #108 issued', '2025-10-20 13:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `subcategories`
--

CREATE TABLE `subcategories` (
  `id` int(11) NOT NULL,
  `account_title_id` int(11) NOT NULL,
  `subcategory_name` varchar(255) NOT NULL,
  `uacs_code` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subcategories`
--

INSERT INTO `subcategories` (`id`, `account_title_id`, `subcategory_name`, `uacs_code`) VALUES
(5, 4, 'Medical Equipment', '10605111000'),
(6, 5, 'Motor Vehicles', '1060601000'),
(7, 5, 'Trains', '1060602000'),
(8, 5, 'Watercrafts', '1060604000'),
(9, 5, 'Aircrafts and Aircrafts Ground Equipment', '1060603000'),
(10, 6, 'Furniture and Fisxtures', '1060701000'),
(11, 6, 'Books', '1060702000'),
(12, 7, 'Buildings', '1060401000'),
(13, 7, 'School Buildings', '1060402000'),
(14, 7, 'Hospitals and Health Centers', '1060403000'),
(15, 7, 'Markets', '1060404000'),
(16, 8, 'Road Networks', '1060301000'),
(17, 8, 'Flood Control Systems ', '1060302000'),
(18, 8, 'Sewer Systems', '1060303000'),
(19, 4, 'Office Equipment', '1060502000'),
(20, 4, 'Marine and Fishery Equipment', '1060505000');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `employee_id` int(25) UNSIGNED DEFAULT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `qty_returned` int(25) NOT NULL,
  `qty_re_issued` int(25) NOT NULL,
  `PAR_No` varchar(25) DEFAULT NULL,
  `ICS_No` varchar(25) DEFAULT NULL,
  `RRSP_No` varchar(25) NOT NULL,
  `transaction_type` enum('issue','return','re-issue','disposed') NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `return_date` datetime DEFAULT NULL,
  `re_issue_date` datetime DEFAULT NULL,
  `status` enum('Issued','Returned','Damaged','Re-issued','Partially Returned','Partially Re-Issued') NOT NULL DEFAULT 'Issued',
  `condition` varchar(25) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `employee_id`, `item_id`, `quantity`, `qty_returned`, `qty_re_issued`, `PAR_No`, `ICS_No`, `RRSP_No`, `transaction_type`, `transaction_date`, `return_date`, `re_issue_date`, `status`, `condition`, `remarks`) VALUES
(53, 5, 11, 2, 2, 1, NULL, '2025-10-0094', '2025-10-0002', 'return', '2025-10-11 00:00:00', '2025-10-11 00:00:00', '2025-10-11 00:00:00', 'Partially Re-Issued', '', 'TYUIOP\r\n'),
(54, 6, 11, 2, 1, 0, NULL, '2025-10-0097', '2025-10-0003', 'return', '2025-10-11 00:00:00', '2025-10-18 00:00:00', NULL, 'Partially Returned', '', ''),
(55, 6, 1, 1, 0, 0, '2025-10-0095', NULL, '', 'issue', '2025-10-11 00:00:00', '2028-10-11 00:00:00', NULL, 'Issued', '', 'jhgtfd'),
(56, 2, 12, 1, 1, 0, NULL, '2025-10-3002', '2025-10-0002', 'return', '2025-10-18 00:00:00', '2025-10-18 00:00:00', NULL, 'Returned', '', 'bulaga'),
(57, 2, 12, 1, 0, 0, NULL, '2025-10-3667', '', 'issue', '2025-10-18 00:00:00', '2028-10-18 00:00:00', NULL, 'Issued', '', ''),
(58, 6, 1, 1, 1, 0, NULL, '2025-10-0096', '2025-10-0004', 'return', '2025-10-19 00:00:00', '2025-10-20 00:00:00', NULL, 'Returned', '', 'wala na sira na'),
(59, 2, 1, 1, 0, 0, '2025-10-0093', NULL, '', 'issue', '2025-10-19 00:00:00', '2028-10-19 00:00:00', NULL, 'Issued', '', 'dftrfufytdeaewaghoiutr'),
(60, 6, 1, 1, 0, 0, NULL, '2025-10-0003', '', 'issue', '2025-10-20 00:00:00', '2028-10-20 00:00:00', NULL, 'Issued', '', 'hghvfhvdfgerg');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `symbol` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`, `symbol`) VALUES
(1, 'Piece', 'pc'),
(2, 'Dozen', 'dz'),
(3, 'Box', 'box'),
(4, 'Pack', 'pk'),
(5, 'Gram', 'g'),
(6, 'Kilogram', 'kg'),
(7, 'Milligram', 'mg'),
(8, 'Ton', 't'),
(9, 'Millimeter', 'mm'),
(10, 'Centimeter', 'cm'),
(11, 'Meter', 'm'),
(12, 'Kilometer', 'km'),
(13, 'Milliliter', 'mL'),
(14, 'Centiliter', 'cL'),
(15, 'Liter', 'L'),
(16, 'Gallon', 'gal');

-- --------------------------------------------------------

--
-- Table structure for table `unit_conversions`
--

CREATE TABLE `unit_conversions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `from_unit_id` int(11) NOT NULL,
  `to_unit_id` int(11) NOT NULL,
  `conversion_rate` decimal(12,6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `unit_conversions`
--

INSERT INTO `unit_conversions` (`id`, `item_id`, `from_unit_id`, `to_unit_id`, `conversion_rate`) VALUES
(1, 65, 3, 21, 12.000000),
(2, 64, 3, 21, 12.000000),
(3, 66, 3, 16, 10.000000),
(4, 67, 3, 14, 7.000000),
(5, 68, 3, 8, 15.000000),
(6, 68, 6, 9, 20.000000),
(7, 69, 4, 21, 2.000000);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `office` varchar(25) NOT NULL,
  `position` varchar(255) NOT NULL,
  `division` varchar(25) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(1) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `office`, `position`, `division`, `user_level`, `image`, `status`, `last_login`, `last_edited`, `created_at`) VALUES
(6, 'Meow Cat', 'user1', '12dea96fec20593566ab75692c9949596833adc9', '11', 'Taga kain', '3', 3, 'j4ub93go6.jpg', 1, '2025-10-19 21:56:45', '2025-10-20 03:56:45', '2025-10-05 22:59:16'),
(10, 'Administrator', 'Admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', '12', 'Admin', '1', 1, 'xokc7qp110.jpg', 1, '2025-10-20 06:27:00', '2025-10-20 12:27:00', '2025-10-05 22:59:16'),
(11, 'Jhoy Bangleg', 'User2', '12dea96fec20593566ab75692c9949596833adc9', '8', 'Taga Kain', '4', 3, '9djigqw11.jpg', 1, '2025-10-20 04:30:15', '2025-10-20 10:30:15', '2025-10-05 22:59:16'),
(12, 'Dave Nabaysan', 'user3', '12dea96fec20593566ab75692c9949596833adc9', '10', 'HR Manager', '3', 3, '3bct9r7912.jpg', 1, '2025-10-19 22:02:24', '2025-10-20 04:02:24', '2025-10-05 22:59:16'),
(28, 'kafka', 'IT', '12dea96fec20593566ab75692c9949596833adc9', '12', 'Taga Kain', '1', 2, 'b94yw6ny28.jpg', 1, '2025-10-19 18:52:59', '2025-10-20 03:24:55', '2025-10-05 22:59:16'),
(29, 'momo', 'momo', '12dea96fec20593566ab75692c9949596833adc9', '', 'Dean', '', 3, 'y1u2ee29.jpg', 0, '2025-10-18 04:02:56', '2025-10-18 10:02:56', '2025-10-05 22:59:16');

-- --------------------------------------------------------

--
-- Table structure for table `user_groups`
--

CREATE TABLE `user_groups` (
  `id` int(11) NOT NULL,
  `group_name` varchar(150) NOT NULL,
  `group_level` int(11) NOT NULL,
  `group_status` int(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user_groups`
--

INSERT INTO `user_groups` (`id`, `group_name`, `group_level`, `group_status`) VALUES
(1, 'Admin', 1, 1),
(2, 'IT', 2, 1),
(3, 'User', 3, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_title`
--
ALTER TABLE `account_title`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `archive`
--
ALTER TABLE `archive`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `base_units`
--
ALTER TABLE `base_units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `fund_clusters`
--
ALTER TABLE `fund_clusters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD UNIQUE KEY `stock_card` (`stock_card`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `media_id` (`media_id`),
  ADD KEY `fk_unit` (`unit_id`),
  ADD KEY `fk_items_base_unit` (`base_unit_id`);

--
-- Indexes for table `item_stocks_per_year`
--
ALTER TABLE `item_stocks_per_year`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `school_year_id` (`school_year_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_office_division` (`division_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `property_no` (`property_no`),
  ADD KEY `fk_properties_subcategory` (`subcategory_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `request_items`
--
ALTER TABLE `request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `req_id` (`req_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `school_years`
--
ALTER TABLE `school_years`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_school_year_semester` (`school_year`,`semester`);

--
-- Indexes for table `semicategories`
--
ALTER TABLE `semicategories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semi_exp_prop`
--
ALTER TABLE `semi_exp_prop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_inv_item_no` (`inv_item_no`);

--
-- Indexes for table `signatories`
--
ALTER TABLE `signatories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_title_id` (`account_title_id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `fk_transactions_employee` (`employee_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `unit_conversions`
--
ALTER TABLE `unit_conversions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `from_unit_id` (`from_unit_id`),
  ADD KEY `to_unit_id` (`to_unit_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_level` (`user_level`);

--
-- Indexes for table `user_groups`
--
ALTER TABLE `user_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `group_level` (`group_level`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_title`
--
ALTER TABLE `account_title`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `archive`
--
ALTER TABLE `archive`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `base_units`
--
ALTER TABLE `base_units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fund_clusters`
--
ALTER TABLE `fund_clusters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `item_stocks_per_year`
--
ALTER TABLE `item_stocks_per_year`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `request_items`
--
ALTER TABLE `request_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

--
-- AUTO_INCREMENT for table `school_years`
--
ALTER TABLE `school_years`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `semicategories`
--
ALTER TABLE `semicategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `semi_exp_prop`
--
ALTER TABLE `semi_exp_prop`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `signatories`
--
ALTER TABLE `signatories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `unit_conversions`
--
ALTER TABLE `unit_conversions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `user_groups`
--
ALTER TABLE `user_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `FK_items` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_items_base_unit` FOREIGN KEY (`base_unit_id`) REFERENCES `base_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_unit` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`);

--
-- Constraints for table `item_stocks_per_year`
--
ALTER TABLE `item_stocks_per_year`
  ADD CONSTRAINT `item_stocks_per_year_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `item_stocks_per_year_ibfk_2` FOREIGN KEY (`school_year_id`) REFERENCES `school_years` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `fk_office_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_items`
--
ALTER TABLE `request_items`
  ADD CONSTRAINT `request_items_ibfk_1` FOREIGN KEY (`req_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`);

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`account_title_id`) REFERENCES `account_title` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `semi_exp_prop` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `unit_conversions`
--
ALTER TABLE `unit_conversions`
  ADD CONSTRAINT `unit_conversions_ibfk_1` FOREIGN KEY (`from_unit_id`) REFERENCES `units` (`id`),
  ADD CONSTRAINT `unit_conversions_ibfk_2` FOREIGN KEY (`to_unit_id`) REFERENCES `base_units` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
