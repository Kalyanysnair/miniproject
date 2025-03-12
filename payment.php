<?php
// Initialize session
session_start();

// Display error message if it exists
$error_message = '';
if(isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message
}

// Process the form only if it was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Initialize response array
    $response = array(
        'success' => false,
        'message' => ''
    );

    // Get form data and sanitize inputs
    $cardName = isset($_POST['cardName']) ? filter_var($_POST['cardName'], FILTER_SANITIZE_STRING) : '';
    $cardNumber = isset($_POST['cardNumber']) ? preg_replace('/\D/', '', $_POST['cardNumber']) : '';
    $expiry = isset($_POST['expiry']) ? filter_var($_POST['expiry'], FILTER_SANITIZE_STRING) : '';
    $cvv = isset($_POST['cvv']) ? filter_var($_POST['cvv'], FILTER_SANITIZE_STRING) : '';
    $email = isset($_POST['email']) ? filter_var($_POST['email'], FILTER_SANITIZE_EMAIL) : '';
    
    // Validate inputs
    $errors = array();
    
    // Validation logic remains the same...
    // [All your validation code]
    
    // Process payment if no errors
    if (empty($errors)) {
        // In a real application, you would integrate with a payment gateway here
        // This is just a simulation
        
        // For demo purposes, we'll pretend the payment was successful
        $paymentSuccessful = true;
        
        if ($paymentSuccessful) {
            // Record transaction in database (in a real application)
            // generateTransactionRecord($cardName, $email, 149.99);
            
            // Prepare success response
            $response['success'] = true;
            $response['message'] = 'Payment processed successfully! An email confirmation has been sent.';
            
            // Send email confirmation (in a real application)
            // sendConfirmationEmail($email);
            
            // Redirect to success page
            header('Location: payment_success.php');
            exit;
        } else {
            $response['message'] = 'Payment failed. Please try again or contact support.';
        }
    } else {
        // Errors found, return first error
        $response['message'] = $errors[0];
    }
    
    // Redirect to error page if there are issues
    if (!$response['success']) {
        $_SESSION['error_message'] = $response['message'];
        header('Location: payment.php?error=1');
        exit;
    }
}

// Helper functions
function validateCardNumber($number) {
    // Your existing function
}

function checkExpiryDate($month, $year) {
    // Your existing function
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link href="assets/css/main.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-image: url('assets/assets/img/template/Groovin/hero-carousel/road.jpg'); /* Replace with your background image */
            background-size: cover;
            background-position: center;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .container {
            width: 90%;
            max-width: 450px;
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            padding: 25px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        /* .header h1 {
            font-size: 24px;
            color: #333;
        } */ */
        
        .amount {
            background-color: #f5f5f5;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .amount h2 {
            font-size: 28px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .error {
            color: red;
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }
        
        .system-error {
            color: red;
            font-size: 14px;
            margin-bottom: 15px;
            text-align: center;
            display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;
        }
        
        .row {
            display: flex;
            gap: 15px;
        }
        
        .row .form-group {
            flex: 1;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        button:hover {
            background-color: #45a049;
        }
        
        .secure-text {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
<!-- <?php include 'header.php';?>  -->
    

    
    <div class="container">
    
        
        <div class="amount">
            <h2>$149.99</h2>
        </div>
        
        <?php if(!empty($error_message)): ?>
        <div class="system-error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
        <?php endif; ?>
        
        <form id="paymentForm" action="process_payment.php" method="POST">
            <div class="form-group">
                <label for="cardName">Cardholder Name</label>
                <input type="text" id="cardName" name="cardName" required>
                <div id="nameError" class="error">Please enter the name as it appears on your card</div>
            </div>
            
            <div class="form-group">
                <label for="cardNumber">Card Number</label>
                <input type="text" id="cardNumber" name="cardNumber" maxlength="19" required>
                <div id="cardError" class="error">Please enter a valid card number</div>
            </div>
            
            <div class="row">
                <div class="form-group">
                    <label for="expiry">Expiry Date (MM/YY)</label>
                    <input type="text" id="expiry" name="expiry" maxlength="5" placeholder="MM/YY" required>
                    <div id="expiryError" class="error">Please enter a valid expiry date</div>
                </div>
                
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" maxlength="3" required>
                    <div id="cvvError" class="error">Please enter a valid CVV</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
                <div id="emailError" class="error">Please enter a valid email address</div>
            </div>
            
            <button type="submit" id="submitBtn">Pay Now</button>
        </form>
        
        <div class="secure-text">
            <p>Secure Payment Processing</p>
        </div>
    </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const cardNumber = document.getElementById('cardNumber');
            const expiry = document.getElementById('expiry');
            const cvv = document.getElementById('cvv');
            const email = document.getElementById('email');
            const cardName = document.getElementById('cardName');
            
            // Format card number with spaces
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = '';
                
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) {
                        formattedValue += ' ';
                    }
                    formattedValue += value[i];
                }
                
                e.target.value = formattedValue;
            });
            
            // Format expiry date
            expiry.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                
                if (value.length > 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                
                e.target.value = value;
            });
            
            // Allow only numbers for CVV
            cvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
            
            // Form validation
            form.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Validate card name
                if (cardName.value.trim().length < 3) {
                    document.getElementById('nameError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('nameError').style.display = 'none';
                }
                
                // Validate card number (simple Luhn algorithm check)
                const cardVal = cardNumber.value.replace(/\s/g, '');
                if (cardVal.length < 13 || !luhnCheck(cardVal)) {
                    document.getElementById('cardError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('cardError').style.display = 'none';
                }
                
                // Validate expiry date
                const expiryVal = expiry.value;
                const expiryRegex = /^(0[1-9]|1[0-2])\/([0-9]{2})$/;
                
                if (!expiryRegex.test(expiryVal)) {
                    document.getElementById('expiryError').style.display = 'block';
                    isValid = false;
                } else {
                    const parts = expiryVal.split('/');
                    const month = parseInt(parts[0], 10);
                    const year = parseInt('20' + parts[1], 10);
                    
                    const now = new Date();
                    const currentYear = now.getFullYear();
                    const currentMonth = now.getMonth() + 1;
                    
                    if (year < currentYear || (year === currentYear && month < currentMonth)) {
                        document.getElementById('expiryError').style.display = 'block';
                        isValid = false;
                    } else {
                        document.getElementById('expiryError').style.display = 'none';
                    }
                }
                
                // Validate CVV
                if (cvv.value.length !== 3 || !/^\d+$/.test(cvv.value)) {
                    document.getElementById('cvvError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('cvvError').style.display = 'none';
                }
                
                // Validate email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email.value)) {
                    document.getElementById('emailError').style.display = 'block';
                    isValid = false;
                } else {
                    document.getElementById('emailError').style.display = 'none';
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
            // Luhn algorithm for credit card validation
            function luhnCheck(cardNumber) {
                let sum = 0;
                let shouldDouble = false;
                
                for (let i = cardNumber.length - 1; i >= 0; i--) {
                    let digit = parseInt(cardNumber.charAt(i));
                    
                    if (shouldDouble) {
                        digit *= 2;
                        if (digit > 9) digit -= 9;
                    }
                    
                    sum += digit;
                    shouldDouble = !shouldDouble;
                }
                
                return (sum % 10) === 0;
            }
        });
    </script>
</body>
</html>