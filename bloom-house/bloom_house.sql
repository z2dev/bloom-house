-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 09, 2026 at 01:58 PM
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
-- Database: `bloom_house`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `full_name`, `email`, `password`) VALUES
(1, 'Zahra Mohsen', 'zahra@bloomhouse.com', '123456');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `message_body` text NOT NULL,
  `status` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`message_id`, `full_name`, `subject`, `message_body`, `status`, `created_at`, `admin_reply`, `replied_at`) VALUES
(2, 'Abeer Saad', 'Care Instructions', 'My plant leaves are turning yellow. What should I do?', 'Replied', '2026-05-06 00:34:50', 'ok, just put it in sun', '2026-05-09 12:35:04'),
(3, 'Huda Faisal', 'Order Update', 'Can I change the delivery address after placing the order?', 'Unread', '2026-05-06 00:34:50', NULL, NULL),
(4, 'Riman Aidrous', 'Website Feedback', 'The website is very easy to use, but I suggest adding more plant filters.', 'Unread', '2026-05-06 00:34:50', NULL, NULL),
(5, 'Maram Alotaibi', 'Support Request', 'I forgot my password and cannot login to my account.', 'Read', '2026-05-06 00:34:50', NULL, NULL),
(8, 'zahra mohsen', 'Plant', 'i want a easy plant', 'Replied', '2026-05-09 13:31:35', 'OK', '2026-05-09 13:43:35'),
(9, 'zahra mohsen', 'order', 'how can I order?', 'Read', '2026-05-09 13:31:52', 'You can place an order by browsing the plants, adding your favorite items to the cart, then proceeding to checkout to complete your order.', '2026-05-09 13:41:46');

-- --------------------------------------------------------

--
-- Table structure for table `faqs`
--

