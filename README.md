# DONMA ATS User Manual

This guide is for the people who use the system every day: teachers, school staff, and students.

The system helps the school keep track of attendance easily and quickly.

## 1. What this system is for

The DONMA Attendance Tracking System is used to:

- record when students arrive at school
- record when students leave
- know who is present, late, or absent
- manage teacher and student accounts
- view reports for attendance
- help teachers monitor attendance from their devices

## 2. How to open the system

Use a browser such as Google Chrome or Microsoft Edge.

Open the school website or the local address provided by the school.

Example:

- http://localhost/dmcmes/landing.php
- or the school’s online access link

If you are using the system on a school computer, the school office or IT staff can give you the correct link.

## 3. Logging in

### For teachers and administrators

1. Open the system.
2. Click the Login button.
3. Enter your email address or teacher ID.
4. Enter your password.
5. Click Login.

If your account is not yet active, the administrator may need to approve it first.

### For students

Students do not log in to the main system. Staff enroll students, who then use the kiosk for attendance.

## 4. The main screen

After logging in, you will see the dashboard.

The dashboard shows:

- total number of students
- students present today
- students late today
- recent attendance records
- quick links to other pages

The page is simple and lets you move around the system using the menu on the left side.

## 5. Menu and pages

The system has different pages depending on your role.

### Administrator menu

The administrator can:

- view all users
- approve teacher accounts
- enroll students from the sidebar or dashboard
- manage student enrollment requests
- view school attendance reports
- check pending records

### Teacher menu

Teachers can:

- enroll students from the sidebar or dashboard
- see today’s attendance
- monitor the student kiosk
- send messages to the kiosk
- check their class reports

### Student access

Students use the kiosk for attendance after staff enroll them in the system.

## 6. How teachers use the system

### View student list

1. Go to the Student List page.
2. Find the student you want to check.
3. Review the student’s information and enrollment status.

### Enroll a student

1. Select **Enroll Student** from the Students section in the sidebar or from Quick Actions on the dashboard.
2. Enter the student's learner and personal information, then submit the form.
3. Teacher-submitted enrollments wait for administrator approval. Administrator enrollments are approved immediately.

### Check attendance

1. Open the dashboard or Attendance Records page.
2. Look for the day or student you want to review.
3. Check whether the student is present, late, or absent.

### Live monitoring

Teachers can view the student kiosk live.

1. Open Live Monitoring.
2. Wait for the connection to the kiosk.
3. Watch the kiosk feed when it is active.
4. If needed, send a message to the kiosk screen.

This is helpful when a teacher wants to guide students or share school updates.

## 7. How administrators use the system

### Approve teacher accounts

1. Open the Users & Teachers page.
2. Look for teacher accounts waiting approval.
3. Click Approve.
4. The teacher can then log in after approval.

### Manage pending enrollments

The administrator checks students waiting to be approved.

1. Open Pending Enrollments.
2. Review each student request.
3. Approve or reject the request when needed.

Administrators can enroll students from the sidebar or dashboard. Those enrollments are approved immediately.

### View reports

The administrator can open the reports page to see:

- student attendance totals
- late attendance counts
- class attendance summaries
- daily trends

## 8. How the kiosk works

The student kiosk is used for checking in and out quickly.

### Attendance process

1. Open the kiosk page.
2. The system shows the current time.
3. The student scans or enters the required information.
4. The system checks the student’s face.
5. Attendance is recorded.
6. A confirmation message appears.

### Attendance status

The system may show:

- Present
- Late
- Absent

The result depends on the attendance rules and school schedule.

## 9. Attendance records

The system keeps a record of each attendance entry.

These records may include:

- student name
- LRN
- time in
- time out
- attendance status
- captured photo reference

Teachers and administrators can review these records from the dashboard or attendance pages.

## 10. Reports

Reports help the school review attendance trends.

### Teacher reports

Teacher reports show:

- which students are present
- which students are late
- which students are absent
- current attendance patterns in the class

### Administrator reports

