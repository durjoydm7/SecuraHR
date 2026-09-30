# SecuraHR – Human Resource Management System

SecuraHR is a web-based Human Resource Management System designed to manage employee information, attendance, leave requests, payroll, notices, user accounts, and administrative activities.

The system provides separate interfaces for **Administrators** and **Employees**, allowing HR-related tasks to be managed through a centralized platform.

---

## 📌 Project Overview

SecuraHR is developed as a PHP and MySQL-based web application running on a local XAMPP server.

The system provides:

* Employee registration and management
* User authentication and authorization
* Employee approval and account management
* Attendance management
* Leave request management
* Payroll and payslip management
* Notice management
* Audit log tracking
* Employee profile management
* Password reset functionality

---

## 🚀 Features

### 👨‍💼 Admin Features

Administrators can:

* View the admin dashboard
* Manage employee accounts
* Approve or reject employee registrations
* View employee information
* Manage employee attendance
* Review and manage leave requests
* Generate and manage payroll records
* Publish notices
* View system audit logs
* Monitor system activities

### 👨‍💻 Employee Features

Employees can:

* Log in securely
* View their dashboard
* View and update their profile
* View attendance information
* Clock in and clock out
* Submit leave requests
* View leave request history
* View salary information
* View payroll and payslip records
* Read published notices

### 🔐 Authentication & Security

The system includes:

* Email-based login
* Password hashing
* Role-based access control
* Employee account approval
* Pending/rejected/disabled account status handling
* Password reset functionality
* Audit logging
* Session-based authentication

---

## 🛠️ Technologies Used

### Frontend

* HTML5
* CSS3
* JavaScript

### Backend

* PHP
* PDO

### Database

* MySQL

### Development Environment

* XAMPP
* Apache
* MySQL
* Visual Studio Code

---

## 🗂️ Project Structure

```text
SecuraHR/
│
├── admin/
│   ├── includes/
│   │   └── sidebar.php
│   ├── attendance.php
│   ├── audit.php
│   ├── dashboard.php
│   ├── employees.php
│   ├── leave.php
│   ├── notices.php
│   ├── payroll.php
│   └── users.php
│
├── assets/
│   ├── favicon.svg
│   └── logo.svg
│
├── config/
│   └── database.php
│
├── css/
│   ├── admin.css
│   ├── employee.css
│   └── style.css
│
├── database/
│   ├── securahr.sql
│   └── seed.php
│
├── employee/
│   ├── includes/
│   │   └── sidebar.php
│   ├── attendance.php
│   ├── dashboard.php
│   ├── leave.php
│   ├── notices.php
│   ├── payroll.php
│   └── profile.php
│
├── forgot_password.php
├── index.php
├── login.php
├── logout.php
├── register.php
└── reset_password.php
```

---

## 🗄️ Database

The project uses a MySQL database named:

```text
securahr_db
```

The database contains tables for:

* Users
* Employees
* Departments
* Attendance
* Leave Requests
* Leave Balances
* Payroll
* Notices
* Audit Logs
* Notifications
* Password Resets
* Performance Reviews
* Promotions
* Salary Components
* Salary History
* Login Attempts
* Company Settings
* Contact Messages
* Admins

The database structure can be found in:

```text
database/securahr.sql
```

---

## ⚙️ Installation & Setup

### 1. Install XAMPP

Install XAMPP with:

* Apache
* MySQL
* PHP

### 2. Copy the Project

Place the project folder inside the XAMPP `htdocs` directory:

```text
C:\xampp\htdocs\SecuraHR
```

### 3. Start XAMPP

Open XAMPP Control Panel and start:

```text
Apache
MySQL
```

### 4. Create the Database

Open phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Create a database named:

```text
securahr_db
```

Import the SQL file:

```text
database/securahr.sql
```

### 5. Configure Database Connection

Open:

```text
config/database.php
```

Make sure the database configuration points to:

```text
Database Name: securahr_db
```

Update the database username and password if required by your local MySQL configuration.

### 6. Run the Project

Open the project in a browser:

```text
http://localhost/SecuraHR/
```

