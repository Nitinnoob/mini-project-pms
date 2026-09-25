# mini-project-pms (SyncSpace)

PMS is a web-based academic project management system built with PHP, MySQL, JavaScript, and Tailwind CSS to streamline student mini-projects, team collaboration, and teacher evaluations.

## How to Set Up and Run

1. **Install XAMPP**:
   - Download and install XAMPP (with Apache and MySQL / MariaDB).
   - Start both the **Apache** and **MySQL** modules from the XAMPP Control Panel.

2. **Clone / Place Project**:
   - Place this repository folder into your XAMPP `htdocs` directory:
     `C:\xampp\htdocs\pjtmgmt2`

3. **Set Up the Database**:
   - Open phpMyAdmin in your browser: `http://localhost/phpmyadmin/`
   - Import or run the SQL queries in `schema.sql` to create the `pms` database and all required tables.
   - Verify database connection settings in `dbs.php` (default: host `localhost`, user `root`, password ``, database `pms`).

4. **Run the Application**:
   - Visit `http://localhost/pjtmgmt2/login.php` in your browser.
   - Create an account via `registers.php` or preview the interface using the built-in UI demo links on `hub.php`.
