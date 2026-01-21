-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- ホスト: 127.0.0.1
-- 生成日時: 2025-12-24 04:32:02
-- サーバのバージョン： 10.4.32-MariaDB
-- PHP のバージョン: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- データベース: `pizza_db`
--

-- --------------------------------------------------------

--
-- テーブルの構造 `allowed_zipcodes`
--

CREATE TABLE `allowed_zipcodes` (
  `code` varchar(20) DEFAULT NULL,
  `area_name` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `allowed_zipcodes`
--

INSERT INTO `allowed_zipcodes` (`code`, `area_name`) VALUES
('123-4567', 'Zone A');

-- --------------------------------------------------------

--
-- テーブルの構造 `delivery_staff`
--

CREATE TABLE `delivery_staff` (
  `staff_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `menu`
--

CREATE TABLE `menu` (
  `id` int(11) NOT NULL,
  `pizza_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `price` int(11) DEFAULT NULL,
  `available` tinyint(1) DEFAULT 1,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `menu`
--

INSERT INTO `menu` (`id`, `pizza_name`, `description`, `size`, `price`, `available`, `image_url`) VALUES
(1, 'マルゲリータ', 'トマト、モッツァレラチーズ', 'M', 900, 1, 'images/pizza1.jpg'),
(2, 'ペパロニ', 'ペパロニソーセージ、チーズ', 'L', 1200, 1, 'images/pizza2.jpg'),
(3, 'ハワイアン', 'チキン、パイナップル、チーズ', 'M', 1000, 1, 'images/pizza3.jpg'),
(4, 'マリナーラ', 'トマトソース、にんにく、オリーブオイル、オレガノ\r\n', 'M', 850, 1, 'images/pizza4.jpg'),
(5, 'シーフードピザ', 'エビ、イカ、貝類、チーズ', 'L', 1350, 1, 'images/pizza5.jpg'),
(6, 'BBQチキンピザ', 'BBQソース、チキン、玉ねぎ、チーズ', 'M', 780, 1, 'images/pizza6.jpg'),
(7, 'シカゴ・ディープディッシュピザ', 'たっぷりのチーズ、トマトソース', 'M', NULL, 1, 'images/pizza7.jpg'),
(8, 'ニューヨークスタイルピザ', 'トマトソース、チーズ\r\n', 'L', 2200, 1, 'images/pizza8.jpg'),
(9, 'クアトロ・フォルマッジ', '4種類のチーズ（モッツァレラ、ゴルゴンゾーラ、パルメザンなど）\r\n', 'M', 1700, 1, 'images/pizza9.png\r\n\r\n\r\n\r\n'),
(10, 'ベジタリアンピザ', 'きのこ、ピーマン、玉ねぎ、オリーブ\r\n', 'M', 550, 1, 'images/pizza10.jpg');

-- --------------------------------------------------------

--
-- テーブルの構造 `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `pizza_size` varchar(20) DEFAULT NULL,
  `zip_code` varchar(20) DEFAULT NULL,
  `status` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_price` int(11) DEFAULT NULL,
  `estimated_time` datetime DEFAULT NULL,
  `delivery_staff_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` int(11) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `product_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `created_at`, `product_name`) VALUES
(0, 0, 0, 0, 0, NULL, '');

-- --------------------------------------------------------

--
-- テーブルの構造 `product_details`
--

CREATE TABLE `product_details` (
  `id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `calories` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- テーブルの構造 `settings`
--

CREATE TABLE `settings` (
  `setting_key` varchar(50) DEFAULT NULL,
  `setting_value` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- テーブルのデータのダンプ `settings`
--

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('total_drivers', 2);

--
-- ダンプしたテーブルのインデックス
--

--
-- テーブルのインデックス `delivery_staff`
--
ALTER TABLE `delivery_staff`
  ADD PRIMARY KEY (`staff_id`);

--
-- テーブルのインデックス `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- テーブルのインデックス `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- テーブルのインデックス `product_details`
--
ALTER TABLE `product_details`
  ADD PRIMARY KEY (`id`);

--
-- ダンプしたテーブルの AUTO_INCREMENT
--

--
-- テーブルの AUTO_INCREMENT `menu`
--
ALTER TABLE `menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- テーブルの AUTO_INCREMENT `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;