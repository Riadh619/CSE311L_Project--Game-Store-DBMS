-- =====================================================================
-- GAME STORE  |  DBMS DEMONSTRATION QUERIES
-- File: database/queries.sql
-- Run after importing game_store.sql
-- =====================================================================
USE game_store;

-- ---------------------------------------------------------------------
-- A. BASIC SELECT / WHERE / ORDER BY / DISTINCT / LIKE / BETWEEN / IN
-- ---------------------------------------------------------------------

-- A1. All active games, newest first
SELECT game_id, title, price, stock, release_date
FROM games
WHERE status = 'active'
ORDER BY release_date DESC;

-- A2. LIKE - search games whose title contains 'neon'
SELECT game_id, title, price
FROM games
WHERE title LIKE '%neon%';

-- A3. BETWEEN - games in a price band
SELECT title, price
FROM games
WHERE price BETWEEN 20.00 AND 50.00
ORDER BY price ASC;

-- A4. IN - games released on PC or Steam Deck
SELECT DISTINCT g.title, g.price
FROM games g
JOIN game_platforms gp ON gp.game_id = g.game_id
WHERE gp.platform_id IN (1, 5)
ORDER BY g.title;

-- A5. DISTINCT - every country a developer operates from
SELECT DISTINCT country FROM developers ORDER BY country;

-- ---------------------------------------------------------------------
-- B. JOINS
-- ---------------------------------------------------------------------

-- B1. INNER JOIN - game with its developer and publisher
SELECT g.title, d.developer_name, p.publisher_name, g.price
FROM games g
INNER JOIN developers d ON d.developer_id = g.developer_id
INNER JOIN publishers p ON p.publisher_id = g.publisher_id
ORDER BY g.title;

-- B2. LEFT JOIN - every game with its review count (games with none show 0)
SELECT g.game_id, g.title, COUNT(r.review_id) AS review_count
FROM games g
LEFT JOIN reviews r ON r.game_id = g.game_id
GROUP BY g.game_id, g.title
ORDER BY review_count DESC, g.title;

-- B3. RIGHT JOIN - every user, including those who never ordered
SELECT u.user_id, u.name, COUNT(o.order_id) AS orders_placed
FROM orders o
RIGHT JOIN users u ON u.user_id = o.user_id
WHERE u.role = 'customer'
GROUP BY u.user_id, u.name
ORDER BY orders_placed DESC;

-- B4. Multi-table JOIN (5 tables) - full order line detail
SELECT o.order_id, u.name AS customer, g.title AS game, oi.quantity, oi.price,
       (oi.quantity * oi.price) AS line_total, pay.payment_method, pay.payment_status
FROM orders o
JOIN users u        ON u.user_id  = o.user_id
JOIN order_items oi ON oi.order_id = o.order_id
JOIN games g        ON g.game_id  = oi.game_id
LEFT JOIN payments pay ON pay.order_id = o.order_id
ORDER BY o.order_id, g.title;

-- B5. Self-contained many-to-many JOIN - games grouped by genre
SELECT ge.genre_name, GROUP_CONCAT(g.title ORDER BY g.title SEPARATOR ' | ') AS games
FROM genres ge
JOIN game_genres gg ON gg.genre_id = ge.genre_id
JOIN games g        ON g.game_id   = gg.game_id
GROUP BY ge.genre_name
ORDER BY ge.genre_name;

-- ---------------------------------------------------------------------
-- C. AGGREGATES / GROUP BY / HAVING
-- ---------------------------------------------------------------------

-- C1. Store-wide aggregate snapshot
SELECT COUNT(*) AS total_games, MIN(price) AS cheapest, MAX(price) AS dearest,
       ROUND(AVG(price),2) AS average_price, SUM(stock) AS total_stock
FROM games;

-- C2. Top selling games (business query)
SELECT g.game_id, g.title, SUM(oi.quantity) AS units_sold,
       ROUND(SUM(oi.quantity * oi.price),2) AS revenue
FROM order_items oi
JOIN orders o ON o.order_id = oi.order_id
JOIN games g  ON g.game_id  = oi.game_id
WHERE o.order_status IN ('paid','completed')
GROUP BY g.game_id, g.title
ORDER BY units_sold DESC, revenue DESC
LIMIT 10;

-- C3. Highest rated games with at least 2 reviews (HAVING)
SELECT g.title, ROUND(AVG(r.rating),2) AS avg_rating, COUNT(r.review_id) AS reviews
FROM games g
JOIN reviews r ON r.game_id = g.game_id
GROUP BY g.game_id, g.title
HAVING COUNT(r.review_id) >= 2
ORDER BY avg_rating DESC;