Admin reports show:

- school-wide attendance totals
- daily and weekly attendance trends
- student attendance summaries
- class-based reports

## 11. Sending messages to the kiosk

Teachers can send messages to the kiosk.

Examples of messages:

- good morning
- attendance reminder
- class reminder
- school announcement

This helps students receive important information during attendance time.

## 12. Account and password help

### If you forget your password

Use the password reset option on the login page.

1. Click Forgot Password.
2. Enter the email or account information required.
3. Follow the instructions sent to your email.

If you do not receive the reset message, contact the school office or administrator.

### If your account is inactive

Your account may still be waiting for approval.

If this happens:

- contact the administrator
- ask the school office to check your account status

## 13. Common issues and solutions

### I cannot log in

Possible reasons:

- wrong email or password
- account is not active yet
- teacher account is waiting for approval

What to do:

- recheck your login details
- ask the administrator if the account is approved
- ask the school office for support

### The kiosk is not working

Possible reasons:

- camera permission is blocked
- the browser is not allowed to access the camera
- the kiosk page is not loaded correctly

What to do:

- allow camera access in the browser
- refresh the page
- ask the school office for help if needed

### The system is slow or not loading

Try:

- refreshing the page
- checking your internet connection
- closing extra browser tabs
- logging out and logging back in

## 14. Best practices

- Log out after using the system on a shared computer.
- Use your assigned account only.
- Do not share passwords.
- Check attendance regularly.
- Report any problems to the school office immediately.

## 15. Quick start guide

### For teachers

1. Log in.
2. Open the dashboard.
3. Check student attendance.
4. View the class report.
5. Use Live Monitoring as needed.

### For administrators

1. Log in.
2. Review pending accounts and enrollments.
3. Approve teacher accounts.
4. Check attendance reports.
5. Manage school records.

### For students

1. Use the attendance kiosk as instructed by school staff.

## 16. Support

If you have problems with the system, contact:

- the school office
- the administrator
- the teacher in charge of the attendance system

The school office can help with account access, approval, and attendance questions.

## 17. Technical specifications

### System information

| Item | Details |
|---|---|
| Product name | DONMA Attendance Tracking System (DONMA ATS) |
| Version | Not specified in the project |
| Type | Server-rendered web application |
| Platform | Web browser; Apache/XAMPP for local hosting, or a PHP-compatible web host |
| Backend | PHP 8.1 or newer |
| Database | MySQL-compatible database; PDO MySQL, InnoDB, `utf8mb4` |
| Frontend | HTML, CSS, JavaScript; locally bundled CoreUI and Chart.js 4.5.1 |
| Institution | Don Marcelo C. Marty Elementary School |
| Main functions | Staff and student management, attendance, reports, kiosk QR scanning, camera capture, live monitoring, and kiosk messages |

### System requirements

| Component | Requirements |
|---|---|
| Browser | Current Chrome, Edge, Firefox, or Safari; camera access is needed for kiosk camera features |
| Device | Computer, tablet, or smartphone with a camera for kiosk capture and scanning |
| Internet/network | Required to access a hosted system and its database; local XAMPP use can run on the same computer or school network |
| Web server | Apache or another server that supports PHP 8.1+ |
| PHP extensions | PDO and PDO MySQL (`pdo_mysql`) |
| Database server | MySQL-compatible server with InnoDB support |
| PHP packages | Composer dependencies, including PHPMailer 6.9; run `composer install` |
| Camera security | HTTPS on deployed sites; `http://localhost` is supported for local development |
| Configuration | Set database credentials and application URL through environment variables or private `config/deployment.php`; see `config/deployment.example.php` |
| Storage | Writable application storage for captured attendance images and runtime files |
| Database setup | Import or migrate with the SQL files in the `config/` directory |

## 18. Final reminder

This system is made to make attendance easier and faster for the whole school. It helps teachers, administrators, and students stay organized and informed.

If you use it correctly and check it regularly, it will make daily school attendance smoother and easier to manage.
