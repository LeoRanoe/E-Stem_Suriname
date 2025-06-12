<?php
class VoterAuth {
    private $db;
    private $debug = true;

    public function __construct($db) {
        $this->db = $db;
    }

    private function log($message, $type = 'info') {
        if ($this->debug) {
            error_log("[VoterAuth] [$type] $message");
        }
    }

    public function generateVoterCode() {
        // Generate a unique voter code (VOTER + 6 random digits)
        do {
            $code = 'VOTER' . str_pad(mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $this->db->prepare("SELECT id FROM voters WHERE voter_code = ?");
            $stmt->execute([$code]);
        } while ($stmt->rowCount() > 0);

        return $code;
    }

    public function generatePassword() {
        // Generate a random 8-character password
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < 8; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $password;
    }

    public function createVoter($data) {
        try {
            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'id_number', 'district_id', 'resort_id'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            // Generate voter code and password
            $voterCode = $this->generateVoterCode();
            $password = $this->generatePassword();
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert voter
            $stmt = $this->db->prepare("
                INSERT INTO voters (
                    voter_code, password, first_name, last_name, id_number,
                    district_id, resort_id, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");

            $stmt->execute([
                $voterCode,
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['id_number'],
                $data['district_id'],
                $data['resort_id']
            ]);

            $voterId = $this->db->lastInsertId();

            // Log the creation
            $this->logVoterCreation($voterId);

            return [
                'id' => $voterId,
                'voter_code' => $voterCode,
                'password' => $password,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name']
            ];
        } catch (PDOException $e) {
            error_log("Error creating voter: " . $e->getMessage());
            throw new Exception("Failed to create voter");
        }
    }

    public function verifyVoter($voterCode, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, password_hash, is_used, first_name, last_name 
                FROM voters 
                WHERE voter_code = ?
            ");
            $stmt->execute([$voterCode]);
            $voter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$voter) {
                $this->log("Voter not found: $voterCode", 'error');
                return false;
            }

            if ($voter['is_used']) {
                $this->log("Voter code already used: $voterCode", 'error');
                return false;
            }

            if (!password_verify($password, $voter['password_hash'])) {
                $this->log("Invalid password for voter: $voterCode", 'error');
                return false;
            }

            // Log successful login
            $this->logLoginAttempt($voter['id'], true, 'manual');
            
            // Mark voter code as used
            $this->markVoterCodeAsUsed($voter['id']);

            return [
                'voter_id' => $voter['id'],
                'first_name' => $voter['first_name'],
                'last_name' => $voter['last_name']
            ];
        } catch (PDOException $e) {
            $this->log("Error verifying voter: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    private function logLoginAttempt($voterId, $success, $attemptType) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO voter_logins (voter_id, status, attempt_type, ip_address)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $voterId,
                $success ? 'success' : 'failed',
                $attemptType,
                $_SERVER['REMOTE_ADDR'] ?? null
            ]);
        } catch (PDOException $e) {
            $this->log("Error logging login attempt: " . $e->getMessage(), 'error');
        }
    }