-- C4. Total revenue of the store
SELECT ROUND(SUM(total_amount),2) AS total_revenue,
       COUNT(*) AS paid_orders,
       ROUND(AVG(total_amount),2) AS average_order_value
FROM orders
WHERE order_status IN ('paid','completed');

-- C5. Revenue by genre
SELECT ge.genre_name,
       SUM(oi.quantity) AS units_sold,
       ROUND(SUM(oi.quantity * oi.price),2) AS revenue
FROM order_items oi
JOIN orders o       ON o.order_id = oi.order_id AND o.order_status IN ('paid','completed')
JOIN game_genres gg ON gg.game_id = oi.game_id
JOIN genres ge      ON ge.genre_id = gg.genre_id
GROUP BY ge.genre_name
ORDER BY revenue DESC;

-- C6. Revenue by month
SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS sales_month,
       COUNT(DISTINCT o.order_id) AS orders_count,
       ROUND(SUM(oi.quantity * oi.price),2) AS revenue
FROM orders o
JOIN order_items oi ON oi.order_id = o.order_id
WHERE o.order_status IN ('paid','completed')
GROUP BY DATE_FORMAT(o.order_date, '%Y-%m')
ORDER BY sales_month;

-- C7. Most active customers (by number of orders)
SELECT u.user_id, u.name, COUNT(o.order_id) AS total_orders
FROM users u
JOIN orders o ON o.user_id = u.user_id
GROUP BY u.user_id, u.name
ORDER BY total_orders DESC
LIMIT 5;

-- C8. Top spenders
SELECT u.user_id, u.name, u.email, ROUND(SUM(o.total_amount),2) AS lifetime_value
FROM users u
JOIN orders o ON o.user_id = u.user_id
WHERE o.order_status IN ('paid','completed')
GROUP BY u.user_id, u.name, u.email
ORDER BY lifetime_value DESC
LIMIT 5;

-- C9. Customers with more than five purchased items (HAVING on an aggregate)
SELECT u.user_id, u.name, SUM(oi.quantity) AS games_bought, COUNT(DISTINCT o.order_id) AS orders
FROM users u
JOIN orders o       ON o.user_id  = u.user_id AND o.order_status IN ('paid','completed')
JOIN order_items oi ON oi.order_id = o.order_id
GROUP BY u.user_id, u.name
HAVING SUM(oi.quantity) > 5
ORDER BY games_bought DESC;

-- C10. Most popular genre by units sold
SELECT ge.genre_name, SUM(oi.quantity) AS units_sold
FROM genres ge
JOIN game_genres gg ON gg.genre_id = ge.genre_id
JOIN order_items oi ON oi.game_id  = gg.game_id
JOIN orders o       ON o.order_id  = oi.order_id AND o.order_status IN ('paid','completed')
GROUP BY ge.genre_name
ORDER BY units_sold DESC
LIMIT 1;

-- C11. Sales grouped by developer
SELECT d.developer_name,
       COUNT(DISTINCT g.game_id) AS titles_published,
       COALESCE(SUM(oi.quantity),0) AS units_sold,
       ROUND(COALESCE(SUM(oi.quantity * oi.price),0),2) AS revenue
FROM developers d
JOIN games g            ON g.developer_id = d.developer_id
LEFT JOIN order_items oi ON oi.game_id = g.game_id
LEFT JOIN orders o       ON o.order_id = oi.order_id AND o.order_status IN ('paid','completed')
GROUP BY d.developer_id, d.developer_name
ORDER BY revenue DESC;

-- C12. Average rating per game, ordered
SELECT g.title, COALESCE(ROUND(AVG(r.rating),2), 0) AS avg_rating, COUNT(r.review_id) AS reviews
FROM games g
LEFT JOIN reviews r ON r.game_id = g.game_id
GROUP BY g.game_id, g.title
ORDER BY avg_rating DESC, reviews DESC;

-- ---------------------------------------------------------------------
-- D. SUBQUERIES / EXISTS / NOT EXISTS / CASE
-- ---------------------------------------------------------------------

-- D1. Games priced above the store average (scalar subquery)
SELECT title, price
FROM games
WHERE price > (SELECT AVG(price) FROM games)
ORDER BY price DESC;

-- D2. Games that were never purchased (NOT EXISTS)
SELECT g.game_id, g.title, g.price, g.stock
FROM games g
WHERE NOT EXISTS (
    SELECT 1 FROM order_items oi
    JOIN orders o ON o.order_id = oi.order_id AND o.order_status IN ('paid','completed')
    WHERE oi.game_id = g.game_id
)
ORDER BY g.title;

