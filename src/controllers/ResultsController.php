<?php

require_once __DIR__ . '/../../include/db_connect.php';
require_once __DIR__ . '/../models/Vote.php';

class ResultsController {
    private $voteModel;
    private $pdo;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->voteModel = new Vote($pdo);
    }
    
    /**
     * Get active elections
     * 
     * @return array List of active elections
     */
    public function getActiveElections() {
        return $this->voteModel->getActiveElections();
    }
    
    /**
     * Get vote results by election
     * 
     * @param int $electionId Election ID
     * @return array Vote results data
     */
    public function getResultsByElection($electionId) {
        $results = [
            'votes_by_candidate' => $this->voteModel->getVotesByElection($electionId),
            'votes_by_party' => $this->voteModel->getVotesByParty($electionId),
            'votes_by_district' => $this->voteModel->getVotesByDistrict($electionId),
            'total_votes' => $this->voteModel->getTotalVotes($electionId)
        ];
        
        return $results;
    }
    
    /**
     * Get vote data for charts
     * 
     * @param int $electionId Election ID
     * @return array Chart data
     */
    public function getChartData($electionId) {
        $votesByParty = $this->voteModel->getVotesByParty($electionId);
        $votesByDistrict = $this->voteModel->getVotesByDistrict($electionId);
        $totalVotes = $this->voteModel->getTotalVotes($electionId);
        
        // Format data for charts
        $partyLabels = [];
        $partyData = [];
        $partyColors = [];
        
        foreach ($votesByParty as $party) {
            $partyLabels[] = $party['PartyName'];
            $partyData[] = $party['vote_count'];
            // Generate a random color for each party
            $partyColors[] = 'rgb(' . rand(0, 200) . ',' . rand(0, 200) . ',' . rand(0, 200) . ')';
        }
        
        $districtLabels = [];
        $districtData = [];
        
        foreach ($votesByDistrict as $district) {
            $districtLabels[] = $district['DistrictName'];
            $districtData[] = $district['vote_count'];
        }
        
        return [
            'party' => [
                'labels' => $partyLabels,
                'data' => $partyData,
                'colors' => $partyColors
            ],
            'district' => [
                'labels' => $districtLabels,
                'data' => $districtData
            ],
            'total_votes' => $totalVotes
        ];
    }
    
    /**
     * Check if results should be displayed
     * 
     * @param int $electionId Optional election ID (defaults to current election)
     * @return bool True if results should be displayed, false otherwise
     */
    public function shouldDisplayResults($electionId = null) {
        try {
            // If no election ID is provided, get the current active election
            if ($electionId === null) {
                $stmt = $this->pdo->query("SELECT id FROM elections WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $electionId = $stmt->fetchColumn();
                
                // If no active election found, try to get the most recent completed election
                if (!$electionId) {
                    $stmt = $this->pdo->query("SELECT id FROM elections ORDER BY end_date DESC LIMIT 1");
                    $electionId = $stmt->fetchColumn();
                }
                
                // If still no election found, return false
                if (!$electionId) {
                    return false;
                }
            }
            
            // Check if the election is completed or if results are explicitly allowed to be shown
            $stmt = $this->pdo->prepare("
                SELECT 
                    CASE 
                        WHEN status = 'completed' THEN 1
                        WHEN end_date < NOW() THEN 1
                        WHEN show_results = 1 THEN 1
                        ELSE 0
                    END as show_results
                FROM elections 
                WHERE id = ?
            ");
            $stmt->execute([$electionId]);
            return (bool)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in shouldDisplayResults: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if results should be shown (alias for shouldDisplayResults)
     * 
     * @return bool True if results should be shown, false otherwise
     */
    public function shouldShowResults() {
        return $this->shouldDisplayResults();
    }
    
    /**
     * Get election results
     * 
     * @param int $electionId Optional election ID (defaults to current election)
     * @return array Election results data
     */
    public function getElectionResults($electionId = null) {
        try {
            // If no election ID is provided, get the current active election
            if ($electionId === null) {
                $stmt = $this->pdo->query("SELECT id FROM elections WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $electionId = $stmt->fetchColumn();
                
                // If no active election found, try to get the most recent completed election
                if (!$electionId) {
                    $stmt = $this->pdo->query("SELECT id FROM elections ORDER BY end_date DESC LIMIT 1");
                    $electionId = $stmt->fetchColumn();
                }
                
                // If still no election found, return empty array
                if (!$electionId) {
                    return [];
                }
            }
            
            // Get candidate results
            $stmt = $this->pdo->prepare("
                SELECT 
                    c.id,
                    c.name as candidate_name,
                    p.id as party_id,
                    p.name as party_name,
                    p.logo as party_logo,
                    COUNT(v.id) as vote_count
                FROM candidates c
                JOIN parties p ON c.party_id = p.id
                LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = ?
                WHERE c.election_id = ?
                GROUP BY c.id
                ORDER BY vote_count DESC, c.name ASC
            ");
            $stmt->execute([$electionId, $electionId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database error in getElectionResults: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get total votes for an election
     * 
     * @param int $electionId Optional election ID (defaults to current election)
     * @return int Total number of votes
     */
    public function getTotalVotes($electionId = null) {
        try {
            // If no election ID is provided, get the current active election
            if ($electionId === null) {
                $stmt = $this->pdo->query("SELECT id FROM elections WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $electionId = $stmt->fetchColumn();
                
                // If no active election found, try to get the most recent completed election
                if (!$electionId) {
                    $stmt = $this->pdo->query("SELECT id FROM elections ORDER BY end_date DESC LIMIT 1");
                    $electionId = $stmt->fetchColumn();
                }
                
                // If still no election found, return 0
                if (!$electionId) {
                    return 0;
                }
            }
            
            // Count total votes
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM votes WHERE election_id = ?");
            $stmt->execute([$electionId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalVotes: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get total number of voters
     * 
     * @return int Total number of voters
     */
    public function getTotalVoters() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM voters WHERE status = 'active'");
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Database error in getTotalVoters: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get election status
     * 
     * @param int $electionId Optional election ID (defaults to current election)
     * @return string Election status (active, completed, upcoming)
     */
    public function getElectionStatus($electionId = null) {
        try {
            // If no election ID is provided, get the current active election
            if ($electionId === null) {
                $stmt = $this->pdo->query("SELECT id FROM elections WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $electionId = $stmt->fetchColumn();
                
                // If no active election found, try to get the most recent completed election
                if (!$electionId) {
                    $stmt = $this->pdo->query("SELECT id FROM elections ORDER BY end_date DESC LIMIT 1");
                    $electionId = $stmt->fetchColumn();
                }
                
                // If still no election found, return 'unknown'
                if (!$electionId) {
                    return 'unknown';
                }
            }
            
            // Get election status
            $stmt = $this->pdo->prepare("SELECT status FROM elections WHERE id = ?");
            $stmt->execute([$electionId]);
            return $stmt->fetchColumn() ?: 'unknown';
        } catch (PDOException $e) {
            error_log("Database error in getElectionStatus: " . $e->getMessage());
            return 'unknown';
        }
    }
    
    /**
     * Update results visibility
     * 
     * @param int $showResults 1 to show results, 0 to hide
     * @param int $electionId Optional election ID (defaults to current election)
     * @return bool Success status
     */
    public function updateResultsVisibility($showResults, $electionId = null) {
        try {
            // If no election ID is provided, get the current active election
            if ($electionId === null) {
                $stmt = $this->pdo->query("SELECT id FROM elections WHERE status = 'active' ORDER BY id DESC LIMIT 1");
                $electionId = $stmt->fetchColumn();
                
                // If no active election found, try to get the most recent completed election
                if (!$electionId) {
                    $stmt = $this->pdo->query("SELECT id FROM elections ORDER BY end_date DESC LIMIT 1");
                    $electionId = $stmt->fetchColumn();
                }
                
                // If still no election found, return false
                if (!$electionId) {
                    return false;
                }
            }
            
            // Update show_results flag
            $stmt = $this->pdo->prepare("UPDATE elections SET show_results = ? WHERE id = ?");
            return $stmt->execute([$showResults, $electionId]);
        } catch (PDOException $e) {
            error_log("Database error in updateResultsVisibility: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Export results to CSV
     * 
     * @param int $electionId Election ID
     * @return string CSV content
     */
    public function exportResultsToCSV($electionId) {
        $results = $this->getResultsByElection($electionId);
        
        // Create CSV content
        $csv = "Candidate,Party,District,Votes,Percentage\n";
        
        foreach ($results['votes_by_candidate'] as $candidate) {
            $percentage = ($results['total_votes'] > 0) ? 
                round(($candidate['vote_count'] / $results['total_votes']) * 100, 2) : 0;
            
            $csv .= "\"{$candidate['candidate_name']}\",";
            $csv .= "\"{$candidate['PartyName']}\",";
            $csv .= "\"{$candidate['DistrictName']}\",";
            $csv .= "{$candidate['vote_count']},";
            $csv .= "{$percentage}%\n";
        }
        
        return $csv;
    }
}
