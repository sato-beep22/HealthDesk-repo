# HealthDesk - Student Clinic Medical Records and Inventory Management System

## Overview

HealthDesk is a comprehensive web-based system designed for university or school clinics to manage student medical records and inventory efficiently. The system supports two user roles: Admin Staff and regular Clinic Staff, with role-based access controls.

## Features

### User Authentication & Roles
- Secure login system with Admin ID/Staff ID and password
- Two distinct user roles: Admin Staff and Staff
- Role-based access control for different system features
- Password recovery placeholder (ready for implementation)

### Dashboard (Admin Only)
- Welcome message with current date and patient count
- Search functionality for student records
- Recently added patients list with quick actions
- Recent health reports summary
- Inventory status overview by category

### Patient Records Management
- Comprehensive patient information forms
- Personal Information, Physical Examination, and Medical History sections
- Searchable and paginated patient list
- Detailed patient record views with complete medical history
- Health reports and dispensation history tracking

### Inventory Management (Admin Only)
- Add, edit, and delete inventory items
- Categorization (Medicine, First Aid, Equipment)
- Stock level tracking with status indicators (In Stock, Low Stock, Critical)
- Administer items to patients with automatic quantity deduction
- Dispensation logging and history

### Reports System (Admin Only)
- Generate downloadable patient reports (Full Record, Health Summary, Medical History)
- View and manage health reports
- Rich text editor for detailed report writing

### Settings
- User profile management
- System configuration options

## Technical Stack

- **Frontend**: HTML5, CSS3 (Responsive design)
- **Backend**: PHP 8.x
- **Database**: MySQL
- **Styling**: Custom CSS with dark green, light green, and off-white color scheme

## Installation & Setup

### Prerequisites
- PHP 8.x or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx) or local development environment (XAMPP/WAMP)

### Installation Steps

1. **Clone or Download the Project**
   ```
   Download the HealthDesk folder to your web server's document root
   For XAMPP: Place in htdocs folder
   For WAMP: Place in www folder
   ```

2. **Database Setup**
   - Create a new MySQL database named `healthdesk`
   - Import the `database.sql` file to create tables and sample data
   - Update database credentials in `includes/config.php` if necessary

3. **Configuration**
   - Open `includes/config.php`
   - Update database connection settings if needed:
     ```php
     define('DB_HOST', 'localhost');
     define('DB_USER', 'your_mysql_username');
     define('DB_PASS', 'your_mysql_password');
     define('DB_NAME', 'healthdesk');
     ```

4. **Access the Application**
   - Open your web browser
   - Navigate to `http://localhost/HealthDesk/` (adjust path as needed)
   - Login with sample credentials:
     - Admin: admin001 / password
     - Staff: staff001 / password

## Database Schema

The system uses the following main tables:

- **users**: User authentication and role management
- **patients**: Complete patient medical records
- **inventory**: Medical supplies and equipment tracking
- **dispensation_log**: Item administration history
- **reports**: Health reports and medical documentation

## User Roles & Permissions

### Admin Staff
- Full access to all system features
- Dashboard with comprehensive overview
- Complete inventory management (CRUD operations)
- Patient record management
- Report generation and management
- System settings access

### Staff
- Limited access to core functions
- Add new patient records
- View patient lists and records
- Redirected to Add Records page upon login
- No access to inventory management or reports

## Security Features

- Password hashing using PHP's password_hash()
- Session-based authentication
- Role-based access control
- Input validation and sanitization
- SQL injection prevention with prepared statements

## File Structure

```
HealthDesk/
├── css/
│   └── style.css          # Main stylesheet
├── includes/
│   └── config.php         # Database configuration and utilities
├── pages/                 # (Reserved for future expansion)
├── images/                # (Reserved for future assets)
├── js/                    # (Reserved for future scripts)
├── index.php              # Application entry point
├── login.php              # User authentication
├── logout.php             # Session termination
├── dashboard.php          # Admin dashboard
├── add_record.php         # Patient record creation
├── patients_list.php      # Patient search and listing
├── patient_record.php     # Detailed patient view
├── inventory.php          # Inventory management
├── add_report.php         # Health report creation
├── reports.php            # Report management
├── generate_report.php    # Report generation
├── view_report.php        # Report viewing
├── edit_inventory.php     # Inventory item editing
├── delete_inventory.php   # Inventory item deletion
├── settings.php           # User settings
├── forgot_password.php    # Password recovery (placeholder)
└── database.sql           # Database schema and sample data
```

## Sample Data

The system includes sample data for testing:
- 2 users (1 Admin, 1 Staff)
- 2 sample patients with complete medical records
- Sample inventory items across all categories
- Sample health reports

## Future Enhancements

- Email-based password recovery
- PDF report generation
- Advanced search and filtering
- Appointment scheduling
- Medical imaging integration
- Multi-language support
- API endpoints for mobile app integration

## Support

For technical support or questions about the HealthDesk system, please contact your system administrator or refer to the inline code documentation.

## License

This project is developed for educational and institutional use. Please ensure compliance with local data protection regulations (e.g., HIPAA, GDPR) when deploying in production environments.
