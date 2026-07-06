# Ignite 🔥

A Laravel + Vue.js goal tracking application that helps users visualize their progress and stay motivated to achieve their objectives.

## 📖 About

Ignite is a full-stack web application designed to combat project abandonment by making progress visible and tangible. Research shows that visual cues (progress bars, checklists, levels, etc.) motivate the brain by making advancement concrete. Ignite provides various visualization tools to help users track and complete their goals.

## ✨ Features

- **Goal Management**: Create and manage different types of goals (simple, quantifiable, recurring, multi-step)
- **Progress Tracking**: Record your progress with entries, notes, and attachments
- **Visual Dashboards**: Multiple visualization options including:
  - Progress bars and circular indicators
  - Charts and graphs (line, bar, pie)
  - Kanban boards
  - Calendar heatmaps
  - Timeline views
- **Gamification**: Earn points, unlock achievements, and track streaks
- **Milestones**: Break down large goals into smaller, manageable checkpoints
- **Categories**: Organize goals by category (health, career, finance, learning, etc.)
- **Notifications**: Get reminders and encouragement to stay on track

## 🛠️ Tech Stack

- **Backend**: Laravel 13
- **Frontend**: Vue.js 3 + Inertia.js
- **Database**: PostgreSQL
- **Styling**: Tailwind CSS v4
- **Charts**: ApexCharts

## 🚀 Installation

There are two ways to set up a development environment. **Docker is the recommended path** — it needs nothing on your machine besides Docker itself and guarantees the same stack for everyone (nginx, PHP-FPM, PostgreSQL 18, queue worker).

### Option A — Docker (recommended)

**Requirements:** [Docker Desktop](https://docs.docker.com/get-docker/) for Windows (or Docker Engine + Compose)

1. **Clone the repository**
   ```bash
   git clone https://github.com/Promethys/ignite.git
   cd ignite
   ```

2. **Environment setup**
   ```bash
   cp .env.example .env
   ```

3. **Build and start the stack**
   ```bash
   docker compose -f compose.dev.yaml up --build -d
   ```
   Migrations run automatically on startup. Five services come up: `web` (nginx), `php-fpm`, `workspace` (CLI tools), `postgres`, and `queue` (queue worker).

4. **Generate the app key and seed (first run only)**
   ```bash
   docker compose -f compose.dev.yaml exec workspace php artisan key:generate
   docker compose -f compose.dev.yaml exec workspace php artisan db:seed
   ```

5. **Start Vite for hot module reload** (optional, for frontend work)
   ```bash
   docker compose -f compose.dev.yaml exec workspace npm install
   docker compose -f compose.dev.yaml exec workspace npm run dev
   ```

Visit `http://localhost:8080` in your browser.

Useful commands:
```bash
docker compose -f compose.dev.yaml exec workspace php artisan <cmd>   # any artisan command
docker compose -f compose.dev.yaml logs -f php-fpm                    # tail logs
docker compose -f compose.dev.yaml down                               # stop (data persists)
docker compose -f compose.dev.yaml down -v                            # stop + wipe database
```

> **Note:** the Docker PostgreSQL is published on host port **5433** (to avoid clashing with a native PostgreSQL on 5432). Connect from your host tools with `localhost:5433`.

### Option B — Native setup

**Requirements:** PHP 8.5+, Composer, Node.js & NPM, PostgreSQL 18+

1. **Clone the repository**
   ```bash
   git clone https://github.com/Promethys/ignite.git
   cd ignite
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure your database** in `.env`
   ```env
   DB_CONNECTION=pgsql
   DB_HOST=127.0.0.1
   DB_PORT=5432
   DB_DATABASE=ignite
   DB_USERNAME=postgres
   DB_PASSWORD=secret
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Start everything** (server, queue worker, and Vite in one command)
   ```bash
   composer run dev
   ```

Visit `http://localhost:8000` in your browser.

## 📊 Database Structure

- **users**: User accounts and authentication
- **categories**: Goal categories for organization
- **goals**: User objectives with various types and tracking
- **goal_entries**: Progress records for each goal
- **milestones**: Checkpoints within goals
- **achievements**: Unlockable badges and rewards
- **user_achievements**: Tracking user progress on achievements

## 🎯 Usage

1. **Create an account** and log in
2. **Add categories** to organize your goals (optional)
3. **Create your first goal** with a target and deadline
4. **Track your progress** by adding entries regularly
5. **View your dashboard** to see visualizations and statistics
6. **Unlock achievements** as you make progress
7. **Stay motivated** with reminders and visual feedback

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📝 License

This project is open-source and available under the [MIT License](LICENSE).

## 👤 Author

Your Name - [@nirine1](https://github.com/nirine1)

## 🙏 Acknowledgments

- Inspired by the psychology of visual progress tracking
- Built with Laravel, Vue.js, and Inertia.js
- Icons from Lucide Icons

---

Made with ❤️ to help you achieve your goals
