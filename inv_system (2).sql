-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3308
-- Generation Time: Oct 02, 2025 at 06:29 AM
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
(50, 26, 'items', '{\"id\":\"26\",\"fund_cluster\":\"GAA\",\"stock_card\":\"\",\"inv_item_no\":\"\",\"property_no\":\"H1-24-10-0094\",\"name\":\"Laptop Acer\",\"UOM\":\"unit\",\"quantity\":\"1\",\"unit_cost\":\"39000.00\",\"categorie_id\":\"14\",\"media_id\":\"12\",\"description\":\"S# 33506024476\",\"date_added\":\"2025-09-14 23:16:49\",\"last_edited\":null}', '2025-09-15 02:55:54', 0),
(51, 25, 'items', '{\"id\":\"25\",\"fund_cluster\":\"GAA\",\"stock_card\":\"001\",\"inv_item_no\":\"\",\"property_no\":\"\",\"name\":\"Alcohol\",\"UOM\":\"Bottle\",\"quantity\":\"100\",\"unit_cost\":\"500.00\",\"categorie_id\":\"1\",\"media_id\":\"0\",\"description\":\"500 ml\",\"date_added\":\"2025-09-14 19:14:09\",\"last_edited\":\"2025-09-14 23:22:06\"}', '2025-09-15 02:55:57', 0),
(52, 31, 'categories', '{\"id\":\"31\",\"name\":\"llllygugjjh\"}', '2025-09-15 04:05:13', 0),
(53, 30, 'categories', '{\"id\":\"30\",\"name\":\"Property\"}', '2025-09-19 13:51:59', 0),
(54, 14, 'categories', '{\"id\":\"14\",\"name\":\" High Value _Semi-expendables\"}', '2025-09-19 13:52:07', 0),
(55, 16, 'categories', '{\"id\":\"16\",\"name\":\"Low Value Semi-Expendables\"}', '2025-09-19 13:52:12', 0),
(56, 33, 'requests', '{\"id\":\"33\",\"requested_by\":\"11\",\"department\":\"College Of Apllied Technology\",\"date\":\"2025-09-15 12:24:11\",\"status\":\"Pending\",\"date_approved\":\"2025-09-15 12:24:11\"}', '2025-09-19 13:53:26', 0),
(57, 32, 'requests', '{\"id\":\"32\",\"requested_by\":\"11\",\"department\":\"College Of Apllied Technology\",\"date\":\"2025-09-15 12:10:26\",\"status\":\"Approved\",\"date_approved\":\"2025-09-15 12:11:27\"}', '2025-09-19 13:55:36', 0),
(58, 3, 'employees', '{\"id\":\"3\",\"first_name\":\"Jhoy\",\"last_name\":\"Bangleg\",\"middle_name\":\"Lugares\",\"position\":\"Taga Kain\",\"image\":\"1758522295_Cats.jpg\",\"office\":\"College Of Apllied Technology\",\"status\":\"Active\",\"created_at\":\"2025-09-22 14:24:55\",\"updated_at\":\"2025-09-22 14:24:55\"}', '2025-09-22 06:50:17', 0),
(59, 4, 'employees', '{\"id\":\"4\",\"first_name\":\"Jhoy\",\"last_name\":\"Bangleg\",\"middle_name\":\"Lugares\",\"position\":\"Taga Kain\",\"image\":\"1758712282_pfp1.jpg\",\"office\":\"College Of Apllied Technology\",\"status\":\"Active\",\"created_at\":\"2025-09-22 14:24:55\",\"updated_at\":\"2025-09-24 19:11:23\"}', '2025-09-24 11:31:07', 0),
(60, 24, 'categories', '{\"id\":\"24\",\"name\":\"Consumables\",\"object_code\":\"0\"}', '2025-09-30 09:57:22', 0),
(61, 29, 'categories', '{\"id\":\"29\",\"name\":\"GSO Supplies\",\"object_code\":\"0\"}', '2025-09-30 11:57:15', 0);

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
(1, 'Common Office Supplies'),
(2, 'Electrical Supplies '),
(34, 'GSO Supplies'),
(3, 'Janitorial Supplies'),
(28, 'Motorpool Supplies');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) UNSIGNED NOT NULL,
  `dpt` varchar(11) NOT NULL,
  `department` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dpt`, `department`) VALUES
(1, 'CAT', 'College Of Apllied Technology'),
(2, 'CED', 'College Of Education'),
(3, 'CCJE', 'College Of Criminal Justice Education'),
(5, 'SPMO', 'Supply and Property Management Office');

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
  `office` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `first_name`, `last_name`, `middle_name`, `position`, `image`, `office`, `status`, `created_at`, `updated_at`) VALUES
(1, 'John', 'Doe', 'A.', 'Software Engineer', '1758712163_pfp1.jpg', 'CAT', 'Active', '2025-09-20 11:21:57', '2025-09-27 15:58:42'),
(2, 'Jane', 'Smith', 'B.', 'HR Manager', '1758712274_pfp1.jpg', 'CED', 'Active', '2025-09-20 11:21:57', '2025-09-27 15:58:49');

-- --------------------------------------------------------

--
-- Table structure for table `fund_clusters`
--

CREATE TABLE `fund_clusters` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fund_clusters`
--