-- D3. Customers who have bought at least one RPG (EXISTS + nested join)
SELECT u.user_id, u.name
FROM users u
WHERE EXISTS (
    SELECT 1
    FROM orders o
    JOIN order_items oi ON oi.order_id = o.order_id
    JOIN game_genres gg ON gg.game_id = oi.game_id
    JOIN genres ge      ON ge.genre_id = gg.genre_id
    WHERE o.user_id = u.user_id AND ge.genre_name = 'RPG'
)
ORDER BY u.name;

-- D4. Low stock report with a CASE severity label
SELECT game_id, title, stock,
       CASE
           WHEN stock = 0            THEN 'Out of stock'
           WHEN stock <= 5           THEN 'Critical'
           WHEN stock <= 15          THEN 'Low'
           ELSE 'Healthy'
       END AS stock_status
FROM games
ORDER BY stock ASC;

-- D5. Currently discounted games with the discounted price
SELECT g.title, g.price AS original_price, dc.discount_percentage,
       ROUND(g.price * (1 - dc.discount_percentage/100), 2) AS discounted_price,
       dc.start_date, dc.end_date
FROM games g
JOIN discounts dc ON dc.game_id = g.game_id
WHERE CURDATE() BETWEEN dc.start_date AND dc.end_date
ORDER BY dc.discount_percentage DESC;

-- D6. The single best selling game (subquery in HAVING style)
SELECT g.title, SUM(oi.quantity) AS units_sold
FROM games g
JOIN order_items oi ON oi.game_id = g.game_id
GROUP BY g.game_id, g.title
HAVING SUM(oi.quantity) = (
    SELECT MAX(total_units) FROM (
        SELECT SUM(quantity) AS total_units FROM order_items GROUP BY game_id
    ) AS totals
);

-- D7. Customers who have never left a review
SELECT u.user_id, u.name
FROM users u
WHERE u.role = 'customer'
  AND u.user_id NOT IN (SELECT DISTINCT user_id FROM reviews)
ORDER BY u.name;

-- D8. Rating bucket summary with CASE
SELECT
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) AS five_star,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) AS four_star,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) AS three_star,
    SUM(CASE WHEN rating <= 2 THEN 1 ELSE 0 END) AS poor
FROM reviews;

-- ---------------------------------------------------------------------
-- E. VIEW USAGE
-- ---------------------------------------------------------------------

-- E1. Discounted catalogue straight from the view
SELECT title, price, discount_percentage, final_price, avg_rating
FROM game_catalog_view
WHERE discount_percentage > 0
ORDER BY discount_percentage DESC;

-- E2. Recent orders from the summary view
SELECT order_id, customer_name, total_amount, order_status, payment_status, order_date
FROM order_summary_view
ORDER BY order_date DESC
LIMIT 10;

-- E3. Best performing titles from the sales view
SELECT title, developer_name, units_sold, revenue
FROM sales_by_game_view
ORDER BY revenue DESC
LIMIT 10;

-- E4. One customer's full purchase history
SELECT order_id, order_date, game_title, quantity, price, line_total
FROM customer_purchase_history_view
WHERE user_id = 3
ORDER BY order_date;

-- E5. Rating distribution from the rating view
SELECT title, review_count, avg_rating, five_star, four_star, three_star
FROM game_rating_view
WHERE review_count > 0
ORDER BY avg_rating DESC;

-- ---------------------------------------------------------------------
-- F. UPDATE / DELETE  (write operations)
-- ---------------------------------------------------------------------

-- F1. Reduce stock after a sale
UPDATE games SET stock = stock - 1 WHERE game_id = 1 AND stock > 0;

-- F2. Mark a paid order as completed
UPDATE orders SET order_status = 'completed' WHERE order_id = 7 AND order_status = 'paid';

-- F3. Restock every game that has fallen to five units or fewer
UPDATE games SET stock = stock + 25 WHERE stock <= 5;

-- F4. Apply a price correction to one developer's catalogue
UPDATE games
SET price = ROUND(price * 0.95, 2)
WHERE developer_id = (SELECT developer_id FROM developers WHERE developer_name = 'Pixel Pantry');

-- F5. Promote a customer to admin
UPDATE users SET role = 'admin' WHERE email = 'sadia.admin@gamestore.com';

-- F6. Delete an expired discount
DELETE FROM discounts WHERE end_date < CURDATE();

-- F7. Delete a low rated review (admin moderation)
DELETE FROM reviews WHERE rating = 1;

-- F8. Clear one user's cart
DELETE ci FROM cart_items ci
JOIN carts c ON c.cart_id = ci.cart_id
WHERE c.user_id = 4;
