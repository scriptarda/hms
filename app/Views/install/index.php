<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HEMS Installation Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            padding: 2rem 1rem;
        }
        .wizard-container {
            width: 100%;
            max-width: 650px;
        }
        .card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 2rem 2rem 1rem;
        }
        .card-body {
            padding: 2.5rem;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2.5rem;
            position: relative;
        }
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 18px;
            left: 10%;
            right: 10%;
            height: 2px;
            background: rgba(255, 255, 255, 0.1);
            z-index: 1;
        }
        .step-progress-line {
            position: absolute;
            top: 18px;
            left: 10%;
            width: 0%;
            height: 2px;
            background: #4f46e5;
            z-index: 2;
            transition: width 0.4s ease;
        }
        .step-item {
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 25%;
        }
        .step-icon {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: #334155;
            border: 2px solid #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: 700;
            color: #94a3b8;
            transition: all 0.4s ease;
        }
        .step-item.active .step-icon {
            background: #4f46e5;
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
        }
        .step-item.completed .step-icon {
            background: #10b981;
            border-color: #34d399;
            color: #ffffff;
        }
        .step-label {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .step-item.active .step-label {
            color: #ffffff;
        }
        .step-item.completed .step-label {
            color: #34d399;
        }
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #cbd5e1;
        }
        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 10px;
        }
        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #6366f1;
            box-shadow: 0 0 10px rgba(99, 102, 241, 0.25);
            color: #ffffff;
        }
        .btn-wizard {
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .btn-wizard-primary {
            background: #4f46e5;
            border: none;
            color: #ffffff;
        }
        .btn-wizard-primary:hover {
            background: #4338ca;
            box-shadow: 0 4px 15px rgba(67, 56, 202, 0.4);
        }
        .btn-wizard-primary:disabled {
            background: #312e81;
            color: #94a3b8;
        }
        .wizard-step-pane {
            display: none;
        }
        .wizard-step-pane.active {
            display: block;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .loading-spinner {
            display: none;
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
            vertical-align: middle;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>

<div class="wizard-container">
    <div class="card">
        <div class="card-header text-center">
            <h3 class="fw-bold mb-1"><i class="bi bi-hospital me-2 text-indigo"></i>HEMS Installation</h3>
            <p class="text-muted small mb-0">Healthcare Enterprise Management System Setup</p>
        </div>
        <div class="card-body">
            
            <!-- Step Indicators -->
            <div class="step-indicator">
                <div class="step-progress-line" id="stepProgressLine"></div>
                <div class="step-item active" id="stepIndicator1">
                    <div class="step-icon">1</div>
                    <div class="step-label">Database</div>
                </div>
                <div class="step-item" id="stepIndicator2">
                    <div class="step-icon">2</div>
                    <div class="step-label">Migrations</div>
                </div>
                <div class="step-item" id="stepIndicator3">
                    <div class="step-icon">3</div>
                    <div class="step-label">Admin & App</div>
                </div>
                <div class="step-item" id="stepIndicator4">
                    <div class="step-icon">4</div>
                    <div class="step-label">Finished</div>
                </div>
            </div>

            <!-- Step 1 Pane: Database Config -->
            <div class="wizard-step-pane active" id="stepPane1">
                <h4 class="fw-bold mb-3"><i class="bi bi-database me-2 text-primary"></i>Database Configuration</h4>
                <p class="text-muted small mb-4">Please input details for your MySQL 8 connection. If the database does not exist, the setup will attempt to create it.</p>
                
                <form id="formDb">
                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label">Database Host</label>
                            <input type="text" class="form-control" name="host" value="127.0.0.1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Port</label>
                            <input type="number" class="form-control" name="port" value="3306" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Database Name</label>
                            <input type="text" class="form-control" name="database" value="hems_db" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" value="root" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" placeholder="Password (usually blank for local root)">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-wizard btn-wizard-primary" id="btnStep1">
                            <span class="spinner-border spinner-border-sm loading-spinner" id="spinner1"></span>
                            Test & Save Connection
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 2 Pane: Schema & Seeds -->
            <div class="wizard-step-pane" id="stepPane2">
                <h4 class="fw-bold mb-3"><i class="bi bi-table me-2 text-success"></i>Database Initialization</h4>
                <p class="text-muted small mb-4">We are ready to build the tables, relationships, and populate initial roles, permissions, departments, and seed data.</p>
                
                <div class="p-3 mb-4 rounded bg-dark border border-secondary text-monospace text-success small" style="max-height: 150px; overflow-y: auto;">
                    Ready to load:<br>
                    - migrations/schema.sql (Tables, foreign keys, indexes)<br>
                    - seeds/sample_data.sql (Staff, Tech, Admin, Departments, Rooms, SLA Rules)
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <button type="button" class="btn btn-wizard btn-outline-secondary" onclick="prevStep(1)">Back</button>
                    <button type="button" class="btn btn-wizard btn-wizard-primary" id="btnStep2">
                        <span class="spinner-border spinner-border-sm loading-spinner" id="spinner2"></span>
                        Run Migrations & Seeds
                    </button>
                </div>
            </div>

            <!-- Step 3 Pane: Admin User & Settings -->
            <div class="wizard-step-pane" id="stepPane3">
                <h4 class="fw-bold mb-3"><i class="bi bi-person-gear me-2 text-warning"></i>Admin Account & Branding</h4>
                <p class="text-muted small mb-4">Configure your primary administrative user and global settings.</p>

                <form id="formAdmin">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Admin First Name</label>
                            <input type="text" class="form-control" name="admin_first_name" value="System" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Admin Last Name</label>
                            <input type="text" class="form-control" name="admin_last_name" value="Admin" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Super Admin Email</label>
                            <input type="email" class="form-control" name="admin_email" value="admin@healthcentral.org" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Super Admin Password</label>
                            <input type="password" class="form-control" name="admin_password" value="Password123!" required>
                            <small class="text-muted">Minimum 8 characters. Used to login initially.</small>
                        </div>
                        <hr class="my-4 border-secondary">
                        <div class="col-md-6">
                            <label class="form-label">App Title / Brand Name</label>
                            <input type="text" class="form-control" name="app_name" value="HealthCentral HEMS" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Application Base URL</label>
                            <input type="url" class="form-control" name="app_url" value="http://localhost/hms" required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-wizard btn-outline-secondary" onclick="prevStep(2)">Back</button>
                        <button type="submit" class="btn btn-wizard btn-wizard-primary" id="btnStep3">
                            <span class="spinner-border spinner-border-sm loading-spinner" id="spinner3"></span>
                            Complete Configuration
                        </button>
                    </div>
                </form>
            </div>

            <!-- Step 4 Pane: Finish -->
            <div class="wizard-step-pane" id="stepPane4">
                <div class="text-center py-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                    <h3 class="fw-bold mt-3">Installation Finished!</h3>
                    <p class="text-muted mb-4">HEMS Core is successfully set up and configured for production use.</p>
                    
                    <div class="card bg-dark border-secondary text-start p-3 mb-4 mx-auto" style="max-width: 450px;">
                        <h6 class="fw-bold text-success"><i class="bi bi-info-circle me-1"></i>Quick Login Info:</h6>
                        <ul class="mb-0 small text-muted">
                            <li><strong>Super Admin:</strong> <span class="text-light" id="infoEmail">admin@healthcentral.org</span></li>
                            <li><strong>Password:</strong> <span class="text-light">Your configured password</span></li>
                        </ul>
                    </div>

                    <a href="<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); ?>/install/finish" class="btn btn-wizard btn-wizard-primary px-5">Go to Login</a>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const baseUrl = '<?php echo rtrim(dirname($_SERVER['SCRIPT_NAME']), "/\\"); ?>';

    function nextStep(step) {
        $('.wizard-step-pane').removeClass('active');
        $('#stepPane' + step).addClass('active');
        
        // Update indicators
        $('.step-item').removeClass('active');
        for (let i = 1; i <= step; i++) {
            if (i < step) {
                $('#stepIndicator' + i).addClass('completed');
            } else {
                $('#stepIndicator' + i).addClass('active').removeClass('completed');
            }
        }
        
        // Progress line width
        let pct = ((step - 1) / 3) * 80;
        $('#stepProgressLine').css('width', pct + '%');
    }

    function prevStep(step) {
        nextStep(step);
    }

    // Ajax Form step 1
    $('#formDb').on('submit', function(e) {
        e.preventDefault();
        $('#btnStep1').prop('disabled', true);
        $('#spinner1').show();

        $.post(baseUrl + '/install/step1', $(this).serialize(), function(res) {
            $('#btnStep1').prop('disabled', false);
            $('#spinner1').hide();
            if (res.success) {
                Swal.fire('Success!', res.message, 'success').then(() => {
                    nextStep(2);
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            $('#btnStep1').prop('disabled', false);
            $('#spinner1').hide();
            Swal.fire('Error', 'AJAX request failed. Check server log.', 'error');
        });
    });

    // Step 2 Run Migrations
    $('#btnStep2').on('click', function() {
        $('#btnStep2').prop('disabled', true);
        $('#spinner2').show();

        $.post(baseUrl + '/install/step2', function(res) {
            $('#btnStep2').prop('disabled', false);
            $('#spinner2').hide();
            if (res.success) {
                Swal.fire('Initialized!', res.message, 'success').then(() => {
                    nextStep(3);
                });
            } else {
                Swal.fire('Initialization Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            $('#btnStep2').prop('disabled', false);
            $('#spinner2').hide();
            Swal.fire('Error', 'AJAX execution failed.', 'error');
        });
    });

    // Step 3 Admin config
    $('#formAdmin').on('submit', function(e) {
        e.preventDefault();
        $('#btnStep3').prop('disabled', true);
        $('#spinner3').show();

        const email = $('input[name="admin_email"]').val();
        $('#infoEmail').text(email);

        $.post(baseUrl + '/install/step3', $(this).serialize(), function(res) {
            $('#btnStep3').prop('disabled', false);
            $('#spinner3').hide();
            if (res.success) {
                Swal.fire('Success!', res.message, 'success').then(() => {
                    nextStep(4);
                });
            } else {
                Swal.fire('Error', res.message, 'error');
            }
        }, 'json').fail(function() {
            $('#btnStep3').prop('disabled', false);
            $('#spinner3').hide();
            Swal.fire('Error', 'AJAX submission failed.', 'error');
        });
    });
</script>
</body>
</html>