INSERT INTO `fund_clusters` (`id`, `name`, `description`) VALUES
(1, 'GAA', NULL),
(2, 'IGI', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) UNSIGNED NOT NULL,
  `fund_cluster` varchar(255) NOT NULL,
  `stock_card` varchar(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `UOM` varchar(255) NOT NULL,
  `quantity` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(25,2) DEFAULT NULL,
  `categorie_id` int(11) UNSIGNED NOT NULL,
  `media_id` int(11) DEFAULT 0,
  `description` varchar(255) NOT NULL,
  `date_added` datetime NOT NULL,
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `fund_cluster`, `stock_card`, `name`, `UOM`, `quantity`, `unit_cost`, `categorie_id`, `media_id`, `description`, `date_added`, `last_edited`) VALUES
(28, 'IGI', '50', 'Coupon Bond', 'Reams', '2', 500.00, 1, 31, 'Short', '2025-09-18 21:04:44', '2025-09-28 21:17:29'),
(31, 'GAA', '33', 'Cables', 'meter', '11', 900.00, 2, 20, '', '2025-09-18 21:20:50', '2025-09-19 12:20:22'),
(32, 'GAA', '02', 'Muriatic Acid', 'Bottle', '50', 450.00, 3, 21, '', '2025-09-18 21:21:38', '2025-09-29 00:28:53'),
(34, 'GAA', '36', 'Screw Driver', 'Box', '55', 455.00, 28, 23, '', '2025-09-18 21:23:05', '2025-09-24 20:47:37'),
(36, 'GAA', '024-BI', 'Lace', 'pc', '20', 150.00, 1, 33, 'ID Lace', '2025-09-26 13:05:21', '2025-09-28 21:17:29'),
(37, 'IGI', '0365-SW', 'Extension', 'Length', '30', 5000.00, 2, 34, '', '2025-09-26 13:06:31', '2025-09-29 00:08:58'),
(38, 'IGI', '0384', 'Weld Mask', 'pc', '1', 800.00, 28, 35, 'For welding', '2025-09-26 13:08:23', '2025-09-29 00:27:42');

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
(35, 'no_image.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `role` enum('admin','user','both') DEFAULT NULL,
  `message` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-info-circle',
  `color` varchar(50) DEFAULT 'text-muted',
  `link` varchar(255) DEFAULT '#',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 1, 'OSS', '2025-10-01 14:59:27', '2025-10-01 14:59:27'),
(3, 1, 'BUDGET', '2025-10-01 14:59:27', '2025-10-01 14:59:51'),
(4, 1, 'MOTORPOOL', '2025-10-01 15:00:16', '2025-10-01 15:00:16'),
(5, 3, 'CED', '2025-10-01 15:00:37', '2025-10-01 15:00:37'),
(6, 3, 'CAT', '2025-10-01 15:00:45', '2025-10-01 15:00:45'),
(7, 4, 'LIBRARY', '2025-10-01 15:19:52', '2025-10-01 15:19:52'),
(8, 4, 'GUIDANCE', '2025-10-01 15:19:52', '2025-10-01 15:19:52'),
(9, 4, 'CLINIC', '2025-10-01 15:19:52', '2025-10-01 15:19:52');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) UNSIGNED NOT NULL,
  `ris_no` varchar(25) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Ready','Archived') DEFAULT 'Pending',
  `remarks` varchar(255) NOT NULL,
  `date_approved` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `ris_no`, `requested_by`, `date`, `status`, `remarks`, `date_approved`) VALUES
