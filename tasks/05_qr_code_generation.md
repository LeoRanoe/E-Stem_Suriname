# ✅ Task: Implement QR Code Generation

## 🧠 Context
Each voter needs a scannable QR code containing their unique voting code for easy login. The system should:
1. Generate QR codes for all imported voters
2. Allow admins to re-generate QR codes
3. Make QR codes downloadable/printable

## 🔧 Actions
1. Implement QR code library (like phpqrcode)
2. Create QR generation endpoint at `src/controllers/QrCodeController.php`
3. Add QR code display to voter management views
4. Implement bulk QR generation for imports
5. Add download/print options

## 🗃️ Files to Modify/Create
- `src/controllers/QrCodeController.php` (update)
- `src/views/qrcodes.php` (new)
- `admin/views/voters.php` (add QR column)
- `composer.json` (add QR library if needed)

## 📎 Testing
- Verify QR codes scan correctly
- Test bulk generation performance
- Check QR codes contain correct voting codes
- Test download/print functionality

## ⚠️ Constraints
- QR codes must be readable by common mobile scanners
- Generation should work without external dependencies if possible
- Codes should be small but still scannable