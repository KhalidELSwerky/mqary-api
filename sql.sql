
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `broker_leads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `broker_id` int(11) NOT NULL,
  `status` enum('pending','contacted','ignored') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `broker_leads` (`id`, `user_id`, `broker_id`, `status`, `created_at`) VALUES
(1, 1, 0, 'pending', '2026-02-11 12:51:51'),
(2, 1, 19, 'pending', '2026-02-11 18:01:14');

CREATE TABLE `commitment_tracker` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `month_date` date NOT NULL,
  `is_committed` tinyint(1) DEFAULT 1,
  `amount_saved` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `commitment_tracker` (`id`, `user_id`, `month_date`, `is_committed`, `amount_saved`, `created_at`) VALUES
(23, 13, '2026-02-09', 1, 7000.00, '2026-02-09 15:04:27'),
(24, 13, '2026-02-09', 1, 14000.00, '2026-02-09 15:06:21'),
(31, 13, '2026-02-09', 1, 50000.00, '2026-02-09 17:34:50'),
(32, 13, '2026-02-09', 1, 0.00, '2026-02-09 17:42:28'),
(33, 13, '2026-02-09', 1, 0.00, '2026-02-09 17:42:30'),
(34, 13, '2026-02-09', 1, 0.00, '2026-02-09 17:42:32'),
(35, 13, '2026-02-09', 1, 0.00, '2026-02-09 17:42:32'),
(36, 13, '2026-02-09', 1, 1000.00, '2026-02-09 18:12:04'),
(37, 13, '2026-02-09', 1, 1000.00, '2026-02-09 18:12:14'),
(38, 13, '2026-02-09', 1, 1000.00, '2026-02-09 18:12:22'),
(39, 13, '2026-02-09', 1, 5000.00, '2026-02-09 18:12:31'),
(40, 13, '2026-02-09', 1, 5000.00, '2026-02-09 19:12:36'),
(41, 13, '2026-02-09', 1, 70000.00, '2026-02-09 19:13:32'),
(42, 13, '2026-02-09', 1, 70000.00, '2026-02-09 19:13:40'),
(43, 13, '2026-02-09', 1, 25000.00, '2026-02-09 19:13:51'),
(44, 13, '2026-02-09', 1, 1000.00, '2026-02-09 19:14:06'),
(45, 26, '2026-02-10', 1, 50000.00, '2026-02-10 12:30:06'),
(46, 26, '2026-02-10', 0, 2000.00, '2026-02-10 12:33:46'),
(47, 27, '2026-02-10', 1, 10000.00, '2026-02-10 19:16:17'),
(48, 27, '2026-02-10', 1, 500000.00, '2026-02-10 19:16:23'),
(49, 27, '2026-02-12', 1, 5000.00, '2026-02-12 16:09:44'),
(50, 27, '2026-02-13', 1, 5600.00, '2026-02-13 16:22:50'),
(51, 32, '2026-02-13', 1, 50000.00, '2026-02-13 20:03:07'),
(52, 32, '2026-02-14', 1, 50000.00, '2026-02-14 08:09:08'),
(53, 27, '2026-02-15', 1, 15007.00, '2026-02-15 04:47:03'),
(54, 27, '2026-02-15', 1, 4993.00, '2026-02-15 04:47:19'),
(55, 27, '2026-02-15', 1, 1507.00, '2026-02-15 04:47:31'),
(56, 27, '2026-02-15', 1, 1555.00, '2026-02-15 04:47:40'),
(57, 27, '2026-02-15', 1, 10000.00, '2026-02-15 04:47:44'),
(58, 27, '2026-02-15', 1, 20000.00, '2026-02-15 04:47:51'),
(59, 27, '2026-02-15', 1, 2.00, '2026-02-15 04:48:08'),
(60, 27, '2026-02-15', 1, 2000.00, '2026-02-15 04:48:28'),
(61, 27, '2026-02-15', 1, 20000.00, '2026-02-15 04:48:57'),
(62, 34, '2026-02-15', 1, 1500000.00, '2026-02-15 06:36:12');

CREATE TABLE `financial_plans` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `monthly_income` decimal(10,2) NOT NULL,
  `expenses` decimal(10,2) NOT NULL,
  `savings_amount` decimal(10,2) NOT NULL,
  `target_property_price` decimal(12,2) NOT NULL,
  `down_payment_target` decimal(12,2) NOT NULL,
  `plan_duration_months` int(11) NOT NULL,
  `status` enum('active','completed','paused') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `financial_plans` (`id`, `user_id`, `monthly_income`, `expenses`, `savings_amount`, `target_property_price`, `down_payment_target`, `plan_duration_months`, `status`) VALUES
(1, 1, 10000.00, 1500.00, 8500.00, 1500000.00, 300000.00, 177, 'active'),
(2, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'active'),
(3, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'active'),
(4, 1, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 'active'),
(5, 10, 5000.00, 4500.00, 500.00, 500000.00, 100000.00, 1000, 'active'),
(6, 11, 15000.00, 10000.00, 5000.00, 250000.00, 50000.00, 50, 'active'),
(7, 12, 6464646.00, 6646.00, 6458000.00, 10576768.00, 2115353.60, 2, 'active'),
(8, 13, 6500.00, 3000.00, 3500.00, 250000.00, 50000.00, 72, 'active'),
(9, 16, 30000.00, 15000.00, 15000.00, 500000.00, 100000.00, 34, 'active'),
(10, 17, 40000.00, 15000.00, 25000.00, 15000000.00, 3000000.00, 600, 'active'),
(11, 20, 97948.00, 949.00, 96999.00, 499.00, 99.80, 1, 'active'),
(12, 25, 12000.00, 1575.00, 10425.00, 2500000.00, 500000.00, 240, 'active'),
(13, 26, 30000.00, 15000.00, 15000.00, 500000.00, 100000.00, 34, 'active'),
(14, 27, 20000.00, 15000.00, 5000.00, 575464.00, 115092.80, 116, 'active'),
(15, 28, 150000.00, 15000.00, 135000.00, 6767676.00, 1353535.20, 51, 'active'),
(16, 29, 10000.00, 1500.00, 8500.00, 570000.00, 114000.00, 68, 'active'),
(17, 30, 15000.00, 10000.00, 5000.00, 1000000.00, 200000.00, 200, 'active'),
(18, 31, 15000.00, 7000.00, 8000.00, 1000000.00, 200000.00, 125, 'active'),
(19, 32, 25000.00, 15000.00, 10000.00, 1500000.00, 300000.00, 150, 'active'),
(20, 33, 97966746.00, 646764.00, 97319982.00, 9999999999.99, 9999999999.99, 695405, 'active'),
(21, 34, 15000.00, 10000.00, 5000.00, 15000000.00, 3000000.00, 3000, 'active');


CREATE TABLE `follows` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `broker_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `follows` (`id`, `user_id`, `broker_id`, `created_at`) VALUES
(1094, 31, 19, '2026-02-13 05:32:18'),
(1100, 27, 19, '2026-02-13 19:42:43'),
(1114, 32, 19, '2026-02-14 06:40:51'),
(1115, 34, 19, '2026-02-15 08:19:39');

