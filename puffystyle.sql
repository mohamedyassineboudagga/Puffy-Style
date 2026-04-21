-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 21 avr. 2026 à 23:55
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `puffystyle`
--

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `admin_reply`, `is_read`, `created_at`) VALUES
('MSG09226759', 'sfasdf', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'fdafsdfs', 'dasdasdasd', 1, '2026-04-17 22:13:20'),
('MSG16879423', 'Louay Ncibi', 'Louay.Ncibi@polytechnicien.tn', 'Product Information', 'hello ich ', 'ok', 1, '2026-04-21 16:04:51'),
('MSG30676268', 'sdsd', 'fasfsaf@sdfsdfaf', 'Product Information', 'dsdds', NULL, 1, '2026-04-15 23:44:29'),
('MSG45230637', 'Louay Ncibi', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'asdasd', NULL, 1, '2026-04-21 16:32:19'),
('MSG47399602', 'Louay Ncibi', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'I HAVN T RECEVE MY PRODUCT YET', 'I WILL BE DILIVRED TO YOU IN 2 DAYS', 1, '2026-04-17 21:47:58'),
('MSG51177775', 'dsfsdf', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'dfsd', 'sdfwggwgwg', 1, '2026-04-17 22:09:57'),
('MSG60042773', 'dfsdf', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'sdfsdfsdf', 'fwef', 1, '2026-04-17 22:04:30'),
('MSG63956445', 'Louay Ncibi', 'Louay.Ncibi@polytechnicien.tn', 'Order Tracking', 'adfdfsdfg', 'fdff', 1, '2026-04-21 16:26:51'),
('MSG66511017', 'ElfWorld Big B 99,999 Puffs 5MG', 'mohamedyassine.boudagga@polytechnicien.tn', 'Product Information', 'dasdasdasd', 'fdsfsdf', 1, '2026-04-15 23:50:17');

-- --------------------------------------------------------

--
-- Structure de la table `coupons`
--

CREATE TABLE `coupons` (
  `id` varchar(20) NOT NULL,
  `code` varchar(50) NOT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,3) NOT NULL,
  `min_order` decimal(10,3) DEFAULT 0.000,
  `max_uses` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `type`, `value`, `min_order`, `max_uses`, `used_count`, `start_date`, `end_date`, `is_active`) VALUES
('COUP6847649', 'AB', 'percentage', 15.000, 10.000, 1, 1, '2026-04-12', '2026-04-13', 1),
('COUP9317255', 'AAAAA', 'fixed', 20.000, 20.000, 1, 0, '2026-04-12', '2026-04-13', 1);

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(1, 'USR9413', 'Admin replied to: \'Order Tracking\' - dasdasdasd', 1, '2026-04-17 21:13:35'),
(2, 'USR9413', 'Admin replied to: \'Product Information\' - ok', 1, '2026-04-21 15:05:22'),
(3, 'USR9413', 'Admin replied to: \'Order Tracking\' - fdff', 0, '2026-04-21 15:28:48');

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `subtotal` decimal(10,3) NOT NULL,
  `tax_amount` decimal(10,3) DEFAULT 0.000,
  `shipping_cost` decimal(10,3) DEFAULT 0.000,
  `discount_amount` decimal(10,3) DEFAULT 0.000,
  `total` decimal(10,3) NOT NULL,
  `shipping_address_id` varchar(20) DEFAULT NULL,
  `shipping_method` varchar(50) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `coupon_id` varchar(20) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `status`, `subtotal`, `tax_amount`, `shipping_cost`, `discount_amount`, `total`, `shipping_address_id`, `shipping_method`, `payment_method`, `coupon_id`, `notes`, `created_at`) VALUES
