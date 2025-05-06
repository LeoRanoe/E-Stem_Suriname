# ✅ Task: Separate Admin Access

## 🧠 Context
Currently, admins and voters share the same login form and database table. We need to:
1. Create a dedicated admin login system
2. Store admin credentials in a separate table
3. Implement proper role-based access control

## 🔧 Actions
1. Create new `admins` table with:
   - id, username, password_hash, created_at, last_login
2. Create new admin login form at `admin/login.php`
3. Move admin authentication logic to `include/admin_auth.php`
4. Update all admin pages to check admin session
5. Remove admin accounts from voters table

## 🗃️ Files to Modify/Create
- `database/e-stem_suriname.sql` (add admins table)
- `admin/login.php` (new)
- `include/admin_auth.php` (new)
- Update all admin/*.php files to use new auth
- `src/controllers/VoterController.php` (remove admin accounts)

## 📎 Testing
- Verify admin login works with new form
- Check voter login can't access admin areas
- Test all existing admin functionality still works

## ⚠️ Constraints
- Must migrate existing admin accounts to new table
- Maintain all admin functionality during transition