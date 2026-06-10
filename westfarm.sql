-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 09, 2026 at 11:15 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `westfarm`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `booking_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `booking_status_id` bigint UNSIGNED NOT NULL,
  `payment_status_id` bigint UNSIGNED NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`booking_id`, `customer_id`, `booking_status_id`, `payment_status_id`, `total_amount`, `created_at`, `updated_at`) VALUES
(2, 5, 3, 4, 75000.00, '2026-06-08 11:33:28', '2026-06-09 10:00:05'),
(3, 5, 3, 1, 22500.00, '2026-06-08 17:25:56', '2026-06-09 09:46:44');

-- --------------------------------------------------------

--
-- Table structure for table `booking_cancellations`
--

CREATE TABLE `booking_cancellations` (
  `cancellation_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `reason_id` bigint UNSIGNED NOT NULL,
  `cancelled_by_user_id` bigint UNSIGNED NOT NULL,
  `refund_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `additional_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_cancellations`
--

INSERT INTO `booking_cancellations` (`cancellation_id`, `booking_id`, `reason_id`, `cancelled_by_user_id`, `refund_amount`, `additional_notes`, `cancelled_at`) VALUES
(1, 3, 1, 5, 0.00, NULL, '2026-06-09 09:46:44'),
(2, 2, 6, 5, 75000.00, NULL, '2026-06-09 10:00:05');

-- --------------------------------------------------------

--
-- Table structure for table `booking_items`
--

CREATE TABLE `booking_items` (
  `booking_item_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `facility_id` bigint UNSIGNED NOT NULL,
  `check_in_date` datetime NOT NULL,
  `check_out_date` datetime NOT NULL,
  `price_at_booking` decimal(10,2) NOT NULL,
  `num_adults` int NOT NULL DEFAULT '1',
  `num_kids` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_items`
--

INSERT INTO `booking_items` (`booking_item_id`, `booking_id`, `facility_id`, `check_in_date`, `check_out_date`, `price_at_booking`, `num_adults`, `num_kids`, `created_at`, `updated_at`) VALUES
(2, 2, 2, '2026-06-07 12:00:00', '2026-06-17 14:00:00', 7500.00, 1, 0, '2026-06-08 11:33:28', '2026-06-08 11:33:28'),
(3, 3, 2, '2026-06-23 12:00:00', '2026-06-26 14:00:00', 7500.00, 1, 0, '2026-06-08 17:25:56', '2026-06-08 17:25:56');

-- --------------------------------------------------------

--
-- Table structure for table `booking_statuses`
--

CREATE TABLE `booking_statuses` (
  `booking_status_id` bigint UNSIGNED NOT NULL,
  `status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `booking_statuses`
--

