# Multi-Currency Payment

A modern, highly polished, and enterprise-grade Laravel 12 & React 19 application to manage multi-currency payment requests. This project has been built to meet the standards of Buzzvel's 2026 Dev Team evaluation, demonstrating clean backend architectures (Laravel 12, Sanctum, Service Pattern, Value Objects, Enums, robust policies) combined with a premium, responsive React SPA frontend (Vite, React Router, Shadcn UI, Tailwind CSS,  Lucide Icons).

---

## 🌟 Key Features

### Backend Architecture (Laravel 12)
*   **Authentication & Session Control**: Clean token-based authentication using **Laravel Sanctum**. Implements standard registration, secure login (invalidating prior sessions to prevent double logins), user profile fetching, token refresh, and clean logout.
*   **Payment Request Lifecycle**:
    *   **Submission**: Employees can submit payment requests in their local currency.
    *   **Approval Flow**: A finance-exclusive role validation restricts approval/rejection actions to authorized users.
    *   **Filtering & Pagination**: Listing endpoints support pagination and custom filters (status, currency, user_id).
*   **Real-time Exchange Rate Integration**: Integrates directly with the public [ExchangeRate-API](https://www.exchangerate-api.com/).
    *   Rates are fetched relative to **EUR** (the default conversion currency {find it on `.env`}) on creation.
    *   Implements safe conversion rates calculation, storing the immutable rate, timestamp, and source at creation time.
*   **Auto-Expiration Scheduler**: A console command (`payments:expire`) runs automatically to scan and mark pending requests older than 48 hours as `expired`.
*   **Strict Types & Data Integrity**: Powered by PHP 8.2+ features, custom Value Objects (e.g., `Money`), Backed Enums (`PaymentStatusEnum`, `UserRoleEnum`), custom Form Requests, and Policies.
*   **JSON-First API design**: Middleware automatically forces JSON responses across all `/api/*` endpoints.

    *Note: At the beginning of the project, I considered using Laravel's starter kit with Inertia + React, but decided to implement the API and frontend separately (even in the same repo) to better meet the requirements of the challenge.*

### Frontend Presentation (React 19 + Tailwind CSS v4)
*   **Monorepo Structure**: The frontend is built as a Single Page Application (SPA) within the `/resources/js/` directory, communicating with the Laravel backend via a RESTful API.
*   **API Integration**:  Built with React 19, using Vite for fast development, React Router for navigation, and Axios for HTTP requests.
*   **Premium Visuals & Dark Mode**: A gorgeous user interface featuring a modern dark-mode aesthetic, harmonious color palettes, and glassmorphic micro-animations.
*   **Live Dashboard**: Interactive workspace for submitting payment requests, filtering through active submissions, and a dedicated workspace for Finance personnel to review and process pending requests.

---

## 🛠️ Architecture & Design Patterns

### 1. Service Layer Pattern
Business logic is decoupled from Controllers. The `PaymentRequestController` delegates actions to `PaymentRequestService`, ensuring clean separation of concerns and testability.

### 2. Interface/Implementation Binding
The external Exchange Rate integration is abstracted via `ExchangerateInterface`. The service provider binds it to `ExchangeRateApiService`, facilitating mockability and future api swaps.

### 3. Value Objects
Money logic (amount rounding, currencies matching, and formatting) is encapsulated within the `Money` Value Object, preventing mathematical inaccuracies and ensuring a single source of truth for financial operations.

### 4. Backed Enums
Statuses (`pending`, `approved`, `rejected`, `expired`) and user roles (`employee`, `finance`) are strongly typed using native PHP Enums, eliminating magic strings.

---

## 🚀 Local Development Setup

This project is fully dockerized with a multi-container environment (MySQL, Redis, Nginx, NodeJS, Laravel application, and Worker(to handle schedules)).

### Prerequisites
*   [Docker](https://www.docker.com/) and Docker Compose installed.
*   [Node.js](https://nodejs.org/) & NPM (if building assets on the host, though Docker handles runtime serving).

### Step-by-Step Installation

1.  **Clone the Repository**:
    ```bash
    git clone https://github.com/lucasmendes-dev/multi-currency-payment.git
    cd multi-currency-payment
    ```

2.  **Configure Environment**:
    Copy the example environment file (it already has almost everything you need):
    ```bash
    cp .env.example .env
    ```
    Open `.env` and fill in your custom credentials. To use the real-time exchange rates, get a free API key from [ExchangeRate-API](https://www.exchangerate-api.com/) and configure:
    ```env
    EXCHANGE_RATE_API_TOKEN=your_token_here
    EXCHANGE_RATE_SOURCE=https://www.exchangerate-api.com/
    ```

3.  **Start Docker Environment**:
    Build and start all containers in the background:
    ```bash
    docker compose up -d --build
    ```
    This spins up:
    *   `app`: The PHP 8.5 workspace container.
    *   `nginx`: Running on port `http://localhost:8585`.
    *   `db`: MySQL 8.0 server exposing port `3305` (internal database `multi_currency_payment`).
    *   `redis`: Redis cache & session store.
    *   `worker`: Running `php artisan schedule:work` to automatically process scheduled tasks.

4.  **Initialize Project Data**:
    Run composer installations, key generation, database migrations, and feed seeders:
    ```bash
    docker compose exec app composer install
    docker compose exec app php artisan key:generate
    docker compose exec app php artisan migrate --seed
    ```
    *Note: The seeders automatically create:*
    *   **1 Finance User**: `finance@example.com` (Password: `Finance@123`)
    *   **5 Random Employees** with distinct countries and currencies.
    *   **20 Sample Payment Requests** for immediate testing.

5.  **Build and Run Frontend**:
    Install frontend dependencies and start the Vite server:
    ```bash
    # Running directly inside the app container
    docker compose exec app npm install
    docker compose exec app npm run build
    ```

6.  **Access the Application**:
    *   Web Client (Dashboard): [http://localhost:8585](http://localhost:8585)
    *   API: [http://localhost:8585/api/login](http://localhost:8585/api/login)

---

## ⏰ Background Scheduler (Auto-Expiration)

Payment requests remaining `pending` for more than **48 hours** are marked `expired`. There is a column `expires_at` in the `payment_requests` table that is set to `NOW() + 48 hours` when a payment request is created.

*   **Docker Container Scheduler**: The `worker` service inside `docker-compose.yml` *automatically* executes the command continuously in background:
    ```bash
    php artisan schedule:work
    ```
*   **Manual Trigger**: You can also manually trigger the evaluation of pending payments expiration immediately by running:
    ```bash
    docker compose exec app php artisan payments:expire
    ```

---

## 🧪 Testing Suite

We maintain a strict quality control environment with comprehensive unit and feature tests covering all critical system components.

To run the complete test suite:
```bash
docker compose exec app php artisan test
```

### Coverage Details
*   **Unit Tests**:
    *   `ExchangeRateApiServiceTest`: Validates exchange rate conversions, EUR base calculations, and API error scenarios.
    *   `PaymentRequestServiceTest`: Verifies business logic for creating, approving, and rejecting requests.
    *   `UserModelTest`: Checks user attributes, model behavior, and roles.
*   **Feature Tests**:
    *   `AuthControllerTest`: Verifies registration, login, profile view, token refresh, and logout.
    *   `PaymentRequestControllerTest`: Ensures endpoints validation rules, authorization logic, and index pagination/filters work correctly.
    *   `PaymentRequestPolicyTest`: Asserts role-based security policies for employees and finance staff.
    *   `ExpirePaymentRequestsCommandTest`: Validates the 48-hour expiration logic and the scheduled cron job command behavior.

---

## 📖 API Documentation

All request parameters, types, authorization states, and responses are detailed below. All responses return a `Content-Type: application/json` header.

### Authentication Endpoints

#### 1. Register User
*   **Method**: `POST`
*   **URL**: `/api/auth/register`
*   **Request Body**:
    ```json
    {
      "name": "John Doe",
      "email": "john.doe@example.com",
      "password": "Password123",
      "password_confirmation": "Password123",
      "country": "Brazil",
      "local_currency": "BRL"
    }
    ```
*   **Response (201 Created)**:
    ```json
    {
      "message": "User registered successfully.",
      "user": {
        "id": 2,
        "name": "John Doe",
        "email": "john.doe@example.com",
        "country": "Brazil",
        "local_currency": "BRL"
      },
      "token": "1|sanctum_generated_token_here",
      "type": "Bearer",
      "expires_in_minutes": 120
    }
    ```

#### 2. User Login
*   **Method**: `POST`
*   **URL**: `/api/auth/login`
*   **Request Body**:
    ```json
    {
      "email": "finance@example.com",
      "password": "Finance@123"
    }
    ```
*   **Response (200 OK)**:
    ```json
    {
      "message": "Logged in successfully.",
      "user": {
        "id": 1,
        "name": "Finance User",
        "email": "finance@example.com",
        "country": "Portugal",
        "local_currency": "EUR"
      },
      "token": "2|sanctum_generated_token_here",
      "type": "Bearer",
      "expires_in_minutes": 120
    }
    ```

#### 3. Fetch User Profile
*   **Method**: `GET`
*   **URL**: `/api/auth/me`
*   **Headers**: `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "user": {
        "id": 1,
        "name": "Finance User",
        "email": "finance@example.com",
        "country": "Portugal",
        "local_currency": "EUR"
      },
      "session_expires_in": "2 hours from now"
    }
    ```

#### 4. Refresh Token
*   **Method**: `POST`
*   **URL**: `/api/auth/refresh`
*   **Headers**: `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "message": "Token refreshed successfully.",
      "token": "3|sanctum_generated_token_here",
      "type": "Bearer",
      "expires_in_minutes": 120
    }
    ```

#### 5. Logout User
*   **Method**: `POST`
*   **URL**: `/api/auth/logout`
*   **Headers**: `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "message": "Logged out successfully."
    }
    ```

---

### Payment Requests Endpoints

#### 1. List Payment Requests
*   **Method**: `GET`
*   **URL**: `/api/payments`
*   **Headers**: `Authorization: Bearer <token>`
*   **Query Parameters (Optional)**:
    *   `status`: Filter by status (`pending`, `approved`, `rejected`, `expired`).
    *   `local_currency`: Filter by 3-character currency code.
    *   `user_id`: Filter by employee ID (only accessible by finance role).
*   **Response (200 OK)**:
    ```json
    {
      "data": [
        {
          "id": 1,
          "user_id": 2,
          "local_currency": "BRL",
          "local_amount": 550.00,
          "target_currency": "EUR",
          "converted_amount": 91.67,
          "exchange_rate": 0.16667000,
          "exchange_rate_source": "https://www.exchangerate-api.com/",
          "exchange_rate_fetched_at": "2026-06-11 18:00:00",
          "description": "Monthly software license subscription",
          "status": "pending",
          "approved_by": null,
          "approved_at": null,
          "rejected_by": null,
          "rejected_at": null,
          "rejection_reason": null,
          "expires_at": "2026-06-13 18:00:00",
          "created_at": "2026-06-11 18:00:00",
          "updated_at": "2026-06-11 18:00:00",
          "user": {
            "name": "John Doe",
            "email": "john.doe@example.com",
            "country": "Brazil"
          }
        }
      ],
      "meta": {
        "total": 1,
        "per_page": 10,
        "current_page": 1,
        "last_page": 1
      }
    }
    ```

#### 2. Create Payment Request
*   **Method**: `POST`
*   **URL**: `/api/payments`
*   **Headers**: `Authorization: Bearer <token>`
*   **Request Body**:
    ```json
    {
      "local_amount": 550.00,
      "local_currency": "BRL",
      "description": "Monthly software license subscription"
    }
    ```
*   **Response (201 Created)**:
    ```json
    {
      "message": "Payment request created successfully.",
      "data": {
        "id": 1,
        "user_id": 2,
        "local_currency": "BRL",
        "local_amount": 550.00,
        "target_currency": "EUR",
        "converted_amount": 91.67,
        "exchange_rate": 0.16667000,
        "exchange_rate_source": "https://www.exchangerate-api.com/",
        "exchange_rate_fetched_at": "2026-06-11 18:56:42",
        "description": "Monthly software license subscription",
        "status": "pending",
        "approved_by": null,
        "approved_at": null,
        "rejected_by": null,
        "rejected_at": null,
        "rejection_reason": null,
        "expires_at": "2026-06-13 18:56:42",
        "created_at": "2026-06-11 18:56:42",
        "updated_at": "2026-06-11 18:56:42"
      }
    }
    ```

#### 3. View Payment Request Detail
*   **Method**: `GET`
*   **URL**: `/api/payments/{id}`
*   **Headers**: `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "data": {
        "id": 1,
        "user_id": 2,
        "local_currency": "BRL",
        "local_amount": 550.00,
        "target_currency": "EUR",
        "converted_amount": 91.67,
        "exchange_rate": 0.16667000,
        "exchange_rate_source": "https://www.exchangerate-api.com/",
        "exchange_rate_fetched_at": "2026-06-11 18:56:42",
        "description": "Monthly software license subscription",
        "status": "pending",
        "expires_at": "2026-06-13 18:56:42",
        "created_at": "2026-06-11 18:56:42",
        "updated_at": "2026-06-11 18:56:42",
        "user": {
          "name": "John Doe",
          "email": "john.doe@example.com",
          "country": "Brazil"
        }
      }
    }
    ```

#### 4. Approve Payment Request (Finance Only)
*   **Method**: `PATCH`
*   **URL**: `/api/payments/{id}/approve`
*   **Headers**: `Authorization: Bearer <token>`
*   **Response (200 OK)**:
    ```json
    {
      "message": "Payment request approved successfully.",
      "data": {
        "id": 1,
        "user_id": 2,
        "local_currency": "BRL",
        "local_amount": 550.00,
        "target_currency": "EUR",
        "converted_amount": 91.67,
        "exchange_rate": 0.16667000,
        "exchange_rate_source": "https://www.exchangerate-api.com/",
        "exchange_rate_fetched_at": "2026-06-11 18:56:42",
        "status": "approved",
        "approved_by": 1,
        "approved_at": "2026-06-11 19:10:00",
        "rejected_by": null,
        "rejected_at": null,
        "rejection_reason": null,
        "expires_at": "2026-06-13 18:56:42",
        "created_at": "2026-06-11 18:56:42",
        "updated_at": "2026-06-11 19:10:00"
      }
    }
    ```

#### 5. Reject Payment Request (Finance Only)
*   **Method**: `PATCH`
*   **URL**: `/api/payments/{id}/reject`
*   **Headers**: `Authorization: Bearer <token>`
*   **Request Body (optional)**:
    ```json
    {
      "rejection_reason": "Requested amount exceeds monthly local team allowance."
    }
    ```
*   **Response (200 OK)**:
    ```json
    {
      "message": "Payment request rejected successfully.",
      "data": {
        "id": 1,
        "user_id": 2,
        "local_currency": "BRL",
        "local_amount": 550.00,
        "target_currency": "EUR",
        "converted_amount": 91.67,
        "exchange_rate": 0.16667000,
        "exchange_rate_source": "https://www.exchangerate-api.com/",
        "exchange_rate_fetched_at": "2026-06-11 18:56:42",
        "status": "rejected",
        "approved_by": null,
        "approved_at": null,
        "rejected_by": 1,
        "rejected_at": "2026-06-11 19:15:00",
        "rejection_reason": "Requested amount exceeds monthly local team allowance.",
        "expires_at": "2026-06-13 18:56:42",
        "created_at": "2026-06-11 18:56:42",
        "updated_at": "2026-06-11 19:15:00"
      }
    }
    ```

---