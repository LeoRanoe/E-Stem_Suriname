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
                $stmt = $this->db->query("SELECT election_id FROM elections WHERE status = 'active' LIMIT 1");
                $election = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$election) {
                    $this->log("No active election found when checking if voter has voted", 'error');
                    return false;
                }
                $electionId = $election['election_id'];
            }
            
            // Check if voter has already cast a vote in this election
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM votes 
                WHERE user_id = ? AND election_id = ?
            ");
            $stmt->execute([$voterId, $electionId]);
            
            return ($stmt->fetchColumn() > 0);
        } catch (PDOException $e) {
            $this->log("Error checking if voter has voted: " . $e->getMessage(), 'error');
            return false;
        }
    }
} 