CREATE TABLE `leads_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `status` enum('pending','connected','rejected') DEFAULT 'pending',
  `commission_paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `leads_requests` (`id`, `user_id`, `property_id`, `status`, `commission_paid`, `created_at`) VALUES
(17, 1, 25, '', 0, '2026-02-11 16:19:26'),
(18, 1, 26, '', 0, '2026-02-11 18:03:19');

-- --------------------------------------------------------

--
-- بنية الجدول `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `type` enum('reminder','lead_update','system') DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `type`, `created_at`) VALUES
(38, 27, 'هلا ', 'هلا فيك شفيك يا الحبيب', 1, 'lead_update', '2026-02-15 03:00:17');

-- --------------------------------------------------------

--
-- بنية الجدول `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `broker_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `governorate` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `phone` int(11) NOT NULL,
  `rooms` int(11) DEFAULT NULL,
  `images_json` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `bathrooms` int(11) DEFAULT 0,
  `area_sqm` int(11) DEFAULT 0,
  `latitude` double DEFAULT 0,
  `longitude` double DEFAULT 0,
  `floor_number` int(11) DEFAULT 0,
  `priority_level` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `properties`
--

INSERT INTO `properties` (`id`, `broker_id`, `title`, `description`, `price`, `governorate`, `city`, `phone`, `rooms`, `images_json`, `is_featured`, `created_at`, `bathrooms`, `area_sqm`, `latitude`, `longitude`, `floor_number`, `priority_level`) VALUES
(25, 19, 'احمد جمال ', 'احمد جمال عنق كبير ', 5000000.00, 'قنا', 'ابو تشت', 1114884069, 5, NULL, 1, '2026-02-11 16:18:21', 2, 500, 0, 0, 0, 1),
(26, 19, 'زبدبن', 'زرز', 948.00, 'الدقهلية', 'تروي', 6494810, 94, NULL, 1, '2026-02-11 18:01:57', 946, 64, 0, 0, 0, 1),
(27, 19, 'هلا وغلا ', 'اهبص', 15.00, 'قنا', 'هيج ', 1070836923, 5, NULL, 1, '2026-02-13 06:18:36', 3, 560, 0, 0, 0, 1);

-- --------------------------------------------------------

--
-- بنية الجدول `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- إرجاع أو استيراد بيانات الجدول `property_images`
--

