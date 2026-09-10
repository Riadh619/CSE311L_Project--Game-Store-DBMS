[README.md](https://github.com/user-attachments/files/32077147/README.md)
 # CSE311L Game Store DBMS Project NSU 

A Game Store e-commerce project developed for the CSE311L Database Management Systems Lab.

A complete PHP + MySQL storefront built for a Database Management Systems course.
It is a working full-stack application, not a UI mockup: real authentication, a real
shopping cart, a transactional checkout, and a full admin panel — all reading and
writing to a normalised MySQL database.

**Stack:** HTML5 · CSS3 · vanilla JavaScript · PHP 8 (PDO) · MySQL · XAMPP

---

## 1. Install XAMPP

1. Download XAMPP from `https://www.apachefriends.org` and install it (PHP 8 or newer).
2. Open the **XAMPP Control Panel**.
3. Press **Start** next to **Apache** and next to **MySQL**. Both should turn green.

## 2. Put the project in place

Copy the `game_store` folder into your XAMPP htdocs directory so the path reads:

```
C:\xampp\htdocs\game_store\
```

The folder must be named `game_store`. If you rename it, also change `BASE_URL`
at the top of `backend/database.php`.

## 3. Create the database

1. Open `http://localhost/phpmyadmin` in your browser.
2. Click the **Import** tab at the top.
3. Choose the file `game_store/database/game_store.sql`.
4. Scroll down and press **Go**.

The script drops any old copy, creates the `game_store` database, builds all 17 tables
and 5 views, and loads the sample data. A summary table of row counts prints when it
finishes.

To run the demonstration queries afterwards, open the **SQL** tab, paste in a query from
`database/queries.sql`, and press **Go**.

## 4. Open the site

```
http://localhost/game_store/
```

To edit the code, open the `game_store` folder in VS Code.

## 5. Log in

The sample data ships with working accounts:

| Role     | Email                  | Password   |
|----------|------------------------|------------|
| Admin    | admin@gamestore.com    | admin123   |
| Admin    | sadia.admin@gamestore.com | admin123 |
| Customer | tanvir@example.com     | user123    |
| Customer | nusrat@example.com     | user123    |

Every other sample customer also uses `user123`. The admin panel is at
`http://localhost/game_store/admin/login.php`.

### Creating your own admin account

Register normally at `register.php`, then either promote yourself from
**Admin → Users → Make admin**, or run this in phpMyAdmin:

```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

## 6. Test it works

Walk through this list to confirm every part of the stack:

1. **Register** a new account — check `users` in phpMyAdmin and confirm the password
   column holds a `$2y$` bcrypt hash, not plain text.
2. **Log in and out.**
3. **Search** for `neon` and **filter** by genre, platform and price on the store page.
4. **Add to cart** from a game card — the badge updates without a page reload.
5. **Increase, decrease and remove** cart lines; try adding more copies than the stock
   allows and confirm it is refused.
6. **Checkout** — then check that `orders`, `order_items`, `payments` and `library` all
   gained rows, `games.stock` went down, and `cart_items` is empty.
7. **Review** a game you own (allowed) and one you do not (refused).
8. **Admin panel** — add a game, edit it, change an order status, schedule a discount,
   restock inventory, delete a review.

---

## Folder structure

```
game_store/
├── index.php              storefront homepage
├── games.php              catalogue: search, filters, sorting, pagination
├── game_details.php       single game, reviews, cart and wishlist
├── login.php  register.php  logout.php
├── cart.php  checkout.php
├── orders.php  order_details.php
├── wishlist.php  library.php  reviews.php  profile.php
├── about.php  contact.php
├── admin/
│   ├── login.php  dashboard.php
│   ├── games.php  add_game.php  edit_game.php
│   ├── categories.php  genres.php  discounts.php  inventory.php
│   ├── orders.php  users.php  reviews.php
│   └── header.php  footer.php
├── backend/
│   ├── database.php           PDO connection (edit credentials here)
│   ├── functions.php          sessions, auth guards, escaping, helpers
│   ├── auth.php               register / login / profile / password
│   ├── cart_actions.php       add, increase, decrease, remove, clear
│   ├── wishlist_actions.php   add, remove, toggle, move to cart
│   ├── checkout_process.php   the checkout transaction
│   ├── review_actions.php     save and delete reviews
│   ├── game_card.php          reusable card partial
│   └── header.php  footer.php
├── css/    style.css, admin.css
├── js/     main.js
├── images/ covers/ (SVG cover art) + placeholder.svg
└── database/
    ├── game_store.sql     schema, constraints, indexes, sample data, views
    └── queries.sql        the DBMS demonstration queries
```

## Database at a glance

**17 tables:** users, games, developers, publishers, genres, platforms, categories,
game_genres, game_platforms, carts, cart_items, wishlists, orders, order_items,
payments, library, reviews, discounts.

**5 views:** `game_catalog_view`, `order_summary_view`, `sales_by_game_view`,
`customer_purchase_history_view`, `game_rating_view`.

**Key relationships:** a user has many orders; an order has many order items; a game has
many order items and many reviews; games relate to genres and to platforms many-to-many;
a user has exactly one cart; a cart has many cart items; users and games meet
many-to-many through the wishlist; a game has many discounts; developers and publishers
each have many games.

## How the checkout transaction works

`backend/checkout_process.php` wraps the whole purchase in one MySQL transaction, so it
either all succeeds or nothing changes:

1. Re-check stock with `SELECT ... FOR UPDATE` (locks the rows)
2. Insert the `orders` row
3. Insert each `order_items` row at the discounted price
4. Insert the `payments` record with a transaction reference
5. Reduce `games.stock`
6. Copy each purchased game into `library` with a licence key
7. Empty the cart

If any step throws — a game sells out mid-checkout, for instance — the whole thing rolls
back and the customer keeps their cart.

## Security

- Passwords hashed with `password_hash()` and checked with `password_verify()`; plain
  text is never stored
- Every query uses PDO prepared statements with bound parameters, and
  `ATTR_EMULATE_PREPARES` is off so they are prepared by MySQL itself
- All output escaped through `htmlspecialchars()` before it reaches the page
- CSRF tokens on every state-changing form
- Session-based auth with `session_regenerate_id()` on login
- Role checks (`require_login()`, `require_admin()`) guard customer and admin pages
- Uploaded cover images are checked by MIME type and size

## Notes for the demo

- Payment is simulated. Choosing "cash on delivery" leaves the order `pending`; every
  other method marks it `paid`.
- Deleting a game that appears in past orders will hide it from the store instead of
  deleting the row, because the order history holds a foreign key to it. This is
  intentional — order history should not be destroyed by a catalogue edit.
- Cover art is SVG so the project stays small; upload JPG or PNG covers from the admin
  panel if you prefer.

  ## Screenshot of the project

  <img width="1707" height="905" alt="Screenshot 2026-09-11 030442" src="https://github.com/user-attachments/assets/c9f907c1-be71-4480-8125-c1c785895bd0" />

