# 📦 Courier & Parcel Tracking System

A web-based courier management system built with Core PHP and MySQL, featuring 
three separate panels — Admin, Rider, and Customer — with real-time parcel 
status tracking.

## Features
- Role-based authentication (Admin, Rider, Customer)
- Customers can book parcels and track them via unique Tracking ID
- Admin can assign parcels to riders and manage all bookings
- Riders can update parcel status (Assigned → Picked Up → In Transit → Delivered)
- Live status timeline/history for every parcel
- Responsive UI built with Bootstrap

## Tech Stack
- **Backend:** Core PHP (OOP)
- **Database:** MySQL
- **Frontend:** HTML, CSS, Bootstrap 5

## Setup Instructions
1. Clone this repository
2. Import `database.sql` into MySQL (via phpMyAdmin)
3. Update database credentials in `config.php` if needed (default: host=localhost, user=root, password=empty)
4. Place project folder in `htdocs` (XAMPP) or `www` (WAMP)
5. Start Apache & MySQL, then open in browser:
   `http://localhost/courier-tracking-system/`

## Login Credentials

**Admin**
- Email: admin@courier.com
- Password: admin123

**Rider & Customer**
- Register a new customer account from the signup page
- Rider accounts are created by Admin from the Admin Panel

## Database Structure
- `users` — Customers and Admin accounts
- `riders` — Rider accounts (created by Admin only)
- `parcels` — All parcel bookings and their current status
- `parcel_status_history` — Full status timeline for tracking.