('ORD036180197', 'USR9413', 'ORD036180197', 'pending', 46.000, 0.000, 7.000, 0.000, 53.000, NULL, 'Standard', 'COD', NULL, 'Phone: 1651651 | City: dfsdf | Address: fsdfsdf', '2026-04-21 16:26:34'),
('ORD492167565', 'USR9114', 'ORD492167565', 'pending', 55.000, 0.000, 7.000, 0.000, 62.000, NULL, 'Standard', 'COD', NULL, 'Phone: 54654 | City: gsrgsr | Address: gsrgsrg', '2026-04-16 10:57:12');

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` varchar(20) NOT NULL,
  `order_id` varchar(20) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `flavor` varchar(100) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,3) NOT NULL,
  `total_price` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `flavor`, `product_name`, `quantity`, `unit_price`, `total_price`) VALUES
('ORDI23508849', 'ORD036180197', 'PRD101', 'fdsadsf', 'dasdsad', 1, 46.000, 46.000);

-- --------------------------------------------------------

--
-- Structure de la table `passforget`
--

CREATE TABLE `passforget` (
  `id` int(11) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `passforget`
--

INSERT INTO `passforget` (`id`, `user_id`, `email`, `token`, `created_at`) VALUES
(1, 'USR9114', 'mohamedyassine.boudagga@polytechnicien.tn', '90655910f969b8956eaa42b1e0f8a581de4b5fec7d19ca1a756f89b50ced0b2e', '2026-04-16 01:27:49');

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` varchar(20) NOT NULL,
  `category_id` varchar(20) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,3) NOT NULL,
  `old_price` decimal(10,3) DEFAULT NULL,
  `promo_type` varchar(20) DEFAULT 'none',
  `promo_value` decimal(10,3) DEFAULT 0.000,
  `promotional_price` decimal(10,3) DEFAULT 0.000,
  `compare_price` decimal(10,3) DEFAULT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `brand` varchar(100) DEFAULT NULL,
  `flavor` varchar(100) DEFAULT NULL,
  `puff_count` int(11) DEFAULT NULL,
  `image_main` varchar(255) NOT NULL,
  `additional_images` text DEFAULT NULL,
  `image_gallery` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `short_description`, `price`, `old_price`, `promo_type`, `promo_value`, `promotional_price`, `compare_price`, `sku`, `stock_quantity`, `brand`, `flavor`, `puff_count`, `image_main`, `additional_images`, `image_gallery`, `is_featured`, `is_active`, `created_at`, `updated_at`) VALUES
