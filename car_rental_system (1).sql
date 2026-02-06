-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2026 at 04:35 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `car_rental_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `car_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cleared` tinyint(1) DEFAULT 0,
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_receipt` varchar(255) DEFAULT NULL,
  `payment_status` enum('paid','unpaid') DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `car_id`, `start_date`, `end_date`, `status`, `total_price`, `created_at`, `cleared`, `reference_number`, `payment_receipt`, `payment_status`) VALUES
(7, 1, 1, '2026-02-01', '2026-02-01', 'cancelled', 2500.00, '2026-02-01 00:11:13', 1, NULL, NULL, 'unpaid'),
(9, 1, 1, '2026-02-01', '2026-02-02', 'cancelled', 2500.00, '2026-02-01 00:23:14', 1, NULL, NULL, 'unpaid'),
(12, 1, 1, '2026-02-04', '2026-02-05', 'cancelled', 2500.00, '2026-02-02 00:34:50', 1, NULL, NULL, 'unpaid'),
(13, 1, 1, '2026-02-03', '2026-02-03', 'cancelled', 2500.00, '2026-02-02 01:17:23', 1, NULL, NULL, 'unpaid'),
(14, 1, 6, '2026-02-02', '2026-02-02', 'cancelled', 50.00, '2026-02-02 01:34:34', 1, NULL, NULL, 'unpaid'),
(15, 1, 1, '2026-02-03', '2026-02-04', 'completed', 2500.00, '2026-02-03 10:07:23', 1, NULL, NULL, 'unpaid'),
(16, 1, 1, '2026-02-05', '2026-02-05', 'completed', 2500.00, '2026-02-03 11:00:21', 0, NULL, NULL, 'unpaid'),
(17, 1, 1, '2026-02-06', '2026-02-06', 'cancelled', 2500.00, '2026-02-03 11:09:16', 1, NULL, NULL, 'unpaid'),
(18, 1, 1, '2026-02-03', '2026-02-03', 'completed', 2500.00, '2026-02-03 20:04:53', 1, NULL, NULL, 'unpaid'),
(19, 1, 1, '2026-02-04', '2026-02-04', 'completed', 2500.00, '2026-02-03 20:43:54', 0, NULL, NULL, 'unpaid'),
(33, 1, 1, '2026-02-06', '2026-02-06', 'completed', 2500.00, '2026-02-04 01:36:32', 1, '1234567890123', 'REC_1770140192_1.png', 'paid'),
(50, 1, 1, '2026-02-07', '2026-02-07', 'pending', 2500.00, '2026-02-04 15:16:51', 1, '2141241241241', NULL, 'paid'),
(51, 1, 1, '2026-02-08', '2026-02-08', 'pending', 2500.00, '2026-02-06 18:33:57', 0, '5346346346345', 'RCPT_1770374037_1.png', '');

-- --------------------------------------------------------

--
-- Table structure for table `cars`
--

CREATE TABLE `cars` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `price_per_day` decimal(10,2) NOT NULL,
  `price_per_hour` decimal(10,2) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `type` varchar(50) NOT NULL DEFAULT 'Economy',
  `status` varchar(20) NOT NULL DEFAULT 'available'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cars`
--

INSERT INTO `cars` (`id`, `name`, `brand`, `price_per_day`, `price_per_hour`, `image`, `created_at`, `type`, `status`) VALUES
(1, 'coffee', 'Sedan', 2500.00, 0.00, 'uploads/cars/1769687665_hero1.jfif', '2026-01-29 19:54:25', 'available', 'available'),
(2, 'milktea', 'SUV', 3000.00, 0.00, 'uploads/cars/1769705971_unnamed (5).jpg', '2026-01-30 00:59:31', 'available', 'available'),
(3, 'milktea', 'SUV', 3000.00, 0.00, 'uploads/cars/1769705975_unnamed (5).jpg', '2026-01-30 00:59:35', 'available', 'available'),
(5, 'lamboghini', 'Luxury', 2000.00, 0.00, 'uploads/cars/1769707980_car_fenyr_supersport_silver_car_sport_4k_hd_cars-3840x2160.jpg', '2026-01-30 01:33:00', 'maintenance', 'available'),
(6, 'dasdas', 'SUV', 50.00, 0.00, 'uploads/cars/1769709227_hero1.jfif', '2026-01-30 01:53:47', 'available', 'available');

-- --------------------------------------------------------

--
-- Table structure for table `car_locations`
--

CREATE TABLE `car_locations` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `speed` float DEFAULT 0,
  `recorded_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gps_history`
--

CREATE TABLE `gps_history` (
  `id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `speed` float DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) UNSIGNED NOT NULL,
  `booking_id` int(11) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('Cash','Credit Card','GCash','PayPal') DEFAULT 'Cash',
  `status` enum('Paid','Unpaid') DEFAULT 'Unpaid',
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `booking_id`, `amount`, `payment_method`, `status`, `paid_at`, `created_at`) VALUES
(1, 16, 2500.00, 'GCash', 'Paid', '2026-02-03 15:37:25', '2026-02-03 03:00:21'),
(2, 17, 2500.00, 'GCash', '', NULL, '2026-02-03 03:09:16'),
(3, 18, 2500.00, 'Cash', '', NULL, '2026-02-03 12:04:53'),
(4, 19, 2500.00, 'GCash', 'Paid', '2026-02-03 15:58:24', '2026-02-03 12:43:54'),
(20, 50, 2500.00, 'Cash', 'Paid', NULL, '2026-02-04 07:16:51'),
(21, 51, 2500.00, 'GCash', '', NULL, '2026-02-06 10:33:57');

-- --------------------------------------------------------

--
-- Table structure for table `travel_destinations`
--

CREATE TABLE `travel_destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `phone`, `address`, `role`, `created_at`) VALUES
(1, 'user', 'user@gmail.com', '$2y$10$hmxgSIHNbfyYFbxt4/sYWOaNbqNkWbwzCJ2n8D1VZtq7zhhm7j1mu', '', '', 'client', '2026-01-28 10:30:52'),
(4, 'admin', 'admin@gmail.com', '$2y$10$RpHZrQxh0eDgxlM4n7qNEeX8QeF2VkgEN09QhBy79Png7sz9Omy0O', '+63 912 345 6789', '123 Main Street, Manila', 'admin', '2026-01-29 17:58:37'),
(5, 'nik', 'nik@gmail.com', '$2y$10$HL7WfpWy09.PfHqV1BW0LuyODwTBf.l0HjPi2OMgeDv6X3BNWMMmC', '+63 912 345 6789', '123 Main Street, Manila', 'client', '2026-02-03 17:02:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_car_id` (`car_id`);

--
-- Indexes for table `cars`
--
ALTER TABLE `cars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `car_locations`
--
ALTER TABLE `car_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gps_history`
--
ALTER TABLE `gps_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_booking` (`booking_id`);

--
-- Indexes for table `travel_destinations`
--
ALTER TABLE `travel_destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `cars`
--
ALTER TABLE `cars`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `car_locations`
--
ALTER TABLE `car_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gps_history`
--
ALTER TABLE `gps_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `travel_destinations`
--
ALTER TABLE `travel_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_car` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
