# 📦 BTMS — Bhumi's Tailor Management System

Robust Laravel-based PWA with session-aware Firebase push notifications, real-time updates, and mobile-friendly admin UX. Designed for installability, offline resilience, and deployment in subdirectories like `/btms`.

---

## 🚀 Installation

```bash
git clone https://github.com/sdaruka/btms.git
cd btms
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install && npm run dev
