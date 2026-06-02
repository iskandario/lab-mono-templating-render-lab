# Templating Render Lab

Templating Render Lab — это полнофункциональное веб-приложение для сравнения и тестирования различных движков шаблонов, таких как Handlebars, Pug и EJS. Проект представляет собой монорепозиторий с фронтендом на Vue 3 и бэкендом на PHP с PostgreSQL.

## Описание проекта

Приложение позволяет:
- Сравнивать шаблоны на разных движках (Handlebars, Pug, EJS) с одинаковым JSON-контекстом
- Запускать бенчмарки для оценки производительности шаблонов
- Сохранять и делиться результатами тестов
- Управлять личной библиотекой шаблонов
- Делиться состоянием песочницы через публичные ссылки

Проект состоит из:
- **Frontend**: Vue 3 + Vite + TypeScript + Vuetify, реализует пользовательский интерфейс и клиентский рендеринг шаблонов
- **Backend**: PHP с PostgreSQL, обеспечивает API для аутентификации, управления шаблонами и сохранения результатов тестов
- **Инфраструктура**: Docker и docker-compose для удобного развертывания

## Архитектура

```
mono-templating-render-lab/
├── backend/                # PHP backend с PostgreSQL
│   ├── application/        # Логика приложения
│   ├── domain/             # Доменная модель
│   ├── infrastructure/     # Инфраструктурный код
│   └── public/             # Публичная точка входа
├── frontend/               # Vue 3 frontend
│   ├── src/                # Исходный код
│   ├── features/           # Функциональные компоненты
│   └── public/             # Публичные ресурсы
└── docker-compose.yml      # Оркестрация контейнеров
```

## Технологии

### Frontend
- Vue 3 (Composition API)
- TypeScript
- Vite (сборка и разработка)
- Vuetify (UI-компоненты)
- Pinia (управление состоянием)
- Vue Router (навигация)
- Monaco Editor (редактор кода)
- Handlebars, Pug, EJS (движки шаблонов)

### Backend
- PHP 8.2+
- PostgreSQL
- PDO (работа с базой данных)
- JWT (аутентификация)

### Инфраструктура
- Docker
- Docker Compose

## Установка и запуск

### Предварительные требования
- Node.js (версия 20.19.0 или >=22.12.0)
- npm
- Docker и Docker Compose
- PHP 8.2+ (для локального запуска backend)
- PostgreSQL (для локального запуска backend)

### Быстрый старт (через Docker)

Самый простой способ запустить проект:

```bash
# Клонируйте репозиторий и перейдите в директорию проекта
cd mono-templating-render-lab

# Запустите все сервисы через Docker
docker compose up --build

# Выполните миграции базы данных
docker compose exec backend php bin/migrate.php
```

После этого:
- Frontend будет доступен по адресу: `http://localhost:4173`
- Backend будет доступен по адресу: `http://localhost:8000`
- Swagger UI: `http://localhost:8000/docs`
- OpenAPI JSON: `http://localhost:8000/openapi.json`

### Локальная разработка frontend

Для разработки frontend с использованием моков (без бэкенда):

```bash
# Перейдите в директорию frontend
cd frontend

# Установите зависимости
npm install

# Запустите dev-сервер (с моками)
npm run dev
```

Frontend будет доступен по адресу: `http://localhost:5173`

### Запуск frontend с реальным бэкендом

Чтобы подключить frontend к работающему бэкенду:

1. Создайте файл `.env.local` в директории `frontend/`:
```
VITE_API_URL=http://localhost:8000
```

2. Запустите dev-сервер:
```bash
npm run dev
```

Для проверки интеграции с бэкендом можно собрать production-версию:
```bash
VITE_API_URL=http://localhost:8000 npm run build
npm run preview
```

### Локальная разработка backend

Для локальной разработки backend:

```bash
# Перейдите в директорию backend
cd backend

# Установите зависимости через Composer
composer install

# Настройте переменные окружения в .env
cp .env.example .env
# Отредактируйте .env под вашу конфигурацию

# Запустите миграции
php bin/migrate.php

# Запустите сервер
php -S localhost:8000 -t public/
```

## Основные команды

### Frontend команды

