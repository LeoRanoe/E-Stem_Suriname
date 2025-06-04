<?php

class Vote {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Cast a vote
     * 
     * @param int $voterId Voter ID
     * @param int $candidateId Candidate ID
     * @param int $electionId Election ID
     * @param int $voucherId Voucher ID
     * @return bool Success status
     */
    public function castVote($voterId, $candidateId, $electionId, $voucherId) {
        try {
            // Check if voter has already voted in this election
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM votes 
                WHERE UserID = ? AND ElectionID = ?
            ");
            $stmt->execute([$voterId, $electionId]);
            
            if ($stmt->fetchColumn() > 0) {
                return false; // Voter has already voted
            }
            
            // Insert vote
            $stmt = $this->pdo->prepare("
                INSERT INTO votes (UserID, CandidateID, ElectionID, QRCodeID, TimeStamp)
                VALUES (?, ?, ?, ?, NOW())
            ");
            
            $result = $stmt->execute([$voterId, $candidateId, $electionId, $voucherId]);
            
            if ($result) {
                // Mark voucher as used
                $stmt = $this->pdo->prepare("
                    UPDATE vouchers SET used = 1 WHERE id = ?
                ");
                $stmt->execute([$voucherId]);
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Database error in castVote: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get votes by election
     * 
     * @param int $electionId Election ID
     * @return array Vote data
     */
    public function getVotesByElection($electionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT v.VoteID, v.TimeStamp,
                       c.Name as candidate_name, c.CandidateType,
                       p.PartyName, p.Logo as party_logo,
                       d.DistrictName,
                       COUNT(v.VoteID) as vote_count
                FROM votes v
                JOIN candidates c ON v.CandidateID = c.CandidateID
                JOIN parties p ON c.PartyID = p.PartyID
                JOIN districten d ON c.DistrictID = d.DistrictID
                WHERE v.ElectionID = ?
                GROUP BY c.CandidateID
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVotesByElection: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get vote results by party
     * 
     * @param int $electionId Election ID
     * @return array Vote data grouped by party
     */
    public function getVotesByParty($electionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.PartyID, p.PartyName, p.Logo as party_logo,
                       COUNT(v.VoteID) as vote_count
                FROM votes v
                JOIN candidates c ON v.CandidateID = c.CandidateID
                JOIN parties p ON c.PartyID = p.PartyID
                WHERE v.ElectionID = ?
                GROUP BY p.PartyID
                ORDER BY vote_count DESC
            ");
            $stmt->execute([$electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVotesByParty: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get votes by district
     * 
     * @param int $electionId Election ID
     * @return array Votes by district
     */
    public function getVotesByDistrict($electionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT d.DistrictID, d.DistrictName, COUNT(*) as vote_count
                FROM votes v
                JOIN candidates c ON v.CandidateID = c.CandidateID
                JOIN districten d ON c.DistrictID = d.DistrictID
                WHERE v.ElectionID = ?
                GROUP BY d.DistrictID, d.DistrictName
                ORDER BY vote_count DESC
            ");
            
            $stmt->execute([$electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getVotesByDistrict: " . $e->getMessage());
            return [];
        }
    }
    
    public function getTotalVotes($electionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM votes WHERE ElectionID = ?
            ");
            $stmt->execute([$electionId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalVotes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get active elections
     * 
     * @return array List of active elections
     */
    public function getActiveElections() {
        try {
            $stmt = $this->pdo->query("
                SELECT * FROM elections 
                WHERE ElectionDate <= CURDATE() 
                AND EndDate >= CURDATE() 
                AND Status = 'active' 
                ORDER BY ElectionDate DESC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getActiveElections: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get candidates by election
     * 
     * @param int $electionId Election ID
     * @return array List of candidates
     */
    public function getCandidatesByElection($electionId) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT c.*, p.PartyName, p.Logo as party_logo, d.DistrictName
                FROM candidates c
                JOIN parties p ON c.PartyID = p.PartyID
                JOIN districten d ON c.DistrictID = d.DistrictID
                WHERE c.ElectionID = ?
                ORDER BY c.Name ASC
            ");
            $stmt->execute([$electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getCandidatesByElection: " . $e->getMessage());
            return [];
        }
    }

}
