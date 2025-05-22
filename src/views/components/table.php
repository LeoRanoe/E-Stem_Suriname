<?php
/**
 * Table Component for QR Code Management
 * 
 * Handles QR codes data table
 * 
 * @package QRCodeManagement
 */

// Security check
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}
?>
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">QR Codes</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped" id="qrTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>QR Code</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Expires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>QR-123456</td>
                        <td><span class="badge badge-success">Active</span></td>
                        <td>2025-01-01</td>
                        <td>2025-12-31</td>
                        <td>
                            <button class="btn btn-sm btn-primary">View</button>
                            <button class="btn btn-sm btn-secondary">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>QR-123457</td>
                        <td><span class="badge badge-warning">Used</span></td>
                        <td>2025-01-02</td>
                        <td>2025-12-31</td>
                        <td>
                            <button class="btn btn-sm btn-primary">View</button>
                            <button class="btn btn-sm btn-secondary">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                    <tr>
                        <td>3</td>
                        <td>QR-123458</td>
                        <td><span class="badge badge-danger">Expired</span></td>
                        <td>2025-01-03</td>
                        <td>2025-12-31</td>
                        <td>
                            <button class="btn btn-sm btn-primary">View</button>
                            <button class="btn btn-sm btn-secondary">Edit</button>
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>