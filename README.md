# SamFix

## Description
SamFix is a web-based Phone Repair Management System designed to provide customers with a convenient and hassle-free way to access phone repair services. It allows customers to browse available repair services, check estimated prices, book repair appointments, and track their device repair progress online.

With its user-friendly interface, SamFix helps customers stay informed about their repairs without the need for constant phone calls or manual inquiries. The system also provides workshop information, contact details, and customer support to make the repair experience easier and more accessible.

## Purpose
Phone repair shops often handle bookings and status questions by phone or by hand, which is slow for customers and staff alike. SamFix moves this online:

- **Customers** book a repair, get a ticket number, and check progress at any time.
- **Shop staff** receive every booking in one place, update repair statuses, assign technicians, set estimated costs, and manage services and prices from a private admin dashboard.

## Features

**Customer portal**
- **Repair Tracking** – Track the current status of a phone repair using a unique ticket number.
- **Repair Services** – Browse available phone repair services and their starting prices.
- **Online Booking** – Submit repair requests by providing personal information, device details, and repair concerns.
- **Repair Status Updates** – View repair progress, including Pending, In Progress, Ready for Pickup, Completed, and Cancelled.
- **Estimated Repair Cost** – Check the estimated cost of a repair request.
- **Technician Information** – View the assigned technician when available.
- **Workshop Information** – View the repair shop's address, contact number, email, and operating hours.
- **Google Maps Integration** – Locate the workshop and get directions.
- **Help and Support** – Access basic repair information and customer assistance.

**Admin dashboard**
- Secure sign in for administrators.
- Manage repairs: search, filter by status, edit status, technician, cost and notes, delete, and export to CSV.
- Manage services and prices, customers, reports, and team members (technicians and administrators).

## Built with
PHP, MySQL, Tailwind CSS (CDN), JavaScript, Font Awesome and Lucide icons. No framework and no build step.

## Requirements
- PHP 8.0 or newer with the `pdo_mysql` and `mbstring` extensions
- MySQL 5.7+ or MariaDB 10.3+
- A web browser and an internet connection (styles and icons load from CDNs)

A local environment such as XAMPP, Laragon or WAMP includes all of this.

## Installation and setup
1. **Get the project.** Download or clone the SamFix repository, then open the project folder in Visual Studio Code.
2. **Create the database.** In phpMyAdmin (Import tab) or the MySQL console, import these files in order:
   1. `schema.sql` – creates the `samfix` database and its tables (**this deletes existing data**, so use it on a new database).
   2. `seed.sql` – adds sample repair services.
3. **Configure the project.** Open `includes/config.php` and set:
   - the database connection: `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `BASE_URL`: `''` if you open the site at `localhost:8000`, or `'/SamFix'` if you open it as `localhost/SamFix/`
   - your shop details: `SHOP_NAME`, `SHOP_ADDRESS`, `SHOP_HOURS`, `SHOP_PHONE`, `SHOP_EMAIL`
4. **Create an administrator account.** There is no default admin. Make a password hash from the project folder:
   ```
   php -r "echo password_hash('YourStrongPassword', PASSWORD_DEFAULT);"
   ```
   Then run this in the SQL tab, using your own username and the hash (also see `create_admin.sql`):
   ```sql
   USE samfix;
   INSERT INTO administrators (username, password_hash)
   VALUES ('your_username', '$2y$10$PASTE_THE_WHOLE_HASH_HERE');
   ```

## Running the system
**PHP built-in server (recommended).** From the project folder:
```
php -S localhost:8000 router.php
```
Then open **http://localhost:8000** in your browser. (`router.php` shows the "Page not found" page and blocks private files such as `.sql` files.)

**XAMPP / Apache.** Place the project folder in `htdocs`, start Apache and MySQL, and open **http://localhost/SamFix/** (set `BASE_URL` to `'/SamFix'`).

The admin area is at **/admin/login.php**. Sign in with the administrator account you created in step 4.

## Usage

**Customers**
1. Open the SamFix website.
2. Browse the available phone repair services.
3. Navigate to the **Book a Repair** section.
4. Enter your name, email address, phone number, device brand, and model.
5. Select the repair type and describe the problem.
6. Submit your repair request.
7. Save the ticket number provided after booking (for example `SFX-1506`).
8. Navigate to **Track Repair** and enter your ticket number.
9. View your repair status, estimated cost, technician information, and repair progress.
10. Visit the workshop or contact the service center if you need further assistance.

**Administrators**
1. Sign in at `/admin/login.php`.
2. Open **Repair jobs** to see new bookings, then open a repair to change its status, assign a technician, set the estimated cost, or add notes. Customers see the update on the tracking page.
3. Use **Services & prices** to add, edit, or delete the repair services shown on the website.
4. Use **Team members** to add technicians and administrators.
5. Sign out with the log out icon (you will be asked for your password).

## Contributing
Contributions are welcome. To suggest a change:
1. Fork the repository.
2. Create a branch for your change.
3. Commit your work with a clear message.
4. Open a pull request describing what you changed and why.

## License
This project is for educational purposes. Add your chosen license here (for example MIT) and include the license file in the repository.