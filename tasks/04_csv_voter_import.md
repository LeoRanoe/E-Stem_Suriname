# ✅ Task: Implement CSV Voter Import

## 🧠 Context
Admins need to import complete voter data from CSV files containing:
- Full identification: Full Name, National ID, Date of Birth, Address
- Contact information: Phone, Email
- Voting district/region
- Voucher credentials: Unique Code, Government Password
- Optional Second Password

## 🔧 Actions
1. Create CSV import form at `admin/import_voters.php` with fields mapping
2. Implement import controller at `admin/controllers/ImportController.php`
3. Add CSV validation for:
   - Required identification fields
   - Data formats (dates, IDs)
   - District/region validation
   - Duplicate checks
4. Implement bulk insert to voters table with all fields
5. Add error handling and import summary reporting

## 🗃️ Files to Modify/Create
- `admin/import_voters.php` (new)
- `admin/controllers/ImportController.php` (update)
- `src/controllers/VoterController.php` (add import methods)
- `database/e-stem_suriname.sql` (ensure complete schema)
- `config/valid_districts.php` (new)

## 📎 Testing
- Test with complete voter data CSV
- Verify all fields import correctly
- Check error handling for:
  - Missing required fields
  - Invalid formats
  - Duplicate entries
- Test large imports (1000+ records)

## ⚠️ Constraints
- Must handle special characters in names/addresses
- Support multiple CSV formats (Excel, LibreOffice)
- Preserve data relationships
- Maintain referential integrity