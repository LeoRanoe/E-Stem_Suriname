# CSV Import Test Cases

## Test Files
1. `valid.csv` - Correct format with all required fields
2. `missing_fields.csv` - Missing required fields in some rows
3. `malformed.csv` - Invalid data formats (emails, IDs)
4. `wrong_headers.csv` - Incorrect column headers
5. `medium.csv` - Medium-sized valid file (15 rows)

## Expected Results

### valid.csv
- Should import all rows successfully
- Should show success message
- Should create records in database
- Logs should show successful import

### missing_fields.csv
- Should reject import with error
- Should identify missing fields by row
- Should show sample errors (first 3)
- Should not create any records
- Logs should show validation errors

### malformed.csv
- Should reject import with error
- Should identify invalid fields by row
- Should show sample errors (first 3)
- Should not create any records
- Logs should show validation errors

### wrong_headers.csv
- Should reject import immediately
- Should show header validation error
- Should not attempt data processing
- Logs should show header mismatch

### medium.csv
- Should import all rows successfully
- Should show success message
- Should handle medium file size properly
- Logs should show successful import