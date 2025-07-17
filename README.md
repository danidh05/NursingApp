# Nursing Platform – Feature Overview

## User Authentication & Security

-   User registration with email, phone, and password
-   Phone verification via SMS/WhatsApp OTP
-   Secure login/logout
-   Password reset via phone OTP
-   Role-based access control (admin, user)

## User Management

-   Users can view and update their own profile
-   Admins can:
    -   List all users
    -   Create new users
    -   Delete users
    -   View any user's profile

## Nurse Management

-   Admins can:
    -   List, create, update, and delete nurses
-   Users can:
    -   List all nurses
    -   View nurse details
    -   Rate nurses

## Service Management

-   Admins can:
    -   List, create, update, and delete services
-   Users can:
    -   List all services
    -   View service details

## Category Management

-   Admins can:
    -   List, create, update, and delete categories
-   Users can:
    -   List all categories
    -   View category details

## Request (Order) Management

-   Users can:
    -   Create new service requests (choose nurse, services, time, location, etc.)
    -   View all their requests (including archived/soft-deleted)
    -   Cannot update requests after creation
-   Admins can:
    -   View all requests (except soft-deleted)
    -   Update any request (status, time needed, etc.)
    -   Soft-delete (archive) any request (removes from admin view, but user can still see)
    -   Cannot view soft-deleted requests

## Request Status & Notifications

-   Requests have statuses: pending, approved, rejected, completed
-   Event broadcasting:
    -   User request creation triggers a broadcast event
    -   Admin request update triggers a broadcast event
-   Notifications:
    -   Users receive notifications for request submission and status updates
    -   Admins and users can view and mark notifications as read

## About Page

-   Admins can update the about page
-   Users can view the about page

## Location Management

-   Users can submit and update their location (especially on first login)

## Dashboard

-   Users have a personal dashboard
-   Admins have a dashboard with summary statistics

## Security & Access

-   All sensitive routes are protected by authentication and role-based middleware
-   Only authorized users can access admin/user-specific features

---

## Technical Stack

-   **Framework:** Laravel 11
-   **Database:** MySQL/PostgreSQL with migrations
-   **Authentication:** Laravel Sanctum
-   **SMS/WhatsApp:** Twilio integration
-   **Testing:** PHPUnit with comprehensive test coverage
-   **API:** RESTful API design

## Installation & Setup

1. Clone the repository
2. Install dependencies: `composer install`
3. Copy `.env.example` to `.env` and configure your environment
4. Run migrations: `php artisan migrate`
5. Seed the database: `php artisan db:seed`
6. Start the development server: `php artisan serve`

## Environment Variables

Required environment variables:

```env
# Database
    DB_CONNECTION=mysql
DB_HOST=127.0.0.1
    DB_PORT=3306
DB_DATABASE=nursing_platform
DB_USERNAME=root
DB_PASSWORD=

# Twilio (for SMS/WhatsApp)
TWILIO_SID=your_twilio_account_sid
TWILIO_AUTH_TOKEN=your_twilio_auth_token
TWILIO_VERIFY_SERVICE_SID=your_twilio_verify_service_sid

# App
APP_NAME="Nursing Platform"
APP_ENV=local
APP_KEY=base64:your_app_key
APP_DEBUG=true
APP_URL=http://localhost:8000
```

## API Endpoints

### Authentication

-   `POST /api/register` - User registration
-   `POST /api/verify-sms` - Phone verification
-   `POST /api/login` - User login
-   `POST /api/logout` - User logout
-   `POST /api/send-password-reset-otp` - Send password reset OTP
-   `POST /api/reset-password` - Reset password

### User Management

-   `GET /api/me` - Get current user profile
-   `PUT /api/users/{id}` - Update user profile
-   `GET /api/user/dashboard` - User dashboard
-   `POST /api/submit-location` - Submit user location

### Admin Routes (Protected)

-   `GET /api/admin/users` - List all users
-   `POST /api/admin/users` - Create user
-   `DELETE /api/admin/users/{id}` - Delete user
-   `GET /api/admin/users/{id}` - View user profile

### Nurse Management

-   `GET /api/nurses` - List all nurses (all users)
-   `GET /api/nurses/{id}` - View nurse details (all users)
-   `POST /api/admin/nurses` - Create nurse (admin only)
-   `PUT /api/admin/nurses/{id}` - Update nurse (admin only)
-   `DELETE /api/admin/nurses/{id}` - Delete nurse (admin only)
-   `POST /api/nurses/{id}/rate` - Rate nurse (users only)

### Service Management

-   `GET /api/services` - List all services (all users)
-   `GET /api/services/{id}` - View service details (all users)
-   `POST /api/admin/services` - Create service (admin only)
-   `PUT /api/admin/services/{id}` - Update service (admin only)
-   `DELETE /api/admin/services/{id}` - Delete service (admin only)

### Category Management

-   `GET /api/categories` - List all categories (all users)
-   `GET /api/categories/{id}` - View category details (all users)
-   `POST /api/admin/categories` - Create category (admin only)
-   `PUT /api/admin/categories/{id}` - Update category (admin only)
-   `DELETE /api/admin/categories/{id}` - Delete category (admin only)

### Request Management

-   `GET /api/requests` - List requests (user sees own, admin sees all)
-   `GET /api/requests/{id}` - View request details
-   `POST /api/requests` - Create request (users only)
-   `PUT /api/admin/requests/{id}` - Update request (admin only)
-   `DELETE /api/admin/requests/{id}` - Soft delete request (admin only)

### Notifications

-   `GET /api/notifications` - List user notifications
-   `POST /api/notifications/{id}/read` - Mark notification as read
-   `DELETE /api/notifications/{id}` - Delete notification

### About Page

-   `GET /api/about` - View about page (all users)
-   `PUT /api/admin/about` - Update about page (admin only)

## Testing

Run the test suite:

```bash
php artisan test
```

The application includes comprehensive test coverage for:

-   Authentication flows
-   User management
-   Nurse management
-   Service management
-   Request management
-   Notifications
-   Role-based access control
-   Event broadcasting

## Business Logic Features

### Request Workflow

1. User creates a service request with nurse, services, time, and location
2. Request is created with 'pending' status
3. Admin reviews and can update status to 'approved', 'rejected', or 'completed'
4. Admin can set time needed for arrival
5. Notifications are sent to users for status changes
6. Requests can be soft-deleted by admins (archived but still visible to users)

### User Experience

-   Users must verify their phone before accessing the platform
-   Users can only view and manage their own requests
-   Users can rate nurses after service completion
-   Location tracking for service delivery
-   Real-time notifications for request updates

### Admin Experience

-   Full CRUD operations on all entities
-   Request management with status updates
-   User management and oversight
-   Dashboard with summary statistics
-   Cannot view soft-deleted requests (clean admin interface)

## Security Features

-   Phone verification required for all users
-   Role-based middleware protecting sensitive routes
-   Soft deletion for data integrity
-   Input validation on all endpoints
-   Secure password handling with hashing
-   API token authentication via Sanctum

---

## Test Coverage

All features above are covered by automated tests, ensuring:

-   Correct business logic
-   Proper access control
-   Robust request/response validation
-   Event and notification dispatching
-   Database integrity
-   API endpoint functionality

**Current Test Status:** 77 tests passing (252 assertions)

---

_This document reflects the current, fully-tested feature set of the Nursing Platform as of today._
