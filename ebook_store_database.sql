-- ============================================================================
-- NEXTREAD E-Book Store - Full Database Schema & Seed Data (30+ Orders)
-- Subject: Database Mini Project
-- Group Members:
--   1. นายปรัชญากร นาสา (รหัส 67332110087-8)
--   2. นายพงศกร ศรีวิเศษ (รหัส 67332110095-6)
-- ============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- ----------------------------------------------------------------------------
-- 1. Table structure for table `roles`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL UNIQUE,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`role_id`, `role_name`, `description`) VALUES
(1, 'admin', 'ผู้ดูแลระบบ มีสิทธิ์จัดการหนังสือ คำสั่งซื้อ และสมาชิก'),
(2, 'user', 'ลูกค้าทั่วไป มีสิทธิ์สั่งซื้อและเปิดอ่านหนังสือ');

-- ----------------------------------------------------------------------------
-- 2. Table structure for table `users`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'Administrator NextRead', 'admin@nextread.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'admin', '2026-08-01 08:00:00'),
(2, 'นายปรัชญากร นาสา', 'pratchayakorn@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-15 09:30:00'),
(3, 'นายพงศกร ศรีวิเศษ', 'pongsakorn@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-16 10:15:00'),
(4, 'กานต์ธิดา สุขสมบูรณ์', 'kantida@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-20 14:00:00'),
(5, 'ชานนท์ พิทักษ์ไทย', 'chanon@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-22 11:20:00'),
(6, 'ธนกฤต วิเศษศิลป์', 'thanakrit@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-25 16:45:00'),
(7, 'พิมพ์มาดา วรรณกรรม', 'pimmada@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-28 13:10:00'),
(8, 'อัครเดช รุ่งเรือง', 'akkradej@gmail.com', '$2y$10$abcdefghijklmnopqrstuvw1234567890', 'user', '2026-08-30 15:50:00');

-- ----------------------------------------------------------------------------
-- 3. Table structure for table `categories`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL UNIQUE,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`category_id`, `category_name`, `description`) VALUES
(1, 'เทคโนโลยีและการเขียนโปรแกรม', 'หนังสือด้านไอที การเขียนโค้ด และการออกแบบระบบ'),
(2, 'พัฒนาตนเองและจิตวิทยา', 'หนังสือสร้างแรงบันดาลใจ การพัฒนาศักยภาพชีวิต'),
(3, 'ธุรกิจและการเงิน', 'กลยุทธ์การบริหารธุรกิจ การลงทุน และการตลาด'),
(4, 'วรรณกรรมและนิยาย', 'นวนิยายแฟนตาซี วรรณกรรมเยาวชน และเรื่องสั้น'),
(5, 'วิทยาศาสตร์และนวัตกรรม', 'ความรู้ดาราศาสตร์ ฟิสิกส์ และเทคโนโลยีแห่งอนาคต');

