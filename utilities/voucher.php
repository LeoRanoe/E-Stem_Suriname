<?php

function generateVoucher($qrCode, $userName) {
    $voucherHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>E-Stem Suriname - Stem Voucher</title>
        <style>
            body {
                margin: 0;
                padding: 20px;
                font-family: Arial, sans-serif;
            }
            .voucher {
                width: 600px;
                height: 250px;
                border: 2px dashed #48BB78;
                border-radius: 15px;
                display: flex;
                overflow: hidden;
                background: white;
            }
            .voucher-left {
                flex: 1;
                padding: 20px;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .voucher-right {
                width: 250px;
                background: #48BB78;
                padding: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .title {
                font-size: 32px;
                font-weight: bold;
                margin: 0;
                color: #2D3748;
            }
            .subtitle {
                font-size: 24px;
                color: #48BB78;
                margin: 10px 0;
            }
            .code {
                font-size: 18px;
                color: #718096;
                margin: 10px 0;
            }
            .qr-code {
                width: 200px;
                height: 200px;
                background: white;
                padding: 10px;
                border-radius: 10px;
            }
        </style>
    </head>
    <body>
        <div class="voucher">
            <div class="voucher-left">
                <h1 class="title">E-STEM SURINAME</h1>
                <p class="subtitle">Stem Voucher</p>
                <p class="code">CODE: ' . htmlspecialchars(substr($qrCode, 0, 8)) . '</p>
                <p class="code">Voor: ' . htmlspecialchars($userName) . '</p>
            </div>
            <div class="voucher-right">
                <img class="qr-code" src="https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($qrCode) . '&size=200x200" alt="QR Code">
            </div>
        </div>
    </body>
    </html>';
    
    return $voucherHtml;
}