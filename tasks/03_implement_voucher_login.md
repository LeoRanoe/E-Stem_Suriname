# ✅ Task: Implement Voucher-Based Login

## 🧠 Context
The system needs to transition to a government-issued voucher login system where:
1. Voters receive pre-generated credentials
2. Login requires:
   - Unique code (manual entry or QR scan)
   - Government password
   - Optional user-defined second password
3. Voter table must contain complete identification:
   - Full name, national ID, date of birth, address
   - Contact information
   - Voting district/region

## 🔧 Actions
1. Modify voters table to add:
   - voter_id (national ID)
   - full_name
   - date_of_birth
   - address
   - contact_number
   - district
   - voucher_code (unique)
   - gov_password_hash
   - second_password_hash (nullable)
   - has_voted (boolean)
2. Create new login form at `src/views/voucher_login.php`
3. Implement login controller at `src/controllers/voucher_login.php`
4. Add QR code scanning support to login form
5. Implement 2-step verification flow

## 🗃️ Files to Modify/Create
- `database/e-stem_suriname.sql` (schema changes)
- `src/views/voucher_login.php` (new)
- `src/controllers/voucher_login.php` (new)
- Update `include/auth.php` for new auth logic
- `src/views/scan.php` (update for QR support)

## 📎 Testing
- Test login with all combinations:
  - Code + gov password
  - Code + gov password + second password
  - QR scan + passwords
- Verify invalid combinations are rejected
- Test voting restriction after login
- Confirm all voter info displays correctly

## ⚠️ Constraints
- Must maintain backward compatibility during transition
- QR codes must be scannable from mobile devices
- All voter info must be preserved from existing data