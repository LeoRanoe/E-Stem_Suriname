# ✅ Task: Remove User Self-Registration

## 🧠 Context
Currently, users can register themselves through the registration forms. In a real government voting system, voters would never register themselves - they would be pre-registered by election officials. We need to:
1. Remove all self-registration functionality
2. Ensure the system only accepts pre-imported voters

## 🔧 Actions
1. Delete these files:
   - `src/controllers/register.php`
   - `src/views/register_step1.php`
2. Remove registration links from:
   - `include/nav.php`
   - `src/views/login.php`
3. Update database schema to remove registration-related fields if any
4. Add redirect from any registration URLs to login page

## 🗃️ Files to Modify
- `src/controllers/register.php` (delete)
- `src/views/register_step1.php` (delete)
- `include/nav.php`
- `src/views/login.php`
- `database/e-stem_suriname.sql` (if needed)

## 📎 Testing
- Verify registration forms are completely removed
- Check all registration links redirect properly
- Ensure no registration-related functionality remains

## ⚠️ Constraints
- Must maintain all existing voter data
- Don't remove voter table - just registration functionality