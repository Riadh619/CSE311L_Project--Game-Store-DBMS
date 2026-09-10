-- =====================================================================
-- GAME STORE E-COMMERCE  |  DBMS PROJECT
-- File: database/game_store.sql
-- Engine: MySQL 8 / MariaDB 10.4+ (XAMPP)
-- Import through phpMyAdmin -> Import -> choose this file -> Go
-- =====================================================================

DROP DATABASE IF EXISTS game_store;
CREATE DATABASE game_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE game_store;

-- =====================================================================
-- 1. LOOKUP / MASTER TABLES
-- =====================================================================

CREATE TABLE developers (
    developer_id   INT AUTO_INCREMENT PRIMARY KEY,
    developer_name VARCHAR(100) NOT NULL UNIQUE,
    country        VARCHAR(60)  DEFAULT NULL,
    founded_year   YEAR         DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE publishers (
    publisher_id   INT AUTO_INCREMENT PRIMARY KEY,
    publisher_name VARCHAR(100) NOT NULL UNIQUE,
    country        VARCHAR(60)  DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE genres (
    genre_id   INT AUTO_INCREMENT PRIMARY KEY,
    genre_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE platforms (
    platform_id   INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE categories (
    category_id   INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(50) NOT NULL UNIQUE,
    description   VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- =====================================================================
-- 2. USERS
-- =====================================================================

CREATE TABLE users (
    user_id    INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(120) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,               -- bcrypt hash, never plain text
    phone      VARCHAR(20)  DEFAULT NULL,
    address    VARCHAR(255) DEFAULT NULL,
    role       ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    status     ENUM('active','blocked') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_email CHECK (email LIKE '%_@_%._%')
) ENGINE=InnoDB;

CREATE INDEX idx_users_role ON users(role);

-- =====================================================================
-- 3. GAMES
-- =====================================================================

CREATE TABLE games (
    game_id      INT AUTO_INCREMENT PRIMARY KEY,
    title        VARCHAR(150)   NOT NULL,
    description  TEXT           NOT NULL,
    developer_id INT            NOT NULL,
    publisher_id INT            NOT NULL,
    category_id  INT            DEFAULT NULL,
    release_date DATE           NOT NULL,
    price        DECIMAL(10,2)  NOT NULL,
    stock        INT            NOT NULL DEFAULT 0,
    cover_image  VARCHAR(255)   DEFAULT NULL,
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_games_developer FOREIGN KEY (developer_id) REFERENCES developers(developer_id) ON UPDATE CASCADE,
    CONSTRAINT fk_games_publisher FOREIGN KEY (publisher_id) REFERENCES publishers(publisher_id) ON UPDATE CASCADE,
    CONSTRAINT fk_games_category  FOREIGN KEY (category_id)  REFERENCES categories(category_id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_games_price CHECK (price >= 0),
    CONSTRAINT chk_games_stock CHECK (stock >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_games_title   ON games(title);
CREATE INDEX idx_games_price   ON games(price);
CREATE INDEX idx_games_release ON games(release_date);
CREATE INDEX idx_games_status  ON games(status);

-- Many-to-many: games <-> genres
CREATE TABLE game_genres (
    game_id  INT NOT NULL,
    genre_id INT NOT NULL,
    PRIMARY KEY (game_id, genre_id),
    CONSTRAINT fk_gg_game  FOREIGN KEY (game_id)  REFERENCES games(game_id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_gg_genre FOREIGN KEY (genre_id) REFERENCES genres(genre_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Many-to-many: games <-> platforms
CREATE TABLE game_platforms (
    game_id     INT NOT NULL,
    platform_id INT NOT NULL,
    PRIMARY KEY (game_id, platform_id),
    CONSTRAINT fk_gp_game     FOREIGN KEY (game_id)     REFERENCES games(game_id)         ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_gp_platform FOREIGN KEY (platform_id) REFERENCES platforms(platform_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 4. CART
-- =====================================================================

CREATE TABLE carts (
    cart_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,                 -- User 1 -> 1 Cart
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id      INT NOT NULL,
    game_id      INT NOT NULL,
    quantity     INT NOT NULL DEFAULT 1,
    added_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_game (cart_id, game_id),
    CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ci_game FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_ci_qty CHECK (quantity > 0)
) ENGINE=InnoDB;

-- =====================================================================
-- 5. WISHLIST
-- =====================================================================

CREATE TABLE wishlists (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    game_id     INT NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_wishlist (user_id, game_id),        -- prevents duplicate entries
    CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_wl_game FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 6. ORDERS / PAYMENTS
-- =====================================================================

CREATE TABLE orders (
    order_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    order_status ENUM('pending','paid','completed','cancelled') NOT NULL DEFAULT 'pending',
    order_date   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_orders_total CHECK (total_amount >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_orders_date   ON orders(order_date);
CREATE INDEX idx_orders_status ON orders(order_status);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id      INT NOT NULL,
    game_id       INT NOT NULL,
    quantity      INT NOT NULL DEFAULT 1,
    price         DECIMAL(10,2) NOT NULL,             -- price actually paid per unit
    CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_oi_game  FOREIGN KEY (game_id)  REFERENCES games(game_id)   ON UPDATE CASCADE,
    CONSTRAINT chk_oi_qty   CHECK (quantity > 0),
    CONSTRAINT chk_oi_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE INDEX idx_oi_game ON order_items(game_id);

CREATE TABLE payments (
    payment_id            INT AUTO_INCREMENT PRIMARY KEY,
    order_id              INT NOT NULL UNIQUE,
    payment_method        ENUM('card','bkash','nagad','paypal','cash_on_delivery') NOT NULL,
    payment_status        ENUM('pending','success','failed','refunded') NOT NULL DEFAULT 'pending',
    transaction_reference VARCHAR(60) NOT NULL UNIQUE,
    amount                DECIMAL(10,2) NOT NULL,
    payment_date          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 7. LIBRARY (purchased games / downloads)
-- =====================================================================

CREATE TABLE library (
    library_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    game_id      INT NOT NULL,
    order_id     INT DEFAULT NULL,
    license_key  VARCHAR(60) NOT NULL UNIQUE,
    download_url VARCHAR(255) DEFAULT NULL,
    acquired_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_library (user_id, game_id),
    CONSTRAINT fk_lib_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lib_game  FOREIGN KEY (game_id)  REFERENCES games(game_id)  ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lib_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================================
-- 8. REVIEWS
-- =====================================================================

CREATE TABLE reviews (
    review_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    game_id     INT NOT NULL,
    rating      TINYINT NOT NULL,
    comment     TEXT DEFAULT NULL,
    review_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_review (user_id, game_id),          -- one review per user per game
    CONSTRAINT fk_rev_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rev_game FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_rev_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB;

CREATE INDEX idx_rev_game ON reviews(game_id);

-- =====================================================================
-- 9. DISCOUNTS
-- =====================================================================

CREATE TABLE discounts (
    discount_id         INT AUTO_INCREMENT PRIMARY KEY,
    game_id             INT NOT NULL,
    discount_percentage DECIMAL(5,2) NOT NULL,
    start_date          DATE NOT NULL,
    end_date            DATE NOT NULL,
    CONSTRAINT fk_disc_game FOREIGN KEY (game_id) REFERENCES games(game_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_disc_pct   CHECK (discount_percentage > 0 AND discount_percentage <= 90),
    CONSTRAINT chk_disc_dates CHECK (end_date >= start_date)
) ENGINE=InnoDB;

CREATE INDEX idx_disc_game_dates ON discounts(game_id, start_date, end_date);

-- =====================================================================
-- 10. SAMPLE DATA
-- =====================================================================

INSERT INTO developers (developer_id, developer_name, country, founded_year) VALUES
(1,'Nova Interactive','USA',2008),
(2,'Blackpine Studios','Canada',2011),
(3,'Quantum Forge','Germany',2005),
(4,'Hollow Lantern Games','UK',2014),
(5,'Redstone Works','Japan',1999),
(6,'Pixel Pantry','Bangladesh',2018),
(7,'Ironwake Studio','Poland',2010),
(8,'Aurora Craft','Sweden',2016);

INSERT INTO publishers (publisher_id, publisher_name, country) VALUES
(1,'Nova Publishing','USA'),
(2,'Vertex Games','Canada'),
(3,'Crown Digital','Germany'),
(4,'Lantern House','UK'),
(5,'Redstone Media','Japan'),
(6,'Indie Collective','Bangladesh'),
(7,'Ironwake Publishing','Poland'),
(8,'Aurora Media','Sweden');

INSERT INTO genres (genre_id, genre_name) VALUES
(1,'Action'),(2,'Adventure'),(3,'RPG'),(4,'Strategy'),(5,'Shooter'),
(6,'Racing'),(7,'Simulation'),(8,'Sports'),(9,'Horror'),(10,'Puzzle');

INSERT INTO platforms (platform_id, platform_name) VALUES
(1,'PC'),(2,'PlayStation 5'),(3,'Xbox Series X'),(4,'Nintendo Switch'),(5,'Steam Deck'),(6,'Mac');

INSERT INTO categories (category_id, category_name, description) VALUES
(1,'Featured','Hand picked titles promoted on the homepage'),
(2,'New Release','Launched within the last few months'),
(3,'Popular','Best selling titles of the store'),
(4,'Indie','Small studio and independent titles'),
(5,'AAA','Big budget blockbuster titles'),
(6,'Early Access','Playable builds still in development');

-- Passwords: admins = admin123, customers = user123  (stored as bcrypt hashes)
INSERT INTO users (user_id, name, email, password, phone, address, role, created_at) VALUES
(1,'Rahim Uddin','admin@gamestore.com','$2y$10$udzUopmMZaYy3wUtdU5/o.yvexr1aHfy/yzXWrVn0p4jxsU8.l6/a','01711000001','Dhanmondi, Dhaka','admin','2025-06-01 10:00:00'),
(2,'Sadia Karim','sadia.admin@gamestore.com','$2y$10$udzUopmMZaYy3wUtdU5/o.yvexr1aHfy/yzXWrVn0p4jxsU8.l6/a','01711000002','Uttara, Dhaka','admin','2025-06-02 10:00:00'),
(3,'Tanvir Ahmed','tanvir@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000003','Mirpur, Dhaka','customer','2025-07-15 09:20:00'),
(4,'Nusrat Jahan','nusrat@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000004','Bashundhara, Dhaka','customer','2025-08-02 11:35:00'),
(5,'Arif Hossain','arif@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000005','Chattogram','customer','2025-08-19 14:10:00'),
(6,'Mehjabin Chowdhury','mehjabin@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000006','Sylhet','customer','2025-09-05 16:45:00'),
(7,'Shakib Rahman','shakib@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000007','Khulna','customer','2025-09-28 08:05:00'),
(8,'Farhana Akter','farhana@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000008','Rajshahi','customer','2025-10-11 19:30:00'),
(9,'Imran Kabir','imran@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000009','Gulshan, Dhaka','customer','2025-11-03 12:00:00'),
(10,'Sabrina Haque','sabrina@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000010','Barishal','customer','2025-12-20 10:15:00'),
(11,'Rifat Islam','rifat@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000011','Rangpur','customer','2026-01-14 17:40:00'),
(12,'Anika Tabassum','anika@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000012','Banani, Dhaka','customer','2026-02-27 13:25:00'),
(13,'Zahid Hasan','zahid@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000013','Cumilla','customer','2026-04-08 15:55:00'),
(14,'Priya Das','priya@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000014','Narayanganj','customer','2026-06-19 09:10:00'),
(15,'Omar Faruk','omar@example.com','$2y$10$tQSTJYkVc9DcJh8yHEIunuDC2GT2K/nRm/br53f4opvuqPbaURGxK','01811000015','Savar, Dhaka','customer','2026-08-01 18:20:00');

INSERT INTO games (game_id, title, description, developer_id, publisher_id, category_id, release_date, price, stock, cover_image, status) VALUES
(1,'Nebula Drift','Pilot a salvaged starfighter through collapsing trade routes. Nebula Drift mixes twitch dogfighting with a branching campaign where every wrecked ship you loot changes what you can build next.',1,1,1,'2025-03-14',59.99,40,'covers/nebula_drift.svg','active'),
(2,'Shadow Protocol','A stealth thriller set in a city that never files a police report. Plan the route, cut the power, and leave before the second shift clocks in.',2,2,3,'2024-11-08',49.99,25,'covers/shadow_protocol.svg','active'),
(3,'Kingdoms of Ash','Rebuild a burned realm one province at a time. Council decisions carry over between campaigns, so yesterday''s cheap alliance is tomorrow''s border war.',3,3,5,'2024-06-21',39.99,60,'covers/kingdoms_of_ash.svg','active'),
(4,'Neon Circuit','Wall running, wire jumping, and a soundtrack that reacts to your combo counter. Short levels, very high skill ceiling.',6,6,4,'2025-09-02',29.99,8,'covers/neon_circuit.svg','active'),
(5,'Deep Salvage','You have eleven minutes of air and a flooded reactor to map. A slow horror game about diving with an unreliable light.',4,4,4,'2025-05-30',24.99,15,'covers/deep_salvage.svg','active'),
(6,'Pixel Rally GT','Sixteen-bit rally racing with modern physics underneath. Split screen, ghost times, and a track editor.',6,6,4,'2024-02-17',19.99,100,'covers/pixel_rally_gt.svg','active'),
(7,'Aetherbound','A hundred hour RPG about a cartographer mapping a continent that rearranges itself every season. Full voice cast and a crafting system that respects your time.',5,5,5,'2026-02-11',69.99,30,'covers/aetherbound.svg','active'),
(8,'Iron Harvest Tactics','Turn based squad tactics on a working farm economy. Lose a soldier and you also lose the hands that bring in the crop.',7,7,3,'2025-01-25',34.99,5,'covers/iron_harvest_tactics.svg','active'),
(9,'Last Light Bastion','Hold a mountain fortress through nine winters. Part base builder, part last stand shooter.',7,7,1,'2025-11-19',44.99,20,'covers/last_light_bastion.svg','active'),
(10,'Skyforge Chronicles','Airship crews, floating foundries, and a story that changes depending on which port you supply first.',8,8,1,'2026-05-07',54.99,12,'covers/skyforge_chronicles.svg','active'),
(11,'Cyber Sprawl 2088','An open district shooter where the map is owned by six factions and rent is due weekly. Ray traced rain included.',1,1,5,'2026-06-25',64.99,45,'covers/cyber_sprawl_2088.svg','active'),
(12,'Mystic Meadow Farm','Plant, brew, and befriend a valley of very opinionated neighbours. Co-op for up to four farmers.',6,6,4,'2024-09-13',14.99,90,'covers/mystic_meadow_farm.svg','active'),
(13,'Velocity Arena','Six on six competitive arena sport with rocket-assisted movement. Ranked seasons and a full replay theatre.',2,2,2,'2026-07-30',27.99,3,'covers/velocity_arena.svg','active'),
(14,'Echoes of Vardun','A hand drawn metroidvania about a bell ringer who can rewind sound. Every boss is a puzzle with a rhythm.',4,4,2,'2026-03-18',32.99,18,'covers/echoes_of_vardun.svg','active'),
(15,'Frostfall Survivors','Twenty degrees below and no supply drops until spring. Survival crafting with permanent injuries.',3,3,6,'2026-08-12',22.99,0,'covers/frostfall_survivors.svg','active');

INSERT INTO game_genres (game_id, genre_id) VALUES
(1,1),(1,5),(1,2),
(2,1),(2,2),
(3,4),(3,3),
(4,1),(4,10),
(5,9),(5,2),
(6,6),(6,8),
(7,3),(7,2),
(8,4),(8,7),
(9,5),(9,4),
(10,3),(10,2),
(11,5),(11,1),(11,3),
(12,7),(12,2),
(13,8),(13,1),
(14,2),(14,10),
(15,7),(15,9);

INSERT INTO game_platforms (game_id, platform_id) VALUES
(1,1),(1,2),(1,3),
(2,1),(2,2),
(3,1),(3,6),
(4,1),(4,5),(4,4),
(5,1),(5,2),(5,3),
(6,1),(6,4),(6,5),
(7,1),(7,2),(7,3),
(8,1),(8,6),
(9,1),(9,3),
(10,1),(10,2),(10,4),
(11,1),(11,2),(11,3),
(12,1),(12,4),(12,6),(12,5),
(13,1),(13,2),(13,3),
(14,1),(14,4),(14,5),
(15,1),(15,3);

-- Discounts (one expired row on purpose so the date filter can be demonstrated)
INSERT INTO discounts (discount_id, game_id, discount_percentage, start_date, end_date) VALUES
(1,2,25.00,'2026-08-01','2026-12-31'),
(2,7,15.00,'2026-08-10','2026-09-30'),
(3,10,30.00,'2026-08-05','2026-10-15'),
(4,12,40.00,'2026-07-20','2026-11-30'),
(5,14,20.00,'2026-08-15','2026-09-20'),
(6,1,10.00,'2025-12-01','2025-12-31'),
(7,6,35.00,'2026-08-01','2026-10-31');

-- Orders (total_amount is recalculated from order_items further below)
INSERT INTO orders (order_id, user_id, total_amount, order_status, order_date) VALUES
(1,3,0,'completed','2025-09-12 14:22:00'),
(2,4,0,'completed','2025-10-03 10:05:00'),
(3,5,0,'completed','2025-11-18 20:41:00'),
(4,3,0,'completed','2025-12-05 09:12:00'),
(5,6,0,'completed','2026-01-22 17:33:00'),
(6,7,0,'completed','2026-02-10 12:48:00'),
(7,8,0,'paid','2026-03-27 15:20:00'),
(8,3,0,'completed','2026-04-15 11:07:00'),
(9,9,0,'completed','2026-05-30 19:55:00'),
(10,10,0,'completed','2026-06-11 13:30:00'),
(11,11,0,'completed','2026-07-02 08:44:00'),
(12,12,0,'pending','2026-07-19 21:10:00'),
(13,3,0,'completed','2026-08-05 16:02:00'),
(14,3,0,'completed','2026-08-20 10:26:00'),
(15,4,0,'completed','2026-08-22 18:15:00'),
(16,13,0,'cancelled','2026-08-24 09:38:00');

INSERT INTO order_items (order_id, game_id, quantity, price) VALUES
(1,1,1,59.99),(1,6,1,19.99),
(2,2,1,49.99),
(3,3,1,39.99),(3,12,2,14.99),
(4,7,1,69.99),
(5,11,1,64.99),(5,4,1,29.99),
(6,5,1,24.99),
(7,9,1,44.99),(7,10,1,54.99),
(8,13,1,27.99),
(9,14,1,32.99),(9,8,1,34.99),
(10,1,1,59.99),(10,2,1,44.99),
(11,12,1,14.99),
(12,15,1,22.99),
(13,11,1,64.99),
(14,5,1,24.99),
(15,6,1,12.99),(15,13,1,27.99),
(16,3,1,39.99);

-- Keep order totals consistent with their line items (demonstrates UPDATE + subquery)
UPDATE orders o
SET o.total_amount = (
    SELECT COALESCE(SUM(oi.quantity * oi.price), 0)
    FROM order_items oi
    WHERE oi.order_id = o.order_id
);

INSERT INTO payments (order_id, payment_method, payment_status, transaction_reference, amount, payment_date)
SELECT o.order_id,
       ELT(1 + (o.order_id % 5),'card','bkash','nagad','paypal','cash_on_delivery'),
       CASE WHEN o.order_status = 'cancelled' THEN 'refunded'
            WHEN o.order_status = 'pending'   THEN 'pending'
            ELSE 'success' END,
       CONCAT('TXN-', DATE_FORMAT(o.order_date,'%Y%m%d'), '-', LPAD(o.order_id, 5, '0')),
       o.total_amount,
       o.order_date
FROM orders o;

-- Purchased games land in the library (paid + completed orders only)
INSERT INTO library (user_id, game_id, order_id, license_key, download_url, acquired_at)
SELECT o.user_id, oi.game_id, MIN(o.order_id),
       CONCAT('GS-', UPPER(SUBSTRING(MD5(CONCAT(o.user_id,'-',oi.game_id,'-gamestore')),1,4)), '-',
                     UPPER(SUBSTRING(MD5(CONCAT(oi.game_id,'-',o.user_id,'-key')),1,4)), '-',
                     UPPER(SUBSTRING(MD5(CONCAT(o.user_id, oi.game_id)),1,4))),
       CONCAT('downloads/game-', oi.game_id, '.zip'),
       MIN(o.order_date)
FROM orders o
JOIN order_items oi ON oi.order_id = o.order_id
WHERE o.order_status IN ('paid','completed')
GROUP BY o.user_id, oi.game_id;

-- Reviews (every row below belongs to a user who actually owns that game)
INSERT INTO reviews (user_id, game_id, rating, comment, review_date) VALUES
(3,1,5,'The dogfighting feels great once you stop fighting the drift. Sixty hours in and still finding new loadouts.','2025-09-20 21:14:00'),
(3,6,4,'Track editor alone is worth the price. Handling takes a while to click.','2025-09-25 18:02:00'),
(3,7,5,'Easily my game of the year. The seasonal map rewrites never stopped surprising me.','2025-12-19 22:40:00'),
(3,13,3,'Fun in a squad, rough solo. Matchmaking needs work.','2026-04-22 20:11:00'),
(3,11,4,'The rain looks unreal. Faction rent system gets grindy in the last act.','2026-08-10 19:30:00'),
(4,2,4,'Proper stealth game. No waypoint hand holding, just a map and a stopwatch.','2025-10-12 13:26:00'),
(4,6,5,'Bought it twice, once for me and once for my brother. Split screen still rules.','2026-08-24 11:45:00'),
(5,3,5,'Campaign carry-over is brilliant. My third playthrough started with an empty treasury and it was the best one.','2025-11-27 16:50:00'),
(5,12,4,'Cosy without being empty. The neighbours actually remember what you said.','2025-12-01 09:15:00'),
(6,11,5,'Best open district shooter in years. Runs fine on a mid range card too.','2026-02-01 23:05:00'),
(6,4,3,'Great movement, way too short. Finished the whole thing in one evening.','2026-02-03 20:20:00'),
(7,5,4,'Genuinely tense. The eleven minute air timer does all the work.','2026-02-18 22:35:00'),
(8,9,5,'Nine winters and I still lost the fortress. Perfect difficulty curve.','2026-04-02 14:12:00'),
(8,10,4,'Story branches are real, not cosmetic. Airship controls take practice.','2026-04-05 17:48:00'),
(9,14,4,'The rewind mechanic is used cleverly in every boss. Art is gorgeous.','2026-06-08 10:30:00'),
(9,8,5,'Losing a soldier hurting the harvest is such a good idea. Brutal and fair.','2026-06-12 19:22:00'),
(10,1,4,'Solid space combat. Wish the campaign branched a bit earlier.','2026-06-20 12:05:00'),
(10,2,3,'Good game, but I hit a save bug on chapter four.','2026-06-22 15:40:00'),
(11,12,5,'Four player co-op farming is exactly what my friend group needed.','2026-07-10 21:55:00');

INSERT INTO wishlists (user_id, game_id) VALUES
(3,10),(3,14),(3,9),
(4,7),(4,11),
(5,1),(5,13),
(6,7),(6,10),
(7,11),(7,15),
(8,1),(8,14),
(9,10),(9,11),
(10,7),(10,12),
(11,4),(11,9),
(12,1),(12,5),
(14,2),(14,7),(14,13),
(15,3),(15,11);

-- A couple of live carts so the cart page has data straight after import
INSERT INTO carts (cart_id, user_id) VALUES (1,3),(2,4),(3,14);
INSERT INTO cart_items (cart_id, game_id, quantity) VALUES
(1,10,1),(1,14,1),
(2,7,1),
(3,2,1),(3,12,2);

-- =====================================================================
-- 11. VIEWS
-- =====================================================================

-- Full catalogue row used by almost every customer-facing page
CREATE OR REPLACE VIEW game_catalog_view AS
SELECT
    g.game_id,
    g.title,
    g.description,
    g.cover_image,
    g.price,
    g.stock,
    g.status,
    g.release_date,
    g.created_at,
    g.developer_id,
    g.publisher_id,
    g.category_id,
    d.developer_name,
    p.publisher_name,
    c.category_name,
    (SELECT GROUP_CONCAT(ge.genre_name ORDER BY ge.genre_name SEPARATOR ', ')
       FROM game_genres gg JOIN genres ge ON ge.genre_id = gg.genre_id
      WHERE gg.game_id = g.game_id)                                       AS genres,
    (SELECT GROUP_CONCAT(pl.platform_name ORDER BY pl.platform_name SEPARATOR ', ')
       FROM game_platforms gp JOIN platforms pl ON pl.platform_id = gp.platform_id
      WHERE gp.game_id = g.game_id)                                       AS platforms,
    COALESCE((SELECT MAX(dc.discount_percentage) FROM discounts dc
               WHERE dc.game_id = g.game_id
                 AND CURDATE() BETWEEN dc.start_date AND dc.end_date), 0) AS discount_percentage,
    ROUND(g.price * (1 - COALESCE((SELECT MAX(dc.discount_percentage) FROM discounts dc
               WHERE dc.game_id = g.game_id
                 AND CURDATE() BETWEEN dc.start_date AND dc.end_date), 0) / 100), 2) AS final_price,
    COALESCE((SELECT ROUND(AVG(r.rating), 2) FROM reviews r WHERE r.game_id = g.game_id), 0) AS avg_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.game_id = g.game_id)          AS review_count,
    COALESCE((SELECT SUM(oi.quantity) FROM order_items oi
                JOIN orders o ON o.order_id = oi.order_id
               WHERE oi.game_id = g.game_id AND o.order_status IN ('paid','completed')), 0) AS units_sold
FROM games g
JOIN developers d ON d.developer_id = g.developer_id
JOIN publishers p ON p.publisher_id = g.publisher_id
LEFT JOIN categories c ON c.category_id = g.category_id;

-- One row per order with customer name and item count
CREATE OR REPLACE VIEW order_summary_view AS
SELECT
    o.order_id,
    o.user_id,
    u.name  AS customer_name,
    u.email AS customer_email,
    o.order_date,
    o.order_status,
    o.total_amount,
    COUNT(oi.order_item_id)              AS line_items,
    COALESCE(SUM(oi.quantity), 0)        AS total_units,
    pm.payment_method,
    pm.payment_status,
    pm.transaction_reference
FROM orders o
JOIN users u              ON u.user_id = o.user_id
LEFT JOIN order_items oi  ON oi.order_id = o.order_id
LEFT JOIN payments pm     ON pm.order_id = o.order_id
GROUP BY o.order_id, o.user_id, u.name, u.email, o.order_date, o.order_status,
         o.total_amount, pm.payment_method, pm.payment_status, pm.transaction_reference;

-- Sales performance per game
CREATE OR REPLACE VIEW sales_by_game_view AS
SELECT
    g.game_id,
    g.title,
    d.developer_name,
    COALESCE(SUM(oi.quantity), 0)              AS units_sold,
    COALESCE(SUM(oi.quantity * oi.price), 0)   AS revenue,
    COUNT(DISTINCT o.order_id)                 AS orders_count,
    COUNT(DISTINCT o.user_id)                  AS distinct_buyers
FROM games g
JOIN developers d        ON d.developer_id = g.developer_id
LEFT JOIN order_items oi ON oi.game_id = g.game_id
LEFT JOIN orders o       ON o.order_id = oi.order_id AND o.order_status IN ('paid','completed')
GROUP BY g.game_id, g.title, d.developer_name;

-- Every purchase a customer has made
CREATE OR REPLACE VIEW customer_purchase_history_view AS
SELECT
    u.user_id,
    u.name AS customer_name,
    u.email,
    o.order_id,
    o.order_date,
    o.order_status,
    g.game_id,
    g.title AS game_title,
    oi.quantity,
    oi.price,
    (oi.quantity * oi.price) AS line_total
FROM users u
JOIN orders o      ON o.user_id = u.user_id
JOIN order_items oi ON oi.order_id = o.order_id
JOIN games g       ON g.game_id = oi.game_id;

-- Rating breakdown per game
CREATE OR REPLACE VIEW game_rating_view AS
SELECT
    g.game_id,
    g.title,
    COUNT(r.review_id)                                    AS review_count,
    ROUND(AVG(r.rating), 2)                               AS avg_rating,
    SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END)         AS five_star,
    SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END)         AS four_star,
    SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END)         AS three_star,
    SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END)         AS two_star,
    SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END)         AS one_star
FROM games g
LEFT JOIN reviews r ON r.game_id = g.game_id
GROUP BY g.game_id, g.title;

-- =====================================================================
-- 12. QUICK VERIFICATION
-- =====================================================================
SELECT 'users' AS table_name, COUNT(*) AS rows_loaded FROM users
UNION ALL SELECT 'games', COUNT(*) FROM games
UNION ALL SELECT 'orders', COUNT(*) FROM orders
UNION ALL SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL SELECT 'payments', COUNT(*) FROM payments
UNION ALL SELECT 'library', COUNT(*) FROM library
UNION ALL SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL SELECT 'wishlists', COUNT(*) FROM wishlists
UNION ALL SELECT 'discounts', COUNT(*) FROM discounts;