(34, '0', 11, '2025-09-18 13:28:17', 'Approved', '', '2025-09-24 14:41:39'),
(35, '0', 11, '2025-09-18 13:28:25', 'Approved', '', '2025-09-18 14:01:23'),
(36, '2025-09-0007', 12, '2025-09-18 13:29:07', 'Approved', '', '2025-09-29 09:02:03'),
(37, '0', 12, '2025-09-18 13:29:16', 'Approved', '', '2025-09-24 14:41:34'),
(38, '0', 12, '2025-09-18 13:29:28', 'Approved', '', '2025-09-18 14:01:18'),
(39, '0', 6, '2025-09-18 13:29:49', 'Approved', '', '2025-09-24 14:40:56'),
(40, '0', 6, '2025-09-18 13:30:03', 'Pending', '', '2025-09-18 13:30:03'),
(41, '0', 6, '2025-09-18 13:30:11', 'Approved', '', '2025-09-18 14:01:13'),
(42, '0', 11, '2025-09-19 04:20:22', 'Approved', '', '2025-09-19 04:21:57'),
(43, '0', 1, '2025-09-20 12:57:23', 'Approved', '', '2025-09-24 14:41:29'),
(44, '0', 2, '2025-09-21 04:58:02', 'Pending', '', '2025-09-21 04:58:02'),
(45, '0', 2, '2025-09-24 12:44:50', 'Approved', '', '2025-09-24 14:40:50'),
(46, '0', 1, '2025-09-24 12:47:37', 'Pending', '', '2025-09-24 12:47:37'),
(47, '2025-10-0000', 10, '2025-09-24 12:51:07', 'Pending', '', '2025-10-01 14:41:34'),
(48, '2025-10-0000', 6, '2025-09-24 14:43:16', 'Pending', '', '2025-10-01 10:09:47'),
(49, '2025', 10, '2025-09-28 16:08:58', 'Approved', '', '2025-09-29 08:57:03'),
(50, '2025', 10, '2025-09-28 16:27:42', 'Approved', '', '2025-09-29 08:44:26'),
(51, '2025', 12, '2025-09-28 16:28:53', 'Approved', '', '2025-09-29 08:28:40');

-- --------------------------------------------------------

--
-- Table structure for table `request_items`
--