-- ----------------------------------------------------------------------------
-- 4. Table structure for table `authors`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `authors` (
  `author_id` int(11) NOT NULL AUTO_INCREMENT,
  `author_name` varchar(150) NOT NULL,
  `biography` text DEFAULT NULL,
  PRIMARY KEY (`author_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `authors` (`author_id`, `author_name`, `biography`) VALUES
(1, 'ดร.สมเกียรติ พัฒนารุ่งเรือง', 'ผู้เชี่ยวชาญด้านสถาปัตยกรรมซอฟต์แวร์และ AI'),
(2, 'James Clear', 'นักเขียนระดับโลก ผู้เชี่ยวชาญด้านการสร้างนิสัยและพัฒนาตนเอง'),
(3, 'Morgan Housel', 'อดีตคอลัมนิสต์การเงิน The Wall Street Journal'),
(4, 'แพรวไพลิน นิยายรัก', 'นักเขียนนิยายแฟนตาซีและวรรณกรรมร่วมสมัย'),
(5, 'ดร.วิชัย นวัตกรรมก้าวหน้า', 'นักวิจัยเทคโนโลยีคอมพิวเตอร์และคลาวด์');

-- ----------------------------------------------------------------------------
-- 5. Table structure for table `ebooks`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ebooks` (
  `ebook_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author_id` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL CHECK (`price` >= 0),
  `description` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ebook_id`),
  KEY `fk_author` (`author_id`),
  KEY `fk_category` (`category_id`),
  CONSTRAINT `fk_author` FOREIGN KEY (`author_id`) REFERENCES `authors` (`author_id`) ON DELETE SET NULL,
  CONSTRAINT `fk_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `ebooks` (`ebook_id`, `title`, `author_id`, `category_id`, `price`, `description`, `cover_image`, `is_active`) VALUES
(1, 'Mastering MySQL & Database Design', 1, 1, 350.00, 'เจาะลึกการออกแบบฐานข้อมูลเชิงลึกและการปรับปรุง Performance', 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=500', 1),
(2, 'Modern PHP 8 & Web Architecture', 1, 1, 320.00, 'คู่มือการพัฒนาเว็บแอปพลิเคชันยุคใหม่ด้วย PHP 8 แบบมืออาชีพ', 'https://images.unsplash.com/photo-1532012164546-f432f2e35b75?w=500', 1),
(3, 'Atomic Habits เพราะชีวิตดีได้กว่าที่เป็น', 2, 2, 290.00, 'วิธีสร้างนิสัยที่ดีและเปลี่ยนแปลงชีวิตอย่างยั่งยืนทีละ 1%', 'https://images.unsplash.com/photo-1543002588-bfa74002ed7e?w=500', 1),
(4, 'The Psychology of Money จิตวิทยาการเงิน', 3, 3, 280.00, 'ข้อคิดและบทเรียนเหนือกาลเวลาเรื่องความมั่งคั่ง ความโลภ และความสุข', 'https://images.unsplash.com/photo-1592496431122-2349e0fbc666?w=500', 1),
(5, 'The Magic Kingdom of Astra', 4, 4, 220.00, 'นวนิยายแฟนตาซีผจญภัยในดินแดนเวทมนตร์แห่งดวงดาว', 'https://images.unsplash.com/photo-1512820790803-83ca734da794?w=500', 1),
(6, 'Clean Code Architecture in Practice', 1, 1, 390.00, 'ศาสตร์แห่งการเขียนโค้ดที่สะอาด อ่านง่าย และบำรุงรักษาได้ยั่งยืน', 'https://images.unsplash.com/photo-1589829085413-56de8ae18c73?w=500', 1),
(7, 'Deep Work ทำงานลึกสร้างผลลัพธ์เลิศ', 2, 2, 260.00, 'กฎแห่งความสำเร็จในโลกที่เต็มไปด้วยสิ่งรบกวนสมาธิ', 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=500', 1),
(8, 'AI Revolution: ปัญญาประดิษฐ์เปลี่ยนโลก', 5, 5, 340.00, 'ก้าวทันเทคโนโลยี AI และ Generative Model ที่กำลังขับเคลื่อนอนาคต', 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=500', 1);

-- ----------------------------------------------------------------------------
-- 6. Table structure for table `carts` and `cart_items`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carts` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL UNIQUE,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cart_items` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `cart_id` int(11) NOT NULL,
  `ebook_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1 CHECK (`quantity` > 0),
  `added_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`item_id`),
  KEY `fk_item_cart` (`cart_id`),
  KEY `fk_item_ebook` (`ebook_id`),
  CONSTRAINT `fk_item_cart` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`cart_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_ebook` FOREIGN KEY (`ebook_id`) REFERENCES `ebooks` (`ebook_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- 7. Table structure for table `orders`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL CHECK (`total_amount` >= 0),
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `order_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_id`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`order_id`, `user_id`, `total_amount`, `status`, `order_date`) VALUES
(1, 2, 670.00, 'approved', '2026-09-01 10:15:00'),
(2, 3, 350.00, 'approved', '2026-09-02 11:30:00'),
(3, 4, 290.00, 'approved', '2026-09-03 14:20:00'),
(4, 5, 570.00, 'approved', '2026-09-04 09:10:00'),
(5, 6, 220.00, 'approved', '2026-09-05 16:45:00'),
(6, 7, 740.00, 'approved', '2026-09-06 18:00:00'),
(7, 8, 320.00, 'approved', '2026-09-07 13:15:00'),
(8, 2, 280.00, 'approved', '2026-09-08 15:50:00'),
(9, 3, 610.00, 'approved', '2026-09-09 12:00:00'),
(10, 4, 340.00, 'approved', '2026-09-10 17:30:00'),
(11, 5, 390.00, 'approved', '2026-09-11 08:45:00'),
(12, 6, 510.00, 'approved', '2026-09-12 19:20:00'),
(13, 7, 350.00, 'approved', '2026-09-13 14:10:00'),
(14, 8, 260.00, 'approved', '2026-09-14 11:05:00'),
(15, 2, 390.00, 'approved', '2026-09-15 16:30:00'),
(16, 3, 290.00, 'approved', '2026-09-16 10:40:00'),
(17, 4, 630.00, 'approved', '2026-09-17 13:25:00'),
(18, 5, 220.00, 'approved', '2026-09-18 15:15:00'),
(19, 6, 350.00, 'approved', '2026-09-19 20:00:00'),
(20, 7, 570.00, 'approved', '2026-09-20 09:30:00'),
(21, 8, 340.00, 'approved', '2026-09-21 14:50:00'),
(22, 2, 320.00, 'approved', '2026-09-22 17:10:00'),
(23, 3, 740.00, 'approved', '2026-09-23 11:45:00'),
(24, 4, 280.00, 'approved', '2026-09-24 16:00:00'),
(25, 5, 350.00, 'approved', '2026-09-25 18:20:00'),
(26, 6, 290.00, 'pending',  '2026-09-26 10:05:00'),
(27, 7, 670.00, 'pending',  '2026-09-27 12:40:00'),
(28, 8, 390.00, 'pending',  '2026-09-28 15:15:00'),
(29, 2, 220.00, 'rejected', '2026-09-29 09:00:00'),
(30, 3, 340.00, 'cancelled','2026-09-30 14:10:00');

-- ----------------------------------------------------------------------------
-- 8. Table structure for table `order_items`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `ebook_id` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL CHECK (`price_at_purchase` >= 0),
  PRIMARY KEY (`order_item_id`),
  KEY `fk_item_order` (`order_id`),
  KEY `fk_order_ebook` (`ebook_id`),
  CONSTRAINT `fk_item_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_ebook` FOREIGN KEY (`ebook_id`) REFERENCES `ebooks` (`ebook_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `order_items` (`order_id`, `ebook_id`, `price_at_purchase`) VALUES
(1, 1, 350.00), (1, 2, 320.00),
(2, 1, 350.00),
(3, 3, 290.00),
(4, 3, 290.00), (4, 4, 280.00),
(5, 5, 220.00),
(6, 1, 350.00), (6, 6, 390.00),
(7, 2, 320.00),
(8, 4, 280.00),
(9, 1, 350.00), (9, 7, 260.00),
(10, 8, 340.00),
(11, 6, 390.00),
(12, 3, 290.00), (12, 5, 220.00),
(13, 1, 350.00),
(14, 7, 260.00),
(15, 6, 390.00),
(16, 3, 290.00),
(17, 1, 350.00), (17, 4, 280.00),
(18, 5, 220.00),
(19, 1, 350.00),
(20, 3, 290.00), (20, 4, 280.00),
(21, 8, 340.00),
(22, 2, 320.00),
(23, 1, 350.00), (23, 6, 390.00),
(24, 4, 280.00),
(25, 1, 350.00),
(26, 3, 290.00),
(27, 1, 350.00), (27, 2, 320.00),
(28, 6, 390.00),
(29, 5, 220.00),
(30, 8, 340.00);

-- ----------------------------------------------------------------------------
-- 9. Table structure for table `payments`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL UNIQUE,
  `payment_method` varchar(100) NOT NULL DEFAULT 'PromptPay',
  `amount` decimal(10,2) NOT NULL CHECK (`amount` >= 0),
  `slip_url` varchar(255) DEFAULT NULL,
  `payment_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  CONSTRAINT `fk_payment_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `payments` (`order_id`, `payment_method`, `amount`, `slip_url`, `payment_date`) VALUES
(1, 'PromptPay QR', 670.00, 'uploads/slips/slip_01.jpg', '2026-09-01 10:16:00'),
(2, 'PromptPay QR', 350.00, 'uploads/slips/slip_02.jpg', '2026-09-02 11:31:00'),
(3, 'PromptPay QR', 290.00, 'uploads/slips/slip_03.jpg', '2026-09-03 14:22:00'),
(4, 'PromptPay QR', 570.00, 'uploads/slips/slip_04.jpg', '2026-09-04 09:12:00'),
(5, 'PromptPay QR', 220.00, 'uploads/slips/slip_05.jpg', '2026-09-05 16:46:00'),
(6, 'PromptPay QR', 740.00, 'uploads/slips/slip_06.jpg', '2026-09-06 18:02:00'),
(7, 'PromptPay QR', 320.00, 'uploads/slips/slip_07.jpg', '2026-09-07 13:16:00'),
(8, 'PromptPay QR', 280.00, 'uploads/slips/slip_08.jpg', '2026-09-08 15:52:00'),
(9, 'PromptPay QR', 610.00, 'uploads/slips/slip_09.jpg', '2026-09-09 12:02:00'),
(10, 'PromptPay QR', 340.00, 'uploads/slips/slip_10.jpg', '2026-09-10 17:32:00'),
(26, 'PromptPay QR', 290.00, 'uploads/slips/slip_26.jpg', '2026-09-26 10:06:00'),
(27, 'PromptPay QR', 670.00, 'uploads/slips/slip_27.jpg', '2026-09-27 12:42:00'),
(28, 'PromptPay QR', 390.00, 'uploads/slips/slip_28.jpg', '2026-09-28 15:17:00');

-- ----------------------------------------------------------------------------
-- 10. Table structure for table `download_links`
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `download_links` (
  `link_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ebook_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `download_token` varchar(100) NOT NULL UNIQUE,
  `expire_date` datetime DEFAULT NULL,
  `download_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`link_id`),
  KEY `fk_dl_user` (`user_id`),
  KEY `fk_dl_ebook` (`ebook_id`),
  KEY `fk_dl_order` (`order_id`),
  CONSTRAINT `fk_dl_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dl_ebook` FOREIGN KEY (`ebook_id`) REFERENCES `ebooks` (`ebook_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_dl_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
