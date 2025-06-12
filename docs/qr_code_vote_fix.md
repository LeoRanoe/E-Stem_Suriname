# QR Code and Vote Submission Fix

## Issue Summary

The voting system was experiencing database errors during vote submission. Specifically, votes were failing to be recorded in the database with the error:

```
Error: Failed to record votes: SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails (`e-stem_suriname`.`votes`, CONSTRAINT `votes_ibfk_4` FOREIGN KEY (`QRCodeID`) REFERENCES `qrcodes` (`QRCodeID`))
```

This indicated that the system was trying to insert votes with a QRCodeID that didn't exist in the qrcodes table.

## Database Constraints

The `votes` table has a foreign key constraint that requires the `QRCodeID` field to reference a valid `QRCodeID` in the `qrcodes` table. This means:

1. A QR code must be created first
2. The QR code must be associated with a valid voter ID and election ID
3. Votes must use this valid QR code ID when being inserted

## Solution Implemented

### 1. QR Code Creation

Created a new method `getOrCreateQRCodeEntry` in the `VoterAuth` class that:
- Checks if a QR code already exists for the voter and election
- If not, creates a new QR code entry with the proper references
- Returns a valid QR code ID that can be used for votes

```php
public function getOrCreateQRCodeEntry($voterId, $electionId) {
    try {
        // First check if a QR code already exists for this voter and election
        $checkStmt = $this->db->prepare("
            SELECT QRCodeID FROM qrcodes 
            WHERE UserID = ? AND ElectionID = ?
            LIMIT 1
        ");
        $checkStmt->execute([$voterId, $electionId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $this->log("Using existing QR code for voter ID: $voterId, election: $electionId", 'info');
            return $existing['QRCodeID'];
        }
        
        // Create a new QR code
        $qrCode = md5(uniqid(mt_rand(), true));
        $stmt = $this->db->prepare("
            INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status, CreatedAt, UpdatedAt)
            VALUES (?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$voterId, $electionId, $qrCode]);
        
        $qrCodeId = $this->db->lastInsertId();
        $this->log("Created new QR code for voter ID: $voterId, election: $electionId, QR code ID: $qrCodeId", 'info');
        
        return $qrCodeId;
    } catch (PDOException $e) {
        $this->log("Error creating QR code: " . $e->getMessage(), 'error');
        throw new Exception("Failed to create QR code: " . $e->getMessage());
    }
}
```

### 2. Updated Vote Casting Process

Updated the `castVotes` method to use the QR code ID:

```php
public function castVotes($voterId, $dnaId, $rrId, $electionId) {
    try {
        // First ensure we have a valid QR code entry
        $qrCodeId = $this->getOrCreateQRCodeEntry($voterId, $electionId);
        
        if (!$qrCodeId) {
            throw new Exception("Could not create or retrieve QR code");
        }
        
        $this->db->beginTransaction();
        
        // Insert DNA vote using the QR code ID
        $stmtDNA = $this->db->prepare("
            INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtDNA->execute([$voterId, $dnaId, $electionId, $qrCodeId]);
        
        // Insert RR vote using the same QR code ID
        $stmtRR = $this->db->prepare("
            INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmtRR->execute([$voterId, $rrId, $electionId, $qrCodeId]);
        
        $this->db->commit();
        return true;
    } catch (PDOException $e) {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
        $this->log("Error casting votes: " . $e->getMessage(), 'error');
        throw new Exception("Failed to record votes: " . $e->getMessage());
    }
}
```

### 3. Updated Voting Page

Modified the voting page to properly handle QR code creation and vote submission:
- Added a direct QR code creation step
- Uses proper transaction handling
- Better error handling and debugging
- Ensures all required foreign key relationships are satisfied

## Testing

A test script (`test_vote.php`) was created to verify:
1. The correct voter, candidate, and election IDs exist
2. QR codes can be successfully created and retrieved
3. Votes can be successfully cast using the QR code

The test confirmed that the solution works properly and votes are now being recorded in the database.

## Additional Notes

The error was caused by a database schema constraint that required proper relationships between voters, QR codes, and votes. The fix ensures that these relationships are properly maintained.

If similar errors occur in the future, ensure that:
1. All database constraints are checked and satisfied
2. Foreign key relationships are properly maintained
3. Transactions are used to ensure data consistency
4. Error logging is comprehensive for easier debugging 