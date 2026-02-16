-- phpMyAdmin SQL Dump
-- version 5.2.1
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 03:00 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+09:00"; -- Tokyo Time Zone

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `team_2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `delivery_slots` (NEW for Rider Management)
--

CREATE TABLE `delivery_slots` (
  `slot_id` int(11) NOT NULL AUTO_INCREMENT,
  `status` enum('Free','Busy') NOT NULL DEFAULT 'Free',
  `next_available_time` datetime DEFAULT NULL,
  PRIMARY KEY (`slot_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `delivery_slots` (Initial 2 Riders)
--

INSERT INTO `delivery_slots` (`slot_id`, `status`, `next_available_time`) VALUES
(1, 'Free', NULL),
(2, 'Free', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(50) NOT NULL,
  `phonenumber` varchar(15) NOT NULL,
  `address` varchar(100) NOT NULL,
  `pizza_type` varchar(50) NOT NULL,
  `quantity` int(3) NOT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL, -- Added for Location
  `longitude` decimal(11,8) DEFAULT NULL, -- Added for Location
  `assigned_slot_id` int(11) DEFAULT NULL, -- Added for Smart Routing
  `status` varchar(20) DEFAULT 'Pending',
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `start_time` datetime DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `return_time` datetime DEFAULT NULL,
  `reject_reason` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders` (Sample Data kept mostly clean)
--

INSERT INTO `orders` (`id`, `customer_name`, `phonenumber`, `address`, `pizza_type`, `quantity`, `postal_code`, `status`, `order_date`) VALUES
(1, 'Sample Customer', '08012345678', 'Yokohama Station', 'M', 2, '2200011', 'Completed', '2026-02-05 10:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `partner_shops`
--

CREATE TABLE `partner_shops` (
  `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT,
  `shop_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `website_url` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_shops`
--

INSERT INTO `partner_shops` (`id`, `shop_name`, `latitude`, `longitude`, `website_url`) VALUES
(1, 'School Shop', 35.46378365, 139.60973245, 'http://localhost:3000/customer/index.php'),
(2, 'Home Shop', 35.48862775, 139.65907190, 'https://maps.app.goo.gl/5t6U4XhiC3cznJSF8');

-- --------------------------------------------------------

--
-- Table structure for table `shop_settings`
--

CREATE TABLE `shop_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `kitchen_staff` int(11) DEFAULT 1,
  `delivery_drivers` int(11) DEFAULT 1,
  `capacity_per_staff` int(11) DEFAULT 2,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_settings`
--

INSERT INTO `shop_settings` (`id`, `kitchen_staff`, `delivery_drivers`, `capacity_per_staff`) VALUES
(1, 2, 2, 2);

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;