CREATE TABLE `request_items` (
  `id` int(11) UNSIGNED NOT NULL,
  `req_id` int(11) UNSIGNED NOT NULL,
  `item_id` int(11) UNSIGNED NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) DEFAULT NULL,
  `remarks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `request_items`
--

INSERT INTO `request_items` (`id`, `req_id`, `item_id`, `qty`, `price`, `remarks`) VALUES
(38, 34, 28, 4, NULL, ''),
(40, 36, 31, 3, NULL, ''),
(42, 37, 28, 1, NULL, ''),
(43, 38, 34, 1, NULL, ''),
(46, 39, 28, 2, NULL, ''),
(48, 40, 34, 2, NULL, ''),
(50, 41, 32, 2, NULL, ''),
(51, 42, 31, 1, NULL, ''),
(52, 42, 32, 1, NULL, ''),
(54, 42, 34, 1, NULL, ''),
(56, 43, 28, 2, NULL, ''),
(57, 44, 32, 2, NULL, ''),
(58, 45, 28, 3, NULL, ''),
(59, 46, 34, 1, NULL, ''),
(60, 47, 32, 2, NULL, ''),
(61, 48, 28, 1, NULL, ''),
(62, 49, 37, 4, NULL, ''),
(63, 50, 38, 1, NULL, ''),
(64, 51, 32, 5, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `semi_exp_prop`
--

CREATE TABLE `semi_exp_prop` (
  `id` int(10) UNSIGNED NOT NULL,
  `fund_cluster` varchar(50) NOT NULL,
  `inv_item_no` varchar(255) DEFAULT NULL,
  `property_no` varchar(255) DEFAULT NULL,
  `item_description` varchar(255) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `unit_cost` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_qty` int(25) NOT NULL,
  `qty_left` int(25) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `estimated_use` varchar(50) DEFAULT NULL,
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  `status` enum('available','issued','lost','returned','disposed','archived') DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semi_exp_prop`
--

INSERT INTO `semi_exp_prop` (`id`, `fund_cluster`, `inv_item_no`, `property_no`, `item_description`, `unit`, `unit_cost`, `total_qty`, `qty_left`, `subcategory_id`, `estimated_use`, `date_added`, `last_edited`, `status`) VALUES
(1, 'GAA', '908453-3487', NULL, 'Laptop ACER', 'Unit', 39000.00, 1, 1, 10, '', '2025-09-20 18:29:42', '2025-10-01 20:05:56', 'available'),
(2, 'IGI', NULL, 'H1-24-10-0094', 'Fire Extinguisher', 'Bottle', 5000.00, 1, 1, 19, '5 Years', '2025-09-21 23:12:30', '2025-10-01 22:14:16', 'available'),
(11, 'GAA', 'GAA-908453-3487', NULL, 'Chair', 'Unit', 39000.00, 17, 15, 5, '', '2025-09-22 11:40:04', '2025-10-01 22:14:30', 'available'),
(12, 'GAA', '12345-678', NULL, 'Lapis', 'Box', 17.00, 20, 10, 14, '', '2025-09-22 12:43:15', '2025-10-01 22:14:39', 'available'),
(13, 'GAA', NULL, '345-89f', 'Tissue huhu', 'roll', 75.00, 12, 12, 6, '', '2025-09-28 18:41:46', '2025-10-01 22:14:53', 'available');

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
-- Table structure for table `supply_items`
--

CREATE TABLE `supply_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `item_description` text NOT NULL,
  `qty` int(11) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `supply_id` varchar(25) DEFAULT NULL,
  `remarks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_items`
--

INSERT INTO `supply_items` (`id`, `request_id`, `item_description`, `qty`, `unit`, `supply_id`, `remarks`) VALUES
(22, 29, 'Drum', 2, 'set', '', ''),
(23, 30, 'Kape', 20, 'Sachet', '2', ''),
(24, 31, 'Laptop ACER', 1, 'Unit', '11', ''),
(25, 32, 'Cup', 10, '200', '12', '');

-- --------------------------------------------------------

--
-- Table structure for table `supply_requests`
--

