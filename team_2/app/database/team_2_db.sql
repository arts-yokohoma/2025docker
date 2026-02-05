-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 05, 2026 at 02:44 PM
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
-- Database: `team_2_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(6) UNSIGNED NOT NULL,
  `customer_name` varchar(50) NOT NULL,
  `phonenumber` varchar(15) NOT NULL,
  `address` varchar(100) NOT NULL,
  `pizza_type` varchar(50) NOT NULL,
  `quantity` int(3) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT 'Pending',
  `reject_reason` text DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `return_time` datetime DEFAULT NULL,
  `start_time` datetime DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `phonenumber`, `address`, `pizza_type`, `quantity`, `order_date`, `status`, `reject_reason`, `departure_time`, `return_time`, `start_time`, `postal_code`) VALUES
(1, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 3, '2026-01-19 10:15:10', 'Completed', NULL, '2026-01-15 03:46:34', '2026-01-19 19:15:10', NULL, NULL),
(2, 'MYINTMYAT AUNG', '08061954340', 'yokohama', 'S', 9, '2026-01-14 18:45:13', 'Completed', NULL, '2026-01-15 03:44:56', '2026-01-15 03:45:13', NULL, NULL),
(3, 'bnn', '909', 'yokohama', 'S', 7, '2026-01-19 10:15:20', 'Completed', NULL, '2026-01-15 04:21:03', '2026-01-19 19:15:20', NULL, NULL),
(4, 'dd', '009', 'yokohama', 'S', 2, '2026-01-28 14:23:25', 'Completed', NULL, '2026-01-19 19:20:36', '2026-01-28 23:23:25', NULL, NULL),
(5, 'aa', '111', 'yokohama', 'S', 2, '2026-01-28 14:22:04', 'Completed', NULL, '2026-01-19 19:19:53', '2026-01-28 23:22:04', NULL, NULL),
(6, 'xx', '788', 'yokohama', 'S', 6, '2026-01-14 19:21:10', 'Completed', NULL, '2026-01-15 04:16:52', '2026-01-15 04:21:10', NULL, NULL),
(8, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 2, '2026-01-28 16:56:58', 'Completed', NULL, '2026-01-29 00:14:02', '2026-01-29 01:56:58', '2026-01-28 20:46:53', '1111111'),
(9, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 5, '2026-01-28 15:11:28', 'Rejected', NULL, NULL, NULL, NULL, '2210021'),
(10, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通', 'S', 2, '2026-01-28 15:11:15', 'Rejected', NULL, NULL, NULL, NULL, '2210021'),
(11, 'MYINTMYAT AUNG', '08061954340', '３－３６５メゾンドエトレ－ヌ３０８', 'S', 1, '2026-01-28 15:13:27', 'Completed', NULL, '2026-01-29 00:11:35', '2026-01-29 00:13:27', '2026-01-28 23:22:11', ''),
(18, 'bgfdsfd', 'asfgd', 'sadfgd', 'S', 1, '2026-01-28 16:50:51', 'Completed', NULL, '2026-01-29 00:41:12', '2026-01-29 01:50:51', '2026-01-29 00:25:37', '2210021'),
(19, 'jhcghjk', 'puoyfu', 'poigfuih', 'S', 1, '2026-01-28 15:24:34', 'Rejected', NULL, NULL, NULL, NULL, '2210021'),
(20, ';kpojihgu', '000', 'loiguf', 'S', 1, '2026-01-28 16:57:04', 'Completed', NULL, '2026-01-29 00:41:06', '2026-01-29 01:57:04', '2026-01-29 00:25:43', '2210021'),
(22, 'iuyte', '111', 'p9y8t7f', 'S', 1, '2026-01-28 16:51:01', 'Completed', NULL, '2026-01-29 00:43:10', '2026-01-29 01:51:01', '2026-01-29 00:42:52', '2200073'),
(23, 'jo8t7f', '222', 'ouyufy', 'S', 1, '2026-01-28 16:01:30', 'Rejected', NULL, NULL, NULL, NULL, '2200051'),
(24, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:01:01', 'Rejected', NULL, NULL, NULL, NULL, '2210021'),
(25, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:00:48', 'Rejected', NULL, NULL, NULL, NULL, '2210021'),
(26, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:57:43', 'Rejected', 'out', NULL, NULL, NULL, '2210021'),
(27, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:56:50', 'Completed', NULL, '2026-01-29 01:55:54', '2026-01-29 01:56:50', NULL, '2210021'),
(28, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:40:35', 'Rejected', 'out', NULL, NULL, NULL, '2210021'),
(29, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 16:46:23', 'Rejected', 'ffff', NULL, NULL, NULL, '2210021'),
(30, 'ik', '222', 'pioyu', 'S', 1, '2026-01-28 17:02:10', 'Rejected', 'hg', NULL, NULL, NULL, '2210021'),
(31, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 17:27:39', 'Rejected', 'ljihh', NULL, NULL, NULL, '2210021'),
(32, '76756', '9999', 'ljoigugf', 'S', 1, '2026-01-28 17:39:10', 'Completed', NULL, '2026-01-29 02:31:28', '2026-01-29 02:39:10', NULL, '2210021'),
(33, 'oiuycg', 'puoyiuf', 'puoiyuyfg', 'S', 1, '2026-01-28 17:39:33', 'Rejected', 'bh', NULL, NULL, NULL, '2210021'),
(34, 'ko', '000', 'pi', 'S', 1, '2026-01-28 18:01:36', 'Completed', NULL, '2026-01-29 03:01:29', '2026-01-29 03:01:36', NULL, '2210021'),
(35, 'MYINTMYAT AUNG', '08061954340', '子安通', 'S', 1, '2026-01-28 17:51:24', 'Completed', NULL, '2026-01-29 02:49:57', '2026-01-29 02:51:24', NULL, '2210021'),
(36, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 sdfht', 'M', 1, '2026-02-03 14:52:20', 'Completed', NULL, '2026-01-29 03:22:10', '2026-02-03 23:52:20', NULL, '2210021'),
(37, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 [ipouyt', 'M', 1, '2026-02-03 14:49:53', 'Rejected', 'out', NULL, NULL, NULL, '2210021'),
(38, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 oiuyf', 'M', 1, '2026-02-03 14:51:36', 'Completed', NULL, '2026-02-03 23:50:59', '2026-02-03 23:51:36', NULL, '2210021'),
(39, 'lin', '080', '神奈川県横浜市西区中央 234', 'M', 1, '2026-02-03 15:13:37', 'Completed', NULL, '2026-02-04 00:03:08', '2026-02-04 00:13:37', NULL, '2200051'),
(40, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 ３－３６５メゾンドエトレ－ヌ３０８', 'M', 1, '2026-02-03 15:18:25', 'Completed', NULL, '2026-02-04 00:15:05', '2026-02-04 00:18:25', NULL, '2210021'),
(41, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 aaa', 'M', 1, '2026-02-03 15:41:12', 'Completed', NULL, '2026-02-04 00:27:28', '2026-02-04 00:41:12', '2026-02-04 00:27:04', '2210021'),
(42, 'line', '000', '神奈川県横浜市西区中央 unt', 'M', 1, '2026-02-03 15:41:21', 'Completed', NULL, '2026-02-04 00:33:44', '2026-02-04 00:41:21', '2026-02-04 00:33:24', '2200051'),
(43, 'aaa', '999', '神奈川県横浜市西区岡野 poi', 'M', 1, '2026-02-03 15:37:25', 'Rejected', 'lee pal ya meal kwa', NULL, NULL, NULL, '2200073'),
(44, 'uuu', '888', '神奈川県横浜市西区中央 joiu', 'M', 1, '2026-02-03 16:10:28', 'Completed', NULL, '2026-02-04 01:09:21', '2026-02-04 01:10:28', '2026-02-04 01:08:32', '2200051'),
(45, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市西区中央 ３－３６５メゾンドエトレ－ヌ３０８', 'M', 1, '2026-02-03 15:42:20', 'Rejected', 'not way', NULL, NULL, NULL, '2200051'),
(46, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 ３－３６５メゾンドエトレ－ヌ３０８', 'M', 1, '2026-02-03 16:10:31', 'Completed', NULL, '2026-02-04 01:09:59', '2026-02-04 01:10:31', '2026-02-04 01:08:38', '2210021'),
(47, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 ３－３６５メゾンドエトレ－ヌ３０８', 'M', 1, '2026-02-03 16:10:35', 'Completed', NULL, '2026-02-04 01:10:01', '2026-02-04 01:10:35', '2026-02-04 01:08:53', '2210021'),
(48, 'hhh', '090', '神奈川県横浜市西区中央 lk', 'M', 1, '2026-02-03 16:10:38', 'Completed', NULL, '2026-02-04 01:10:03', '2026-02-04 01:10:38', '2026-02-04 01:08:55', '2200051'),
(49, 'iii', '0009', '神奈川県横浜市神奈川区子安通 ouyutytd', 'M', 1, '2026-02-03 16:10:40', 'Completed', NULL, '2026-02-04 01:10:08', '2026-02-04 01:10:40', '2026-02-04 01:08:57', '2210021'),
(50, 'piouiyufy', ';kig', '神奈川県横浜市西区岡野 pouiu', 'M', 1, '2026-02-03 16:10:42', 'Completed', NULL, '2026-02-04 01:10:10', '2026-02-04 01:10:42', '2026-02-04 01:08:59', '2200073'),
(51, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 9876', 'M', 1, '2026-02-03 16:10:47', 'Completed', NULL, '2026-02-04 01:10:12', '2026-02-04 01:10:47', '2026-02-04 01:09:02', '2210021'),
(52, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 koi', 'M', 1, '2026-02-03 17:38:33', 'Delivering', NULL, '2026-02-04 02:38:33', NULL, '2026-02-04 02:38:24', '2210021'),
(53, 'ljiuy', '000', '神奈川県横浜市西区岡野 piuoiguh', 'M', 1, '2026-02-03 17:38:05', 'Pending', NULL, NULL, NULL, NULL, '2200073'),
(54, 'pio', '090', '神奈川県横浜市西区中央 piuoi', 'M', 1, '2026-02-03 17:39:04', 'Pending', NULL, NULL, NULL, NULL, '2200051'),
(55, '09y8tiuj', '111', '神奈川県横浜市西区岡野 ;kpiouiyuh', 'M', 1, '2026-02-03 17:39:41', 'Pending', NULL, NULL, NULL, NULL, '2200073'),
(56, 'MYINTMYAT AUNG', '08061954340', '神奈川県横浜市神奈川区子安通 poyutfy', 'M', 1, '2026-02-03 17:40:29', 'Pending', NULL, NULL, NULL, NULL, '2210021');

-- --------------------------------------------------------

--
-- Table structure for table `partner_shops`
--

CREATE TABLE `partner_shops` (
  `id` int(6) UNSIGNED NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `website_url` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_shops`
--

INSERT INTO `partner_shops` (`id`, `shop_name`, `latitude`, `longitude`, `website_url`) VALUES
(1, 'school ', 35.46378365, 139.60973245, 'http://localhost:3000/customer/index.php'),
(2, 'home', 35.48862775, 139.65907190, 'https://maps.app.goo.gl/5t6U4XhiC3cznJSF8');

-- --------------------------------------------------------

--
-- Table structure for table `shop_settings`
--

CREATE TABLE `shop_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `kitchen_staff` int(11) DEFAULT 1,
  `delivery_drivers` int(11) DEFAULT 1,
  `capacity_per_staff` int(11) DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_settings`
--

INSERT INTO `shop_settings` (`id`, `kitchen_staff`, `delivery_drivers`, `capacity_per_staff`) VALUES
(1, 2, 2, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_shops`
--
ALTER TABLE `partner_shops`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shop_settings`
--
ALTER TABLE `shop_settings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `partner_shops`
--
ALTER TABLE `partner_shops`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