INSERT INTO `property_images` (`id`, `property_id`, `image_url`, `created_at`) VALUES
(31, 25, 'IMG_1770826701_698cabcd66725.jpg', '2026-02-11 16:18:21'),
(32, 25, 'IMG_1770826701_698cabcd67604.jpg', '2026-02-11 16:18:21'),
(33, 25, 'IMG_1770826701_698cabcd6873c.jpg', '2026-02-11 16:18:21'),
(34, 25, 'IMG_1770826701_698cabcd697a8.jpg', '2026-02-11 16:18:21'),
(35, 25, 'IMG_1770826701_698cabcd6de79.jpg', '2026-02-11 16:18:21'),
(36, 25, 'IMG_1770826701_698cabcd6fb18.jpg', '2026-02-11 16:18:21'),
(37, 25, 'IMG_1770826701_698cabcd70a63.jpg', '2026-02-11 16:18:21'),
(38, 25, 'IMG_1770826701_698cabcd71b5a.jpg', '2026-02-11 16:18:21'),
(39, 26, 'IMG_1770832917_698cc415e7596.jpg', '2026-02-11 18:01:57'),
(40, 26, 'IMG_1770832917_698cc415ea525.jpg', '2026-02-11 18:01:57'),
(41, 26, 'IMG_1770832917_698cc415ebc9c.jpg', '2026-02-11 18:01:57'),
(42, 26, 'IMG_1770832917_698cc415ece4f.jpg', '2026-02-11 18:01:57'),
(43, 26, 'IMG_1770832918_698cc41647d3b.jpg', '2026-02-11 18:01:58'),
(44, 26, 'IMG_1770832918_698cc41648e92.jpg', '2026-02-11 18:01:58'),
(45, 27, 'IMG_1770963516_698ec23c1a7c6.jpg', '2026-02-13 06:18:36'),
(46, 27, 'IMG_1770963516_698ec23c1b3bb.jpg', '2026-02-13 06:18:36'),
(47, 27, 'IMG_1770963516_698ec23c1c0b2.jpg', '2026-02-13 06:18:36'),
(48, 27, 'IMG_1770963516_698ec23c1ccb9.jpg', '2026-02-13 06:18:36'),
(49, 27, 'IMG_1770963516_698ec23c1d83a.jpg', '2026-02-13 06:18:36'),
(50, 27, 'IMG_1770963516_698ec23c1e39f.jpg', '2026-02-13 06:18:36'),
(51, 27, 'IMG_1770963516_698ec23c1f136.jpg', '2026-02-13 06:18:36');

-- --------------------------------------------------------

--
-- بنية الجدول `subscriptions`
--

