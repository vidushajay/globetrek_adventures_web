<?php
// payment_notify.php
// PayHere calls this URL directly, server-to-server (not through the customer's browser).
// This is the ONLY place that should be trusted to mark a payment as genuinely paid -
// the customer's browser redirect (payment_success.php) can be spoofed or interrupted,
// but this notify call is signed with your merchant secret and verified below.

require 'db_connect.php';
require 'payhere_config.php';

$merchant_id      = isset($_POST['merchant_id']) ? $_POST['merchant_id'] : '';
$order_id         = isset($_POST['order_id']) ? $_POST['order_id'] : '';
$payhere_amount   = isset($_POST['payhere_amount']) ? $_POST['payhere_amount'] : '';
$payhere_currency = isset($_POST['payhere_currency']) ? $_POST['payhere_currency'] : '';
$status_code      = isset($_POST['status_code']) ? $_POST['status_code'] : '';
$md5sig           = isset($_POST['md5sig']) ? $_POST['md5sig'] : '';
$payment_id       = isset($_POST['payment_id']) ? $_POST['payment_id'] : '';

$local_hashed_secret = strtoupper(md5($payhere_merchant_secret));
$local_md5sig = strtoupper(
    md5($merchant_id . $order_id . $payhere_amount . $payhere_currency . $status_code . $local_hashed_secret)
);

// Only proceed if the signature genuinely matches AND PayHere reports success (status_code 2)
if ($local_md5sig === $md5sig && $status_code == 2) {

    $stmt = $conn->prepare(
        "UPDATE bookings SET payment_status = 'paid' WHERE payhere_order_id = ?"
    );
    $stmt->bind_param("s", $order_id);
    $stmt->execute();
    $stmt->close();

    http_response_code(200);
    echo "OK";
} else {
    // Signature mismatch or payment not successful - do NOT mark as paid
    http_response_code(400);
    echo "Invalid signature or unsuccessful payment";
}
?>
