

### Phase 2

Build the MVC foundation.

Create:

* Router
* BaseController
* BaseModel
* Database Connection Class
* Session Service
* Authentication Middleware
* Authorization Middleware
* CSRF Service
* Validation Service
* Response Helpers

All code must use OOP PHP.

### Phase 3
Implement authentication.

Features:

* Login
* Logout
* Forgot Password
* Reset Password
* Change Password

Security:

* Password hashing
* Session regeneration
* Login throttling
* CSRF protection

Create controllers, models, views and routes.

### Phase 4
Implement role-based access control.

Roles:

* Staff
* Nurse
* Doctor
* Technician
* Biomedical Engineer
* Manager
* Administrator
* Super Administrator

Create:

* Permission Matrix
* Middleware
* Role Assignment UI
* Permission Assignment UI

Protect all routes.


### Phase 5
Build the main application layout.

Create:

- Sidebar
- Top Navigation
- User Menu
- Notification Bell
- Breadcrumbs
- Footer

Use Bootstrap 5.

Responsive desktop and mobile.

Create dashboard pages.

Staff Dashboard:
- My Tickets
- Recent Requests

Technician Dashboard:
- Assigned Tickets
- SLA Alerts

Manager Dashboard:
- Department Metrics

Admin Dashboard:
- System Overview

Use Chart.js.
Create Ticket Category Management.

Features:

- Add Category
- Edit Category
- Delete Category
- Subcategories

CRUD with validation.


Build ticket creation.

Fields:

- Title
- Description
- Category
- Priority
- Department
- Location
- Asset

Attachments:
- Images
- Documents

Create database integration.

Create ticket detail page.

Sections:

- Ticket Information
- Attachments
- Comments
- Timeline
- Assignment History
- Activity Log

Allow status updates.


Create ticket listing page.

Features:

- Search
- Filters
- Pagination
- Sorting

Display:

- Ticket Number
- Title
- Status
- Priority
- Department
- Assigned To


Create ticket detail page.

Sections:

- Ticket Information
- Attachments
- Comments
- Timeline
- Assignment History
- Activity Log

Allow status updates.



Create resolution workflow.

Statuses:

New
Assigned
In Progress
Waiting User
Resolved
Closed

Capture:

- Resolution Notes
- Resolution Time
- Technician Notes


Create resolution workflow.

Statuses:

New
Assigned
In Progress
Waiting User
Resolved
Closed

Capture:

- Resolution Notes
- Resolution Time
- Technician Notes


Create Asset Category module.

Examples:

- Computers
- Printers
- Scanners
- Servers
- Medical Devices

CRUD operations.

Build Asset Registry.

Fields:

- Asset Tag
- Serial Number
- Manufacturer
- Model
- Department
- Purchase Date
- Warranty Expiry

Create full CRUD.

Build asset assignment.

Features:

- Assign User
- Assign Department
- Transfer Asset
- Return Asset

Track history.


Create asset lifecycle tracking.

Statuses:

Active
Maintenance
Retired
Disposed

Show complete history.


Prompt 17 – QR Codes
Generate QR codes for assets.

Requirements:

- Unique QR code
- Download QR
- Print QR Label

Scanning should open asset page.
Phase 5: Maintenance
Prompt 18 – Maintenance Dashboard
Build maintenance dashboard.

Widgets:

- Upcoming Maintenance
- Overdue Maintenance
- Completed Maintenance

Use charts.
Prompt 19 – Preventive Maintenance
Create preventive maintenance module.

Features:

- Schedule Maintenance
- Recurring Tasks
- Calendar View
- Assign Technician
Prompt 20 – Work Orders
Build work order management.

Fields:

- Work Order Number
- Asset
- Technician
- Due Date
- Status

Track completion.
Phase 6: Inventory
Prompt 21 – Inventory Categories
Create inventory categories.

Examples:

- Toners
- Network Cables
- Hard Drives
- Memory Modules
Prompt 22 – Inventory Management
Build inventory module.

Features:

- Stock In
- Stock Out
- Current Stock
- Minimum Stock

Track movements.
Prompt 23 – Reorder Alerts
Implement stock threshold monitoring.

Generate alerts when stock reaches minimum level.
Phase 7: Knowledge Base
Prompt 24 – Knowledge Categories
Create knowledge base categories.

CRUD functionality.
Prompt 25 – Articles
Build article management.

Features:

- Rich Text Editor
- Images
- Attachments
- Categories

Full CRUD.
Prompt 26 – Search
Implement knowledge base search.

Search:
- Titles
- Content
- Categories

Use AJAX.
Phase 8: Notifications
Prompt 27 – Notification System
Build notification module.

Features:

- In-App Notifications
- Read/Unread
- Notification Center

Use AJAX refresh.
Prompt 28 – Email Notifications
Implement email notifications.

Events:

- Ticket Created
- Assigned
- Resolved
- Closed

Use SMTP configuration.
Phase 9: SLA Engine
Prompt 29 – SLA Rules
Build SLA configuration.

Fields:

- Priority
- Response Time
- Resolution Time

CRUD interface.
Prompt 30 – Escalation Engine
Build automated SLA escalation.

If ticket exceeds SLA:

- Notify Manager
- Flag Ticket
- Record Escalation
Phase 10: Reporting
Prompt 31 – Ticket Reports
Create ticket reporting.

Filters:

- Date Range
- Department
- Priority
- Status

Export PDF and Excel.
Prompt 32 – Asset Reports
Create asset reports.

Show:

- Asset Utilization
- Warranty Expiry
- Asset Inventory

Export functionality.
Prompt 33 – Maintenance Reports
Create maintenance analytics.

Charts:

- Scheduled Maintenance
- Completed Maintenance
- Overdue Maintenance
Phase 11: Administration
Prompt 34 – Department Management
Create department management.

CRUD for:

- Departments
- Buildings
- Floors
- Rooms
Prompt 35 – User Management
Build user management.

Features:

- Create User
- Edit User
- Deactivate User
- Assign Roles
Prompt 36 – Audit Logs
Implement audit logging.

Track:

- Logins
- Ticket Updates
- Asset Updates
- Permission Changes

Create audit viewer.
Phase 12: Production
Prompt 37 – Security Hardening
Review entire system.

Implement:

- XSS Protection
- CSRF Protection
- Secure Uploads
- Rate Limiting
- Session Security
Prompt 38 – Installer
Build installation wizard.

Steps:

1. Database Configuration
2. System Setup
3. Admin Account Creation
4. Finish Installation
Prompt 39 – Backup Module
Create backup management.

Features:

- Database Backup
- File Backup
- Restore Backup
Prompt 40 – Final Audit
Review the entire codebase.

Identify:

- Security issues
- Performance bottlenecks
- Missing validations
- Database optimization opportunities

Refactor and optimize production readiness.