CREATE TABLE `faqs` (
  `faq_id` int(11) NOT NULL,
  `question` varchar(255) NOT NULL,
  `answer` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faqs`
--

INSERT INTO `faqs` (`faq_id`, `question`, `answer`, `created_at`) VALUES
(1, 'What is Bloom House?', 'Bloom House is an online plant store that offers a variety of indoor and outdoor plants, pots, and plant care accessories. We aim to bring nature closer to your home.', '2026-04-21 18:22:31'),
(2, 'Do you sell indoor or outdoor plants?', 'We sell both indoor and outdoor plants, including succulents, flowering plants, and decorative houseplants.', '2026-04-21 18:22:31'),
(3, 'Are your plants suitable for beginners?', 'Yes! We offer beginner-friendly plants and provide care instructions with every purchase.', '2026-04-21 18:22:31'),
(4, 'Do you deliver to all cities in Saudi Arabia?', 'Yes, we deliver to most cities across Saudi Arabia. Delivery time may vary depending on the location.', '2026-04-21 18:22:31'),
(5, 'How long does delivery take?', 'Orders are usually delivered within 2–5 business days.', '2026-04-21 18:22:31'),
(6, 'Can I track my order?', 'Yes, once your order is shipped, you will receive a tracking number via email.', '2026-04-21 18:22:31'),
(7, 'What payment methods do you accept?', 'We accept credit/debit cards, Mada, and online payment gateways.', '2026-04-21 18:22:31'),
(8, 'Is online payment secure?', 'Yes, all payments are processed through secure and encrypted payment systems.', '2026-04-21 18:22:31'),
(9, 'How do I take care of my plant?', 'Each plant comes with a care guide that includes watering frequency, sunlight requirements, and maintenance tips.', '2026-04-21 18:22:31'),
(10, 'What if my plant arrives damaged?', 'If your plant arrives damaged, please contact us within 24 hours with a photo, and we will arrange a replacement or refund.', '2026-04-21 18:22:31'),
(11, 'Can I return a plant?', 'Plants can only be returned if they arrive damaged or incorrect.', '2026-04-21 18:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `favourite`
--

CREATE TABLE `favourite` (
  `favourite_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `date_added` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favourite`
--

INSERT INTO `favourite` (`favourite_id`, `user_id`, `plant_id`, `date_added`) VALUES
(3, 5, 11, '2026-04-21 22:41:39'),
(4, 5, 11, '2026-04-21 22:41:39'),
(17, 6, 9, '0000-00-00 00:00:00'),
(18, 1, 1, '2026-05-05 23:49:31'),
(19, 1, 2, '2026-05-05 23:49:40'),
(20, 1, 4, '2026-05-05 23:57:15'),
(39, 11, 5, '2026-05-08 18:45:27'),
(40, 11, 7, '2026-05-08 18:45:33'),
(46, 12, 4, '2026-05-09 11:09:37'),
(47, 12, 13, '2026-05-09 11:28:19');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `order_status` varchar(50) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `city` varchar(50) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `discount` decimal(10,2) NOT NULL,
  `delivery_method` varchar(50) NOT NULL DEFAULT 'Delivery'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `order_date`, `order_status`, `shipping_address`, `payment_method`, `city`, `phone`, `discount`, `delivery_method`) VALUES
(1, 1, '2026-04-21 18:51:37', 'Shipped', 'Al Faisaliyah District', 'cash', 'Dammam', '0558063943', 0.00, 'Delivery'),
(2, 6, '2026-04-21 18:56:30', 'Delivered', 'Tarout', 'Apple pay', 'Qatif', '0543654925', 0.00, 'Delivery'),
(3, 5, '2026-04-21 18:58:09', 'Pending', 'Al Fayha District', 'cash', 'qatif', '0563525966', 0.00, 'Delivery'),
(4, 1, '2026-05-05 22:37:53', 'pending', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Tamara', 'Store Pickup', '0557330489', 0.00, 'Delivery'),
(5, 1, '2026-05-05 23:01:07', 'Processing', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Cash on Delivery', 'Store Pickup', '0557330489', 10.00, 'Store Pickup'),
(6, 12, '2026-05-06 19:11:34', 'pending', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', 'bbvv7ms@gmail.com', 0.00, 'Delivery'),
(7, 12, '2026-05-06 19:12:25', 'pending', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', 'bbvv7ms@gmail.com', 19.50, 'Delivery'),
(8, 12, '2026-05-06 19:37:01', 'Completed', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', '0543654925', 0.00, 'Delivery'),
(9, 12, '2026-05-08 09:02:11', 'Cancelled', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Cash on Delivery', 'Store Pickup', '0543654925', 30.75, 'Delivery'),
(10, 12, '2026-05-08 10:43:14', 'pending', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', '0543654925', 0.00, 'Delivery'),
(11, 13, '2026-05-08 11:09:47', 'Pending', 'Aabir Al Qarath Street, As Swaryee, Governorate of Jidda, Makkah Region, 23734, Saudi Arabia', 'Credit Card', 'Store Pickup', '0543654925', 20.00, 'Delivery'),
(12, 11, '2026-05-08 14:35:05', 'Processing', 'Dammam, Dammam Governorate, Eastern Province, 32242, Saudi Arabia', 'Apple Pay', 'Dammam', '0567485234', 15.00, 'Delivery'),
(13, 11, '2026-05-08 16:08:43', 'Shipped', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Cash on Delivery', 'Store Pickup', '0556789432', 0.00, 'Delivery'),
(14, 11, '2026-05-08 16:09:04', 'Delivered', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Cash on Delivery', 'Store Pickup', '0556789432', 0.00, 'Delivery'),
(15, 11, '2026-05-08 16:09:22', 'Delivered', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', '0556789432', 0.00, 'Delivery'),
(16, 11, '2026-05-08 16:09:47', 'Completed', 'PICKUP: Bloom House Store - Al Qatif, Eastern Region, KSA | https://maps.app.goo.gl/qSpfMMKmrCs74KJY8?g_st=ic', 'Credit Card', 'Store Pickup', '0556789432', 10.00, 'Delivery'),
(17, 12, '2026-05-09 10:05:21', 'pending', 'Ar Rabeiyah, Sanabis, Qatif Governorate, Eastern Province, 32611, Saudi Arabia', 'Apple Pay', 'Sanabis', '0543654925', 0.00, 'Delivery'),
(18, 12, '2026-05-09 10:15:39', 'pending', 'Ar Rabeiyah, Sanabis, Qatif Governorate, Eastern Province, 32611, Saudi Arabia', 'Cash on Delivery', 'Qatif', '0543654925', 0.00, 'Delivery'),
(19, 12, '2026-05-09 10:21:28', 'Delivered', 'Ar Rabeiyah, Sanabis, Qatif Governorate, Eastern Province, 32611, Saudi Arabia', 'Apple Pay', 'Sanabis', '0543654925', 0.00, 'Delivery'),
(20, 12, '2026-05-09 12:56:02', 'pending', 'Ar Rabeiyah, Sanabis, Qatif Governorate, Eastern Province, 32611, Saudi Arabia', 'Apple Pay', 'Sanabis', '0543654925', 0.00, 'Delivery'),
(21, 12, '2026-05-09 13:55:11', 'pending', 'Ar Rabeiyah, Sanabis, Qatif Governorate, Eastern Province, 32611, Saudi Arabia', 'Apple Pay', 'Sanabis', '0543654925', 0.00, 'Delivery');

-- --------------------------------------------------------

--
-- Table structure for table `orders_items`
--

CREATE TABLE `orders_items` (
  `order_item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders_items`
--

INSERT INTO `orders_items` (`order_item_id`, `order_id`, `plant_id`, `quantity`, `unit_price`) VALUES
(1, 1, 16, 2, 35.00),
(2, 1, 3, 1, 60.00),
(3, 2, 12, 3, 120.00),
(4, 1, 1, 1, 35.00),
(5, 3, 7, 2, 120.00),
(6, 4, 1, 1, 35.00),
(7, 4, 4, 1, 60.00),
(8, 4, 8, 1, 175.00),
(9, 5, 2, 1, 35.00),
(10, 6, 2, 1, 35.00),
(11, 7, 2, 2, 35.00),
(12, 7, 3, 1, 60.00),
(13, 8, 1, 1, 35.00),
(14, 9, 1, 1, 35.00),
(15, 9, 4, 2, 60.00),
(16, 9, 13, 2, 25.00),
(17, 10, 2, 1, 35.00),
(18, 10, 8, 1, 175.00),
(19, 11, 4, 1, 60.00),
(20, 11, 15, 1, 35.00),
(21, 12, 5, 1, 25.00),
(22, 12, 12, 1, 40.00),
(23, 12, 15, 1, 35.00),
(24, 13, 4, 1, 60.00),
(25, 14, 2, 1, 35.00),
(26, 14, 5, 1, 25.00),
(27, 15, 10, 1, 140.00),
(28, 15, 13, 1, 25.00),
(29, 16, 12, 1, 40.00),
(30, 16, 14, 1, 15.00),
(31, 17, 4, 5, 60.00),
(32, 18, 7, 1, 120.00),
(33, 18, 8, 1, 175.00),
(34, 19, 6, 1, 65.00),
(35, 20, 8, 1, 175.00),
(36, 21, 1, 1, 35.00),
(37, 21, 4, 1, 60.00),
(38, 21, 5, 1, 25.00);

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `plant_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `plant_name` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int(11) NOT NULL,
  `light_requirement` varchar(100) NOT NULL,
  `watering_instruction` text NOT NULL,
  `image` varchar(255) NOT NULL,
  `availability_status` varchar(50) NOT NULL,
  `category` varchar(50) NOT NULL,
  `height_cm` int(11) NOT NULL,
  `humidity_requirement` enum('Low','Medium','High') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`plant_id`, `admin_id`, `plant_name`, `description`, `price`, `stock_quantity`, `light_requirement`, `watering_instruction`, `image`, `availability_status`, `category`, `height_cm`, `humidity_requirement`) VALUES
