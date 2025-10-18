PC Management System - Haramaya University

📖 Overview
The PC Management System is a centralized, web-based application developed to streamline the process of managing and tracking personal computers (primarily laptops) for students and staff across Haramaya University's main campus and its branches.

The system addresses two key challenges:

Gate Pass Management: Automates the "paper check" process when students/staff enter or leave the campus with their laptops, replacing a manual, paper-based system with a digital, efficient one.

Centralized Data & Multi-Campus Support: Provides a single source of truth for registered PC data. A user registered at the main campus can have their device recognized at any other branch (e.g., for events, library use, or temporary work), ensuring seamless mobility within the university ecosystem.

✨ Features
User & Device Registration: Students and staff can register their personal details and laptop information (Make, Model, Serial Number, etc.) into the system.

Digital Gate Pass: Generates a digital pass with a QR code for quick verification at campus entry/exit points. Security personnel can scan the QR code to validate the device's registration status.

Check-In / Check-Out Logging: Automatically logs the timestamp and gate location whenever a device is brought onto or taken off campus, creating a clear audit trail.

Multi-Campus Synchronization: Device data is centralized. A user traveling from the Main Campus to the Dire Dawa Branch, for example, will have their device information available for verification at the branch campus.

Admin Dashboard: Authorized personnel (e.g., ICT office staff, security heads) can view all registered devices, generate reports, and manage user accounts.

Search & Verification: Security personnel can quickly search for a device by serial number, owner name, or scan the QR code for instant verification.

🏛️ Supported Campuses
The system is designed to serve the following campuses of Haramaya University:

Main Campus (Haramaya)

Dire Dawa Campus

Hirna Campus

(Add any other specific branches your project includes)

🛠️ Technology Stack
Frontend: HTML, CSS, JavaScript (Consider mentioning a framework like React, Vue.js, or Bootstrap if you used one)

Backend: PHP / Python (Django/Flask) / Node.js (Choose one)

Database: MySQL / PostgreSQL

QR Code Generation: A library like qrcode (for Python) or endroid/qr-code (for PHP)

Version Control: Git & GitHub

🚀 Installation & Setup
Follow these steps to set up the project locally for development and testing.

Prerequisites
A web server (e.g., XAMPP, WAMP, or LAMP stack)

PHP 7.4+ / Python 3.8+ / Node.js 16+

MySQL 5.7+ / PostgreSQL

Composer / pip / npm (depending on your backend choice)

🧪 Usage

Upon successful registration, you will receive a digital pass with a QR code. Save this on your phone or print it.

For Security Personnel:Log in to the security portal at the gate.Use the "Verify" page to scan a user's QR code or manually search for their device by serial number.

The system will display the owner's details and log the check-in/check-out event.

For Admins: Log in to the admin dashboard. View all registered devices, filter by campus, and generate activity reports.

🤝 Contributing
We welcome contributions from the Haramaya University community!

Fork the Project.

Create your Feature Branch (git checkout -b feature/AmazingFeature).

Commit your Changes (git commit -m 'Add some AmazingFeature').

Push to the Branch (git push origin feature/AmazingFeature).

Open a Pull Request.

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

👥 Authors and Acknowledgments
Cherinet Habtamu - meke16


📞 Support
For support, email habtamucherinet40@gmail.com or open an issue on the GitHub repository.

