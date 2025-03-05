<?php
session_start();
require 'db.php'; // Adjusted path to db.php
include './include/nav.php'; 
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Verkiezing</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f9f9f9;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .bg-custom-green {
            background: linear-gradient(135deg, #A8D8AA, #C7E3C5);
        }
        .btn-custom-green {
            background-color: #4A774C;
            border: none;
            color: white;
            transition: background-color 0.3s ease;
        }
        .btn-custom-green:hover {
            background-color: #3F6640;
        }
        .card-custom {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        .text-dark-green {
            color: #4A774C;
        }
        .section-title {
            font-weight: bold;
            letter-spacing: 1px;
        }
        .rounded-lg {
            border-radius: 15px !important;
        }
        .custom-select {
            border-radius: 15px !important;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            transition: border-color 0.3s ease;
        }
        .custom-select:focus {
            border-color: #4A774C;
            box-shadow: 0 0 5px rgba(74, 119, 76, 0.5);
        }
        .lead {
            font-size: 1.2rem;
            line-height: 1.6;
        }
        .display-4 {
            font-size: 2.5rem;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .display-4 {
                font-size: 2rem;
            }
            .lead {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <section class="bg-custom-green rounded-lg p-5 mb-4">
            <div class="row align-items-center">
                <div class="col-md-8 pr-md-5">
                    <h1 class="display-4 section-title mb-3">Lorem ipsum dolor sit amet, consectetur adipiscing elit.</h1>
                    <p class="lead mb-4">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam,</p>
                    <button class="btn btn-light rounded-lg">Lees meer ></button>
                </div>
                <div class="col-md-4 text-center">
                    <img src="https://storage.googleapis.com/a1aa/image/vVsMMt_gmicg9Lg3_jpO9j6unKbdNnfFqCrSbb48Rjc.jpg" alt="Voting illustration" class="img-fluid rounded-lg">
                </div>
            </div>
        </section>

        <section class="text-center mb-4">
            <h2 class="display-4 section-title mb-3">Hoe moet je stemmen?</h2>
            <p class="lead mb-5">Doe mee aan de online stemsimulatie en ontdek hoe eenvoudig het is om je stem uit te brengen. Volg onze simpele stappen om ervoor te zorgen dat je stem gehoord wordt bij de komende verkiezingen.</p>
            
            <div class="row justify-content-center">
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4 h-100">
                        <i class="fas fa-ticket-alt fa-3x text-dark-green mb-3"></i>
                        <h3 class="mb-3">Step 1: Voucher ophalen</h3>
                        <p>Haal uw voucher op bij CBB met uw identiteitskaart.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4 h-100">
                        <i class="fas fa-qrcode fa-3x text-dark-green mb-3"></i>
                        <h3 class="mb-3">Step 2: Scan de QR-code</h3>
                        <p>Scan de QR-code op de voucher staat.</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4 h-100">
                        <i class="fas fa-vote-yea fa-3x text-dark-green mb-3"></i>
                        <h3 class="mb-3">Step 3: Ga stemmen!</h3>
                        <p>Selecteer je gekozen kandidaten en dien je stem in.</p>
                    </div>
                </div>
            </div>
            <p class="text-danger font-weight-bold mt-3">Let op: u kunt maar één keer stemmen.</p>
        </section>

        <section class="bg-custom-green text-white text-center rounded-lg p-5 mb-4">
            <h2 class="display-4 section-title mb-3">Doe mee aan de online verkiezing</h2>
            <p class="lead mb-4">Ervaar de toekomst van online stemmen vandaag!</p>
            <div>
                <button class="btn btn-light rounded-lg mr-2">Registreer</button>
                <button class="btn btn-light rounded-lg">Log in</button>
            </div>
        </section>

        <section>
            <h2 class="display-4 text-center section-title mb-3">Nieuws</h2>
            <p class="lead text-center mb-4">Kom meer te weten over de politieke partijen, kandidaten, ressortsraden enz.</p>
            
            <div class="text-center mb-3">
                <select class="custom-select w-auto rounded-lg">
                    <option>Filter</option>
                </select>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4">
                        <h3>Politieke Partij</h3>
                        <h4 class="text-dark-green">Nooit Pessimistisch Standpunt (NPS)</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        <button class="btn btn-custom-green rounded-lg">Lees meer ></button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4">
                        <h3>Politieke Partij</h3>
                        <h4 class="text-dark-green">Nooit Pessimistisch Standpunt (NPS)</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        <button class="btn btn-custom-green rounded-lg">Lees meer ></button>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card card-custom p-4">
                        <h3>Politieke Partij</h3>
                        <h4 class="text-dark-green">Nooit Pessimistisch Standpunt (NPS)</h4>
                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        <button class="btn btn-custom-green rounded-lg">Lees meer ></button>
                    </div>
                </div>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-light rounded-lg mr-2">Bekijk meer</button>
                <button class="btn btn-custom-green rounded-lg">Bekijk meer</button>
            </div>
        </section>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php
include './include/footer.php'; 
?>
