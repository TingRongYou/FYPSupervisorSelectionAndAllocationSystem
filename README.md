# TAR UMT Supervisor Selection and Allocation System (SSAS)

## 🛠️ Local Environment Setup Guide
To ensure code compatibility and prevent database conflicts, please follow these exact steps to set up the SSAS project on your local workstation.

### 1. Prerequisite
You **must** use the exact same XAMPP version as the rest of the team to ensure PHP 8.0 compatibility.
* **Download XAMPP:** XAMPP for Windows`8.0.30 / PHP 8.0.30 64-bit version` at [Apache Friend websites](https://www.apachefriends.org/download.html).
* **IDE:** Visual Studio Code.
* **Required VS Code Extensions:** Please install the following extensions: 
    * `Database Client JDBC (by database-client.com)`- Connect local MySQL and run database initialisation scripts.
    * `PHP Intelephense (by intelephense.com)` - PHP syntax highlighting, auto-completion, and error tracking.
    * `Prettier - Code Formatter (by prettier.io)` - Maintain consistent HTML/CSS/JS styling across the team.

### 2. Cloning the Repository
Your local server needs to host the files. Do not clone this to your Desktop. 
1. Navigate to your XAMPP installation's public directory: `C:\xampp\htdocs\`
2. Open your terminal in this folder and run:
   ```bash
   git clone git@github.com:TingRongYou/FYPSupervisorSelectionAndAllocationSystem.git ssas
   ```
3. Open the newly created SSAS folder in VS Code

### 3. Database Initialisation (Clean Slate)
We are using a single "idempotent" SQL script to build the entire database architecture at once.
1. Open the XAMPP Control Panel and Start both Apache and MySQL.
2. Inside VS Code, locate the `database/schema.sql` file.
3. You can execute this file using the VS Code Database Client extension. _(Note: Our schema.sql includes a DROP DATABASE IF EXISTS command. You can run this file as many times as you want to instantly reset your local database to a clean slate during testing.)_

### 4. Architecture Overview
This project strictly follows a 2-Tier Client-Server Layered Architecture. Please ensure new files are created in their designated layers:
* `/client` - Presentation Layer (HTML, CSS, JS / AJAX API calls)
* `/server/application` - API Gateway & Authentication Managers
* `/server/business` - Core Logic (Allocation Engine, Quota Calculations)
* `/server/data` - Data Access Objects (DAOs) and PDO Database Drivers
* `/database` - SQL schema blueprints
* `/storage` - External file storage for FYP Proposal PDFs (Ignored by Git)

# TAR UMT Supervisor Selection and Allocation System (SSAS)
===========================================================

## 📌 Project Overview
=======================

The Supervisor Selection and Allocation System (SSAS) is a web-based Final Year Project (FYP) management platform developed for TAR UMT to digitize and automate the existing manual supervisor allocation workflow.

The system replaces spreadsheet and email-based processes with a centralized platform that supports:

* Real-time supervisor quota management
* Student-supervisor matching
* Request tracking workflows
* Automated allocation logic
* Administrative reporting
* Supervisor professional profiling

The project strictly follows the Software Requirements Specification (SRS) and Software Design Document (SDD).

---

## ⚠️ Important Development Rules
==================================

All implementation MUST strictly follow:

* Functional Requirements (FR)
* Non-Functional Requirements (NFR)
* ERD Diagrams
* DFD Diagrams
* Activity Diagrams
* Class Diagrams
* Sequence Diagrams
* Business Rules
* Allocation Algorithms
* System Architecture Design
* Waterfall SDLC Documentation

Developers and AI-assisted tools (e.g. Codex) must NOT introduce workflows, database structures, or business logic that contradict the approved SRS and SDD documentation.

---

## 👥 Team Distribution
========================

### Ting Rong You
==================

Student Journey Developer & QA Testing Lead

Modules:

* Availability & Status Module
* Discovery & Search Module
* Request & Proposal Module
* Student Profile Module
* Student Review Module
* Timeline & Milestone Module

### Yong Chong Xin
===================

Supervisor Journey Developer & Backend Architect

Modules:

* User Management Module
* Allocation & Quota Module
* Administrator Report Module
* Professional Profile Module
* Request Management Module
* Supervisor Report Module

---

## 🏗️ System Architecture
==========================

This project follows a strict 2-Tier Client-Server Layered Architecture.

### Layer Structure
===================

* `/client`

  * Presentation Layer
  * HTML / CSS / JavaScript / AJAX

* `/server/application`

  * API Gateway
  * Authentication Managers

* `/server/business`

  * Business Logic Layer
  * Allocation Engine
  * Quota Validation
  * Workflow Enforcement

* `/server/data`

  * Data Access Objects (DAO)
  * PDO Database Drivers

* `/database`

  * SQL Schema
  * Database Initialization Scripts

* `/storage`

  * Uploaded proposal PDFs
  * Ignored by Git

---

## 🔒 Architectural Constraints
================================

The system implementation MUST:

* Preserve layered architecture separation
* Prevent direct database access from frontend
* Enforce RBAC authorization rules
* Maintain quota consistency
* Follow centralized workflow transitions
* Preserve proposal lifecycle states:

  * Pending
  * Accepted
  * Rejected

---

## 🔄 Workflow Integrity
=========================

All request flows and allocation processes must follow the approved activity diagrams and system flow documentation.

Critical business rules:

* No supervisor overallocation
* No invalid proposal state transitions
* No bypassing quota validation
* Auto-allocation only after selection deadline
* Role-based authorization required for all protected actions

---

## 🧠 AI-Assisted Development Notes
====================================

When using AI tools such as Codex:

* Read README.md before generating code
* Follow SRS and SDD documents strictly
* Preserve database consistency with ERD
* Preserve class/module relationships
* Do not modify teammate-owned modules
* Do not create duplicate workflows
* Follow existing naming conventions and folder structure

---

# 📂 Recommended Project Structure

```plaintext
FYPSupervisorSelectionAndAllocationSystem/
│
├── client/                         # Frontend Presentation Layer
│   ├── css/
│   ├── js/
│   ├── pages/
│   └── assets/
│
├── server/
│   ├── application/               # API Gateway & Authentication
│   │   ├── AuthManager.php
│   │   └── SessionManager.php
│   │
│   ├── business/                  # Business Logic Layer
│   │   ├── AllocationEngine.php
│   │   ├── QuotaManager.php
│   │   └── RequestWorkflowManager.php
│   │
│   └── data/                      # DAO & Database Layer
│       ├── database.php
│       ├── UserDAO.php
│       ├── SupervisorDAO.php
│       └── RequestDAO.php
│
├── database/
│   └── schema.sql
│
├── storage/
│
├── README.md
├── index.php
└── .gitignore
```

---

# 🔄 Application Request Lifecycle

The supervisor application workflow strictly follows the approved activity diagrams and business rules from the SRS and SDD documentation.

```plaintext
Pending
   ↓
Accepted
   ↓
Allocated
```

```plaintext
Pending
   ↓
Rejected
```

### Workflow Rules

* Students cannot submit duplicate active requests
* Supervisors cannot exceed quota limits
* Allocation only occurs after supervisor approval
* Invalid state transitions are prohibited
* Request lifecycle validation must occur inside the business layer
* Workflow implementation must follow the approved activity diagrams

---

# 🔐 Role-Based Access Control (RBAC)

The system implements strict role-based authorization.

### System Roles

* Student
* Supervisor
* Administrator

### Authorization Rules

* Students can only manage their own applications
* Supervisors can only manage assigned requests
* Administrators have full system access
* Protected routes must validate session authentication
* Unauthorized access attempts must be blocked

---

# 🌿 Git Workflow

This project uses branch-based collaborative development.

### Main Branch

```plaintext
main
```

### Developer Branches

```plaintext
Admin
Student
```

### Git Rules

* Never push directly to main
* Commit frequently with meaningful messages
* Pull latest updates before pushing
* Resolve merge conflicts immediately
* Test functionality before committing
* Keep commits modular and readable

### Recommended Git Commands

```bash
git pull
git status
git add .
git commit -m "Implemented quota validation"
git push
```

---

# 🧹 Coding Standards

### Backend Rules

* Business logic must remain inside `/server/business`
* Database queries must remain inside `/server/data`
* Authentication must remain inside `/server/application`
* Frontend must never directly access the database
* Use PDO prepared statements only
* Prevent SQL injection vulnerabilities

### Frontend Rules

* Maintain separation between UI and backend logic
* Use modular JavaScript structure
* Keep UI components reusable and maintainable

### General Rules

* Follow naming consistency across modules
* Preserve ERD relationships and constraints
* Preserve class relationships from class diagrams
* Preserve workflow integrity from activity diagrams
* Avoid duplicate business logic implementations

---

# 🚀 Recommended Development Roadmap

### Phase 1 — Core Infrastructure

* Database Connection
* Session Management
* Authentication System
* RBAC Authorization

### Phase 2 — Core Modules

* User Management Module
* Professional Profile Module
* Request Management Module

### Phase 3 — Business Logic

* Allocation Engine
* Quota Validation
* Workflow Enforcement

### Phase 4 — Reporting & Analytics

* Supervisor Reports
* Administrator Reports
* Dashboard Statistics

### Phase 5 — Finalisation

* Validation Testing
* UI Refinement
* Security Testing
* Integration Testing
* Deployment Preparation

```
```
