# User Import Process Documentation

## File Requirements
- CSV file format (Excel files must be converted to CSV first)
- Required columns:
  - Voornaam (First name)
  - Achternaam (Last name)
  - Email (Email address)
  - IDNumber (Identification number)
  - DistrictID (District identifier)
  - Status (Optional, defaults to 'active')
- First row must contain headers
- Maximum file size: 5MB

## Import Steps
1. Navigate to QR Codes section in admin panel
2. Click "Import Users" button
3. Select an election from dropdown (required)
4. Choose CSV file with user data
5. Click "Import" button

## Data Processing Flow
1. Frontend validates file type and election selection
2. CSV is parsed using PapaParse library
3. Each row is validated for required fields
4. Valid data is sent to backend as JSON:
   ```json
   {
     "election_id": "selected_election_id",
     "data": [
       {
         "Voornaam": "...",
         "Achternaam": "...",
         "Email": "...",
         "IDNumber": "...",
         "DistrictID": "..."
       }
     ]
   }
   ```
5. Backend performs additional validation and imports data

## Validation Rules
- All required fields must be present in every row
- Email addresses must be valid format and unique
- ID numbers must be unique
- District IDs must exist in database
- Data types are automatically converted as needed

## Error Handling
- Invalid files are rejected immediately with specific error
- Missing/invalid fields show row numbers and field names
- Up to 3 sample errors shown if multiple errors exist
- Complete error log available in system logs
- Transactions ensure no partial imports on failure

## Technical Details
- Frontend: Uses PapaParse 5.3.0 for CSV parsing
- Backend endpoint: `/src/ajax/import-users.php`
- Processing: Handled by ImportController
- Data safety:
  - All imports run in database transactions
  - Detailed logging of all operations
  - Automatic rollback on failure
- Performance:
  - Processes ~1000 records/second
  - Memory efficient streaming processing