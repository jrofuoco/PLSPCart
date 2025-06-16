-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `PLSPCART` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `PLSPCART`;

-- Users table
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','seller','buyer') NOT NULL DEFAULT 'buyer',
  `student_id` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','shipped','delivered','cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) NOT NULL,
  `payment_status` enum('pending','paid','failed') NOT NULL DEFAULT 'pending',
  `address` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Order items table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Buy and Sell table
CREATE TABLE IF NOT EXISTS `buy_and_sell` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `approval_status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `seller_id` (`seller_id`),
  CONSTRAINT `buy_and_sell_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `buy_and_sell_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE sell_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_name VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE cart (
    id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    quantity INTEGER NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@plspcart.com', 'Admin01', 'admin');

-- Insert default categories
INSERT INTO `categories` (`name`, `description`) VALUES
('CNAHS', 'College of Nursing and Allied Health Sciences'),
('CAS', 'College of Arts and Sciences'),
('CBA', 'College of Business Administration'),
('CCST', 'College of Computer Studies and Technology'),
('COA', 'College of Accountancy'),
('COE', 'College of Engineering'),
('CTED', 'College of Teacher Education'),
('CTH', 'College of Tourism and Hospitality Management'),
('CHK', 'College of Human Kinetics'),
('PLSP', 'University Products');

-- Insert sample products based on actual images
INSERT INTO `products` (`name`, `description`, `price`, `stock`, `category_id`, `seller_id`, `image`, `featured`) VALUES
-- CNAHS Products
('CNAHS Uniform', 'Official PLSP Nursing uniform with embroidered logo', 1500.00, 50, 1, 1, 'CNAHS Uniform.jpg', 1),
('CNAHS Department Shirt', 'Official CNAHS department shirt with college logo', 800.00, 100, 1, 1, 'CNAHS SHIRT.jpg', 1),
('Nursing Books Set 1', 'Essential nursing textbooks for first year students', 2500.00, 15, 1, 1, 'CNAHS BOOK.jpg', 0),
('Nursing Books Set 2', 'Advanced nursing textbooks for upper years', 2800.00, 15, 1, 1, 'CNAHS BOOK2.jpg', 0),

-- CAS Products
('CAS Department Shirt', 'Official CAS department shirt with college logo', 800.00, 100, 2, 1, 'CAS DEPT. SHIRT.jpg', 1),
('CAS Regular Shirt', 'CAS college shirt with embroidered logo', 750.00, 100, 2, 1, 'CAS SHIRT (2).jpg', 0),
('CAS Pin', 'Official CAS college pin', 150.00, 200, 2, 1, 'CAS Pin.jpg', 1),
('CAS Books', 'Set of essential books for arts and sciences students', 2500.00, 15, 2, 1, 'CAS BOOK.jpg', 0),

-- CBA Products
('CBA Department Shirt', 'Official CBA department shirt with college logo', 800.00, 100, 3, 1, 'CBA DEPT. SHIRT.jpg', 1),
('CBA Regular Shirt', 'CBA college shirt with embroidered logo', 750.00, 100, 3, 1, 'CBA SHIRT.jpg', 0),
('CBA Pin Set 1', 'Official CBA college pin design 1', 150.00, 200, 3, 1, 'CBA pin.jpg', 1),
('CBA Pin Set 2', 'Official CBA college pin design 2', 150.00, 200, 3, 1, 'CBA pin2.jpg', 1),
('CBA Books', 'Set of essential books for business administration students', 2500.00, 15, 3, 1, 'CBA BOOK.jpg', 0),

-- CCST Products
('CCST Department Shirt', 'Official CCST department shirt with college logo', 800.00, 100, 4, 1, 'CCST SHIRT.jpg', 1),
('CCST Pin', 'Official CCST college pin', 150.00, 200, 4, 1, 'CCST Pin.jpg', 1),
('CCST ID Lace', 'Official CCST ID lace with department logo', 150.00, 200, 4, 1, 'CCST I.D Lace.jpg', 0),
('CCST Books', 'Set of essential books for computer studies students', 2500.00, 15, 4, 1, 'CCST BOOK.jpg', 0),

-- COA Products
('COA Department Shirt', 'Official COA department shirt with college logo', 800.00, 100, 5, 1, 'COA SHIRT.jpg', 1),
('COA ID Lace', 'Official COA ID lace with department logo', 150.00, 200, 5, 1, 'COA I.D Lace.jpg', 0),
('COA Books', 'Set of essential books for accountancy students', 2500.00, 15, 5, 1, 'COA BOOK.jpg', 0),

-- COE Products
('COE Department Shirt', 'Official COE department shirt with college logo', 800.00, 100, 6, 1, 'COE SHIRT.jpg', 1),
('COE Books', 'Set of essential books for engineering students', 2500.00, 15, 6, 1, 'COE BOOK.jpg', 0),

-- CTED Products
('CTED Department Shirt', 'Official CTED department shirt with college logo', 800.00, 100, 7, 1, 'CTED SHIRT.jpg', 1),
('CTED Books', 'Set of essential books for education students', 2500.00, 15, 7, 1, 'CTED BOOK.jpg', 0),

-- CTH Products
('CTH Department Shirt', 'Official CTH department shirt with college logo', 800.00, 100, 8, 1, 'CTHM SHIRT.jpg', 1),
('CTH Books', 'Set of essential books for tourism and hospitality students', 2500.00, 15, 8, 1, 'CTHM BOOK.jpg', 0),

-- CHK Products
('CHK Department Shirt', 'Official CHK department shirt with college logo', 800.00, 100, 9, 1, 'CHK SHIRT.jpg', 1),
('CHK Books', 'Set of essential books for human kinetics students', 2500.00, 15, 9, 1, 'CHK BOOK.jpg', 0);