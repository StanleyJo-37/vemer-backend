<div align="center">
  <h1><b>Vemer</b></h1>
  <p><i>Your Go-To Platform for Volunteer and Activity Management, Gamified!</i></p>

  <p>
    <a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
  </p>
</div>

---

### **📖 Table of Contents**
<details open>
  <summary>Click to view</summary>
  <ol>
    <li><a href="#about-the-project">About The Project</a></li>
    <li><a href="#related-projects">Related Projects</a></li>
    <li><a href="#key-features">Key Features</a></li>
    <li><a href="#tech-stack">Tech Stack</a></li>
    <li><a href="#getting-started">Getting Started</a></li>
    <li><a href="#api-endpoints">API Endpoints</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
  </ol>
</details>

---

## About The Project 🎯

**Vemer** is a centralized activity management system built with Laravel, designed to bridge the gap between event organizers and volunteers. It introduces a layer of **gamification**—including points, badges, and leaderboards—to create an engaging and rewarding experience for participants, motivating them to join and contribute to events.

Whether you're an organizer looking for an efficient way to manage your events or a volunteer searching for meaningful opportunities, Vemer provides the tools you need in one seamless platform.

---

## Related Projects 🔗

This repository contains the backend API for the Vemer platform.

- **Frontend Repository**: [**`github.com/StanleyJo-37/vemer-frontend`**](https://github.com/StanleyJo-37/vemer-frontend)

---

## Key Features ✨

Vemer is packed with features to serve both event organizers and participants:

#### For Everyone 👤
- **Secure Authentication**: Robust user registration and login, with support for Google SSO.
- **Activity Discovery**: Browse and search for activities based on type, date, and keywords.
- **Gamified Profile**: Track points, view earned badges, and see your level progress.
- **Global Leaderboard**: See how you rank against other volunteers in the community.
- **Personalized Dashboard**: Get recommendations, view upcoming events, and see announcements.

#### For Publishers (Event Organizers) 🎪
- **Create & Manage Activities**: A dedicated dashboard to create, update, and manage events.
- **Custom Badge Creation**: Design and award unique badges to participants for their achievements.
- **Participant Management**: Easily view and approve participant enrollment for your activities.
- **Notifications**: Keep your participants informed with activity-specific announcements.

---

## Tech Stack 🛠️

This project is built with a modern and powerful tech stack. Click on any badge to visit its official website.

| Category      | Technology   | Badge                                                                                                                                                           |
| :------------ | :----------- | :-------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Backend** | PHP          | <a href="https://www.php.net" target="_blank">![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)</a>                   |
| **Backend** | Laravel      | <a href="https://laravel.com" target="_blank">![Laravel](https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)</a>         |
| **Database** | PostgreSQL   | <a href="https://www.postgresql.org" target="_blank">![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=for-the-badge&logo=postgresql&logoColor=white)</a> |
| **Frontend** | JavaScript   | <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript" target="_blank">![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)</a> |
| **Frontend** | Vite         | <a href="https://vitejs.dev" target="_blank">![Vite](https://img.shields.io/badge/Vite-646CFF?style=for-the-badge&logo=vite&logoColor=white)</a>                   |
| **Frontend** | Tailwind CSS | <a href="https://tailwindcss.com" target="_blank">![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)</a> |
| **Storage** | Supabase     | <a href="https://supabase.com" target="_blank">![Supabase](https://img.shields.io/badge/Supabase-3FCF8E?style=for-the-badge&logo=supabase&logoColor=white)</a>     |

---

## Getting Started 🚀

To get a local copy up and running, please follow these steps.

### **Prerequisites**

Make sure you have the following installed on your development machine:
- PHP >= 8.2
- Composer
- Node.js & npm
- PostgreSQL

### **Installation Guide**
<details>
  <summary><strong>Click to expand installation steps</strong></summary>
  <br />
  <ol>
    <li>
      <strong>Clone the repository:</strong>
      <pre><code>git clone https://github.com/your-username/vemer-backend.git
cd vemer-backend</code></pre>
    </li>
    <li>
      <strong>Install PHP and JavaScript dependencies:</strong>
      <pre><code>composer install
npm install</code></pre>
    </li>
    <li>
      <strong>Set up your environment:</strong>
      <p>Copy the <code>.env.example</code> file to <code>.env</code> and generate your application key.</p>
      <pre><code>cp .env.example .env
php artisan key:generate</code></pre>
      <p>Next, open your <code>.env</code> file and configure your database (<code>DB_*</code>), Supabase storage, and Google SSO credentials.</p>
    </li>
    <li>
      <strong>Run database migrations and seeders:</strong>
      <p>This will create the necessary tables and populate them with initial data.</p>
      <pre><code>php artisan migrate --seed</code></pre>
    </li>
    <li>
      <strong>Build frontend assets:</strong>
      <pre><code>npm run build</code></pre>
    </li>
    <li>
      <strong>Launch the application:</strong>
      <p>Run the backend and frontend development servers in separate terminals.</p>
      <pre><code># Run the Laravel backend server
php artisan serve

# Run the Vite frontend server for hot-reloading
npm run dev</code></pre>
    </li>
  </ol>
</details>

---

## API Endpoints 🔌

Vemer is powered by a RESTful API. All routes are defined in `routes/v1/api.php`.

<details>
  <summary><strong>Click to see example API endpoints</strong></summary>
  <br />
  
  **Public Routes**
  - `POST /public/auth/register`
  - `POST /public/auth/login`
  - `GET /public/leaderboard/user`

  **Authenticated Routes**
  - `GET /auth/me`
  - `GET /auth/activities`
  - `POST /auth/dashboard/publisher/create-activity`
  - `GET /auth/dashboard/user/stats`

  *For a complete list of endpoints, please refer to the `routes/v1/api.php` file.*
</details>

---

## Contributing 🤝

Contributions are what make the open-source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement". Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## License 📄

This project is open-sourced software licensed under the **MIT license**. See the `LICENSE` file for more information.