CREATE TABLE `supply_requests` (
  `id` int(11) NOT NULL,
  `req_id` int(11) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `request_date` date NOT NULL,
  `status` enum('pending','issued','denied','archived','lost','damaged') DEFAULT 'pending',
  `remarks` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `supply_requests`
--

INSERT INTO `supply_requests` (`id`, `req_id`, `requester_name`, `position`, `request_date`, `status`, `remarks`, `created_at`) VALUES
(29, 12, 'Dave A. Nabaysan', 'Taga Kain', '2025-09-24', 'pending', '', '2025-09-24 12:41:34'),
(30, 2, 'Jane Smith', 'HR Manager', '2025-09-24', 'pending', '', '2025-09-24 12:44:12'),
(31, 1, 'John Doe', 'Software Engineer', '2025-09-26', 'issued', '', '2025-09-26 10:42:58'),
(32, 12, 'Dave A. Nabaysan', 'Taga Kain', '2025-09-28', 'issued', '', '2025-09-28 06:40:49');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `employee_id` int(25) UNSIGNED DEFAULT NULL,
  `user_id` int(25) UNSIGNED DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `item_id` int(10) UNSIGNED NOT NULL,
  `quantity` int(11) NOT NULL,
  `PAR_No` varchar(25) DEFAULT NULL,
  `ICS_No` varchar(25) DEFAULT NULL,
  `RRSP_No` int(25) NOT NULL,
  `transaction_type` enum('issue','return','re-issue','disposed') NOT NULL,
  `transaction_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `employee_id`, `user_id`, `request_id`, `item_id`, `quantity`, `PAR_No`, `ICS_No`, `RRSP_No`, `transaction_type`, `transaction_date`, `status`, `remarks`) VALUES
(31, 1, NULL, 31, 11, 1, NULL, 'ICS-2025-68d90570c1200', 0, '', '2025-09-28 17:52:48', '', ''),
(33, NULL, 12, 32, 12, 10, 'PAR-2025-68d9119311d1d', NULL, 0, '', '2025-09-28 18:44:35', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) UNSIGNED NOT NULL,
  `name` varchar(60) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `user_level` int(11) NOT NULL,
  `image` varchar(255) DEFAULT 'no_image.jpg',
  `status` int(1) NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `last_edited` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `username`, `password`, `department`, `position`, `user_level`, `image`, `status`, `last_login`, `last_edited`) VALUES
(6, 'Meow Cat', 'user1', '12dea96fec20593566ab75692c9949596833adc9', '3', 'Taga kain', 3, 'j4ub93go6.jpg', 1, '2025-09-28 08:36:14', '2025-09-28 14:36:14'),
(10, 'Administrator', 'Admin', 'd033e22ae348aeb5660fc2140aec35850c4da997', '5', 'Admin', 1, 'qzzjg8gn10.jpg', 1, '2025-10-02 02:54:35', '2025-10-02 08:54:35'),
(11, 'Jhoy L. Bangleg', 'User2', '12dea96fec20593566ab75692c9949596833adc9', '1', 'Taga Kain', 3, '9djigqw11.jpg', 1, '2025-10-02 02:55:51', '2025-10-02 08:55:51'),
(12, 'Dave A. Nabaysan', 'user3', '12dea96fec20593566ab75692c9949596833adc9', '2', 'Taga Kain', 3, '3bct9r7912.jpg', 1, '2025-10-02 02:56:04', '2025-10-02 08:56:04'),
(28, 'kafka', 'IT', '12dea96fec20593566ab75692c9949596833adc9', '1', 'Taga Kain', 2, 'b94yw6ny28.jpg', 1, '2025-09-28 08:34:01', '2025-09-28 14:34:01'),
(29, 'momo', 'momo', '12dea96fec20593566ab75692c9949596833adc9', '2', 'Dean', 3, 'y1u2ee29.jpg', 0, '2025-09-28 12:06:47', '2025-09-28 18:06:47');

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
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `divisions`
--
ALTER TABLE `divisions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

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
  ADD KEY `media_id` (`media_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_office_division` (`division_id`);

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
-- Indexes for table `semi_exp_prop`
--
ALTER TABLE `semi_exp_prop`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_property_no` (`property_no`),
  ADD UNIQUE KEY `unique_inv_item_no` (`inv_item_no`);

--
-- Indexes for table `signatories`
--
ALTER TABLE `signatories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_title_id` (`account_title_id`);

--
-- Indexes for table `supply_items`
--
ALTER TABLE `supply_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `request_id` (`request_id`);

--
-- Indexes for table `supply_requests`
--
ALTER TABLE `supply_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `fk_transactions_employee` (`employee_id`),
  ADD KEY `fk_transactions_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_level` (`user_level`),
  ADD KEY `department` (`department`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `divisions`
--
ALTER TABLE `divisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fund_clusters`
--
ALTER TABLE `fund_clusters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `request_items`
--
ALTER TABLE `request_items`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

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
-- AUTO_INCREMENT for table `subcategories`
--
ALTER TABLE `subcategories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `supply_items`
--
ALTER TABLE `supply_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `supply_requests`
--
ALTER TABLE `supply_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
  ADD CONSTRAINT `FK_products` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `offices`
--
ALTER TABLE `offices`
  ADD CONSTRAINT `fk_office_division` FOREIGN KEY (`division_id`) REFERENCES `divisions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `request_items`
--
ALTER TABLE `request_items`
  ADD CONSTRAINT `request_items_ibfk_1` FOREIGN KEY (`req_id`) REFERENCES `requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `request_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subcategories`
--
ALTER TABLE `subcategories`
  ADD CONSTRAINT `subcategories_ibfk_1` FOREIGN KEY (`account_title_id`) REFERENCES `account_title` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supply_items`
--
ALTER TABLE `supply_items`
  ADD CONSTRAINT `supply_items_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `supply_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_transactions_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `semi_exp_prop` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `transactions_ibfk_3` FOREIGN KEY (`request_id`) REFERENCES `supply_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `FK_user` FOREIGN KEY (`user_level`) REFERENCES `user_groups` (`group_level`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
