<?php
include 'config.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'fetch_windows':
            $result = $conn->query("SELECT window_number, status FROM staff");
            $windows = [];
            while ($row = $result->fetch_assoc()) {
                $windows[] = $row;
            }
            header('Content-Type: application/json');
            echo json_encode($windows);
            exit();
            
        case 'save_step':
            if (isset($_POST['step']) && isset($_SESSION['queue_data'])) {
                $_SESSION['queue_data'][$_POST['step']] = $_POST;
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid request']);
            }
            exit();
    }
}

// Clear session data if starting fresh
if (!isset($_GET['step'])) {
    unset($_SESSION['queue_data']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Kiosk System</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        .step-content { display: none; }
        .step-content.active { display: block; animation: fadeIn 0.5s ease-in-out; }
        .progress-bar { transition: width 0.5s ease-in-out; }
        .window-option, .service-btn { transition: all 0.3s ease; }
        .window-option:hover:not(.opacity-50), .service-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<?php include 'top-bar-kiosk.php'; ?>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Welcome to Queue Services</h1>
            <p class="text-gray-600">Please follow the steps to get your queue number</p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-8">
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div class="progress-bar bg-blue-600 h-2.5 rounded-full" style="width: 0%"></div>
            </div>
            <div class="flex justify-between mt-2 text-sm text-gray-600">
                <span>Personal Info</span>
                <span>Select Window</span>
                <span>Choose Service</span>
                <span>Confirm</span>
            </div>
        </div>

        <!-- Step 1: Personal Information -->
        <div class="step-content active" id="step1">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold mb-6">Personal Information</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-700 mb-2" for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 mb-2" for="contact">Contact Number</label>
                        <input type="text" id="contact" name="contact" class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <button onclick="nextStep(1)" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200">Continue to Window Selection</button>
                </div>
            </div>
        </div>

        <!-- Step 2: Window Selection -->
        <div class="step-content" id="step2">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold mb-6">Select Service Window</h2>
                <div class="grid grid-cols-2 gap-4 mb-6" id="windows-grid">
                    <?php
                    $result = $conn->query("SELECT window_number, status FROM staff ORDER BY window_number");
                    while ($row = $result->fetch_assoc()) {
                        $statusClass = $row['status'] === 'Open' ? 'bg-green-100 border-green-500' : 'bg-red-100 border-red-500 opacity-50';
                        echo "<div class='window-option p-4 rounded-lg border-2 {$statusClass} cursor-pointer' data-window='{$row['window_number']}' data-status='{$row['status']}'>
                                <h3 class='font-bold'>Window {$row['window_number']}</h3>
                                <span class='text-sm'>{$row['status']}</span>
                              </div>";
                    }
                    ?>
                </div>
                <div class="flex justify-between">
                    <button onclick="prevStep(2)" class="bg-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-400 transition duration-200">Back</button>
                    <button onclick="nextStep(2)" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200">Continue to Services</button>
                </div>
            </div>
        </div>

        <!-- Step 3: Service Selection -->
        <div class="step-content" id="step3">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold mb-6">Choose Service</h2>
                <div class="space-y-4 mb-6">
                    <?php
                    $services = [
                        'Submit Request' => 'Submit a new service request',
                        'Inquire' => 'Ask about our services',
                        'TES Concern' => 'TES-related inquiries and concerns'
                    ];
                    
                    foreach ($services as $service => $description) {
                        echo "<button onclick=\"selectService('$service')\" class=\"service-btn w-full p-4 rounded-lg border-2 hover:bg-blue-50 transition duration-200 text-left\">
                                <h3 class=\"font-bold\">$service</h3>
                                <p class=\"text-sm text-gray-600\">$description</p>
                              </button>";
                    }
                    ?>
                </div>
                <div class="flex justify-between">
                    <button onclick="prevStep(3)" class="bg-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-400 transition duration-200">Back</button>
                    <button onclick="nextStep(3)" class="bg-blue-600 text-white py-3 px-6 rounded-lg hover:bg-blue-700 transition duration-200">Review Details</button>
                </div>
            </div>
        </div>

        <!-- Step 4: Confirmation -->
        <div class="step-content" id="step4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-2xl font-bold mb-6">Confirm Your Details</h2>
                <div class="space-y-4 mb-6">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <dl class="space-y-4">
                            <?php
                            $fields = [
                                'name' => 'Name',
                                'contact' => 'Contact',
                                'window' => 'Window',
                                'service' => 'Service'
                            ];
                            
                            foreach ($fields as $id => $label) {
                                echo "<div>
                                        <dt class=\"text-gray-600\">$label</dt>
                                        <dd class=\"font-semibold text-lg\" id=\"confirm-$id\"></dd>
                                      </div>";
                            }
                            ?>
                        </dl>
                    </div>
                </div>
                <div class="flex justify-between">
                    <button onclick="prevStep(4)" class="bg-gray-300 text-gray-700 py-3 px-6 rounded-lg hover:bg-gray-400 transition duration-200">Back</button>
                    <button onclick="submitQueue()" class="bg-green-600 text-white py-3 px-6 rounded-lg hover:bg-green-700 transition duration-200">Confirm & Get Queue Number</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    let currentStep = 1;
    let formData = { step1: {}, step2: {}, step3: {} };

    // Core functions
    function updateProgressBar() {
        $('.progress-bar').css('width', ((currentStep - 1) / 3) * 100 + '%');
    }

    function showStep(step) {
        $('.step-content').removeClass('active');
        $(`#step${step}`).addClass('active');
        currentStep = step;
        updateProgressBar();
    }

    function validateStep(step) {
        const validations = {
            1: () => {
                const name = $('#name').val().trim();
                const contact = $('#contact').val().trim();
                if (!name || !contact) {
                    showError('Required Fields Missing', 'Please fill in both name and contact number.');
                    return false;
                }
                if (contact.length < 10) {
                    showError('Invalid Contact Number', 'Please enter a valid contact number.');
                    return false;
                }
                return true;
            },
            2: () => {
                if (!formData.step2.window_number) {
                    showError('Window Not Selected', 'Please select a service window.');
                    return false;
                }
                return true;
            },
            3: () => {
                if (!formData.step3.service) {
                    showError('Service Not Selected', 'Please select a service type.');
                    return false;
                }
                return true;
            }
        };
        
        return validations[step] ? validations[step]() : true;
    }

    function showError(title, text) {
        Swal.fire({
            icon: 'error',
            title: title,
            text: text,
            confirmButtonText: 'OK'
        });
    }

    // Navigation functions
    function nextStep(step) {
        if (!validateStep(step)) return;

        if (step === 1) {
            formData.step1 = {
                name: $('#name').val().trim(),
                contact: $('#contact').val().trim()
            };
        }

        saveStepData(step).then(() => {
            showStep(step + 1);
            if (step + 1 === 4) updateConfirmationPage();
        });
    }

    function prevStep(step) {
        showStep(step - 1);
    }

    // Selection functions
    function selectWindow(windowNumber, status) {
        if (status === 'Closed') {
            showError('Window Closed', 'This window is currently closed. Please select another window.');
            return;
        }
        
        formData.step2 = { window_number: windowNumber };
        $('.window-option').removeClass('ring-2 ring-blue-500');
        $(`.window-option[data-window="${windowNumber}"]`).addClass('ring-2 ring-blue-500');
    }

    function selectService(service) {
        formData.step3 = { service: service };
        $('.service-btn').removeClass('ring-2 ring-blue-500');
        $(`.service-btn:contains("${service}")`).first().addClass('ring-2 ring-blue-500');
    }

    // Data handling functions
    function saveStepData(step) {
        return $.ajax({
            url: 'kiosk.php?action=save_step',
            type: 'POST',
            data: { step: `step${step}`, ...formData[`step${step}`] }
        }).catch(error => {
            console.error('Error saving step data:', error);
            showError('Save Error', 'There was a problem saving your data. Please try again.');
        });
    }

    function updateConfirmationPage() {
        $('#confirm-name').text(formData.step1.name);
        $('#confirm-contact').text(formData.step1.contact);
        $('#confirm-window').text(`Window ${formData.step2.window_number}`);
        $('#confirm-service').text(formData.step3.service);
    }

    function submitQueue() {
        Swal.fire({
            title: 'Generating Queue Number',
            text: 'Please wait...',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => { Swal.showLoading(); }
        });

        const formDataToSend = new FormData();
        Object.entries({
            'name': formData.step1.name,
            'contact': formData.step1.contact,
            'window_number': formData.step2.window_number,
            'service': formData.step3.service
        }).forEach(([key, value]) => formDataToSend.append(key, value));

        $.ajax({
            url: 'function/generate_queue.php',
            type: 'POST',
            data: formDataToSend,
            processData: false,
            contentType: false,
            success: function(response) {
                try {
                    let parsedResponse = typeof response === 'string' ? JSON.parse(response.trim()) : response;
                    
                    if (parsedResponse.success) {
                        printQueueTicket(parsedResponse);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Queue Generated Successfully!',
                            html: `
                                <div class="text-center">
                                    <p class="mb-4">Your queue number is:</p>
                                    <p class="text-3xl font-bold mb-4">${parsedResponse.queue_number}</p>
                                    <p class="text-sm text-gray-600">Please wait for your number to be called</p>
                                </div>
                            `,
                            confirmButtonText: 'Done',
                            allowOutsideClick: false
                        }).then(result => {
                            if (result.isConfirmed) window.location.href = 'kiosk.php';
                        });
                    } else {
                        throw new Error(parsedResponse.message || 'Failed to generate queue');
                    }
                } catch (error) {
                    console.error('Error processing response:', error);
                    showError('Error', 'Failed to generate queue number. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showError('Submission Failed', 'There was a problem submitting your request. Please try again.');
            }
        });
    }

    function printQueueTicket(data) {
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        const ticketHTML = `
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; width: 300px; }
                    .ticket { text-align: center; }
                    .queue-number { font-size: 24px; font-weight: bold; margin: 10px 0; }
                    .info { font-size: 14px; margin: 5px 0; }
                    .footer { font-size: 12px; margin-top: 15px; }
                </style>
            </head>
            <body>
                <div class="ticket">
                    <h2>Queue Ticket</h2>
                    <div class="queue-number">${data.queue_number}</div>
                    <div class="info">Window: ${formData.step2.window_number}</div>
                    <div class="info">Service: ${formData.step3.service}</div>
                    <div class="info">Name: ${formData.step1.name}</div>
                    <div class="info">Date: ${new Date().toLocaleDateString()}</div>
                    <div class="info">Time: ${new Date().toLocaleTimeString()}</div>
                    <div class="footer">Please wait for your number to be called</div>
                </div>
            </body>
            </html>
        `;

        iframe.contentWindow.document.open();
        iframe.contentWindow.document.write(ticketHTML);
        iframe.contentWindow.document.close();
        
        setTimeout(() => {
            iframe.contentWindow.print();
            document.body.removeChild(iframe);
        }, 500);
    }

    // Window status updates
    function updateWindows() {
        $.ajax({
            url: 'kiosk.php?action=fetch_windows',
            type: 'GET',
            success: function(windows) {
                windows.forEach(window => {
                    const element = $(`.window-option[data-window="${window.window_number}"]`);
                    element.attr('data-status', window.status);
                    element.find('span').text(window.status);
                    
                    if (window.status === 'Open') {
                        element.removeClass('bg-red-100 border-red-500 opacity-50')
                               .addClass('bg-green-100 border-green-500')
                               .css('cursor', 'pointer');
                    } else {
                        element.removeClass('bg-green-100 border-green-500')
                               .addClass('bg-red-100 border-red-500 opacity-50')
                               .css('cursor', 'not-allowed');
                    }
                });
            },
            error: function(error) {
                console.error('Error updating windows:', error);
            }
        });
    }

    // Initialize
    $(document).ready(function() {
        updateProgressBar();
        setInterval(updateWindows, 5000);

        // Event handlers
        $('#windows-grid').on('click', '.window-option', function() {
            selectWindow($(this).data('window'), $(this).data('status'));
        });

        $('#contact').on('input', function() {
            $(this).val($(this).val().replace(/\D/g, '').substr(0, 11));
        });

        $('#name').on('input', function() {
            $(this).val($(this).val().replace(/[^a-zA-Z\s-]/g, ''));
        });
    });
    </script>