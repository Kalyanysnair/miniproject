<?php
session_start();
require 'connect.php';

// Check if driver is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$driver_id = $_SESSION['user_id'];
$error_message = "";
$success_message = "";

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch role from `tbl_user` instead of `tbl_driver`
$is_palliative_driver = false;
$check_driver_query = "SELECT role FROM tbl_user WHERE userid = ?";
$stmt = $conn->prepare($check_driver_query);

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $driver_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $is_palliative_driver = ($row['role'] === 'palliative');
}
$stmt->close();

// Handle request completion
if (isset($_POST['complete_request'])) {
    $request_type = isset($_POST['request_type']) ? trim($_POST['request_type']) : '';
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;

    if ($request_id > 0 && !empty($request_type)) {
        try {
            // Select the correct table based on request type
            switch ($request_type) {
                case 'palliative':
                    $update_query = "UPDATE tbl_palliative 
                                     SET status = 'Completed', updated_at = CURRENT_TIMESTAMP, amount = ? 
                                     WHERE palliativeid = ? AND driver_id = ?";
                    break;
                case 'prebooking':
                    $update_query = "UPDATE tbl_prebooking 
                                     SET status = 'Completed', amount = ? 
                                     WHERE prebookingid = ? AND driver_id = ?";
                    break;
                case 'emergency':
                    $update_query = "UPDATE tbl_emergency 
                                     SET status = 'Completed', updated_at = CURRENT_TIMESTAMP, amount = ? 
                                     WHERE request_id = ? AND driver_id = ?";
                    break;
                default:
                    throw new Exception("Invalid request type: " . htmlspecialchars($request_type));
            }

            $stmt = $conn->prepare($update_query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("dii", $amount, $request_id, $driver_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $success_message = "Request marked as completed successfully!";
                } else {
                    $error_message = "No matching record found. Please check the request and driver IDs.";
                }
            } else {
                throw new Exception("Failed to update request status: " . $stmt->error);
            }

            $stmt->close();
        } catch (Exception $e) {
            $error_message = "An error occurred while updating the request: " . $e->getMessage();
            error_log("Database error: " . $e->getMessage());
        }
    } else {
        $error_message = "Invalid request data. Please try again.";
    }
}

// Fetch jobs for the driver
try {
    if ($is_palliative_driver) {
        $jobs_query = "(SELECT 
            e.request_id, 
            e.userid,
            e.pickup_location,
            e.contact_phone,
            e.driver_id,
            e.status,
            e.created_at,
            e.updated_at,
            e.patient_name,
            e.amount,
            e.payment_status,
            'emergency' as request_type,
            u.username as requester_name,
            u.phoneno as contact_phone
        FROM tbl_emergency e
        LEFT JOIN tbl_user u ON e.userid = u.userid
        WHERE e.driver_id = ?)
        UNION ALL
        (SELECT 
            p.palliativeid as request_id,
            p.userid,
            p.address as pickup_location,
            u.phoneno as contact_phone,
            p.driver_id,
            p.status,
            p.created_at,
            p.updated_at,
            u.username as patient_name,
            p.amount,
            p.payment_status,
            'palliative' as request_type,
            u.username as requester_name,
            u.phoneno as contact_phone
        FROM tbl_palliative p
        LEFT JOIN tbl_user u ON p.userid = u.userid
        WHERE p.driver_id = ? AND p.status IN ('Pending', 'Approved', 'Completed'))
        ORDER BY created_at DESC";
    } else {
        $jobs_query = "(SELECT 
            e.request_id,
            e.userid,
            e.pickup_location,
            e.contact_phone,
            e.driver_id,
            e.status,
            e.created_at,
            e.updated_at,
            e.patient_name,
            e.amount,
            'emergency' as request_type,
            u.username as requester_name,
            u.phoneno as contact_phone
        FROM tbl_emergency e
        LEFT JOIN tbl_user u ON e.userid = u.userid
        WHERE e.driver_id = ?)
        UNION ALL
        (SELECT 
            p.prebookingid as request_id,
            p.userid,
            p.pickup_location,
            u.phoneno as contact_phone,
            p.driver_id,
            p.status,
            p.created_at,
            p.created_at as updated_at,
            u.username as patient_name,
            p.amount,
            'prebooking' as request_type,
            u.username as requester_name,
            u.phoneno as contact_phone
        FROM tbl_prebooking p
        LEFT JOIN tbl_user u ON p.userid = u.userid
        WHERE p.driver_id = ? AND p.status IN ('Pending', 'Accepted', 'Completed'))
        ORDER BY created_at DESC";
    }

    $stmt = $conn->prepare($jobs_query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $driver_id, $driver_id);
    $stmt->execute();
    $jobs = $stmt->get_result();

    // Debugging: Log the number of rows returned
    error_log("Number of rows fetched: " . $jobs->num_rows);

    $stmt->close();
} catch (Exception $e) {
    $error_message = "Failed to fetch job history: " . $e->getMessage();
    error_log("Database error: " . $e->getMessage());
}

?>

