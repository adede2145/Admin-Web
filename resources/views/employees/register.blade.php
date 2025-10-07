@extends('layouts.theme')

@section('content')
<style>
    .fingerprint-section {
        transition: all 0.3s ease;
    }

    .fingerprint-section.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .progress-wrapper {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .notification {
        animation: slideInRight 0.3s ease-out;
    }

    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
        }

        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .status-text {
        font-weight: 500;
        transition: color 0.2s ease;
    }

    .device-status {
        padding: 8px 12px;
        border-radius: 6px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card aa-card">
            <div class="card-header header-maroon d-flex justify-content-between align-items-center">
                <div class="card-title m-0">
                    <i class="bi bi-person-plus me-2"></i> Register Employee
                </div>
            </div>
            <div class="card-body">
                @if(!auth()->check() || !auth()->user()->role || !in_array(auth()->user()->role->role_name, ['admin','super_admin']))
                <div class="alert alert-danger" role="alert">You do not have permission to access this page.</div>
                @else
                <div class="mb-3">
                    <div class="d-flex align-items-center gap-2 device-status">
                        <i class="bi bi-usb-symbol text-secondary"></i>
                        <span id="deviceStatus" class="text-muted status-text">Checking Device Bridge...</span>
                    </div>
                </div>
                @php
                $roleName = auth()->user()->role->role_name ?? '';
                $isSuper = $roleName === 'super_admin';
                $isAdmin = $roleName === 'admin';
                $userDeptId = auth()->user()->department_id;
                $departments = ($isSuper || $isAdmin) ? \App\Models\Department::orderBy('department_name')->get() : collect();
                @endphp

                <form id="registerForm" action="{{ route('employees.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Profile Image</label>
                            <input type="file" class="form-control" id="profileImage" name="profile_image" accept="image/*">
                            <div class="mt-2">
                                <img id="profilePreview" src="" alt="Preview" class="img-thumbnail d-none" style="max-width: 180px;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="empName" name="emp_name" placeholder="Full name" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="empId" name="emp_id" placeholder="ID/Code" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            @if($isSuper)
                            <select id="departmentId" name="department_id" class="form-select" required>
                                <option value="">Select department</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id ?? $dept->department_id }}">{{ $dept->department_name ?? $dept->name }}</option>
                                @endforeach
                            </select>
                            @else
                            @php
                            $deptName = optional(\App\Models\Department::find($userDeptId))->department_name;
                            @endphp
                            <input type="text" class="form-control" value="{{ $deptName ?? 'My Department' }}" disabled>
                            <input type="hidden" id="departmentId" name="department_id" value="{{ $userDeptId }}">
                            @endif
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Employment Type</label>
                            <select id="employmentType" name="employment_type" class="form-select" required>
                                <option value="">Select employment type</option>
                                <option value="full_time">Full Time</option>
                                <option value="part_time">Part Time</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">RFID (fallback) <span id="rfidStatus" class="ms-2 small text-muted"></span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="rfidUid" name="rfid_uid" placeholder="Tap card or type UID" autocomplete="off">
                                <button class="btn btn-outline-primary" type="button" id="clearRfidBtn">Clear</button>
                            </div>
                            <div class="form-text">If your reader is keyboard-wedge, focus here and tap a card.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Fingerprint Enrollment</label>

                            <!-- Primary Fingerprint Section -->
                            <div class="border rounded p-3 mb-3 bg-light">
                                <h6 class="text-primary mb-2">
                                    <i class="bi bi-1-circle me-1"></i> Primary Fingerprint (Index Finger)
                                </h6>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <button type="button" id="capturePrimaryBtn" class="btn btn-outline-primary btn-sm" disabled>
                                        <i class="bi bi-fingerprint me-1"></i> Scan Primary
                                    </button>
                                    <span id="primaryStatus" class="text-muted">Waiting for Device Bridge...</span>
                                </div>
                                <!-- Scanning Progress for Primary -->
                                <div id="primaryProgress" class="d-none progress-wrapper">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                        <span class="small text-primary">Scanning in progress...</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 6px;">
                                        <div id="primaryProgressBar" class="progress-bar progress-bar-striped progress-bar-animated"
                                            role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="primaryInstruction">Place your index finger on the scanner...</span>
                                    </div>
                                </div>
                                <input type="hidden" id="primaryTemplate" name="primary_template" value="">
                            </div>

                            <!-- Backup Fingerprint Section -->
                            <div class="border rounded p-3 bg-light" id="backupSection" style="opacity: 0.5;">
                                <h6 class="text-secondary mb-2">
                                    <i class="bi bi-2-circle me-1"></i> Backup Fingerprint (Thumb)
                                </h6>
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                    <button type="button" id="captureBackupBtn" class="btn btn-outline-secondary btn-sm" disabled>
                                        <i class="bi bi-fingerprint me-1"></i> Scan Backup
                                    </button>
                                    <span id="backupStatus" class="text-muted">Complete primary fingerprint first</span>
                                </div>
                                <!-- Scanning Progress for Backup -->
                                <div id="backupProgress" class="d-none progress-wrapper">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
                                        <span class="small text-secondary">Scanning in progress...</span>
                                    </div>
                                    <div class="progress mb-2" style="height: 6px;">
                                        <div id="backupProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-secondary"
                                            role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <div class="small text-muted">
                                        <span id="backupInstruction">Place your thumb on the scanner...</span>
                                    </div>
                                </div>
                                <input type="hidden" id="backupTemplate" name="backup_template" value="">
                            </div>

                            <!-- Overall Status -->
                            <div class="mt-3">
                                <span id="fpOverallStatus" class="text-muted">Device status checking...</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <button id="registerBtn" class="btn btn-primary" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Register
                            </button>
                            <span id="registerHint" class="ms-2 text-muted">Register is disabled until primary fingerprint is captured and required fields are filled.</span>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script type="module">
    const bridgeBase = 'http://127.0.0.1:18420';
    const deviceStatus = document.getElementById('deviceStatus');
    const registerBtn = document.getElementById('registerBtn');
    const registerHint = document.getElementById('registerHint');

    // Primary fingerprint elements
    const capturePrimaryBtn = document.getElementById('capturePrimaryBtn');
    const primaryStatus = document.getElementById('primaryStatus');
    const primaryTemplate = document.getElementById('primaryTemplate');
    const primaryProgress = document.getElementById('primaryProgress');
    const primaryProgressBar = document.getElementById('primaryProgressBar');
    const primaryInstruction = document.getElementById('primaryInstruction');

    // Backup fingerprint elements
    const captureBackupBtn = document.getElementById('captureBackupBtn');
    const backupStatus = document.getElementById('backupStatus');
    const backupTemplate = document.getElementById('backupTemplate');
    const backupProgress = document.getElementById('backupProgress');
    const backupProgressBar = document.getElementById('backupProgressBar');
    const backupInstruction = document.getElementById('backupInstruction');
    const backupSection = document.getElementById('backupSection');

    // Other elements
    const fpOverallStatus = document.getElementById('fpOverallStatus');
    const rfidStatus = document.getElementById('rfidStatus');
    const rfidInput = document.getElementById('rfidUid');

    // State tracking
    let deviceConnected = false;
    let enrollmentInProgress = false;
    let fingerprintDeviceModel = '';

    // Global sanitizer for RFID text so all handlers can use it
    function sanitize(raw) {
        const original = String(raw ?? '');
        console.log('RFID Input - Raw value:', JSON.stringify(original));
        const printableOnly = original.replace(/[^\x20-\x7E]/g, '');
        const collapsed = printableOnly.replace(/\s+/g, ' ').trim();
        return collapsed;
    }

    function updateRegisterEnabled() {
        const empName = document.getElementById('empName').value.trim();
        const empId = document.getElementById('empId').value.trim();
        const rfid = (rfidInput?.value || '').trim();
        const deptId = (document.getElementById('departmentId')?.value || '').trim();
        const empType = (document.getElementById('employmentType')?.value || '').trim();
        const hasPrimaryFp = !!primaryTemplate.value;

        const ok = empName && empId && deptId && empType && hasPrimaryFp && rfid.length > 0;
        registerBtn.disabled = !ok;

        if (ok) {
            registerHint.textContent = 'Ready to submit. Click Register button to proceed.';
            registerHint.className = 'ms-2 text-success';
        } else if (!hasPrimaryFp) {
            registerHint.textContent = 'Primary fingerprint is required.';
            registerHint.className = 'ms-2 text-muted';
        } else if (!rfid) {
            registerHint.textContent = 'RFID scan is required.';
            registerHint.className = 'ms-2 text-muted';
        } else {
            registerHint.textContent = 'Please fill all required fields.';
            registerHint.className = 'ms-2 text-muted';
        }
    }

    function updateFingerprintStatus() {
        const hasPrimary = !!primaryTemplate.value;
        const hasBackup = !!backupTemplate.value;

        if (hasPrimary && hasBackup) {
            fpOverallStatus.textContent = 'Both fingerprints captured successfully.';
            fpOverallStatus.className = 'text-success fw-bold';
        } else if (hasPrimary) {
            fpOverallStatus.textContent = 'Primary fingerprint captured. Backup fingerprint is optional.';
            fpOverallStatus.className = 'text-primary';
        } else if (deviceConnected) {
            fpOverallStatus.textContent = 'Primary fingerprint required to proceed.';
            fpOverallStatus.className = 'text-muted';
        } else {
            fpOverallStatus.textContent = 'Device Bridge connection required.';
            fpOverallStatus.className = 'text-danger';
        }
    }

    function updateUIBasedOnDeviceStatus() {
        console.log('Updating UI - Device connected:', deviceConnected, 'Model:', fingerprintDeviceModel);

        if (deviceConnected && !enrollmentInProgress) {
            // Device is available and not currently enrolling
            deviceStatus.textContent = `${fingerprintDeviceModel} detected and ready.`;
            deviceStatus.className = 'text-success status-text';

            // Update primary fingerprint button
            if (!primaryTemplate.value) {
                capturePrimaryBtn.disabled = false;
                primaryStatus.textContent = 'Ready to scan index finger';
                primaryStatus.className = 'text-success';
            } else {
                capturePrimaryBtn.disabled = true;
                primaryStatus.textContent = 'Primary fingerprint captured ✓';
                primaryStatus.className = 'text-success fw-bold';
            }

            // Update backup fingerprint button
            if (primaryTemplate.value && !backupTemplate.value) {
                captureBackupBtn.disabled = false;
                backupStatus.textContent = 'Ready to scan thumb (optional)';
                backupStatus.className = 'text-success';
                backupSection.style.opacity = '1';
            } else if (backupTemplate.value) {
                captureBackupBtn.disabled = true;
                backupStatus.textContent = 'Backup fingerprint captured ✓';
                backupStatus.className = 'text-success fw-bold';
            } else {
                captureBackupBtn.disabled = true;
                backupStatus.textContent = 'Complete primary fingerprint first';
                backupStatus.className = 'text-muted';
                backupSection.style.opacity = '0.5';
            }
        } else if (!deviceConnected) {
            // Device is not available
            deviceStatus.textContent = 'Fingerprint device not detected.';
            deviceStatus.className = 'text-danger status-text';

            capturePrimaryBtn.disabled = true;
            captureBackupBtn.disabled = true;

            primaryStatus.textContent = 'Device not available';
            primaryStatus.className = 'text-danger';

            backupStatus.textContent = 'Device not available';
            backupStatus.className = 'text-danger';
            backupSection.style.opacity = '0.5';
        } else if (enrollmentInProgress) {
            // Currently enrolling - keep buttons disabled
            capturePrimaryBtn.disabled = true;
            captureBackupBtn.disabled = true;
        }

        updateFingerprintStatus();
        updateRegisterEnabled();
    }

    async function checkBridge() {
        try {
            console.log('Checking Device Bridge...');

            // Check if bridge is running
            const healthResponse = await fetch(`${bridgeBase}/api/health/ping`, {
                cache: 'no-store',
                signal: AbortSignal.timeout(3000) // 3 second timeout
            });

            if (!healthResponse.ok) {
                throw new Error('Health check failed');
            }

            console.log('Bridge is running, checking devices...');

            // Check device status
            const deviceResponse = await fetch(`${bridgeBase}/api/devices`, {
                cache: 'no-store',
                signal: AbortSignal.timeout(3000)
            });

            if (!deviceResponse.ok) {
                throw new Error('Device check failed');
            }

            const deviceData = await deviceResponse.json();
            console.log('Device data received:', deviceData);

            const fpPresent = deviceData?.device?.present === true;
            const fpModel = deviceData?.device?.model || 'Unknown Device';

            // Update state
            const wasConnected = deviceConnected;
            deviceConnected = fpPresent;
            fingerprintDeviceModel = fpModel;

            console.log('Device status changed:', wasConnected, '->', deviceConnected);

            // Always update UI when device status changes
            updateUIBasedOnDeviceStatus();

        } catch (error) {
            console.error('Bridge check failed:', error);

            const wasConnected = deviceConnected;
            deviceConnected = false;
            fingerprintDeviceModel = '';

            deviceStatus.textContent = 'Device Bridge not running on this PC.';
            deviceStatus.className = 'text-danger status-text';

            fpOverallStatus.textContent = 'Device Bridge connection failed.';
            fpOverallStatus.className = 'text-danger';

            // Update UI if status changed
            if (wasConnected !== deviceConnected) {
                updateUIBasedOnDeviceStatus();
            }
        }
    }

    async function enrollFingerprint(isPrimary = true) {
        const statusElement = isPrimary ? primaryStatus : backupStatus;
        const progressElement = isPrimary ? primaryProgress : backupProgress;
        const progressBarElement = isPrimary ? primaryProgressBar : backupProgressBar;
        const instructionElement = isPrimary ? primaryInstruction : backupInstruction;
        const templateElement = isPrimary ? primaryTemplate : backupTemplate;
        const fingerType = isPrimary ? 'index finger' : 'thumb';

        enrollmentInProgress = true;
        updateUIBasedOnDeviceStatus();

        progressElement.classList.remove('d-none');
        statusElement.textContent = 'Initializing...';
        statusElement.className = isPrimary ? 'text-primary' : 'text-secondary';
        progressBarElement.style.width = '0%';
        instructionElement.textContent = `Initializing scanner for ${fingerType}...`;

        let sessionId = null;
        let pollTimer = null;

        try {
            instructionElement.textContent = `Place your ${fingerType} on the scanner...`;
            statusElement.textContent = 'Waiting for finger...';
            progressBarElement.style.width = '10%';

            const startResp = await fetch(`${bridgeBase}/api/fingerprint/enroll/start`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store',
                signal: AbortSignal.timeout(5000)
            });
            if (!startResp.ok) throw new Error(await startResp.text().catch(() => 'Failed to start enrollment'));
            const startData = await startResp.json();
            sessionId = startData.sessionId;
            if (!sessionId) throw new Error('No sessionId from Device Bridge');

            const pollIntervalMs = 300;
            const maxDurationMs = 35000;
            const startedAt = Date.now();

            await new Promise((resolve, reject) => {
                pollTimer = setInterval(async () => {
                    try {
                        if (Date.now() - startedAt > maxDurationMs) {
                            clearInterval(pollTimer);
                            pollTimer = null;
                            return reject(new Error('Enrollment timeout'));
                        }

                        const progResp = await fetch(`${bridgeBase}/api/fingerprint/enroll/progress/${sessionId}`, {
                            cache: 'no-store',
                            signal: AbortSignal.timeout(3000)
                        });
                        if (!progResp.ok) throw new Error(await progResp.text().catch(() => 'Progress poll failed'));
                        const prog = await progResp.json();

                        const pct = Math.max(0, Math.min(100, Number(prog.progress) || 0));
                        progressBarElement.style.width = `${pct}%`;

                        statusElement.textContent = prog.message || (prog.phase === 'processing' ? 'Processing...' : 'Scanning...');
                        const scansLeft = typeof prog.scansLeft === 'number' ? prog.scansLeft : null;
                        if (scansLeft !== null && scansLeft > 0) {
                            instructionElement.textContent = `Lift and place your ${fingerType} again... (${scansLeft} more)`;
                        } else if (prog.done) {
                            instructionElement.textContent = `${fingerType.charAt(0).toUpperCase() + fingerType.slice(1)} enrolled successfully!`;
                        } else if (prog.failed) {
                            instructionElement.textContent = `Failed to enroll ${fingerType}. Please try again.`;
                        } else if (prog.phase === 'waiting') {
                            instructionElement.textContent = `Place your ${fingerType} on the scanner...`;
                        }

                        if (prog.failed) {
                            clearInterval(pollTimer);
                            pollTimer = null;
                            return reject(new Error(prog.message || 'Enrollment failed'));
                        }

                        if (prog.done) {
                            const finishResp = await fetch(`${bridgeBase}/api/fingerprint/enroll/finish/${sessionId}`, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json'
                                },
                                cache: 'no-store',
                                signal: AbortSignal.timeout(4000)
                            });
                            if (!finishResp.ok) throw new Error(await finishResp.text().catch(() => 'Failed to finalize enrollment'));
                            const fin = await finishResp.json();
                            if (!fin.template) throw new Error('No template returned');

                            templateElement.value = fin.template;
                            progressBarElement.style.width = '100%';
                            statusElement.textContent = `${isPrimary ? 'Primary' : 'Backup'} fingerprint captured successfully!`;
                            statusElement.className = 'text-success fw-bold';

                            clearInterval(pollTimer);
                            pollTimer = null;
                            return resolve();
                        }
                    } catch (err) {
                        clearInterval(pollTimer);
                        pollTimer = null;
                        reject(err);
                    }
                }, pollIntervalMs);
            });

            showNotification(
                isPrimary ? 'Primary Fingerprint Captured!' : 'Backup Fingerprint Captured!',
                isPrimary ? 'You can now scan a backup fingerprint or continue.' : 'Both fingerprints have been captured successfully!',
                'success'
            );
        } catch (error) {
            console.error('Enrollment error:', error);
            progressBarElement.style.width = '0%';
            statusElement.textContent = `Failed: ${error.message}`;
            statusElement.className = 'text-danger';
            instructionElement.textContent = `Failed to enroll ${fingerType}. Please try again.`;
            showNotification('Fingerprint Enrollment Failed', `Could not capture ${fingerType}: ${error.message}`, 'error');
        } finally {
            if (pollTimer) {
                clearInterval(pollTimer);
            }
            enrollmentInProgress = false;
            setTimeout(() => {
                progressElement.classList.add('d-none');
                progressBarElement.style.width = '0%';
            }, 1200);
            setTimeout(updateUIBasedOnDeviceStatus, 300);
        }
    }

    function showNotification(title, message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed notification`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px; box-shadow: 0 4px 12px rgba(0,0,0,0.15);';

        notification.innerHTML = `
        <div class="d-flex align-items-start">
            <div class="me-2">
                <i class="bi bi-${type === 'error' ? 'exclamation-triangle' : type === 'success' ? 'check-circle' : 'info-circle'} fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <strong>${title}</strong><br>
                <small>${message}</small>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 5000);
    }

    // Event listeners - Real-time fingerprint enrollment with progress tracking
    capturePrimaryBtn?.addEventListener('click', () => enrollFingerprint(true));
    captureBackupBtn?.addEventListener('click', () => enrollFingerprint(false));

    // Optional: auto-focus RFID input to capture keyboard-wedge readers
    rfidInput?.addEventListener('focus', () => {
        rfidInput.select();
    });
    document.getElementById('clearRfidBtn')?.addEventListener('click', () => {
        rfidInput.value = '';
        rfidStatus.textContent = '';
        rfidStatus.classList.add('text-muted');
        rfidInput.focus();
        updateRegisterEnabled();
    });

    // Manual button to check if ready to register
    document.getElementById('checkReadyBtn')?.addEventListener('click', () => {
        console.log('Manual check ready button clicked');
        updateRegisterEnabled();

        setTimeout(() => {
            if (registerBtn.disabled === false) {
                showNotification(
                    'Ready to Register!',
                    'All required fields are complete. Click Register to proceed.',
                    'success'
                );
            } else {
                showNotification(
                    'Not Ready',
                    'Please complete all required fields (Name, ID, Department, Employment Type, RFID, and Primary Fingerprint).',
                    'warning'
                );
            }
        }, 100);
    });

    // Detect keyboard-wedge bursts (fast keystrokes typical of RFID readers)
    if (rfidInput) {
        let lastTs = 0;
        let burstCount = 0;
        const burstWindowMs = 400; // window for considering a burst
        const minBurst = 4; // minimal fast chars to consider as wedge

        rfidInput.addEventListener('keydown', (e) => {
            const now = performance.now();
            if (now - lastTs < 35) {
                burstCount++;
            } else if (now - lastTs > burstWindowMs) {
                burstCount = 0;
            }
            lastTs = now;
        });

        // Sanitize RFID to strip control characters and normalize whitespace
        rfidInput.addEventListener('input', (e) => {
            try {
                const cleaned = sanitize(rfidInput.value);
                if (rfidInput.value !== cleaned) {
                    const selStart = rfidInput.selectionStart;
                    rfidInput.value = cleaned;
                    try {
                        rfidInput.setSelectionRange(selStart, selStart);
                    } catch {}
                }
                const rfidValue = rfidInput.value;
                console.log('RFID Input - Value entered:', JSON.stringify(rfidValue));

                // Update status based on detection method (no sanitization)
                if (rfidValue) {
                    if (burstCount >= minBurst) {
                        rfidStatus.textContent = '✓ RFID card detected';
                        rfidStatus.className = 'text-success small';
                    } else {
                        rfidStatus.textContent = '✓ RFID manually entered';
                        rfidStatus.className = 'text-primary small';
                    }
                } else {
                    rfidStatus.textContent = '';
                    rfidStatus.className = 'text-muted small';
                }

                // Keep manual flow but reflect button state
                updateRegisterEnabled();

            } catch (error) {
                console.error('RFID input handling error:', error);
                rfidStatus.textContent = '⚠ Processing error';
                rfidStatus.className = 'text-warning small';
            }
        });

        // Handle paste events (some RFID software uses paste)
        rfidInput.addEventListener('paste', (e) => {
            setTimeout(() => {
                const event = new Event('input', {
                    bubbles: true
                });
                rfidInput.dispatchEvent(event);

                // NO AUTO-REGISTRATION on paste
                console.log('RFID pasted, but auto-registration is disabled');
            }, 10);
        });



        // Initial focus to streamline scanning
        setTimeout(() => rfidInput.focus(), 100);
    }

    // Prevent form submit for this placeholder page
    document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();

        if (registerBtn.disabled) {
            showNotification('Registration Error', 'Please complete all required fields first.', 'error');
            return;
        }

        // Show loading state
        const originalText = registerBtn.innerHTML;
        registerBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Processing...';
        registerBtn.disabled = true;

        try {
            // Sanitize before submit
            const rfidValue = sanitize(rfidInput?.value || '');
            rfidInput.value = rfidValue;
            console.log('Submitting RFID value (sanitized):', JSON.stringify(rfidValue));

            const formData = new FormData(document.getElementById('registerForm'));

            // Log form data for debugging (no modification)
            console.log('Form submission data:');
            for (let [key, value] of formData.entries()) {
                if (key === 'rfid_uid') {
                    console.log(`  ${key}:`, JSON.stringify(value));
                } else {
                    console.log(`  ${key}:`, typeof value === 'string' ? value.substring(0, 50) + (value.length > 50 ? '...' : '') : '[File]');
                }
            }

            const response = await fetch(document.getElementById('registerForm').action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showNotification(
                    'Registration Successful!',
                    `Employee ${result.employee.name} has been registered successfully.`,
                    'success'
                );

                // Reset form after successful registration
                setTimeout(() => {
                    document.getElementById('registerForm').reset();
                    primaryTemplate.value = '';
                    backupTemplate.value = '';
                    profilePreview.classList.add('d-none');
                    profilePreview.src = '';

                    // Reset RFID status
                    rfidStatus.textContent = '';
                    rfidStatus.className = 'text-muted small';

                    // Reset fingerprint sections and update UI
                    updateUIBasedOnDeviceStatus();

                    // Focus RFID input for next registration
                    setTimeout(() => rfidInput?.focus(), 100);
                }, 2000);

            } else {
                // Handle validation errors
                if (result.errors) {
                    let errorMessages = [];
                    for (const [field, messages] of Object.entries(result.errors)) {
                        errorMessages.push(messages.join(', '));
                    }
                    throw new Error(errorMessages.join('\n'));
                } else {
                    throw new Error(result.message || 'Registration failed');
                }
            }

        } catch (error) {
            console.error('Registration error:', error);
            let errorMessage = error.message || 'An error occurred during registration. Please try again.';

            // No special-case clearing; user can correct and resubmit

            showNotification(
                'Registration Failed',
                errorMessage,
                'error'
            );
        } finally {
            // Restore button state
            setTimeout(() => {
                registerBtn.innerHTML = originalText;
                updateRegisterEnabled();
            }, 1000);
        }
    });

    // Profile image preview
    const profileImage = document.getElementById('profileImage');
    const profilePreview = document.getElementById('profilePreview');
    profileImage?.addEventListener('change', () => {
        const f = profileImage.files && profileImage.files[0];
        if (!f) {
            profilePreview.classList.add('d-none');
            profilePreview.src = '';
            return;
        }
        const url = URL.createObjectURL(f);
        profilePreview.src = url;
        profilePreview.classList.remove('d-none');
    });

    // Re-evaluate enabling when fields change (manually triggered)
    ['empName', 'empId', 'departmentId', 'employmentType'].forEach(id => {
        document.getElementById(id)?.addEventListener('input', updateRegisterEnabled);
        document.getElementById(id)?.addEventListener('change', updateRegisterEnabled);
    });

    // Add click handler to register button to ensure state is current
    registerBtn?.addEventListener('click', () => {
        updateRegisterEnabled(); // Final check before submission
    });

    // Initial checks
    setTimeout(() => {
        checkBridge();
        updateUIBasedOnDeviceStatus();
    }, 100);

    // Check bridge every 3 seconds
    setInterval(checkBridge, 3000);
</script>
@endsection