| Команда | Описание |
|---------|----------|
| `npm run dev` | Dev-сервер на `localhost:5173` (с моками) |
| `npm run build` | Production-сборка (type-check + бандл) |
| `npm run preview` | Запуск production-сборки локально |
| `npm run type-check` | Проверка типов через vue-tsc |
| `npm run lint` | Проверка и исправление кода через oxlint и ESLint |
| `npm run format` | Форматирование кода через oxfmt |

### Backend команды

| Команда | Описание |
|---------|----------|
| `php bin/migrate.php` | Выполнение миграций базы данных |
| `php -S localhost:8000 -t public/` | Запуск PHP встроенного сервера |

### Docker команды

| Команда | Описание |
|---------|----------|
| `docker compose up -d` | Запуск всех сервисов в фоне |
| `docker compose down` | Остановка всех сервисов |
| `docker compose exec backend bash` | Вход в контейнер backend |
| `docker compose logs -f backend` | Просмотр логов backend |

## Функциональность

### Основные возможности

1. **Песочница шаблонов** (`/sandbox`)
   - Редактирование шаблонов в двух слотах (Slot A и Slot B)
   - Совместное использование JSON-контекста
   - Предварительный просмотр и сравнение вывода
   - Запуск бенчмарков с различным количеством итераций

2. **Библиотека шаблонов** (`/templates`)
   - Просмотр публичных шаблонов
   - Поиск и фильтрация по движку
   - Возможность открытия шаблонов в песочнице

3. **Личный кабинет** (`/dashboard`)
   - Управление личными шаблонами
   - История бенчмарков
   - Статистика производительности
   - Настройка публичности шаблонов

4. **Аутентификация**
   - Регистрация и вход пользователей
   - Управление сессией через HttpOnly cookies

### Поддерживаемые движки шаблонов

- **Handlebars**: Логические шаблоны с выражениями и помощниками
- **Pug**: Компактные шаблоны с отступами
- **EJS**: Шаблоны с встроенным JavaScript

## API

Backend предоставляет REST API для взаимодействия с frontend. Основные группы эндпоинтов:

- **Auth API**: Регистрация, вход, выход, проверка сессии
- **Templates API**: CRUD операции с шаблонами, управление публичностью
- **Benchmark Runs API**: Запуск и сохранение результатов бенчмарков
- **Render Runs API**: Запуск и сохранение результатов рендеринга
- **Shared State API**: Сохранение и восстановление состояния песочницы

Документация API доступна через Swagger UI по адресу `/docs` или в формате OpenAPI по адресу `/openapi.json`.

## Конфигурация

### Frontend переменные окружения

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `VITE_API_URL` | URL бэкенда | `http://localhost:8000` |

### Backend переменные окружения

| Переменная | Описание | По умолчанию |
|------------|----------|--------------|
| `POSTGRES_DB` | Имя базы данных | `render_lab` |
| `POSTGRES_USER` | Имя пользователя | `postgres` |
| `POSTGRES_PASSWORD` | Пароль | `secret` |
| `POSTGRES_HOST` | Хост базы данных | `db` |
| `CORS_ORIGINS` | Разрешенные источники для CORS | `http://localhost:5173,http://localhost:4173` |
| `JWT_SECRET` | Секрет для JWT токенов | `dev-only-jwt-secret-change-me-32chars` |
| `PASSWORD_PEPPER` | Pepper для хеширования паролей | `dev-only-password-pepper-change-me-32chars` |
| `COOKIE_SECURE` | Флаг secure для cookie | `false` |
| `COOKIE_SAMESITE` | Режим SameSite для cookie | `lax` |

## Структура директорий

### Frontend
- `src/` - Основной исходный код
  - `components/` - Переиспользуемые компоненты
  - `views/` - Страницы приложения
  - `stores/` - Сторы Pinia
  - `composables/` - Композаблы Vue
  - `utils/` - Вспомогательные функции
- `features/` - Функциональные модули
- `public/` - Публичные ресурсы

### Backend
- `application/` - Логика приложения
- `domain/` - Доменная модель
- `infrastructure/` - Инфраструктурный код (контроллеры, репозитории и т.д.)
- `public/` - Точка входа в приложение

## Лицензия

Этот проект является учебным и не имеет официальной лицензии.