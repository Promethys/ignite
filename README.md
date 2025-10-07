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

- **Backend**: Laravel 11
- **Frontend**: Vue.js 3 + Inertia.js
- **Database**: MySQL/PostgreSQL
- **Styling**: Tailwind CSS
- **Charts**: Chart.js / ApexCharts

## 📋 Requirements

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL 8.0+ or PostgreSQL 13+

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/Promethys/ignite.git
   cd ignite
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Configure your database** in `.env`
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=ignite
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

9. **In another terminal, start Vite** (for development)
   ```bash
   npm run dev
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