CREATE TABLE `subscriptions` (
  `id` int(11) NOT NULL,
  `subscriber_id` int(11) NOT NULL,
  `plan_type` enum('basic','pro','developer_pack') NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `payment_status` enum('pending','active','expired') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- بنية الجدول `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `governorate` varchar(100) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `approx_salary` decimal(10,2) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `fcm_token` text DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `profile_image` varchar(255) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `users` (`id`, `full_name`, `phone`, `password`, `governorate`, `city`, `age`, `approx_salary`, `role`, `created_at`, `updated_at`, `fcm_token`, `is_verified`, `profile_image`, `cover_image`) VALUES
(1, 'خالد جمال ', '01114884069', '$2y$10$tZGtrZ.P1wJNyPA4z0Vi/uhCufPH5eGF3aoJo2dnKLnPGv/a5UFty', 'قنا', 'ابو طشت', 20, 5000.00, 'user', '2026-02-08 19:00:06', '2026-02-08 19:00:06', NULL, 0, NULL, NULL),
(3, 'خالد ', '01114884066', '$2y$10$V62FraG27fN6i99neH9w.OxkgwRiz.lg1aVsOcGlbfRXq1MMhz85C', 'قنا', 'ابو طشت', 20, 5000.00, 'user', '2026-02-09 05:30:16', '2026-02-09 05:30:16', NULL, 0, NULL, NULL),
(4, 'لهان', '4286428', '$2y$10$WhPvcW9VQ7v2lheENbmzQufm6t4UDVilS0irozeoqqsFd.QI8/OW2', 'ةن', 'تللع', 58, 50000.00, 'user', '2026-02-09 05:46:28', '2026-02-09 05:46:28', NULL, 0, NULL, NULL),
(5, 'خالد ', '0154676', '$2y$10$lvKGXGYT7YzIAB3lxzU3GOn4nKRDILHxWYSZDsVLFNVj6uVZxfSN6', 'قنل', 'نىزى', 20, 40000.00, 'user', '2026-02-09 06:08:42', '2026-02-09 06:08:42', NULL, 0, NULL, NULL),
(6, 'خالد ', '06464', '$2y$10$VCORgiiUeuBvDPakJaGfA.fvf5khJvqvHNljVKJXAefBQ.T5CCoJO', 'بنبن', 'نىنىن', 15, 60000.00, 'user', '2026-02-09 06:48:32', '2026-02-09 06:48:32', NULL, 0, NULL, NULL),
(7, 'ا ', '694', '$2y$10$4YK4FpvFn1Ll0wnR4jRkb.cUh39UJhCCuVlqfkuB1iIizrAyuR5gG', 'زيز', 'زبت', 878, 94949.00, 'user', '2026-02-09 06:49:08', '2026-02-09 06:49:08', NULL, 0, NULL, NULL),
(10, 'jv', '01114884065', '$2y$10$TL/Q51LucSsE8HNmZ4ygJOfwQGmV5xFhB47Eqcl7FnodubDxiDrxq', NULL, NULL, 0, 0.00, 'user', '2026-02-09 08:54:29', '2026-02-09 08:54:29', NULL, 0, NULL, NULL),
(11, 'خالد خالد ب', '646464', '$2y$10$mKANRkGNG.XBDYh0c97i..Jjn3G.I2DiR/qWjGFbsbleZ9xYU0KhK', NULL, NULL, 0, 0.00, 'user', '2026-02-09 09:26:16', '2026-02-09 09:26:16', NULL, 0, NULL, NULL),
(12, '67', '676466', '$2y$10$ZK5v62uQdqZZc/sbxAtrMu/REHg9lxnt/4uW.pJix01/G1RL2p6V2', 'قنا', 'قنا', 35, 997676.00, 'user', '2026-02-09 14:29:18', '2026-02-09 14:29:18', NULL, 0, NULL, NULL),
(13, 'ترن', '0184676', '$2y$10$uOofQz4Ug.nr6eryOszJWedLlq7Npi47CtTkfW9sCwwvg2I4arv9u', 'قنا', 'قنا', 35, 79000.00, 'user', '2026-02-09 14:32:14', '2026-02-09 14:32:14', NULL, 0, NULL, NULL),
(14, 'خظر', '67678', '$2y$10$kc8RBJSrV4oyprXaabbjw.yyz9wbfZ3WmB.xmPW6Q/.9QAsAK3rRm', 'قنا', 'قنا', 6764, 57567.00, 'user', '2026-02-10 06:10:15', '2026-02-10 06:10:15', NULL, 0, NULL, NULL),
(16, 'تبت', '01114884063', '$2y$10$EgDEFzO/tGbYAUb8w/s95.W0rEHRMTYuflF9S4LF9vwqPiRQFKSVm', 'قنا', 'قنا', 645, 20000.00, 'user', '2026-02-10 06:10:51', '2026-02-10 06:10:51', NULL, 0, NULL, NULL),
(17, 'نرز', '67248', '$2y$10$PZ0B1aiRge3s0dEsn49nueQX2GrYh2HjrH9r5mVvF2GoGW7L/SiYG', 'قنا', 'قنا', 67, 15000.00, 'user', '2026-02-10 06:11:36', '2026-02-10 06:11:36', NULL, 0, NULL, NULL),
(18, 'خنب', '0111876', '$2y$10$fkkGrxhs0sw0vT.VzzI44eXa2r5.w4EbKBmGJd/XsF3SSdJzTvkPO', 'قنا', 'قنا', 6767, 9797.00, 'broker', '2026-02-10 06:52:10', '2026-02-10 06:52:10', NULL, 0, NULL, NULL),
(19, 'المهندس خالد جمال', '123456', '$2y$10$z6EK503I..nX0Zz.xE4GguZODF19XRVGln/G/Bty6cA22jHn6rSFu', 'قنا', 'قنا', 12, 20000.00, 'broker', '2026-02-10 06:55:16', '2026-02-14 19:17:12', 'fqFaWvQwTJmpKYgfffp9_w:APA91bEJiA6o1nZjbFxaXFSsbU-bopjLYjRYk_LhKGzhWyp4UK6DY8kcWbaKO5RrqFdKB3zsMRRgeTwsFVX0f-PfDPeXwiL3EqsWwUAjRx4KwIoTGc88Now', 1, 'http://192.168.1.6/sha2tak_api/uploads/profiles/profile_19_1770961170.jpg', 'http://192.168.1.6/sha2tak_api/uploads/profiles/cover_19_1770961188.jpg'),
(20, 'زرو', '64', '$2y$10$.SsWzNLrLdvD8eghW7gGJu29HJlw.BRw7btphYnhXDZD3kCMjQI4u', 'قنا', 'قنا', 545, 848.00, 'user', '2026-02-10 08:09:16', '2026-02-10 08:09:16', NULL, 0, NULL, NULL),
(21, 'زرزر', '646', '$2y$10$GbYfqPpPlO0SNBpFqv0TqOERvEJI9sp2hXU0mCTL43q6o1lzHZi2u', 'قنا', 'قنا', 845, 848.00, 'broker', '2026-02-10 08:09:33', '2026-02-10 08:09:33', NULL, 0, NULL, NULL),
(23, 'تبتت', '6469797', '$2y$10$4NUnxjl3hskhlvSwFwR7P.3hEJgF80/5aEo9IreCsdy5cDcH06qhq', 'قنا', 'قنا', 946, 94675.00, 'user', '2026-02-10 08:12:04', '2026-02-10 08:12:04', NULL, 0, NULL, NULL),
(25, 'ظرظ', '6466787', '$2y$10$yVDF8C3W7dySQQKjfylrneIJCTDJXLiiuvI2z6.0rE92sqHJSrrcy', 'قنا', 'قنا', 67, 99999999.99, 'user', '2026-02-10 09:56:56', '2026-02-10 09:56:56', NULL, 0, NULL, NULL),
(26, 'محمد', '01129294534', '$2y$10$x4gZP9SF3zZ8a5HpKvATB.rlylL6IYbFkNRlZOI/itriDBrWQ7lAG', 'قنا', 'قنا', 18, 20000.00, 'user', '2026-02-10 12:28:30', '2026-02-10 12:28:30', NULL, 0, NULL, NULL),
(27, 'خالد جمال ', '123456789', '$2y$10$tKGX9I4yoCjC15Rid.RSCO500.ZQuH3dd74DKM0h2DKcXllWZVUs6', 'قنا', 'قنا', 12, 84545.00, 'user', '2026-02-10 15:25:06', '2026-02-14 19:17:46', 'fqFaWvQwTJmpKYgfffp9_w:APA91bEJiA6o1nZjbFxaXFSsbU-bopjLYjRYk_LhKGzhWyp4UK6DY8kcWbaKO5RrqFdKB3zsMRRgeTwsFVX0f-PfDPeXwiL3EqsWwUAjRx4KwIoTGc88Now', 0, NULL, NULL),
(28, 'خا', '04946', '$2y$10$uuhBJCRjKbPed3AKl893C.TNLqGBf4kMx3edM1x1YDynx9x9Y3WPa', 'قنا', 'قنا', 54, 6764848.00, 'user', '2026-02-10 18:28:19', '2026-02-10 18:28:19', NULL, 0, NULL, NULL),
(29, 'هلا', '1234567890', '$2y$10$j8fTcwvmgVP6lDH5tYdJvuTnTKUGIztaTfu7hv8mrwWF6suen6iaC', 'قنا', 'قنا', 25, 587767.00, 'user', '2026-02-13 03:04:47', '2026-02-13 03:04:47', NULL, 0, NULL, NULL),
(30, 'خالد ', '2580', '$2y$10$7TYM1QVqByCo681u0i4Q3O5dJugVUEcmzMct5h0R3NfI9d3e/karW', 'قنا', 'قنا', 25, 2580.00, 'user', '2026-02-13 04:30:58', '2026-02-13 04:30:58', NULL, 0, NULL, NULL),
(31, 'خلاص ', '0963', '$2y$10$ZSGZQB57mTlnooW74Sg2re38MQrTz9he8GIzqFF118AgqS1iR1GxO', 'قنا', 'قنا', 25, 7000.00, 'user', '2026-02-13 04:39:33', '2026-02-13 04:39:33', NULL, 0, NULL, NULL),
(32, 'احمد جمال ', '0100100100', '$2y$10$CSRAmEmwo1mxBltt1YJjcuj9KYOlMgukfxl2aTvrRSXCD7fftRDsO', 'قنا', 'قنا', 25, 10000.00, 'user', '2026-02-13 20:00:00', '2026-02-13 20:00:00', NULL, 0, NULL, NULL),
(33, 'خالد ', '123321', '$2y$10$e2X9S6bCx88tOcGcL/sZ4uTnR9RMqqTlx/rj.UPbWLvYHSU8nP0yy', 'قنا', 'قنا', 25, 545.00, 'admin', '2026-02-14 11:59:10', '2026-02-15 02:59:44', 'fqFaWvQwTJmpKYgfffp9_w:APA91bEJiA6o1nZjbFxaXFSsbU-bopjLYjRYk_LhKGzhWyp4UK6DY8kcWbaKO5RrqFdKB3zsMRRgeTwsFVX0f-PfDPeXwiL3EqsWwUAjRx4KwIoTGc88Now', 0, NULL, NULL),
(34, 'زرز', '0852', '$2y$10$8adc4oBcg0U5HJoj0RAKVOM4BiRIoiEfiY023RKC9j269r1eduZ6.', 'قنا', 'قنا', 852, 8522580.00, 'user', '2026-02-15 06:35:17', '2026-02-15 06:35:17', 'fqFaWvQwTJmpKYgfffp9_w:APA91bEJiA6o1nZjbFxaXFSsbU-bopjLYjRYk_LhKGzhWyp4UK6DY8kcWbaKO5RrqFdKB3zsMRRgeTwsFVX0f-PfDPeXwiL3EqsWwUAjRx4KwIoTGc88Now', 0, NULL, NULL);

ALTER TABLE `broker_leads`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `commitment_tracker`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `financial_plans`
--
ALTER TABLE `financial_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `follows`
--
ALTER TABLE `follows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_follow` (`user_id`,`broker_id`),
  ADD KEY `broker_id` (`broker_id`);

--
-- Indexes for table `leads_requests`
--
ALTER TABLE `leads_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `broker_id` (`broker_id`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `property_id` (`property_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscriber_id` (`subscriber_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`);

ALTER TABLE `broker_leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `commitment_tracker`
--
ALTER TABLE `commitment_tracker`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `financial_plans`
--
ALTER TABLE `financial_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

ALTER TABLE `follows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1116;

ALTER TABLE `leads_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

ALTER TABLE `subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

ALTER TABLE `commitment_tracker`
  ADD CONSTRAINT `commitment_tracker_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `financial_plans`
  ADD CONSTRAINT `financial_plans_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `follows`
  ADD CONSTRAINT `follows_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `follows_ibfk_2` FOREIGN KEY (`broker_id`) REFERENCES `users` (`id`);

ALTER TABLE `leads_requests`
  ADD CONSTRAINT `leads_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `leads_requests_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `properties`
  ADD CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`broker_id`) REFERENCES `users` (`id`);

ALTER TABLE `property_images`
  ADD CONSTRAINT `property_images_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `subscriptions`
  ADD CONSTRAINT `subscriptions_ibfk_1` FOREIGN KEY (`subscriber_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;
