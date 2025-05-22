<?php
/**
 * Statistics Component for QR Code Management
 * 
 * Handles stats cards and district breakdown
 * 
 * @package QRCodeManagement
 */

// Security check
if (!defined('BASE_PATH')) {
    exit('No direct script access allowed');
}
?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Total QR Codes</h5>
                <h2 class="card-text">1,250</h2>
                <p class="text-muted">All generated QR codes</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Active</h5>
                <h2 class="card-text">850</h2>
                <p class="text-muted">Currently active</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Used</h5>
                <h2 class="card-text">300</h2>
                <p class="text-muted">Redeemed</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Expired</h5>
                <h2 class="card-text">100</h2>
                <p class="text-muted">No longer valid</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5>District Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>District</th>
                                <th>Total</th>
                                <th>Active</th>
                                <th>Used</th>
                                <th>Expired</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Paramaribo</td>
                                <td>500</td>
                                <td>350</td>
                                <td>100</td>
                                <td>50</td>
                            </tr>
                            <tr>
                                <td>Wanica</td>
                                <td>300</td>
                                <td>200</td>
                                <td>80</td>
                                <td>20</td>
                            </tr>
                            <tr>
                                <td>Nickerie</td>
                                <td>200</td>
                                <td>150</td>
                                <td>40</td>
                                <td>10</td>
                            </tr>
                            <tr>
                                <td>Commewijne</td>
                                <td>150</td>
                                <td>100</td>
                                <td>40</td>
                                <td>10</td>
                            </tr>
                            <tr>
                                <td>Saramacca</td>
                                <td>100</td>
                                <td>50</td>
                                <td>40</td>
                                <td>10</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>