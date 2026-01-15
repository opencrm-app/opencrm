# Laravel Vue Inertia App

This is a modern web application built with Laravel, Vue.js 3, and Inertia.js. It features a seamless developer experience and a robust tech stack for building powerful applications.

---

## 🚀 Getting Started

Follow these steps to get the project up and running on your local machine.

### 📋 Prerequisites

Ensure you have the following installed:
- **PHP 8.2+**
- **Composer**
- **Node.js & NPM**
- **MySQL** or **SQLite**

---

## 🛠️ Installation & Setup

### 1. Clone the repository
```bash
git clone <repository-url>
cd <project-folder>
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Install JavaScript Dependencies
```bash
npm install
```

### 4. Environment Configuration
Copy the example environment file and configure your settings:
```bash
cp .env.example .env
```

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Storage Link
Create a symbolic link from `public/storage` to `storage/app/public`:
```bash
php artisan storage:link
```

---

## 🗄️ Database Setup

You can use either **MySQL** or **SQLite** for this project.

### Option A: SQLite (Quickest)
1. In your `.env` file, set the database connection to `sqlite`:
   ```env
   DB_CONNECTION=sqlite
   ```
2. Create the SQLite database file:
   ```bash
   # Linux/macOS
   touch database/database.sqlite
   
   # Windows (PowerShell)
   New-Item -ItemType File database/database.sqlite
   ```

### Option B: MySQL
1. Create a new database in your MySQL server (e.g., `laravel_db`).
2. Update your `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

### Run Migrations
Once your database is configured, run the following command to create the tables:
```bash
php artisan migrate
```

---

## � Running the Application

To start the development environment, you need to run both the Laravel server and the Vite dev server.

### Method 1: Concurrently (Recommended)
This project includes a helper script to run everything at once:
```bash
composer dev
```
This will start the PHP server, Vite, and the queue listener.

### Method 2: Separate Terminals
**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend):**
```bash
npm run dev
```

---

## 📦 Tech Stack

- **Backend:** [Laravel 12](https://laravel.com)
- **Frontend:** [Vue.js 3](https://vuejs.org) with [Inertia.js](https://inertiajs.com)
- **Styling:** [Tailwind CSS 4](https://tailwindcss.com)
- **PDF Generation:** [Laravel-dompdf](https://github.com/barryvdh/laravel-dompdf)
- **Icons:** [Lucide Vue Next](https://lucide.dev)
- **UI Components:** [Reka UI](https://reka-ui.com) & Custom Tailwind components

---

##  License

This project is open-source and available under the **MIT License**.