('PRD101', NULL, 'dasdsad', 'dasdsad', 'fsdfsdf', NULL, 46.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 49, NULL, 'fdsadsf', 1535, '1776461872_0_17758609250p3.1.jpg', '[]', NULL, 0, 1, '2026-04-17 22:37:52', '2026-04-21 16:26:34'),
('prd_69d97a5868559', NULL, 'Vozol Gear ICE AND SWEET 50K 50MG', 'vozol-gear-ice-and-sweet-50k-50mg', 'Massive Puff Count: Up to 50,000 puffs for extended use.\r\nBattery Capacity: Built-in 1100 mAh battery for long-lasting performance.\r\nNicotine Strength: 50 mg/ml, delivering a strong hit.\r\nFlavor Customization:\r\nICE Control: 4 levels of cooling effect.\r\nSWEET Control: 2 levels of sweetness adjustment.\r\nAirflow &amp;amp; Power Control: Personalize vapor intensity and flavor richness.\r\nConvenience: Disposable design — no refilling or maintenance required.', NULL, 69.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 100, NULL, 'Blueberry Ice', 50000, '1775860312_0_p1.jpg', '[]', NULL, 0, 1, '2026-04-10 23:31:52', '2026-04-11 22:35:52'),
('prd_69d97b8222683', NULL, 'Vozol Rave 40K 50MG', 'vozol-rave-40k-50mg', 'Puff Capacity: Between 20,000 – 40,000 puffs, ensuring long-lasting use.\r\nBattery: 1000 mAh rechargeable battery with USB-C fast charging.\r\nE-liquid Capacity: 20 ml, generous for extended vaping sessions.\r\nNicotine Strength: 50 mg/ml, delivering a strong hit.\r\nDual Mesh Coils: Provides smoother, consistent flavor with every puff.\r\nTransparent Design: Lets you easily monitor e-liquid levels.\r\nColor Display: Shows device status for convenience and modern appeal.\r\nEco-friendly: Marketed as a more sustainable disposable option.', NULL, 59.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 50, NULL, 'Blueberry Ice , Strawberry Mango, Blue Razz Ice , Mango Ice , Peach Ice', 50000, '1775860610_0_p2.1.jpg', '[\"1775860610_1_p2.2.jpg\",\"1775860610_2_p2.3.jpg\",\"1775860610_3_p2.4.jpg\",\"1775860610_4_p2.5.jpg\"]', NULL, 0, 1, '2026-04-10 23:36:50', '2026-04-11 22:36:09'),
('prd_69d97cbd5f44e', NULL, 'ElfBar ICE KING 40K 50MG', 'elf-bar-ice-king-40k-50mg', 'Puff Capacity: Up to 40,000 puffs for extended use.\r\nE-liquid Capacity: 20 ml of premium pre-filled liquid.\r\nNicotine Strength: 5% (50 mg/ml) for strong, satisfying hits.\r\nBattery: 850 mAh rechargeable with USB-C fast charging.\r\nCooling Control: 5 adjustable levels to customize freshness.\r\nVapor Intensity: 3 levels to personalize cloud density.\r\nLCD Screen: Displays battery life and e-liquid level.\r\nDesign: Sleek, portable, and durable for everyday use.', NULL, 59.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 26, NULL, 'Watermelon Ice , Baja Splash , Peach+, Blue Rizz Ice , Dragon Strawnana', 40000, '1775860925_0_p3.1.jpg', '[\"1775860925_1_p3.2.jpg\",\"1775860925_2_p3.3.jpg\",\"1775860925_3_p3.4.jpg\",\"1775860925_4_p3.5.jpg\"]', NULL, 0, 1, '2026-04-10 23:42:05', '2026-04-17 21:04:02'),
('prd_69d97dca20489', NULL, 'NexBar 18K 20MG', 'nexbar-18k-20mg', 'Puff Capacity: Up to 18,000 puffs.\r\nE-liquid Capacity: 16 ml pre-filled liquid.\r\nNicotine Strength: 20 mg/ml (2%), lighter than high-strength 50 mg devices.\r\nBattery: 650 mAh rechargeable with USB-C fast charging.\r\nDesign: Sleek, portable, and easy to use.\r\nAvailability: In stock (varies by flavor).', NULL, 49.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 60, NULL, 'Blue Razz Ice , Strawberry Kiwi , Mango Ice , Watermelon Bubblegum , Cola Ice , Grape Ice , Peach Ic', 18000, '1775861194_0_p4.1.jpg', '[\"1775861194_1_p4.2.jpg\"]', NULL, 0, 1, '2026-04-10 23:46:34', NULL),
('prd_69d97e7db7d93', NULL, 'Geek Bar HERO 20K 20MG', 'geek-bar-hero-20k-20mg', 'Puff Capacity: Up to 20,000 puffs for long-lasting use.\r\nE-liquid Capacity: 24 ml, larger than most disposables.\r\nNicotine Strength: 20 mg/ml (2%), moderate compared to stronger 50 mg devices.\r\nBattery: 1200 mAh rechargeable with USB-C fast charging (full charge in 5 minutes, up to 1 hour of continuous vaping).\r\nDual Mesh Coils: Ensures consistent vapor production and rich flavor.\r\nTechnology VPU: Optimizes performance based on the cartridge used.\r\nAI Liquid Identification: Automatically adjusts settings for the best flavor and vapor quality.\r\nPower Modes: 3 customizable levels to match your vaping style.', NULL, 35.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 120, NULL, 'Banana Vanilla , Strawberry Watermelon , Watermelon Bubblegum', 20000, '1775861373_0_p5.jpg', '[]', NULL, 0, 1, '2026-04-10 23:49:33', NULL),
('prd_69d97f5672c16', NULL, ' NexBar 25K 20MG', 'wotofo-nexbar-25k-20mg', 'Puff Capacity: Up to 25,000 puffs.\r\nE-liquid Capacity: 25 ml, ensuring long-lasting use.\r\nNicotine Strength: 20 mg/ml (2%), moderate compared to stronger 50 mg devices.\r\nBattery: 1000 mAh rechargeable with USB-C charging.\r\nDual nexMESH Coils: Richer flavor, faster heating, and denser vapor.\r\nLarge Side Display: Shows real-time battery and e-liquid levels.\r\nLED Ring: Lights up with each puff for a stylish effect.\r\n', NULL, 60.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 50, NULL, 'Fresh Menthe Mojto', 25000, '1775861590_0_p6.1.jpg', '[\"1775861590_1_p6.2.jpg\"]', NULL, 0, 1, '2026-04-10 23:53:10', '2026-04-17 21:02:43'),
('prd_69d97feade0c9', NULL, ' DragBar NEXA ULTRA 40K 20MG', '-dragbar-nexa-ultra-40k-20mg', 'Puff Capacity: Up to 40,000 puffs for extended use.\r\nE-liquid Capacity: 20 ml premium e-liquid.\r\nNicotine Strength: 20 mg/ml (2%), lighter than stronger 50 mg devices.\r\nBattery: 600 mAh rechargeable with USB-C fast charging.\r\nDesign: Modern, durable, and intuitive for everyday use.', NULL, 59.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 52, NULL, 'Watermelon Ice , Mango Oasis , Mancer Triple Berry , Blueberry Watermelon', 40000, '1775861738_0_p7.1.jpg', '[\"1775861738_1_p7.2.jpg\",\"1775861738_2_p7.3.jpg\",\"1775861738_3_p7.4.jpg\"]', NULL, 0, 1, '2026-04-10 23:55:38', NULL),
('prd_69d980b500791', NULL, 'X-Bar X-Line 15K 20MG', 'x-bar-x-line-15k-20mg', 'Puff Capacity: Up to 15,000 puffs.\r\nNicotine Strength: 20 mg/ml (2%), smoother than stronger 50 mg devices.\r\nBattery: 600 mAh rechargeable with USB-C fast charging.\r\nDesign: Compact, sleek, and easy to carry.', NULL, 49.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 48, NULL, 'Strawberry Kiwi , Blue Razz Ice , Mixed Berry , Mango Ice , Love Story', 15000, '1775861941_0_p8.1.jpg', '[\"1775861941_1_p8.2.jpg\",\"1775861941_2_p8.4.jpg\",\"1775861941_3_p8.5.jpg\",\"1775861941_4_p8.6.jpg\"]', NULL, 0, 1, '2026-04-10 23:59:01', NULL),
('prd_69d98146394f6', NULL, 'Crown Bar HyperMax 30K 6MG ', 'crown-bar-hypermax-30k-6mg-', 'Puff Capacity: Up to 30,000 puffs.\r\nE-liquid Capacity: 20 ml premium e-liquid.\r\nNicotine Strength: 6 mg/ml (0.6%), lighter than most disposables.\r\nBattery: 650 mAh rechargeable with USB-C fast charging.\r\nDesign: Compact, sleek, and portable.', NULL, 59.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 96, NULL, 'Watermelon Kiwi , Blue Razz Ice , Strawberry Banana , Cola Ice', 30000, '1775862086_0_p9.1.jpg', '[\"1775862086_1_p9.2.jpg\",\"1775862086_2_p9.3.jpg\",\"1775862086_3_p9.4.jpg\"]', NULL, 0, 1, '2026-04-11 00:01:26', NULL),
('prd_69d9827587a4e', NULL, 'Voopoo Zest 40K 20MG ', 'voopoo-zest-40k-20mg-', 'Puff Capacity: Up to 40,000 puffs.\r\nE-liquid Capacity: 20 ml premium e-liquid.\r\nNicotine Strength: 20 mg/ml (2%), smoother than stronger 50 mg devices.\r\nBattery: 650 mAh rechargeable with USB-C fast charging.\r\nDesign: Sleek, portable, and user-friendly.', NULL, 59.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 57, NULL, 'Blue Razz Ice , Strawberry Kiwi , Peach Ice', 40000, '1775862389_0_p11.1.jpg', '[\"1775862389_1_p11.2.jpg\",\"1775862389_2_p11.3.jpg\"]', NULL, 0, 1, '2026-04-11 00:06:29', '2026-04-14 11:26:30'),
('prd_69d982ec81bc6', NULL, 'Vozol Star 20K 50MG', 'vozol-star-20k-50mg', 'Puff Capacity: Up to 20,000 puffs.\r\nE-liquid Capacity: 16 ml premium e-liquid.\r\nNicotine Strength: 50 mg/ml (5%), very strong compared to 20 mg devices.\r\nBattery: 1000 mAh rechargeable with USB-C fast charging.\r\nDesign: Compact, sleek, and portable.', NULL, 55.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 47, NULL, 'Peach Mango Watermelon , Strawberry Kiwi , Strawberry Banana , Blueberry Ice', 20000, '1775862508_0_p12.1.jpg', '[\"1775862508_1_p12.2.jpg\",\"1775862508_2_p12.3.jpg\",\"1775862508_3_p12.4.jpg\",\"1775862508_4_p12.5.jpg\"]', NULL, 0, 1, '2026-04-11 00:08:28', NULL),
('prd_69d9839f1141c', NULL, ' NexBar 30K 20MG', '-nexbar-30k-20mg', 'Puff Capacity: Up to 30,000 puffs.\r\nE-liquid Capacity: 20 ml premium e-liquid.\r\nNicotine Strength: 20 mg/ml (2%), smoother than high-strength devices.\r\nBattery: 1000 mAh rechargeable with USB-C fast charging.\r\nDual nexMESH Coils: Richer flavor and denser vapor.\r\nDesign: Sleek, modern, and portable.', NULL, 65.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 48, NULL, 'Moscow Evenings , Blue Razz Ice , Strawberry Kiwi , Mango Ice , Peach Ice', 30000, '1775862687_0_p13.1.jpg', '[\"1775862687_1_p13.2.jpg\",\"1775862687_2_p13.3.jpg\",\"1775862687_3_p13.4.jpg\",\"1775862687_4_p13.5.jpg\"]', NULL, 0, 1, '2026-04-11 00:11:27', NULL),
('prd_69d98413bbcc2', NULL, 'R and M Tornado 25K 20MG', 'r-and-m-tornado-25k-20mg', 'Puff Capacity: Up to 25,000 puffs.\r\nNicotine Strength: 20 mg/ml (2%), moderate compared to stronger 50 mg devices.\r\nBattery: 850 mAh rechargeable with USB-C fast charging.\r\nCoil Technology: Mesh coil for smoother, denser vapor.\r\nActivation: Inhalation-based, no buttons required.\r\nStyle: MTL (Mouth-to-Lung) draw, similar to traditional cigarettes.\r\nDesign: Compact, portable, and lightweight.', NULL, 55.000, NULL, 'none', 0.000, 0.000, NULL, NULL, 28, NULL, 'Fizzy Cherry  , Watermelon Ice , Strawberry Bubblegum', 25000, '1775862803_0_p14.1.jpg', '[\"1775862803_1_p14.2.jpg\",\"1775862803_2_p14.3.jpg\"]', NULL, 0, 1, '2026-04-11 00:13:23', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` varchar(20) NOT NULL,
  `product_id` varchar(20) NOT NULL,
  `user_id` varchar(20) NOT NULL,
  `rating` tinyint(4) NOT NULL,
  `comment` text DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` varchar(20) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `date_of_birth` date NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('customer','admin') DEFAULT 'customer',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `date_of_birth`, `phone`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
('USR9114', 'Mohamed Yassine', 'a', 'mohamedyassine.a@polytechnicien.tn', '$2y$10$CAkhOgSe/AqjODC7CGrfP.qmEFkx/wIw12Ajco1FHx0VNJpEzOWsu', '2004-08-20', '+21688888888', 'admin', 1, '2026-04-09 21:37:01', '2026-04-21 22:54:15'),
('USR9413', 'Louay', 'b', 'Louay.b@polytechnicien.tn', '$2y$10$O8O8/ovngESgqIW52.3BbOK2UaUFhLZtqvW/yADcyATvcIn9ilEBO', '2004-01-01', '+21699999999', 'customer', 1, '2026-04-09 21:49:15', '2026-04-21 22:54:28');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Index pour la table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_notification` (`user_id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `shipping_address_id` (`shipping_address_id`),
  ADD KEY `coupon_id` (`coupon_id`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `passforget`
--
ALTER TABLE `passforget`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `category_id` (`category_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `passforget`
--
ALTER TABLE `passforget`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_user_notification` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`);

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
