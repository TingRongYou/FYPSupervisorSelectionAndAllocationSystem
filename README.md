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