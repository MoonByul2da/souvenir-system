-- phpMyAdmin SQL Dump
-- version 4.8.5
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 17, 2026 at 05:27 AM
-- Server version: 10.1.38-MariaDB
-- PHP Version: 5.6.40

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `souvenir_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `souvenir_admins` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `souvenir_admins`
--

INSERT INTO `souvenir_admins` (`admin_id`, `username`, `password`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `souvenir_items` (
  `item_id` int(11) NOT NULL,
  `item_name` varchar(150) NOT NULL,
  `item_image` varchar(255) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `souvenir_items`
--

INSERT INTO `souvenir_items` (`item_id`, `item_name`, `item_image`, `stock`) VALUES
(1, 'แก้วน้ำเก็บความเย็น HUSOC', 'glass.jpeg', 20),
(2, 'ปากกาทีระลึก HUSOC', 'pen.jpeg', 40),
(3, 'ถุงผ้าลดโลกร้อน HUSOC', 'bag.jpeg', 70);

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `souvenir_requests` (
  `request_id` int(11) NOT NULL,
  `doc_no` varchar(50) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `requester_prefix` varchar(50) DEFAULT NULL,
  `requester_position` varchar(150) DEFAULT NULL,
  `requester_phone` varchar(50) DEFAULT NULL,
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `purpose` text,
  `date_required` date DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `souvenir_requests`
--

INSERT INTO `souvenir_requests` (`request_id`, `doc_no`, `user_id`, `requester_prefix`, `requester_position`, `requester_phone`, `request_date`, `purpose`, `date_required`, `status`) VALUES
(12, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-16 03:34:58', 'test', '2026-02-18', 'Approved'),
(13, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-16 03:37:20', 'test', '2026-02-18', 'Approved'),
(14, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-16 07:36:09', 'test', '2026-02-19', 'Rejected'),
(15, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-16 13:40:45', 'test', '2026-02-19', 'Pending'),
(16, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-16 13:47:21', 'test', '2026-02-19', 'Pending'),
(17, '', 2, 'นาง', 'test', '0123456789', '2026-02-16 15:42:44', 'test', '2026-02-20', 'Approved'),
(18, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-17 09:22:53', 'test', '2026-02-20', 'Approved'),
(19, '', 1, 'นาย', 'นิสิตฝึกงาน', '081-111-2222', '2026-02-17 09:28:21', 'test', '2026-02-19', 'Approved'),
(20, '', 2, 'นาง', 'test', '0123456789', '2026-02-17 09:29:02', 'test', '2026-02-19', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `souvenir_request_details`
--

CREATE TABLE `souvenir_request_details` (
  `id` int(11) NOT NULL,
  `request_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `qty_requested` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT 'ชิ้น',
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `souvenir_request_details`
--

INSERT INTO `souvenir_request_details` (`id`, `request_id`, `item_id`, `qty_requested`, `unit`, `remark`) VALUES
(1, 1, 1, 1, 'ชิ้น', ''),
(2, 2, 1, 1, 'ชิ้น', ''),
(3, 3, 1, 1, 'ชิ้น', ''),
(4, 4, 1, 1, 'ชิ้น', ''),
(5, 5, 2, 1, 'ชิ้น', ''),
(6, 6, 1, 1, 'ชิ้น', ''),
(7, 7, 1, 1, 'ชิ้น', ''),
(8, 8, 1, 1, 'ชิ้น', ''),
(9, 8, 2, 1, 'ชิ้น', ''),
(10, 8, 1, 1, 'ชิ้น', ''),
(11, 8, 1, 1, 'ชิ้น', ''),
(12, 8, 3, 1, 'ชิ้น', ''),
(13, 9, 1, 1, 'ชิ้น', ''),
(14, 10, 1, 1, 'ชิ้น', ''),
(15, 11, 2, 1, 'ชิ้น', ''),
(16, 12, 2, 1, 'ชิ้น', ''),
(17, 13, 3, 1, 'ชิ้น', ''),
(18, 14, 3, 1, 'ชิ้น', ''),
(19, 15, 1, 1, 'ชิ้น', ''),
(20, 16, 1, 1, 'ชิ้น', ''),
(21, 17, 3, 10, 'ชิ้น', ''),
(22, 18, 3, 10, 'ชิ้น', ''),
(23, 19, 3, 5, 'ชิ้น', ''),
(24, 20, 3, 3, 'ชิ้น', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `souvenir_users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `prefix` varchar(50) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `souvenir_users`
--

INSERT INTO `souvenir_users` (`user_id`, `full_name`, `prefix`, `position`, `department`, `phone`, `email`) VALUES
(1, 'สหรัฐ แสนสุข', 'นาย', 'นิสิตฝึกงาน', 'ประชาสัมพันธ์', '081-111-2222', '65011210037@msu.ac.th'),
(2, 'test', 'นาง', 'test', 'test', '0123456789', '65011210037@msu.ac.th');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `souvenir_admins`
--
ALTER TABLE `souvenir_admins`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `souvenir_items`
--
ALTER TABLE `souvenir_items`
  ADD PRIMARY KEY (`item_id`);

--
-- Indexes for table `souvenir_requests`
--
ALTER TABLE `souvenir_requests`
  ADD PRIMARY KEY (`request_id`);

--
-- Indexes for table `souvenir_request_details`
--
ALTER TABLE `souvenir_request_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `souvenir_users`
--
ALTER TABLE `souvenir_users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `souvenir_admins`
--
ALTER TABLE `souvenir_admins`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `souvenir_items`
--
ALTER TABLE `souvenir_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `souvenir_requests`
--
ALTER TABLE `souvenir_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `souvenir_request_details`
--
ALTER TABLE `souvenir_request_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `souvenir_users`
--
ALTER TABLE `souvenir_users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
