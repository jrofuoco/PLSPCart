<?php
require_once 'config.php';
require_once 'functions.php';

// Function to generate GCash reference number
function generateGCashReference() {
    return 'GC' . date('YmdHis') . rand(1000, 9999);
}

// Function to verify GCash payment
function verifyGCashPayment($reference, $amount) {
    // In a production environment, you would integrate with GCash's API
    // This is a simplified example
    return false;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'generate_reference':
                if (isset($_POST['order_id']) && isset($_POST['amount'])) {
                    $orderId = (int)$_POST['order_id'];
                    $amount = (float)$_POST['amount'];
                    
                    try {
                        // Generate GCash reference
                        $reference = generateGCashReference();
                        
                        // Update order with GCash details
                        $stmt = $pdo->prepare("
                            UPDATE orders 
                            SET payment_method = 'gcash',
                                gcash_reference = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$reference, $orderId]);
                        
                        $response = [
                            'success' => true,
                            'reference' => $reference,
                            'amount' => $amount,
                            'expires' => date('Y-m-d H:i:s', strtotime('+1 hour'))
                        ];
                    } catch (PDOException $e) {
                        $response['message'] = 'Error processing payment: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'verify_payment':
                if (isset($_POST['order_id']) && isset($_POST['reference'])) {
                    $orderId = (int)$_POST['order_id'];
                    $reference = $_POST['reference'];
                    
                    try {
                        // Get order details
                        $stmt = $pdo->prepare("
                            SELECT gcash_reference, total_amount 
                            FROM orders 
                            WHERE id = ?
                        ");
                        $stmt->execute([$orderId]);
                        $order = $stmt->fetch();
                        
                        if ($order) {
                            // Verify payment
                            $isPaid = verifyGCashPayment($order['gcash_reference'], $order['total_amount']);
                            
                            if ($isPaid) {
                                // Update order status
                                $stmt = $pdo->prepare("
                                    UPDATE orders 
                                    SET status = 'paid',
                                        gcash_paid = TRUE,
                                        updated_at = CURRENT_TIMESTAMP
                                    WHERE id = ?
                                ");
                                $stmt->execute([$orderId]);
                                
                                $response = [
                                    'success' => true,
                                    'paid' => true
                                ];
                            } else {
                                $response = [
                                    'success' => true,
                                    'paid' => false
                                ];
                            }
                        }
                    } catch (PDOException $e) {
                        $response['message'] = 'Error verifying payment: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
} 