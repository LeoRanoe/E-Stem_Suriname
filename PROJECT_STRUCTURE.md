# Project Structure Documentation

## Root Directory
- `.htaccess` - Apache configuration for URL routing and security
- `composer.json` - PHP dependency management
- `composer.lock` - Lock file for PHP dependencies
- `index.php` - Main entry point for the application
- `php.ini` - PHP configuration
- `README.md` - Project documentation

## Admin Section (`/admin`)
- Contains admin interface controllers and views
- Key files:
  - `login.php` - Admin login page
  - `components/` - Reusable admin components
  - `import_voter.php` - Voter import functionality

## Assets Directory (`/assets/js`)
- Contains JavaScript files for frontend functionality
- Key files:
  - `admin-dashboard.js` - Admin dashboard scripts
  - `candidate.js` - Candidate management scripts
  - `election.js` - Election management scripts
  - `results-chart.js` - Results visualization

## Database Directory (`/database`)
- Contains database schema and seed data
- Key files:
  - `e-stem_suriname.sql` - Main database schema

## Include Directory (`/include`)
- Contains shared PHP components
- Key files:
  - `config.php` - Configuration settings
  - `db_connect.php` - Database connection
  - `auth.php` - Authentication functions
  - `header.php` - Common header
  - `footer.php` - Common footer

## Page Directory (`/pages`)
- Contains public-facing pages
- Key files:
  - `logout.php` - Logout functionality
  - `vote_success.php` - Voting confirmation

## Source Code Directory (`/src`)
- Follows MVC pattern
- Key files:
  - `controllers/` - Application controllers
  - `views/` - Application views
  - `uploads/` - Uploaded files

## Task Directory (`/tasks`)
- Contains development plans
- Key files:
  - Numbered task files (e.g. `01_remove_user_registration.md`)

## Upload Directory (`/uploads`)
- Contains uploaded media
- Subdirectories:
  - `candidate/` - Candidate images
  - `party/` - Party images
