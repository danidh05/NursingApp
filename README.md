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

## Temporary Request-Scoped Chat (Feature Gated)

-   **Feature Flag:** Controlled by `CHAT_ENABLED` environment variable (default: `false`)
-   **Scope:** Temporary chat threads linked to specific service requests
-   **Participants:** Admin ↔ Client communication during request lifecycle
-   **Message Types:** Text, images, and location sharing
-   **Real-time:** WebSocket broadcasting via Laravel Echo/Reverb
-   **Media Storage:** Firebase/Google Cloud Storage with signed URLs
-   **Auto-cleanup:** Messages and media automatically purged when thread closes
-   **Security:** Policy-based authorization, media path validation, rate limiting

### **Real-time Communication (WebSockets)**

The chat feature uses Laravel Broadcasting for real-time communication.

#### **Channel Naming Convention**

-   **Format**: `private-chat.{threadId}`
-   **Example**: `private-chat.123` for thread ID 123
-   **Frontend Subscription**: `Echo.private('chat.123')`

#### **Events to Listen For**

-   **MessageCreated**: New message in thread
-   **ThreadClosed**: Thread closure notification

#### **Frontend Integration Example**

```javascript
// Subscribe to chat thread 123
Echo.private("chat.123")
    .listen("MessageCreated", (event) => {
        // Handle new message
        console.log("New message:", event);
    })
    .listen("ThreadClosed", (event) => {
        // Handle thread closure
        console.log("Thread closed:", event);
    });
```

#### **Security Requirements**

-   All channels are private (require authentication)
-   JWT token must be valid
-   User must be participant in the thread
-   Policy check: `$user->can('view', $thread)`

#### **Complete Documentation**

-   **API Endpoints**: See Swagger UI at `/api/documentation`
-   **WebSocket Details**: See `routes/channels.php` for full implementation
-   **Event Schemas**: Available in Swagger under "Real-time Events" tag

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
-   **Real-time Communication:** Laravel Broadcasting (Echo/Reverb)
-   **Media Storage:** Firebase/Google Cloud Storage
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

# Chat Feature (Optional)
CHAT_ENABLED=false
CHAT_SIGNED_URL_TTL=900
CHAT_REDACT_MESSAGES=true
CHAT_ALLOWED_IMAGE_MIME=image/jpeg,image/png,image/webp
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

### Chat Management (Feature Gated)

-   `POST /api/requests/{id}/chat/open` - Open chat thread for request
-   `GET /api/chat/threads/{threadId}/messages` - List chat messages with pagination
-   `POST /api/chat/threads/{threadId}/messages` - Post new message (text/image/location)
-   `PATCH /api/chat/threads/{threadId}/close` - Close chat thread and trigger cleanup

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
-   Chat functionality (threads, messages, media, cleanup)

## Business Logic Features

### Request Workflow

1. User creates a service request with nurse, services, time, and location
2. Request is created with 'pending' status
3. Admin reviews and can update status to 'approved', 'rejected', or 'completed'
4. Admin can set time needed for arrival
5. Notifications are sent to users for status changes
6. Requests can be soft-deleted by admins (archived but still visible to users)

### Chat Workflow (Feature Gated)

1. User or admin opens a chat thread for a specific request
2. Real-time communication via WebSocket broadcasting
3. Support for text messages, image sharing, and location coordinates
4. Media files stored temporarily with signed URLs for security
5. Thread closure triggers automatic cleanup of messages and media
6. Configurable message redaction for privacy compliance

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
-   Chat feature gated behind environment variable
-   Media path validation to prevent directory traversal
-   Signed URLs with configurable TTL for media access
-   Policy-based authorization for chat operations

---

## Test Coverage

All features above are covered by automated tests, ensuring:

-   Correct business logic
-   Proper access control
-   Robust request/response validation
-   Event and notification dispatching
-   Database integrity
-   API endpoint functionality

**Current Test Status:** 107 tests passing (355 assertions)

**Chat Feature Tests:** 30 tests passing (103 assertions)

---

## Deployment Notes

### Chat Feature Rollout

The chat feature is designed for safe, gradual deployment:

1. **Phase 1:** Deploy migrations and code with `CHAT_ENABLED=false`
2. **Phase 2:** Enable in staging environment for testing
3. **Phase 3:** Gradually enable in production with monitoring
4. **Fallback:** Storage lifecycle rules provide cleanup if job fails

### Environment Configuration

```bash
# Enable chat feature
CHAT_ENABLED=true

# Configure media URL TTL (seconds)
CHAT_SIGNED_URL_TTL=900

# Message redaction on cleanup
CHAT_REDACT_MESSAGES=true

# Allowed image MIME types
CHAT_ALLOWED_IMAGE_MIME=image/jpeg,image/png,image/webp
```

---

_This document reflects the current, fully-tested feature set of the Nursing Platform as of today._