INSERT INTO `booking_statuses` (`booking_status_id`, `status_name`) VALUES
(3, 'Cancelled'),
(4, 'Completed'),
(2, 'Confirmed'),
(1, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `cancellation_reasons`
--

CREATE TABLE `cancellation_reasons` (
  `reason_id` bigint UNSIGNED NOT NULL,
  `reason_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cancellation_reasons`
--

INSERT INTO `cancellation_reasons` (`reason_id`, `reason_name`, `description`) VALUES
(1, 'Change of Plans', 'I have a scheduling conflict and can no longer make these dates'),
(2, 'Weather Conditions', 'I am unable to travel due to severe weather or natural events'),
(3, 'Medical or Emergency', 'I have an unexpected health issue or family emergency'),
(4, 'Transportation Issues', 'My flight was cancelled, or I have unexpected vehicle issues'),
(5, 'Accidental Booking', 'I booked the wrong dates or the wrong accommodation by mistake'),
(6, 'Other', 'Another reason not listed above');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` bigint UNSIGNED NOT NULL,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Glamping', 'Standard and luxury glamping accommodations', '2026-05-13 12:35:09', '2026-05-13 12:35:09'),
(2, 'Event Hall', 'Large spaces for weddings, conferences, and parties', '2026-05-13 12:35:09', '2026-05-13 12:35:09'),
(3, 'Private Villa', 'Exclusive private villas for family and group stays', '2026-05-13 12:35:09', '2026-05-13 12:35:09'),
(4, 'Cottage', 'Traditional and comfortable cottages for relaxing getaways', '2026-05-13 12:35:09', '2026-05-13 12:35:09'),
(5, 'Pool', 'Access to swimming pools and aquatic facilities', '2026-05-13 12:35:09', '2026-05-13 12:35:09'),
(6, 'Foods', 'Cray Fish, Tilapia and Milk Fish is availables', '2026-05-14 07:17:45', '2026-05-14 07:17:55');

-- --------------------------------------------------------

--
-- Table structure for table `crayfish_orders`
--

CREATE TABLE `crayfish_orders` (
  `order_id` bigint UNSIGNED NOT NULL,
  `customer_id` bigint UNSIGNED NOT NULL,
  `status_id` bigint UNSIGNED NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_status_id` bigint UNSIGNED NOT NULL DEFAULT '1',
  `payment_method_id` bigint UNSIGNED DEFAULT NULL,
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `pickup_date` datetime DEFAULT NULL,
  `ordered_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `crayfish_orders`
--

INSERT INTO `crayfish_orders` (`order_id`, `customer_id`, `status_id`, `quantity_kg`, `price_per_kg`, `total_amount`, `payment_status_id`, `payment_method_id`, `amount_paid`, `pickup_date`, `ordered_at`, `updated_at`) VALUES
(4, 5, 1, 5.00, 150.00, 750.00, 2, 2, 300.00, '2026-06-10 12:00:00', '2026-06-09 10:41:34', '2026-06-09 10:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `crayfish_payments`
--

CREATE TABLE `crayfish_payments` (
  `payment_id` bigint UNSIGNED NOT NULL,
  `order_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `crayfish_payments`
--

INSERT INTO `crayfish_payments` (`payment_id`, `order_id`, `payment_method_id`, `amount_paid`, `transaction_id`, `payment_date`) VALUES
(1, 4, 2, 300.00, 'WC-4-1781001694', '2026-06-09 10:41:34');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` bigint UNSIGNED NOT NULL,
  `category_id` bigint UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `base_price` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `category_id`, `name`, `description`, `base_price`, `capacity`, `is_active`, `created_at`, `updated_at`) VALUES
(2, 3, 'Private Villa 1', 'Sample', 7500.00, 4, 1, '2026-06-08 11:31:01', '2026-06-08 11:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `facility_images`
--

CREATE TABLE `facility_images` (
  `image_id` bigint UNSIGNED NOT NULL,
  `facility_id` bigint UNSIGNED NOT NULL,
  `image_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `facility_images`
--

INSERT INTO `facility_images` (`image_id`, `facility_id`, `image_path`, `is_primary`, `created_at`) VALUES
(2, 2, 'uploads/facilities/facility_6a26a7f5e62cf1.01201861.jpg', 1, '2026-06-08 11:31:01');

-- --------------------------------------------------------

--
-- Table structure for table `order_statuses`
--

CREATE TABLE `order_statuses` (
  `status_id` bigint UNSIGNED NOT NULL,
  `status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_statuses`
--

INSERT INTO `order_statuses` (`status_id`, `status_name`, `description`) VALUES
(1, 'Pending Order', 'Customer placed the order, waiting for farm confirmation.'),
(2, 'Harvesting & Purging', 'Crayfish are being pulled from the pond and placed in clean water to purge.'),
(3, 'Live & Packed', 'Crayfish are purged, weighed, packed, and ready for immediate pickup.'),
(4, 'Completed', 'Customer has picked up their live crayfish.'),
(5, 'Cancelled', 'The order was cancelled by the customer or the farm.');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` bigint UNSIGNED NOT NULL,
  `booking_id` bigint UNSIGNED NOT NULL,
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `amount_paid` decimal(10,2) NOT NULL,
  `transaction_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `booking_id`, `payment_method_id`, `amount_paid`, `transaction_id`, `payment_date`) VALUES
(2, 2, 2, 75000.00, 'PAY-2-1780939446', '2026-06-08 17:24:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--

CREATE TABLE `payment_methods` (
  `payment_method_id` bigint UNSIGNED NOT NULL,
  `method_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_methods`
--

INSERT INTO `payment_methods` (`payment_method_id`, `method_name`, `description`, `is_active`) VALUES
(1, 'Cash', 'Payment made in cash upon arrival or at the counter', 0),
(2, 'GCash', 'Mobile wallet payment via GCash app', 1),
(3, 'Maya', 'Mobile wallet payment via Maya app', 1),
(4, 'MariBank (Formerly SeaBank)', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payment_statuses`
--

CREATE TABLE `payment_statuses` (
  `payment_status_id` bigint UNSIGNED NOT NULL,
  `status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_statuses`
--

INSERT INTO `payment_statuses` (`payment_status_id`, `status_name`) VALUES
(3, 'Paid'),
(2, 'Partial'),
(4, 'Refunded'),
(1, 'Unpaid');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` bigint UNSIGNED NOT NULL,
  `user_type_id` bigint UNSIGNED NOT NULL,
  `user_status_id` bigint UNSIGNED NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `user_type_id`, `user_status_id`, `email`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'admin@westfarm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2026-05-13 14:09:23', '2026-05-13 14:16:29'),
(2, 2, 1, 'abiandilla2015@gmail.com', '$2y$10$dQTlUWNwvhV6Mg/Ng6xR2ef9RYSmyHifVODvJu.Fej3aDpcqVTMFu', NULL, '2026-05-13 14:54:28', '2026-05-13 15:06:31'),
(3, 3, 1, 'raymundvalerios@gmail.com', '$2y$10$dQTlUWNwvhV6Mg/Ng6xR2ef9RYSmyHifVODvJu.Fej3aDpcqVTMFu', NULL, '2026-05-13 15:54:01', '2026-05-14 06:43:04'),
(4, 2, 1, 'hazel@gmail.com', '$2y$10$iRo43dAouS78n4sDvUteMORk2r7OExF8xaTj1neTqAdgrBcT0j3Vm', NULL, '2026-05-14 06:47:08', '2026-05-14 06:47:08'),
(5, 2, 1, 'relynnecayabyab@gmail.com', '$2y$10$0l2U.XlQ4SEj6xKlrupWh.A.Y24aYcsenCvLoxtGCkCaIOL6d9U3i', NULL, '2026-06-06 05:00:40', '2026-06-06 05:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_profiles`
--

CREATE TABLE `user_profiles` (
  `profile_id` bigint UNSIGNED NOT NULL,
  `user_id` bigint UNSIGNED NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_profiles`
--

INSERT INTO `user_profiles` (`profile_id`, `user_id`, `first_name`, `last_name`, `phone_number`, `address`, `created_at`, `updated_at`) VALUES
(1, 1, 'System', 'Administrator', '09123456789', 'Westfarm Resort, Bayambang, Pangasinan', '2026-05-13 14:09:23', '2026-05-13 14:09:23'),
(2, 2, 'Angelito', 'Biandilla', '09302070991', NULL, '2026-05-13 14:54:28', '2026-05-13 14:54:28'),
(3, 3, 'Raymunds', 'Valerios', '09302070992', NULL, '2026-05-13 15:54:01', '2026-05-14 06:43:04'),
(4, 4, 'Hazel', 'Dela Pena', NULL, NULL, '2026-05-14 06:47:08', '2026-05-14 06:47:08'),
(5, 5, 'Relynne', 'Cayabyab', '09345345345', NULL, '2026-06-06 05:00:40', '2026-06-06 05:00:40');

-- --------------------------------------------------------

--
-- Table structure for table `user_status`
--

CREATE TABLE `user_status` (
  `user_status_id` bigint UNSIGNED NOT NULL,
  `status_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_status`
--

INSERT INTO `user_status` (`user_status_id`, `status_name`, `description`) VALUES
(1, 'Active', 'User can log in and use the system'),
(2, 'Inactive', 'User account is disabled'),
(3, 'Suspended', 'User is temporarily banned');

-- --------------------------------------------------------

--
-- Table structure for table `user_types`
--

CREATE TABLE `user_types` (
  `user_type_id` bigint UNSIGNED NOT NULL,
  `type_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_types`
--

INSERT INTO `user_types` (`user_type_id`, `type_name`, `description`) VALUES
(1, 'Admin', 'Full access to manage the system'),
(2, 'Customer', 'Standard user who can make bookings'),
(3, 'Owner', 'Can manage bookings and facilities but not user roles');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `fk_booking_customer` (`customer_id`),
  ADD KEY `fk_booking_status` (`booking_status_id`),
  ADD KEY `fk_booking_payment_status` (`payment_status_id`);

--
-- Indexes for table `booking_cancellations`
--
ALTER TABLE `booking_cancellations`
  ADD PRIMARY KEY (`cancellation_id`),
  ADD UNIQUE KEY `uk_cancelled_booking` (`booking_id`),
  ADD KEY `fk_cancellation_reason` (`reason_id`),
  ADD KEY `fk_cancellation_user` (`cancelled_by_user_id`);

--
-- Indexes for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD PRIMARY KEY (`booking_item_id`),
  ADD KEY `fk_item_booking` (`booking_id`),
  ADD KEY `fk_item_facility` (`facility_id`);

--
-- Indexes for table `booking_statuses`
--
ALTER TABLE `booking_statuses`
  ADD PRIMARY KEY (`booking_status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `cancellation_reasons`
--
ALTER TABLE `cancellation_reasons`
  ADD PRIMARY KEY (`reason_id`),
  ADD UNIQUE KEY `reason_name` (`reason_name`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `crayfish_orders`
--
ALTER TABLE `crayfish_orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_crayfish_customer` (`customer_id`),
  ADD KEY `fk_crayfish_status` (`status_id`),
  ADD KEY `fk_crayfish_payment_status` (`payment_status_id`),
  ADD KEY `fk_crayfish_payment_method` (`payment_method_id`);

--
-- Indexes for table `crayfish_payments`
--
ALTER TABLE `crayfish_payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_cpay_order` (`order_id`),
  ADD KEY `fk_cpay_method` (`payment_method_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`),
  ADD KEY `fk_facility_category` (`category_id`);

--
-- Indexes for table `facility_images`
--
ALTER TABLE `facility_images`
  ADD PRIMARY KEY (`image_id`),
  ADD KEY `fk_image_facility` (`facility_id`);

--
-- Indexes for table `order_statuses`
--
ALTER TABLE `order_statuses`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_payment_booking` (`booking_id`),
  ADD KEY `fk_payment_method` (`payment_method_id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`payment_method_id`),
  ADD UNIQUE KEY `method_name` (`method_name`);

--
-- Indexes for table `payment_statuses`
--
ALTER TABLE `payment_statuses`
  ADD PRIMARY KEY (`payment_status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_type` (`user_type_id`),
  ADD KEY `fk_users_status` (`user_status_id`);

--
-- Indexes for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `user_status`
--
ALTER TABLE `user_status`
  ADD PRIMARY KEY (`user_status_id`),
  ADD UNIQUE KEY `status_name` (`status_name`);

--
-- Indexes for table `user_types`
--
ALTER TABLE `user_types`
  ADD PRIMARY KEY (`user_type_id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `booking_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `booking_cancellations`
--
ALTER TABLE `booking_cancellations`
  MODIFY `cancellation_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `booking_items`
--
ALTER TABLE `booking_items`
  MODIFY `booking_item_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `booking_statuses`
--
ALTER TABLE `booking_statuses`
  MODIFY `booking_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cancellation_reasons`
--
ALTER TABLE `cancellation_reasons`
  MODIFY `reason_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `crayfish_orders`
--
ALTER TABLE `crayfish_orders`
  MODIFY `order_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `crayfish_payments`
--
ALTER TABLE `crayfish_payments`
  MODIFY `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `facility_images`
--
ALTER TABLE `facility_images`
  MODIFY `image_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_statuses`
--
ALTER TABLE `order_statuses`
  MODIFY `status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `payment_method_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `payment_statuses`
--
ALTER TABLE `payment_statuses`
  MODIFY `payment_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_profiles`
--
ALTER TABLE `user_profiles`
  MODIFY `profile_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_status`
--
ALTER TABLE `user_status`
  MODIFY `user_status_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `user_types`
--
ALTER TABLE `user_types`
  MODIFY `user_type_id` bigint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `fk_booking_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_booking_payment_status` FOREIGN KEY (`payment_status_id`) REFERENCES `payment_statuses` (`payment_status_id`),
  ADD CONSTRAINT `fk_booking_status` FOREIGN KEY (`booking_status_id`) REFERENCES `booking_statuses` (`booking_status_id`);

--
-- Constraints for table `booking_cancellations`
--
ALTER TABLE `booking_cancellations`
  ADD CONSTRAINT `fk_cancel_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cancel_reason` FOREIGN KEY (`reason_id`) REFERENCES `cancellation_reasons` (`reason_id`),
  ADD CONSTRAINT `fk_cancel_user` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `booking_items`
--
ALTER TABLE `booking_items`
  ADD CONSTRAINT `fk_item_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`facility_id`);

--
-- Constraints for table `crayfish_orders`
--
ALTER TABLE `crayfish_orders`
  ADD CONSTRAINT `fk_crayfish_customer` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_crayfish_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`),
  ADD CONSTRAINT `fk_crayfish_payment_status` FOREIGN KEY (`payment_status_id`) REFERENCES `payment_statuses` (`payment_status_id`),
  ADD CONSTRAINT `fk_crayfish_status` FOREIGN KEY (`status_id`) REFERENCES `order_statuses` (`status_id`);

--
-- Constraints for table `crayfish_payments`
--
ALTER TABLE `crayfish_payments`
  ADD CONSTRAINT `fk_cpay_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`),
  ADD CONSTRAINT `fk_cpay_order` FOREIGN KEY (`order_id`) REFERENCES `crayfish_orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `facilities`
--
ALTER TABLE `facilities`
  ADD CONSTRAINT `fk_facility_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`);

--
-- Constraints for table `facility_images`
--
ALTER TABLE `facility_images`
  ADD CONSTRAINT `fk_image_facility` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`facility_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payment_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_status` FOREIGN KEY (`user_status_id`) REFERENCES `user_status` (`user_status_id`),
  ADD CONSTRAINT `fk_users_type` FOREIGN KEY (`user_type_id`) REFERENCES `user_types` (`user_type_id`);

--
-- Constraints for table `user_profiles`
--
ALTER TABLE `user_profiles`
  ADD CONSTRAINT `fk_profile_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