(1, NULL, 'Monstera Deliciosa', 'A popular tropical plant with large split leaves, perfect for creating a fresh and modern atmosphere.', 35.00, 6, 'Bright indirect light', 'A stunning tropical plant with large, glossy split leaves that bring a fresh and modern look to any indoor space.', 'monstera.jpeg', 'Available', 'Indoor', 60, 'High'),
(2, 1, 'Peace Lily', 'An elegant plant with beautiful white flowers and deep green leaves, perfect for a calm and refreshing atmosphere.', 35.00, 10, 'Low to medium indirect light', 'Keep the soil consistently moist without overwatering.', 'peace-lily.jpeg', 'Available', 'Flower', 50, 'High'),
(3, NULL, 'Aglaonema Silver', 'A stylish indoor plant with beautiful silver-green leaves that adds a calm and modern touch to any space.\r\n', 60.00, 9, 'Medium indirect light', 'Water once a week and keep soil slightly moist.', 'aglaonema-silver.jpeg', 'Available', 'Indoor', 55, 'Medium'),
(4, 1, 'Aglaonema Red', 'A vibrant plant with red-tinted leaves that brings warmth and color to indoor environments.', 60.00, 5, 'Medium indirect light', 'Water once a week and avoid overwatering.', 'aglaonema-red.jpeg', 'Available', 'Indoor', 55, 'Medium'),
(5, NULL, 'Pothos Hanging', 'A trailing plant with lush green leaves, ideal for shelves and hanging pots in modern interiors.', 25.00, 7, 'Low to medium light', 'Water every 7–10 days.', 'hanging-pothos.jpeg', 'Available', 'Indoor', 40, 'Medium'),
(6, NULL, 'Snake Plant', 'A low-maintenance plant with upright leaves, perfect for beginners and improving indoor air quality.', 65.00, 9, 'Low to bright indirect light', 'Water every 2–3 weeks.', 'snake-plant.jpeg', 'Available', 'Indoor', 70, 'Low'),
(7, NULL, 'Areca Palm', 'A decorative palm that adds a fresh tropical feel and brightens indoor spaces.', 120.00, 7, 'Bright indirect light', 'Water regularly and keep soil slightly moist.\r\n', 'areca-palm.jpeg', 'Available', 'Indoor', 150, 'High'),
(8, 1, 'Ficus Benjamina', 'A classic indoor tree with dense green foliage, perfect for adding elegance to large spaces.', 175.00, 12, 'Bright indirect light', 'Water when the top soil becomes dry.', 'ficus-benjamina.jpeg', 'Available', 'Indoor', 180, 'Medium'),
(9, NULL, 'Yucca', 'A strong architectural plant with sharp leaves, ideal for modern and minimal designs.\r\n', 130.00, 6, 'Bright light', 'Water every 10–14 days.\r\n', 'yucca.jpeg', 'Available', 'Indoor', 170, 'Low'),
(10, NULL, 'Ficus Microcarpa', 'A compact decorative tree with glossy leaves, perfect for indoor styling.', 140.00, 5, 'Bright indirect light', 'Water regularly without overwatering.', 'ficus-microcarpa.jpeg', 'Available', 'Indoor', 120, 'Medium'),
(11, NULL, 'Dracaena Massangeana', 'A tall indoor plant with long green leaves, ideal for adding height and elegance to any room.', 130.00, 7, 'Medium light', 'Water once a week.', 'dracaena-massangeana.jpeg', 'Available', 'Indoor', 160, 'Medium'),
(12, NULL, 'Anthurium', 'A vibrant plant with glossy leaves and red flowers, perfect for adding a bold decorative touch.\r\n', 40.00, 4, 'Bright indirect light', 'Water every 5–7 days.', 'anthurium.jpeg', 'Available', 'Flower', 45, 'High'),
(13, NULL, 'Water Pothos', 'A fast-growing plant that thrives in water, perfect for modern minimal spaces.', 25.00, 4, 'Medium light', 'Change water weekly.', 'water-pothos.png', 'Available', 'Water', 30, 'High'),
(14, NULL, 'Lucky Bamboo', 'A simple water plant symbolizing luck and prosperity, perfect for minimal decor.', 15.00, 9, 'Indirect light', 'Change water weekly.', 'lucky-bamboo.jpeg', 'Available', 'Water', 35, 'High'),
(15, 1, 'Lilium', 'A beautiful flowering plant with delicate blooms that adds elegance to any space.', 35.00, 8, 'Bright indirect light', 'Water regularly.', 'lilium.jpeg', 'Available', 'Flower', 50, 'Medium'),
(16, NULL, 'Gardenia', 'A fragrant flowering plant with soft white blooms, perfect for a relaxing environment.', 35.00, 5, 'Bright indirect light', 'Keep soil moist and humid.', 'gardenia.jpeg', 'Available', 'Flower', 60, 'High');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `rating` int(1) NOT NULL,
  `review_title` varchar(150) NOT NULL,
  `review_text` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reviews`
--

INSERT INTO `reviews` (`review_id`, `user_id`, `plant_id`, `order_id`, `rating`, `review_title`, `review_text`, `created_at`) VALUES
(1, 1, 1, 1, 5, 'Amazing plant!', 'Really loved it, looks fresh and healthy.', '2026-05-05 22:29:50'),
(2, 1, 1, 1, 5, 'Love it! Perfect plant', 'Arrived healthy and fresh. It looks exactly like the photos and fits beautifully in my room.', '2026-04-05 22:33:11'),
(3, 5, 1, 3, 4, 'Excellent product!', 'Great quality, nice packaging, and the care instructions were very helpful.', '2026-03-05 22:33:11'),
(4, 6, 1, 2, 5, 'So beautiful!', 'The plant looks elegant and healthy. I would definitely order again.', '2026-04-14 22:33:11'),
(5, 1, 1, 1, 5, 'Perfect indoor plant!', 'Big beautiful leaves and very easy to care for. Looks amazing in my living room.', '2026-05-05 22:34:20'),
(6, 5, 2, 3, 5, 'So elegant!', 'The white flowers are stunning and it makes the room feel calm and fresh.', '2026-05-05 22:34:20'),
(7, 6, 3, 2, 4, 'Nice colors', 'The silver leaves look very stylish. Easy to maintain.', '2026-05-05 22:34:20'),
(8, 1, 4, 1, 5, 'Love the color!', 'The red tones add warmth to my space. Highly recommend.', '2026-05-05 22:34:20'),
(9, 5, 5, 3, 5, 'Perfect for decoration', 'Looks amazing hanging on shelves. Grows fast too!', '2026-05-05 22:34:20'),
(10, 6, 6, 2, 5, 'Best for beginners', 'Super easy plant. I forget to water it and it still survives 😅', '2026-05-05 22:34:20'),
(11, 1, 7, 1, 4, 'Nice tropical vibe', 'Makes the room feel like a mini jungle 🌿', '2026-05-05 22:34:20'),
(12, 5, 8, 3, 4, 'Elegant plant', 'Looks classy but needs a bit of care.', '2026-05-05 22:34:20'),
(13, 6, 9, 2, 5, 'Modern look', 'Perfect for minimal style homes. Strong plant.', '2026-05-05 22:34:20'),
(14, 1, 10, 1, 4, 'Cute and compact', 'Small but very decorative.', '2026-05-05 22:34:20'),
(15, 5, 11, 3, 5, 'Great height', 'Adds height to the room and looks very fresh.', '2026-05-05 22:34:20'),
(16, 6, 12, 2, 5, 'Beautiful flowers!', 'The red flowers are bright and eye-catching ❤️', '2026-05-05 22:34:20'),
(17, 1, 13, 1, 5, 'So easy!', 'Just water, no soil. Perfect for lazy people 😅', '2026-05-05 22:34:20'),
(18, 5, 14, 3, 4, 'Simple and nice', 'Minimal and clean look. Good for desk.', '2026-05-05 22:34:20'),
(19, 6, 15, 2, 5, 'Smells amazing!', 'The scent is lovely and the flowers are elegant.', '2026-05-05 22:34:20'),
(20, 1, 16, 1, 5, 'Very relaxing', 'Soft white flowers and great smell. Love it.', '2026-05-05 22:34:20'),
(23, 11, 5, 12, 5, 'Woooow', 'wow I love it !!!!!', '2026-05-08 15:40:12'),
(24, 11, 12, 12, 5, 'Loove it', 'woow', '2026-05-08 16:52:36'),
(25, 11, 2, 14, 5, 'Loove it', 'تجنن اشتروها', '2026-05-08 17:18:35');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `first_name`, `last_name`, `email`, `password`, `phone`, `gender`, `date_of_birth`) VALUES
(1, 'Reham', 'Alfaifi', 'reham@gmail.com', '112233', '0557330489', 'Female', '2003-06-30'),
(5, 'Lama', 'Alaesa', 'lama@gmail.com', '112233', '051234567', 'Female', '2004-04-07'),
(6, 'Nada', 'Mofeed', 'nada@gmail.com', '112233', '059876543', 'Female', '2003-04-01'),
(9, 'Maram', 'Alotaibi', 'maram@gmail.com', '112233', '0556783422', '', NULL),
(10, 'Layan', 'Alajmi', 'layan@gmail.com', '112233', '0502348976', '', NULL),
(11, 'Riman', 'Aidrous', 'riman@gmail.com', '112233', '0556789432', NULL, NULL),
(12, 'zahra', 'mohsen', 'bbvv7ms@gmail.com', '123456', '0543654925', 'Male', NULL),
(13, 'noor', 'hassan', 'noor@gmail.com', '123123', '222', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`);

