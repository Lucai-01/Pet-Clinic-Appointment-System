<?php
session_start();
require("db_connect.php");

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    die("Cart is empty.");
}

$cart = $_SESSION['cart'];

/* Allow NULL patient if no patient selected */
$patient_id = $_SESSION['patient_id'] ?? NULL;

$cash = $_POST['cash'] ?? 0;

$conn->begin_transaction();

try {

    /* COMPUTE TOTAL */
    $total_amount = 0;

    foreach ($cart as $product_id => $qty) {

        $stmt = $conn->prepare("
            SELECT product_price, stock
            FROM Products
            WHERE product_id=?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();
        

        if (!$product) {
            throw new Exception("Product not found.");
        }

        /* Check stock */
        if ($qty > $product['stock']) {
            throw new Exception(
                "Not enough stock for product ID ".$product_id
            );
        }

        $total_amount += $product['product_price'] * $qty;
        
    }


    /* PAYMENT VALIDATION */

    if ($cash < $total_amount) {
        throw new Exception(
            "Insufficient cash. Total is ₱".$total_amount
        );
    }

    $change = $cash - $total_amount;

    $status = "Paid";


    /* VERIFY PATIENT EXISTS */

    if ($patient_id !== NULL) {

        $stmt = $conn->prepare("
            SELECT patient_id
            FROM Patient
            WHERE patient_id=?
        ");

        $stmt->bind_param("i",$patient_id);
        $stmt->execute();

        if($stmt->get_result()->num_rows==0){
            $patient_id=NULL;
        }
    }


    /* INSERT ORDER */

    $stmt = $conn->prepare("
        INSERT INTO Orders
        (
            patient_id,
            total_amount,
            order_status
        )
        VALUES
        (
            ?, ?, ?
        )
    ");

    $stmt->bind_param(
        "ids",
        $patient_id,
        $total_amount,
        $status
    );

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    $order_id = $conn->insert_id;


    /* INSERT PAYMENT */

    $payment_type="Cash";
    $payment_status="Paid";

    $stmt=$conn->prepare("
        INSERT INTO Payment
        (
            patient_id,
            order_id,
            payment_type,
            payment_amount,
            payment_status,
            payment_date
        )
        VALUES
        (
            ?, ?, ?, ?, ?, NOW()
        )
    ");

    $stmt->bind_param(
        "iisds",
        $patient_id,
        $order_id,
        $payment_type,
        $total_amount,
        $payment_status
    );

    $stmt->execute();


    /* ORDER ITEMS + STOCK UPDATE */

    foreach($cart as $product_id=>$qty){

        $stmt=$conn->prepare("
            SELECT product_price, product_name
            FROM Products
            WHERE product_id=?
        ");

        $stmt->bind_param("i",$product_id);
        $stmt->execute();

        $product=$stmt->get_result()->fetch_assoc();

        $subtotal=
            $product['product_price']*$qty;
        $service_id=NULL;
        require_once 'functions.php';

        sendToGoogleSheet(
          "Product Order",
            $product['product_name'],
            "Quantity: $qty | Subtotal: ₱$subtotal",
            "Completed"
            );

        /* Insert order item */

        $stmt=$conn->prepare("
            INSERT INTO Order_items
            (
                order_id,
                product_id,
                service_id,
                quantity,
                subtotal
            )
            VALUES
            (
                ?, ?, ?, ?, ?
            )
        ");

        $stmt->bind_param(
            "iiiid",
            $order_id,
            $product_id,
            $service_id,
            $qty,
            $subtotal
        );

        $stmt->execute();


        /* Reduce stock */

        $stmt=$conn->prepare("
            UPDATE Products
            SET stock=stock-?
            WHERE product_id=?
        ");

        $stmt->bind_param(
            "ii",
            $qty,
            $product_id
        );

        $stmt->execute();
    }


    /* CLEAR CART */

    unset($_SESSION['cart']);

    $conn->commit();


    /* GO TO RECEIPT */

    header("Location: receipt.php?order_id=$order_id&cash=$cash");
exit();

}
catch(Exception $e){

    $conn->rollback();

    die(
        "Checkout failed: ".$e->getMessage()
    );
}
?>