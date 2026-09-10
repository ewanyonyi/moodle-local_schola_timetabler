# Schola Timetabler Moodle Plugin (`local_schola_timetabler`)

**`Schola Timetabler`** is a free, open-source Moodle local plugin designed to generate conflict-free schedules for both **weekly course sessions** (lectures, labs) and **examination windows** (midterms, finals).

This plugin is a free, open-source Moodle local plugin:
* **Free Local Edition**: Built-in native PHP solver engine for local timetable generation, room management, slot creation, and exam scheduling under GPLv3.

---

## 🎧 Support & Contact Channels

* **Customer Support Email**: [wanyonyi.d.emanuel@gmail.com](mailto:wanyonyi.d.emanuel@gmail.com)
* **Repository**: [https://github.com/ewanyonyi/moodle-local_schola_timetabler](https://github.com/ewanyonyi/moodle-local_schola_timetabler)

---

## 📜 Licensing

`local_schola_timetabler` is distributed as a free Moodle local plugin project:

* **Native On-Premise Engine (Free / Community)**: Full weekly class timetabling, examination schedule generation, room/venue capacity management, bell schedule wizard, and CLI data generator running entirely on your Moodle server under GPLv3.
* **Local Solver Engine**: The plugin uses the native local PHP solver to compute conflict-free timetables directly on the Moodle server without any external API dependency.

---


## Local Development Setup (`~/dev/moodle` — Ubuntu 24.04 LTS)

This setup runs Moodle **v5.2.2** and your plugin directly out of your user development directory (`~/dev`), eliminating all `sudo` permission conflicts during development.

### 1. Install & Configure LAMP Dependencies

Ensure Apache, MariaDB, and PHP dependencies are installed:

```bash
sudo apt update && sudo apt install -y \
    apache2 mariadb-server php php-cli php-mysql \
    php-xml php-gd php-curl php-zip php-mbstring \
    php-intl php-soap git unzip
```

Configure PHP execution limits in `/etc/php/8.3/apache2/php.ini` and `/etc/php/8.3/cli/php.ini`:

```ini
max_execution_time = 300
memory_limit = 512M
post_max_size = 100M
upload_max_filesize = 100M
max_input_vars = 5000
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

---

### 2. Create MariaDB Database & User

Log into MariaDB and create a clean database for Moodle 5.2.2:

```sql
CREATE DATABASE moodle DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'moodleuser'@'localhost' IDENTIFIED BY 'YOUR_DATABASE_PASSWORD';
GRANT ALL PRIVILEGES ON moodle.* TO 'moodleuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

### 3. Install Moodle 5.2.2 Core & Plugin Directory in `~/dev`

1. **Clone Moodle Production Release (v5.2.2):**
   ```bash
   mkdir -p ~/dev
   cd ~/dev
   git clone -b v5.2.2 git://git.moodle.org/moodle.git moodle
   mkdir -p ~/dev/moodledata
   ```

2. **Create Plugin Folder Directly in the Dev Tree:**
   ```bash
   mkdir -p ~/dev/moodle/local/schola_timetabler
   ```

3. **Run Non-Interactive Automated Moodle CLI Installation:**
   ```bash
   cd ~/dev/moodle
   php admin/cli/install.php \
     --lang=en \
     --wwwroot=http://localhost \
     --dataroot=$HOME/dev/moodledata \
     --dbtype=mariadb \
     --dbhost=localhost \
     --dbname=moodle \
     --dbuser=moodleuser \
   --dbpass='YOUR_DATABASE_PASSWORD' \
     --fullname="Moodle Dev Site" \
     --shortname="moodle-dev" \
     --adminuser=admin \
   --adminpass='YOUR_ADMIN_PASSWORD' \
     --adminemail=admin@example.com \
     --non-interactive \
     --agree-license
   chmod 644 ~/dev/moodle/config.php
   ```

> [!TIP]
> **If `config.php` already exists**:
> If `config.php` was already created (either via web installer or prior configuration), use `install_database.php` to populate database tables directly without recreating `config.php`:
> ```bash
> cd ~/dev/moodle
> php admin/cli/install_database.php \
>   --agree-license \
>   --fullname="Moodle Dev Site" \
>   --shortname="moodle-dev" \
>   --adminpass='YOUR_ADMIN_PASSWORD' \
>   --adminemail=admin@example.com
> ```

---

### 4. Configure Apache Virtual Host for `~/dev/moodle`

1. **Create Virtual Host File:**
   ```bash
   sudo nano /etc/apache2/sites-available/moodle-dev.conf
   ```

2. **Insert Virtual Host Block (HTTP & HTTPS Support):**
   ```apache
   <VirtualHost *:80>
       ServerName localhost
       DocumentRoot /home/username/dev/moodle/public

       <Directory /home/username/dev/moodle/public>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/moodle_error.log
       CustomLog ${APACHE_LOG_DIR}/moodle_access.log combined
   </VirtualHost>

   <IfModule mod_ssl.c>
   <VirtualHost *:443>
       ServerName localhost
       DocumentRoot /home/username/dev/moodle/public

       <Directory /home/username/dev/moodle/public>
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>

       SSLEngine on
       SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
       SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key

       ErrorLog ${APACHE_LOG_DIR}/moodle_error.log
       CustomLog ${APACHE_LOG_DIR}/moodle_access.log combined
   </VirtualHost>
   </IfModule>
   ```

3. **Set Permissions & Enable Virtual Host:**
   ```bash
   # Grant directory traversal & file permissions to Apache (www-data)
   chmod 755 $HOME
   chmod 755 $HOME/dev
   chmod 644 ~/dev/moodle/config.php
   chmod -R 777 ~/dev/moodledata

   # Enable SSL module & Virtual Host
   sudo a2enmod ssl
   sudo a2ensite moodle-dev.conf
   sudo systemctl restart apache2
   ```

> [!NOTE]
> **Troubleshooting HTTP 500 Errors & Connection Refused (`https://localhost`)**:
> - **HTTP 500 (`config.php` Permission Denied)**: Check `/var/log/apache2/moodle_error.log`. Ensure read permissions are granted to Apache (`www-data`):
>   ```bash
>   chmod 644 ~/dev/moodle/config.php
>   ```
> - **`ERR_CONNECTION_REFUSED` (`https://localhost`)**: Modern browsers auto-redirect `localhost` to `https://`. Ensure you use `http://localhost` or enable SSL support in Apache:
>   ```bash
>   sudo a2enmod ssl
>   sudo a2ensite default-ssl
>   sudo systemctl restart apache2
>   ```

---

### 5. CLI Installation & Testing Workflow (No `sudo` Required)

Once web setup is complete at `http://localhost/`, manage database upgrades, CLI tasks, and coding directly in your user environment:

* **Run Moodle Upgrade / Schema Install:**
  ```bash
  cd ~/dev/moodle
  php admin/cli/upgrade.php
  ```

* **Execute Solver Background Task:**
  ```bash
  cd ~/dev/moodle
  php admin/cli/adhoc_task.php --execute
  ```

---

### 6. Seeding Test Data (CLI Data Generator)

To test the timetabling engine under realistic conditions, populate your Moodle database with academic data (Faculties, Courses, Quizzes/Exams, Faculty Members, Students, Campus Rooms, and Time Slots) using the built-in CLI generator:

#### Quick Run (Default Institutional Scale: 60 Courses, 35 Faculty, 300 Students, 25 Rooms, 20 Slots)

```bash
cd ~/dev/moodle
php local/schola_timetabler/cli/populate_academic_data.php
```

#### Custom Scale Parameters

You can specify custom parameters to represent smaller testing scopes or massive institutional scales:

```bash
# Populate a large institution (100 Courses, 50 Faculty, 500 Students)
php local/schola_timetabler/cli/populate_academic_data.php --courses=100 --teachers=50 --students=500

# Reset/clear existing rooms and time slots before populating
php local/schola_timetabler/cli/populate_academic_data.php --clear

# View all CLI flags and options
php local/schola_timetabler/cli/populate_academic_data.php --help
```

#### Generated Resources Overview

* **Faculties / Categories**: 10 Academic Faculties (Computing, Engineering, Health Sciences, Business, Law, etc.).
* **Faculty Accounts**: `faculty_1` .. `faculty_N` *(passwords are generated by the CLI tool)*.
* **Student Accounts**: `student_1` .. `student_N` *(passwords are generated by the CLI tool)*.
* **Exams**: 1 Midterm Exam and 1 Final Exam Quiz automatically created per course.
* **Campus Rooms (`local_ss_rooms`)**: Auditoriums (300–500 capacity), Lecture Halls (120–180 capacity), Specialized Computer & Science Labs, Classrooms.
* **Time Slots (`local_ss_slots`)**: Mon–Fri recurring class time windows + Morning/Afternoon Exam windows.


---

## Moodle CodeSniffer (`phpcs`) Global Setup Guide

Moodle core's `composer install` does not include `phpcs` in its local `vendor/bin` directory. Follow these steps to install Moodle's official CodeSniffer tools globally on your Ubuntu machine.

---

### Step 1: Install Moodle CodeSniffer Globally

Run these commands in your terminal to download `phpcs` and the official Moodle CodeSniffer rules (`moodle-cs`):

```bash
composer global config minimum-stability dev
composer global require moodlehq/moodle-cs
```

### Step 2: Add Global Composer Binaries to Environment PATH
Ensure your terminal can execute commands installed by global Composer packages:

Open your shell configuration file:
```bash
nano ~/.bashrc
```
Add the following line at the end of the file:
```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Save the file and reload your shell configuration:
```bash
source ~/.bashrc
```
Step 3: Verify & Run CodeSniffer
Confirm that phpcs is accessible globally:

```bash
phpcs --version
```

Run the Moodle standard code check against your local plugin workspace:

# Standard check
```bash
phpcs --standard=moodle ~/dev/moodle/local/schola_timetabler
```

# Extended check (optional)

```bash
phpcs --standard=moodle-extra ~/dev/moodle/local/schola_timetabler
```

---

## ⚖️ Software License

This plugin is free software: you can redistribute it and/or modify it under the terms of the **GNU General Public License (GPL)** as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

* **License**: GNU General Public License v3.0 or later ([GPL-3.0-or-later](http://www.gnu.org/copyleft/gpl.html))
* **Copyright**: © 2026 Emanuel Dickson Wanyonyi <wanyonyi.d.emanuel@gmail.com>
* **Moodle Directory Compliance**: Fully compliant with Moodle Plugins Directory open-source publishing guidelines.

