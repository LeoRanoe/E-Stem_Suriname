<?php
/**
 * Filter Component for QR Code Management
 * 
 * Handles search and filtering of QR codes
 * 
 * @package QRCodeManagement
 */

// Security check
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}
?>
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Filter QR Codes</h5>
    </div>
    <div class="card-body">
        <form id="qrFilterForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
            
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="searchQuery">Search</label>
                        <input type="text" class="form-control" id="searchQuery" name="search" placeholder="Search QR codes...">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="statusFilter">Status</label>
                        <select class="form-control" id="statusFilter" name="status">
                            <option value="">All Statuses</option>
                            <option value="active">Active</option>
                            <option value="used">Used</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dateFrom">Date From</label>
                        <input type="date" class="form-control" id="dateFrom" name="date_from">
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="dateTo">Date To</label>
                        <input type="date" class="form-control" id="dateTo" name="date_to">
                    </div>
                </div>
            </div>
            
            <div class="text-right">
                <button type="reset" class="btn btn-secondary mr-2">Reset</button>
                <button type="submit" class="btn btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>