<!-- Rest of the HTML remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Job History</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/ambulance2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
            padding-top: 100px; /* Space for fixed header */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .job-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .job-card:hover {
            transform: translateY(-5px);
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .request-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Accepted { background: #d4edda; color: #155724; }
        .status-Completed { background: #cce5ff; color: #004085; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }

        .type-emergency { background: #ffd7d7; color: #c41e3a; }
        .type-palliative { background: #d7ffd7; color: #1e8449; }
        .type-prebooking { background: #d7d7ff; color: #1e3a8a; }

        .complete-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .complete-btn:hover {
            background: #218838;
        }

        .complete-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }

        .alert {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #155724;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #721c24;
        }

        .job-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .detail-item {
            padding: 8px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 5px;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #212529;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .job-details {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 10px;
            }
            
            .glass-card {
                padding: 15px;
            }
            
            body {
                padding-top: 80px;
            }
        }
        .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    position: relative;
    background-color: #fff;
    margin: 15% auto;
    padding: 20px;
    border-radius: 10px;
    max-width: 500px;
    animation: modalopen 0.4s;
}

@keyframes modalopen {
    from {opacity: 0; transform: translateY(-60px);}
    to {opacity: 1; transform: translateY(0);}
}

.modal-title {
    margin-top: 0;
    color: #333;
}

.close-modal {
    position: absolute;
    right: 20px;
    top: 10px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    color: #aaa;
}

.close-modal:hover {
    color: #333;
}

.amount-field {
    width: 100%;
    padding: 10px;
    margin: 15px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.submit-amount {
    background: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
}

.submit-amount:hover {
    background: #218838;
}
.job-details {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}
.amount-display {
    font-weight: bold;
    color: #28a745;
}
.status-Pending { background: #fff3cd; color: #856404; }
.status-Paid { background: #d4edda; color: #155724; }
@media (max-width: 768px) {
    .modal-content {
        width: 90%;
        margin: 30% auto;
    }
}

    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    <div class="container">
    <div class="glass-card position-relative">
        <a href="driver.php" class="btn btn-success position-absolute" style="top: 30px; right: 30px;">Back</a>
        <h2 class="mb-4">My Job History</h2>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

            <?php if ($jobs && $jobs->num_rows > 0): ?>
                <?php while ($job = $jobs->fetch_assoc()): ?>
                    <div class="job-card">
                        <div class="job-details">
                            <div class="detail-item">
                                <div class="detail-label">Request ID</div>
                                <div class="detail-value"><?php echo htmlspecialchars($job['request_id']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Request Type</div>
                                <div class="detail-value">
                                    <span class="request-type-badge type-<?php echo htmlspecialchars($job['request_type']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($job['request_type'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Patient/Requester Name</div>
                                <div class="detail-value"><?php echo htmlspecialchars($job['patient_name']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Pickup Location</div>
                                <div class="detail-value"><?php echo htmlspecialchars($job['pickup_location']); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="status-badge status-<?php echo htmlspecialchars($job['status']); ?>">
                                        <?php echo htmlspecialchars($job['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Date</div>
                                <div class="detail-value">
                                    <?php echo date('d M Y, h:i A', strtotime($job['created_at'])); ?>
                                </div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Contact</div>
                                <div class="detail-value"><?php echo htmlspecialchars($job['contact_phone']); ?></div>
                            </div>
                            <div class="detail-item">
    <div class="detail-label">Amount</div>
    <div class="detail-value">
        <?php echo '₹' . number_format($job['amount'], 2); ?>
    </div>
</div>

                            <div class="detail-item">
            <div class="detail-label">Payment Status</div>
            <div class="detail-value">
                <span class="status-badge status-<?php echo htmlspecialchars(isset($job['payment_status']) ? $job['payment_status'] : 'Pending'); ?>">
                    <?php echo htmlspecialchars(isset($job['payment_status']) ? $job['payment_status'] : 'Pending'); ?>
                </span>
            </div>
        </div>
    </div>
                            
                          
                            
                        <!-- </div> -->
                     
      
    <?php if ($job['status'] == 'Accepted' || $job['status'] == 'Approved'): ?>
        <button type="button" 
            class="complete-btn open-modal" 
            data-requestid="<?php echo $job['request_id']; ?>" 
            data-requesttype="<?php echo $job['request_type']; ?>">
            Mark as Completed
        </button>
    <?php endif; ?>
</div>

   

                    
                <?php endwhile; ?>
            <?php else: ?>
                <p>No job history found.</p>
            <?php endif; ?>
        </div>
    </div>
     
    <div id="amountModal" class="modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h3 class="modal-title">Enter Service Amount</h3>
        <form method="POST" id="completeForm">
            <input type="hidden" name="request_id" id="modal_request_id">
            <input type="hidden" name="request_type" id="modal_request_type">
            <div>
                <input type="number" name="amount" id="service_amount" class="amount-field" placeholder="Enter amount (₹)" required min="1" step="0.01">
            </div>
            <button type="submit" name="complete_request" class="submit-amount">
                Complete Service
            </button>
        </form>
    </div>
</div>

    <script>
        // Optional: Add confirmation before marking as complete
        document.querySelectorAll('form').forEach(form => {
            form.onsubmit = function(e) {
                return confirm('Are you sure you want to mark this request as completed?');
            };
        });
    </script>
    <script>
    // Get the modal
    var modal = document.getElementById("amountModal");
    
    // Get the button that opens the modal
    var btns = document.getElementsByClassName("open-modal");
    
    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close-modal")[0];
    
    // Add click event to all "Mark as Completed" buttons
    for (var i = 0; i < btns.length; i++) {
        btns[i].onclick = function() {
            var requestId = this.getAttribute("data-requestid");
            var requestType = this.getAttribute("data-requesttype");
            
            document.getElementById("modal_request_id").value = requestId;
            document.getElementById("modal_request_type").value = requestType;
            
            modal.style.display = "block";
        }
    }
    
    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }
    
    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
    
 // Form validation
document.getElementById("completeForm").onsubmit = function(e) {
    var amount = document.getElementById("service_amount").value;
    
    if (amount <= 0 || amount === "") {
        alert("Please enter a valid amount");
        e.preventDefault();
        return false;
    }
    
    console.log("Submitting form with amount: " + amount); // Debug
    return confirm('Are you sure you want to complete this service with an amount of ₹' + amount + '?');
};
</script>
</body>
</html>