---

## 🔑 User Roles

SecuraHR provides role-based access.

### Administrator

The administrator can manage the overall HR system, including employees, attendance, leave, payroll, notices, users, and audit logs.

### Employee

Employees can access their own dashboard and manage/view their personal HR information.

---

## 🔄 Employee Registration Process

The employee registration process works as follows:

1. A new employee creates an account.
2. The account is initially placed in a pending state.
3. The administrator reviews the registration.
4. The administrator can approve or reject the account.
5. Once approved, the employee can log in.
6. The employee can then access the employee dashboard.

---

## 🕒 Attendance Management

The attendance module allows employees to:

* Clock in
* Clock out
* View attendance history

Administrators can view and manage employee attendance records.

Attendance information includes:

* Work date
* Check-in time
* Check-out time
* Work hours
* Attendance status

---

## 📝 Leave Management

Employees can submit leave requests by providing:

* Leave type
* Start date
* End date
* Reason

The system calculates the total number of leave days.

Administrators can:

* Review pending requests
* Approve requests
* Reject requests

Employees can view their leave request history and status.

---

## 💰 Payroll Management

The payroll module manages employee salary records.

Payroll information includes:

* Basic salary
* House allowance
* Medical allowance
* Transport allowance
* Overtime pay
* Bonus
* Absence deduction
* Late deduction
* Tax deduction
* Other deductions
* Gross salary
* Net salary
* Payroll status

Employees can view their available payslip records from the employee payroll page.

---

## 📢 Notice Management

Administrators can publish notices for employees.

Notices contain information such as:

* Title
* Content
* Category
* Publication status
* Publication date
* Author

Employees can view published notices from their dashboard or notices page.

---

## 📋 Audit Logs

The system records important activities through audit logs.

Audit records can contain:

* User
* Action
* Module
* Target ID
* Description
* IP address
* Timestamp

Administrators can use the audit log page to review system activities.

---

## 🔐 Password Reset

The system provides a password reset mechanism.

The reset process includes:

1. Entering the registered email address
2. Generating a reset token
3. Validating the token
4. Setting a new password

Password reset tokens have an expiration time for security.

---

## 🧪 Testing

The following major modules were tested during development:

* Admin login
* Employee registration
* Employee approval
* Employee login
* Admin dashboard
* Employee dashboard
* Employee profile
* Attendance
* Leave management
* Payroll
* Notices
* Audit logs
* Password reset

Database column mismatches and SQL query errors were corrected according to the implemented database structure.

---

## 📁 Important Files

| File/Folder             | Purpose                |
| ----------------------- | ---------------------- |
| `index.php`             | Main landing page      |
| `login.php`             | User login             |
| `register.php`          | Employee registration  |
| `forgot_password.php`   | Password reset request |
| `reset_password.php`    | Password reset         |
| `admin/`                | Administrator modules  |
| `employee/`             | Employee modules       |
| `config/database.php`   | Database connection    |
| `database/securahr.sql` | Database structure     |
| `css/`                  | Application styles     |
| `assets/`               | Images and icons       |

---

## 💻 Local Development Environment

This project was developed and tested using:

```text
Operating System: Windows
Server: XAMPP / Apache
Backend: PHP
Database: MySQL
Editor: Visual Studio Code
```

---

## 👨‍🎓 Project Purpose

The purpose of this project is to develop a functional Human Resource Management System that demonstrates practical implementation of:

* PHP programming
* MySQL database management
* CRUD operations
* Authentication
* Role-based authorization
* Session management
* Form validation
* Database relationships
* Attendance management
* Leave management
* Payroll management
* Audit logging
* Web application development

---

## 📌 Project Status

**Status: Completed**

The major Admin and Employee modules have been implemented and tested in the local XAMPP environment.

---

## 🔗 GitHub Repository

GitHub Repository:

```text
https://github.com/durjoydm7/SecuraHR
```

---

## 👤 Author

**Name:** Durjoy Datta Mretonjoy

**Project:** SecuraHR – Human Resource Management System

**Technology:** PHP, MySQL, HTML, CSS, JavaScript