    private function markVoterCodeAsUsed($voterId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE voters 
                SET is_used = 1 
                WHERE id = ?
            ");
            $stmt->execute([$voterId]);
        } catch (PDOException $e) {
            $this->log("Error marking voter code as used: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function createSession($voterId) {
        try {
            $sessionId = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $this->db->prepare("
                INSERT INTO voter_sessions (voter_id, session_id, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$voterId, $sessionId, $expiresAt]);

            return $sessionId;
        } catch (PDOException $e) {
            $this->log("Error creating session: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function validateSession($sessionId) {
        try {
            $stmt = $this->db->prepare("
                SELECT vs.*, v.first_name, v.last_name
                FROM voter_sessions vs
                JOIN voters v ON v.id = vs.voter_id
                WHERE vs.session_id = ? 
                AND vs.is_active = 1 
                AND vs.expires_at > NOW()
            ");
            $stmt->execute([$sessionId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Error validating session: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    public function invalidateSession($sessionId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE voter_sessions 
                SET is_active = 0 
                WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            $this->log("Error invalidating session: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Get voters with pagination and filters
     */
    public function getVoters($page = 1, $perPage = 10, $search = '', $districtId = null, $resortId = null, $status = null) {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = [];

        if ($search) {
            $where[] = "(v.first_name LIKE ? OR v.last_name LIKE ? OR v.id_number LIKE ? OR v.voter_code LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($districtId) {
            $where[] = "v.district_id = ?";
            $params[] = $districtId;
        }

        if ($resortId) {
            $where[] = "v.resort_id = ?";
            $params[] = $resortId;
        }

        if ($status) {
            $where[] = "v.status = ?";
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT v.*, d.name as district_name, r.name as resort_name 
                FROM voters v 
                LEFT JOIN districts d ON v.district_id = d.id 
                LEFT JOIN resorts r ON v.resort_id = r.id 
                $whereClause 
                ORDER BY v.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting voters: " . $e->getMessage());
            throw new Exception("Failed to retrieve voters");
        }
    }

    /**
     * Get total number of voters with filters
     */
    public function getTotalVoters($search = '', $districtId = null, $resortId = null, $status = null) {
        $params = [];
        $where = [];

        if ($search) {
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR id_number LIKE ? OR voter_code LIKE ?)";
            $searchParam = "%$search%";
            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
        }

        if ($districtId) {
            $where[] = "district_id = ?";
            $params[] = $districtId;
        }

        if ($resortId) {
            $where[] = "resort_id = ?";
            $params[] = $resortId;
        }

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total FROM voters $whereClause";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['total'];
        } catch (PDOException $e) {
            error_log("Error getting total voters: " . $e->getMessage());
            throw new Exception("Failed to retrieve total voters count");
        }
    }

    /**
     * Get all districts
     */
    public function getDistricts() {
        try {
            $stmt = $this->db->query("SELECT * FROM districts ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting districts: " . $e->getMessage());
            throw new Exception("Failed to retrieve districts");
        }
    }

    /**
     * Get all resorts
     */
    public function getResorts() {
        try {
            $stmt = $this->db->query("SELECT * FROM resorts ORDER BY name");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting resorts: " . $e->getMessage());
            throw new Exception("Failed to retrieve resorts");
        }
    }

    /**
     * Update voter status
     */
    public function updateVoterStatus($voterId, $status) {
        if (!in_array($status, ['active', 'inactive'])) {
            throw new Exception("Invalid status");
        }

        try {
            $stmt = $this->db->prepare("UPDATE voters SET status = ? WHERE id = ?");
            $stmt->execute([$status, $voterId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Voter not found");
            }

            return true;
        } catch (PDOException $e) {
            error_log("Error updating voter status: " . $e->getMessage());
            throw new Exception("Failed to update voter status");
        }
    }

    /**
     * Reset voter password
     */
    public function resetVoterPassword($voterId) {
        try {
            // Generate new password
            $newPassword = $this->generatePassword();

            // Hash the new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            // Update the password
            $stmt = $this->db->prepare("UPDATE voters SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $voterId]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Voter not found");
            }

            // Log the password reset
            $this->logPasswordReset($voterId);

            return $newPassword;
        } catch (PDOException $e) {
            error_log("Error resetting voter password: " . $e->getMessage());
            throw new Exception("Failed to reset voter password");
        }
    }

    /**
     * Log password reset
     */
    private function logPasswordReset($voterId) {
        try {
            $stmt = $this->db->prepare("INSERT INTO voter_logs (voter_id, action, ip_address) VALUES (?, 'password_reset', ?)");
            $stmt->execute([$voterId, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } catch (PDOException $e) {
            error_log("Error logging password reset: " . $e->getMessage());
            // Don't throw exception as this is not critical
        }
    }

    /**
     * Log voter creation
     */
    private function logVoterCreation($voterId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO voter_logs (voter_id, action, created_at)
                VALUES (?, 'created', NOW())
            ");
            $stmt->execute([$voterId]);
        } catch (PDOException $e) {
            $this->log("Error logging voter creation: " . $e->getMessage(), 'error');
        }
    }

    /**
     * Verify voucher credentials
     * 
     * @param string $voucherId The voucher ID to verify
     * @param string $password The password to verify
     * @return array|false Voter data if credentials are valid, false otherwise
     */
    public function verifyVoucher($voucherId, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT v.*, vt.id, vt.first_name, vt.last_name, vt.district_id, vt.resort_id 
                FROM vouchers v
                JOIN voters vt ON v.voter_id = vt.id
                WHERE v.voucher_id = ?
            ");
            $stmt->execute([$voucherId]);
            $voucher = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voucher) {
                $this->log("Voucher not found: $voucherId", 'error');
                return false;
            }
            
            // Verify password
            if (!password_verify($password, $voucher['password'])) {
                $this->log("Invalid password for voucher: $voucherId", 'error');
                return false;
            }
            
            // Check if voter has already voted
            if ($this->hasVoted($voucher['id'])) {
                $this->log("Voter ID {$voucher['id']} has already voted in this election", 'info');
                return false;
            }

            // Log successful login
            $this->logLoginAttempt($voucher['voter_id'], true, 'manual');
            
            return $voucher;
        } catch (PDOException $e) {
            $this->log("Error verifying voucher: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Verify QR code data (voucher ID)
     * 
     * @param string $qrData QR code data
     * @return array|false Voter data if verified, false otherwise
     */
    public function verifyQRCode($qrData) {
        try {
            // Check if voucher exists - NOTE: we don't check "used" status anymore
            // as voters can have vouchers but not have voted yet
            $stmt = $this->db->prepare("
                SELECT v.*, vt.id, vt.first_name, vt.last_name, vt.district_id, vt.resort_id 
                FROM vouchers v
                JOIN voters vt ON v.voter_id = vt.id
                WHERE v.voucher_id = ?
            ");
            $stmt->execute([$qrData]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Before returning the result, check if the voter has already voted
                // by looking for actual votes in the database
                if ($this->hasVoted($result['id'])) {
                    $this->log("Voter ID {$result['id']} has already voted in this election", 'info');
                    return false;
                }
                
                // Log successful login
                $this->logLoginAttempt($result['voter_id'], true, 'qr_scan');
                return $result;
            }
            
            return false;
        } catch (PDOException $e) {
            $this->log("Database error in verifyQRCode: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Mark a voucher as used after successful login
     * 
     * @param string $voucherId Voucher ID
     * @return bool Success status
     */
    public function markVoucherAsUsed($voucherId) {
        try {
            $stmt = $this->db->prepare("UPDATE vouchers SET used = 1 WHERE voucher_id = ?");
            return $stmt->execute([$voucherId]);
        } catch (PDOException $e) {
            $this->log("Error marking voucher as used: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Check if a voter has already voted in the current election
     * 
     * @param int $voterId The voter ID to check
     * @param int $electionId Optional election ID, defaults to current active election
     * @return bool True if voter has already voted, false otherwise
     */
    public function hasVoted($voterId, $electionId = null) {
        try {
            // If no election ID provided, get the current active election
            if (!$electionId) {
                $stmt = $this->db->query("SELECT ElectionID FROM elections WHERE Status = 'active' LIMIT 1");
                $election = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$election) {
                    $this->log("No active election found when checking if voter has voted", 'error');
                    return false;
                }
                $electionId = $election['ElectionID'];
            }
            
            // Check if voter has already cast a vote in this election
            // Updated column names to match the actual database schema
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM votes 
                WHERE UserID = ? AND ElectionID = ?
            ");
            $stmt->execute([$voterId, $electionId]);
            
            $voteCount = $stmt->fetchColumn();
            $this->log("Vote count for voter ID $voterId in election $electionId: $voteCount", 'info');
            
            return ($voteCount > 0);
        } catch (PDOException $e) {
            $this->log("Error checking if voter has voted: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Create a voting session to track the voter's active session
     * 
     * @param int $voterId Voter ID
     * @param int $electionId Election ID
     * @return int|false Session ID if created, false on failure
     */
    public function createVotingSession($voterId, $electionId) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO voting_sessions (UserID, StartTime, Status, CreatedAt, UpdatedAt)
                VALUES (?, NOW(), 'active', NOW(), NOW())
            ");
            $stmt->execute([$voterId]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->log("Error creating voting session: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Complete a voting session after vote is cast
     * 
     * @param int $sessionId Voting session ID
     * @return bool Success status
     */
    public function completeVotingSession($sessionId) {
        try {
            $stmt = $this->db->prepare("
                UPDATE voting_sessions
                SET EndTime = NOW(), Status = 'completed', UpdatedAt = NOW()
                WHERE SessionID = ?
            ");
            return $stmt->execute([$sessionId]);
        } catch (PDOException $e) {
            $this->log("Error completing voting session: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get or create a QR code entry for a voter and election
     * 
     * @param int $voterId Voter ID
     * @param int $electionId Election ID
     * @return int QRCodeID
     */
    public function getOrCreateQRCodeEntry($voterId, $electionId) {
        try {
            // Validate inputs
            if (empty($voterId) || empty($electionId)) {
                $this->log("Error: Missing voter ID or election ID for QR code creation", 'error');
                throw new Exception("Missing voter ID or election ID");
            }
            
            // First check if voter and election exist
            $voterStmt = $this->db->prepare("SELECT id FROM voters WHERE id = ?");
            $voterStmt->execute([$voterId]);
            if ($voterStmt->rowCount() === 0) {
                $this->log("Error: Voter ID $voterId does not exist", 'error');
                throw new Exception("Voter ID does not exist");
            }
            
            $electionStmt = $this->db->prepare("SELECT ElectionID FROM elections WHERE ElectionID = ?");
            $electionStmt->execute([$electionId]);
            if ($electionStmt->rowCount() === 0) {
                $this->log("Error: Election ID $electionId does not exist", 'error');
                throw new Exception("Election ID does not exist");
            }
            
            // Check if a QR code already exists for this voter and election
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
            
            // If we're here, we need to create a new QR code
            // Start a transaction
            if ($this->db->inTransaction()) {
                // If we're already in a transaction, we'll use it
                $this->log("Using existing transaction for QR code creation", 'info');
                $ownTransaction = false;
            } else {
                $this->db->beginTransaction();
                $ownTransaction = true;
            }
            
            try {
                // Generate a unique QR code
                $qrCode = md5(uniqid(mt_rand(), true));
                
                // Insert the QR code
                $stmt = $this->db->prepare("
                    INSERT INTO qrcodes (UserID, ElectionID, QRCode, Status, CreatedAt, UpdatedAt)
                    VALUES (?, ?, ?, 'active', NOW(), NOW())
                ");
                $stmt->execute([$voterId, $electionId, $qrCode]);
                
                // Get the ID of the inserted QR code
                $qrCodeId = $this->db->lastInsertId();
                
                // Verify the QR code was created
                if (!$qrCodeId) {
                    throw new Exception("Failed to create QR code: No ID returned");
                }
                
                // Double-check the QR code exists
                $verifyStmt = $this->db->prepare("SELECT QRCodeID FROM qrcodes WHERE QRCodeID = ?");
                $verifyStmt->execute([$qrCodeId]);
                if ($verifyStmt->rowCount() === 0) {
                    throw new Exception("QR code verification failed: QR code ID not found after insertion");
                }
                
                // If this is our own transaction, commit it
                if ($ownTransaction) {
                    $this->db->commit();
                }
                
                $this->log("Successfully created QR code ID: $qrCodeId for voter: $voterId, election: $electionId", 'info');
                return $qrCodeId;
            } catch (Exception $e) {
                // Only rollback if this is our own transaction
                if ($ownTransaction && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                $this->log("Error creating QR code: " . $e->getMessage(), 'error');
                throw $e;
            }
        } catch (Exception $e) {
            $this->log("Error in getOrCreateQRCodeEntry: " . $e->getMessage(), 'error');
            throw $e;
        }
    }
    
    /**
     * Cast votes for a voter
     * 
     * @param int $voterId Voter ID
     * @param int $dnaId DNA candidate ID
     * @param int $rrId RR candidate ID  
     * @param int $electionId Election ID
     * @return bool Success status
     */
    public function castVotes($voterId, $dnaId, $rrId, $electionId) {
        try {
            // First validate that the voter can vote for these candidates (district/resort check)
            $this->validateCandidateSelection($voterId, $dnaId, $rrId);
            
            // First create the QR code in its own transaction to ensure it exists
            $qrCodeId = $this->getOrCreateQRCodeEntry($voterId, $electionId);
            
            if (!$qrCodeId) {
                throw new Exception("Could not create or retrieve QR code");
            }
            
            // Double-check that the QR code exists in the database
            $qrCheckStmt = $this->db->prepare("SELECT QRCodeID FROM qrcodes WHERE QRCodeID = ?");
            $qrCheckStmt->execute([$qrCodeId]);
            if ($qrCheckStmt->rowCount() === 0) {
                throw new Exception("QR code ID $qrCodeId not found in database");
            }
            
            // Now start a new transaction for the votes
            $this->db->beginTransaction();
            
            try {
                // Insert DNA vote using the QR code ID
                $stmtDNA = $this->db->prepare("
                    INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtDNA->execute([$voterId, $dnaId, $electionId, $qrCodeId]);
                
                $this->log("Inserted DNA vote for voter ID: $voterId, candidate: $dnaId", 'info');
                
                // Insert RR vote using the same QR code ID
                $stmtRR = $this->db->prepare("
                    INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmtRR->execute([$voterId, $rrId, $electionId, $qrCodeId]);
                
                $this->log("Inserted RR vote for voter ID: $voterId, candidate: $rrId", 'info');
                
                $this->db->commit();
                $this->log("Transaction committed successfully", 'info');
                return true;
            } catch (Exception $e) {
                if ($this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                throw $e; // Re-throw to be caught by outer catch
            }
        } catch (Exception $e) {
            $this->log("Error casting votes: " . $e->getMessage(), 'error');
            throw new Exception("Failed to record votes: " . $e->getMessage());
        }
    }
    
    /**
     * Validate that a voter can vote for the selected candidates based on district/resort
     * 
     * @param int $voterId Voter ID
     * @param int $dnaId DNA candidate ID
     * @param int $rrId RR candidate ID
     * @throws Exception If validation fails
     */
    public function validateCandidateSelection($voterId, $dnaId, $rrId) {
        try {
            // Get voter's district and resort
            $voterStmt = $this->db->prepare("
                SELECT district_id, resort_id 
                FROM voters 
                WHERE id = ?
            ");
            $voterStmt->execute([$voterId]);
            $voter = $voterStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$voter) {
                throw new Exception("Voter not found");
            }
            
            $voterDistrictId = $voter['district_id'];
            $voterResortId = $voter['resort_id'];
            
            // Validate DNA candidate is in voter's district
            $dnaStmt = $this->db->prepare("
                SELECT c.CandidateID, c.DistrictID 
                FROM candidates c
                WHERE c.CandidateID = ? AND c.CandidateType = 'DNA'
            ");
            $dnaStmt->execute([$dnaId]);
            $dnaCandidate = $dnaStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$dnaCandidate) {
                throw new Exception("DNA candidate not found");
            }
            
            if ($dnaCandidate['DistrictID'] != $voterDistrictId) {
                throw new Exception("You can only vote for DNA candidates in your district");
            }
            
            // Validate RR candidate is in voter's resort
            $rrStmt = $this->db->prepare("
                SELECT c.CandidateID, c.ResortID 
                FROM candidates c
                WHERE c.CandidateID = ? AND c.CandidateType = 'RR'
            ");
            $rrStmt->execute([$rrId]);
            $rrCandidate = $rrStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$rrCandidate) {
                throw new Exception("RR candidate not found");
            }
            
            if ($rrCandidate['ResortID'] != $voterResortId) {
                throw new Exception("You can only vote for RR candidates in your resort");
            }
            
            return true;
        } catch (PDOException $e) {
            $this->log("Error validating candidate selection: " . $e->getMessage(), 'error');
            throw new Exception("Failed to validate candidate selection: " . $e->getMessage());
        }
    }
} 