--
-- Indexes for table `faqs`
--
ALTER TABLE `faqs`
  ADD PRIMARY KEY (`faq_id`);

--
-- Indexes for table `favourite`
--
ALTER TABLE `favourite`
  ADD PRIMARY KEY (`favourite_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plant_id` (`plant_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `orders_items`
--
ALTER TABLE `orders_items`
  ADD PRIMARY KEY (`order_item_id`),
  ADD KEY `orders_items.plant_id > plants.plant_id` (`plant_id`),
  ADD KEY `orders_items.order_id > orders.order_id` (`order_id`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`plant_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plant_id` (`plant_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `faqs`
--
ALTER TABLE `faqs`
  MODIFY `faq_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `favourite`
--
ALTER TABLE `favourite`
  MODIFY `favourite_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `orders_items`
--
ALTER TABLE `orders_items`
  MODIFY `order_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `plants`
--
ALTER TABLE `plants`
  MODIFY `plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `favourite`
--
ALTER TABLE `favourite`
  ADD CONSTRAINT `favourite_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `favourite_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints for table `orders_items`
--
ALTER TABLE `orders_items`
  ADD CONSTRAINT `orders_items.order_id > orders.order_id` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_items.plant_id > plants.plant_id` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`);

--
-- Constraints for table `plants`
--
ALTER TABLE `plants`
  ADD CONSTRAINT `plants_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`plant_id`) REFERENCES `plants` (`plant_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
