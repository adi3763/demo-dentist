# 🦷 Demo Dentist - Professional Appointment Management System

A complete RESTful API for managing dental appointments, doctor schedules, and patient bookings with WhatsApp notifications.

**Live URL:** `https://demo-dentist-main-adaeep.free.laravel.cloud`

---

## 📋 Table of Contents

- [Features](#features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Installation](#installation)
- [Configuration](#configuration)
- [Running the Application](#running-the-application)
- [API Overview](#api-overview)
- [Authentication](#authentication)
- [Core Features](#core-features)
- [Database Models](#database-models)
- [Error Handling](#error-handling)
- [Response Format](#response-format)

---

## ✨ Features

### Patient Features
- 👁️ **View Doctors** - Browse all available dentists with specializations
- 🩺 **Browse Services** - Explore dental services offered
- 📅 **Check Availability** - View available appointment slots for any doctor
- 📝 **Book Appointments** - Easy appointment booking with patient details
- 🔔 **WhatsApp Notifications** - Automatic confirmations and reminders

### Doctor Features
- 📊 **Manage Schedule** - Create recurring time slots for availability
- 🗓️ **Block Dates** - Mark unavailable dates (holidays, emergencies)
- 👥 **View Appointments** - See all booked appointments with patient info
- ✅ **Mark Complete** - Update appointment status as completed
- 🔄 **Reschedule Appointments** - Reschedule with automatic patient notification

### Admin Features
- 👨‍💼 **Manage Doctors** - Create, update, enable/disable doctor accounts
- 🏥 **Doctor Management** - Full CRUD operations for doctor profiles
- 📱 **Phone & Specialization** - Manage doctor contact and specialization
- 🔐 **Access Control** - Role-based access control (RBAC)

---

## 🛠️ Tech Stack

| Component | Technology |
|-----------|-----------|
| **Framework** | Laravel 11 |
| **Language** | PHP 8.2+ |
| **Database** | MySQL / SQLite |
| **Authentication** | Laravel Sanctum |
| **API** | RESTful JSON API |
| **Real-time Notifications** | WhatsApp API (Vonage/Twilio) |
| **Documentation** | OpenAPI/Swagger 3.0 |
| **Testing** | PHPUnit |

---

## 📁 Project Structure

```
demo-dentist/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php           # Login/Logout
│   │   │   │   ├── SlotController.php            # Doctors, Services, Slots
│   │   │   │   ├── AppointmentController.php     # Book appointments
│   │   │   │   ├── Admin/
│   │   │   │   │   └── UserController.php        # Manage doctors
│   │   │   │   └── Doctor/
│   │   │   │       ├── ScheduleController.php    # Schedule management
│   │   │   │       └── AppointmentController.php # Doctor appointments
│   │   │   └── swagger.php                       # OpenAPI Documentation
│   │   ├── Middleware/
│   │   │   ├── IsAdmin.php                       # Admin check
│   │   │   └── IsDoctor.php                      # Doctor check
│   │   └── Traits/
│   │       └── ApiResponse.php                   # Standardized responses
│   ├── Models/
│   │   ├── User.php                              # Doctor/Admin users
│   │   ├── Appointment.php                       # Appointment records
│   │   ├── Service.php                           # Dental services
│   │   ├── DoctorSchedule.php                    # Doctor availability
│   │   └── BlockedDate.php                       # Blocked dates
│   └── Services/
│       └── WhatsAppService.php                   # WhatsApp notifications
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_services_table.php
│   │   ├── create_doctor_schedules_table.php
│   │   └── create_appointments_table.php
│   ├── factories/
│   │   └── UserFactory.php
│   └── seeders/
│       └── DatabaseSeeder.php
├── routes/
│   ├── api.php                                   # All API routes
│   └── web.php
├── config/
│   ├── app.php
│   ├── database.php
│   ├── l5-swagger.php                            # Swagger config
│   └── sanctum.php                               # API authentication
├── storage/
│   └── logs/
│       └── laravel.log                           # Error logs
├── tests/
│   ├── Feature/
│   └── Unit/
├── API_DOCUMENTATION.md                          # Full API docs
├── .env.example
├── artisan
├── composer.json
└── README.md

```

---

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer
- MySQL or SQLite
- WhatsApp Business Account (for notifications)

### Step 1: Clone/Download Project
```bash
cd c:\xampp\htdocs
# Copy project files to demo-dentist folder
```

### Step 2: Install Dependencies
```bash
cd demo-dentist
composer install
```

### Step 3: Setup Environment
```bash
cp .env.example .env
php artisan key:generate
```

### Step 4: Configure Database
Edit `.env` file:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo_dentist
DB_USERNAME=root
DB_PASSWORD=
```

### Step 5: Run Migrations
```bash
php artisan migrate
php artisan db:seed
```

### Step 6: Configure WhatsApp (Optional)
Add to `.env`:
```env
WHATSAPP_API_KEY=your_api_key
WHATSAPP_PHONE_NUMBER=your_phone_number
```

---

## ⚙️ Configuration

### Environment Variables (.env)

```env
APP_NAME=DemoDentist
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo-dentist-main-adaeep.free.laravel.cloud

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=demo_dentist
DB_USERNAME=root
DB_PASSWORD=

SANCTUM_STATEFUL_DOMAINS=demo-dentist-main-adaeep.free.laravel.cloud
SESSION_DOMAIN=.demo-dentist-main-adaeep.free.laravel.cloud

# WhatsApp Notifications
WHATSAPP_ENABLED=true
WHATSAPP_API_KEY=your_vonage_api_key
WHATSAPP_PHONE_NUMBER=whatsapp:+1234567890
```

---

## 🏃 Running the Application

### Development Server
```bash
php artisan serve
```
Server runs at: `http://localhost:8000`

### Production (Already Hosted)
Live URL: `https://demo-dentist-main-adaeep.free.laravel.cloud`

### Generate API Documentation
```bash
php artisan l5-swagger:generate
```
Access at: `/api/documentation`

### Run Database Migrations
```bash
php artisan migrate
```

### Clear Cache
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

---

## 📚 API Overview

### Quick Reference
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/login` | User login | No |
| GET | `/api/doctors` | List all doctors | No |
| GET | `/api/services` | List all services | No |
| GET | `/api/slots` | Get available slots | No |
| POST | `/api/appointments` | Book appointment | No |
| GET | `/api/me` | Get current user | Yes |
| POST | `/api/logout` | User logout | Yes |
| GET | `/api/doctor/schedule` | Doctor's schedule | Yes |
| POST | `/api/doctor/schedule` | Add schedule slot | Yes |
| GET | `/api/doctor/appointments` | Doctor's appointments | Yes |
| PATCH | `/api/doctor/appointments/{id}/complete` | Mark complete | Yes |
| PATCH | `/api/doctor/appointments/{id}/reschedule` | Reschedule appt | Yes |
| GET | `/api/admin/users` | List doctors (admin) | Yes |
| POST | `/api/admin/users` | Create doctor (admin) | Yes |

**For complete endpoint documentation, see:** [API_DOCUMENTATION.md](./API_DOCUMENTATION.md)

---

## 🔐 Authentication

### Login
```bash
POST /api/login
Content-Type: application/json

{
  "email": "doctor@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "1|abcdefghijklmnop...",
    "user": {
      "id": 2,
      "name": "Dr. John Doe",
      "email": "doctor@example.com",
      "role": "doctor",
      "specialization": "Orthodontist"
    }
  }
}
```

### Using Token
All authenticated endpoints require the `Authorization` header:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

Example cURL request:
```bash
curl -X GET https://demo-dentist-main-adaeep.free.laravel.cloud/api/me \
  -H "Authorization: Bearer 1|abcdefghijklmnop..."
```

---

## 🎯 Core Features

### 1. Appointment Booking System
- Real-time slot availability checking
- Automatic conflict prevention (database unique index)
- Transaction-based booking (all or nothing)
- Supports optional patient email and notes
- Service association with appointments

### 2. Doctor Schedule Management
- Recurring weekly schedules (Monday-Sunday)
- Block specific dates for unavailability
- Manage multiple time slots per day
- Active/Inactive slot control

### 3. WhatsApp Notifications
- Automatic appointment confirmation SMS
- Doctor receives new booking notification
- Reschedule notifications with reasons
- Graceful failure handling (doesn't block operations)

### 4. Error Handling
- Standardized error response format
- Machine-readable error codes
- Detailed error context in responses
- Proper HTTP status codes (400, 401, 403, 404, 409, 422, 500)

### 5. Role-Based Access Control
- **Admin**: Full doctor management
- **Doctor**: Schedule and appointment management
- **Patient**: Public booking (no login needed)

---

## 💾 Database Models

### User (Doctors/Admins)
```
- id (int)
- name (string)
- email (string, unique)
- password (hashed)
- role (admin|doctor)
- phone (string, nullable)
- specialization (string, nullable)
- is_active (boolean)
- timestamps
```

### Appointment
```
- id (int)
- doctor_id (foreign)
- patient_name (string)
- patient_phone (string)
- patient_email (string, nullable)
- service_id (foreign, nullable)
- appointment_date (date)
- start_time (time)
- end_time (time)
- status (pending|confirmed|completed|rescheduled|cancelled)
- patient_notes (text, nullable)
- rescheduled_date (date, nullable)
- rescheduled_start_time (time, nullable)
- reschedule_reason (text, nullable)
- reminder_sent (boolean)
- unique index: (doctor_id, appointment_date, start_time)
- soft deletes
```

### Service
```
- id (int)
- name (string)
- description (text, nullable)
- price (decimal)
- duration_minutes (int)
- is_active (boolean)
- timestamps
```

### DoctorSchedule
```
- id (int)
- user_id (foreign)
- day_of_week (int: 0-6)
- start_time (time)
- end_time (time)
- is_active (boolean)
- unique index: (user_id, day_of_week, start_time)
- timestamps
```

### BlockedDate
```
- id (int)
- user_id (foreign)
- blocked_date (date)
- reason (string, nullable)
- unique index: (user_id, blocked_date)
- timestamps
```

---

## ⚠️ Error Handling

### Standardized Error Response Format
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human-readable message",
    "status": 422,
    "details": {
      "field": "Additional context"
    }
  }
}
```

### Common Error Codes
| Code | Status | Meaning |
|------|--------|---------|
| VALIDATION_ERROR | 422 | Invalid input data |
| UNAUTHORIZED | 401 | Invalid credentials |
| FORBIDDEN | 403 | No permission |
| NOT_FOUND | 404 | Resource not found |
| SLOT_ALREADY_BOOKED | 409 | Appointment slot taken |
| SLOT_DUPLICATE | 409 | Schedule slot exists |
| DATE_BLOCKED | 409 | Doctor unavailable |
| SLOT_CONFLICT | 409 | Reschedule conflict |
| METHOD_NOT_ALLOWED | 405 | Wrong HTTP method |
| BOOKING_FAILED | 500 | Server error |

---

## 📦 Response Format

### Success Response (2xx)
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    "key": "value"
  }
}
```

### Error Response (4xx/5xx)
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "What went wrong",
    "status": 422,
    "details": {}
  }
}
```

---

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Run Specific Test
```bash
php artisan test --filter=BookAppointmentTest
```

### Generate Test Coverage
```bash
php artisan test --coverage
```

---

## 📖 API Documentation

### Interactive Swagger UI
```
https://demo-dentist-main-adaeep.free.laravel.cloud/api/documentation
```

### Markdown Documentation
See [API_DOCUMENTATION.md](./API_DOCUMENTATION.md) for:
- Complete endpoint listing
- Request/response examples
- cURL examples
- Data model documentation

---

## 🔧 Development Notes

### Adding New Appointments
1. Check doctor exists and is active
2. Verify slot exists and is available
3. Check for date blocking
4. Check for existing bookings (application + database)
5. Create appointment in transaction
6. Send WhatsApp notifications
7. Return standardized response

### Database Safety
- **Unique Index** on `(doctor_id, appointment_date, start_time)` prevents race conditions
- **Transactions** ensure atomic operations
- **Soft Deletes** on appointments for audit trail
- **Foreign Keys** with CASCADE DELETE for data integrity

### Middleware
- `auth:sanctum` - Require valid token
- `is_admin` - Require admin role
- `is_doctor` - Require doctor role

---

## 📝 API Request Examples

### Book an Appointment
```bash
curl -X POST https://demo-dentist-main-adaeep.free.laravel.cloud/api/appointments \
  -H "Content-Type: application/json" \
  -d '{
    "doctor_id": 2,
    "patient_name": "John Patient",
    "patient_phone": "+1234567890",
    "appointment_date": "2026-05-15",
    "start_time": "14:30"
  }'
```

### Get Doctor Schedule (authenticated)
```bash
curl -X GET https://demo-dentist-main-adaeep.free.laravel.cloud/api/doctor/schedule \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### Add Schedule Slot
```bash
curl -X POST https://demo-dentist-main-adaeep.free.laravel.cloud/api/doctor/schedule \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "day_of_week": 1,
    "start_time": "09:00",
    "end_time": "10:00"
  }'
```

---

## 🚨 Troubleshooting

### 500 Server Error
Check `storage/logs/laravel.log` for details:
```bash
tail -f storage/logs/laravel.log
```

### Database Connection Failed
Verify `.env` database credentials:
```bash
php artisan tinker
DB::connection()->getPDO();
```

### API Not Responding
Clear caches:
```bash
php artisan cache:clear
php artisan route:clear
php artisan config:clear
```

### WhatsApp Notifications Not Sending
1. Check API credentials in `.env`
2. Verify phone number format: `whatsapp:+1234567890`
3. Check `storage/logs/laravel.log` for errors
4. Ensure WhatsApp is enabled: `WHATSAPP_ENABLED=true`

---

## 📜 License

Proprietary - Demo Dentist System

---

## 👥 Support

For issues, questions, or contributions:
- **Email:** support@demo-dentist.com
- **Live API:** https://demo-dentist-main-adaeep.free.laravel.cloud
- **API Docs:** https://demo-dentist-main-adaeep.free.laravel.cloud/api/documentation

---

## 📋 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | Apr 26, 2026 | Initial release |
| - | - | RESTful API with full CRUD |
| - | - | WhatsApp notifications |
| - | - | Role-based access control |
| - | - | Standardized error handling |

---

**Last Updated:** April 26, 2026  
**API Base URL:** https://demo-dentist-main-adaeep.free.laravel.cloud/api
