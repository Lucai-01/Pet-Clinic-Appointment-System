<?php
require("db_connect.php");

$order_id = $_GET['order_id'] ?? 0;
$cash = isset($_GET['cash']) ? floatval($_GET['cash']) : 0;

if ($order_id == 0) {
    die("Invalid receipt.");
}

/* ORDER (FIXED: LEFT JOIN so NULL patient won't break it) */
$stmt = $conn->prepare("
    SELECT o.*, p.patient_name
    FROM Orders o
    LEFT JOIN Patient p ON o.patient_id = p.patient_id
    WHERE o.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();

$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    die("Order not found. ID: " . $order_id);
}

$total = $order['total_amount'] ?? 0;
$change = $cash - $total;

/* ITEMS */
$stmt = $conn->prepare("
    SELECT oi.*, pr.product_name
    FROM Order_items oi
    JOIN Products pr ON oi.product_id = pr.product_id
    WHERE oi.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();

$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

    <title>Receipt</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body{
            background: #fff5f7;
            display: flex;
        }

        .sidebar{
            width: 240px;
            height: 100vh;
            background: #ff4f87;
            position: fixed;
            left: 0;
            top: 0;
            padding: 30px 20px;
        }

        .logo{
            text-align: center;
            font-size: 40px;
            margin-bottom: 40px;
            color: white;
        }

        .menu a{
            display: block;
            text-decoration: none;
            color: white;
            padding: 14px;
            margin-bottom: 10px;
            border-radius: 10px;
            transition: 0.3s;
            font-weight: 500;
        }

        .menu a:hover{
            background: white;
            color: #ff4f87;
        }

        .main{
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
        }

        .title{
            font-size: 30px;
            font-weight: 600;
            color: #ff4f87;
            margin-bottom: 20px;
        }

        .receipt-box{
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .receipt-header{
            margin-bottom: 20px;
        }

        .receipt-header h2{
            color: #ff4f87;
        }

        .info{
            margin: 5px 0;
        }

        .item{
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .payment-info{
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px dashed #ff4f87;
        }

        .total{
            margin-top: 15px;
            font-size: 18px;
            font-weight: 600;
        }

        .highlight{
            color: #ff4f87;
            font-weight: 600;
        }

        .btn{
            display: inline-block;
            margin-top: 20px;
            padding: 12px 20px;
            background: #ff4f87;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
        }

        .btn:hover{
            background: #ff2f72;
        }
    </style>

</head>

<body>

<div class="sidebar">
    <div class="logo">🐾</div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="patient.php">Patients</a>
        <a href="cart.php">Cart</a>
        <a href="payment.php">Payments</a>
    </div>
</div>

<div class="main">

    <div class="title">Receipt</div>

    <div class="receipt-box">

        <div class="receipt-header">
            <h2>🧾 Receipt #<?= $order_id ?></h2>

            <div class="info">
                <b>Patient:</b>
                <?= $order['patient_name'] ?? 'Walk-in Customer' ?>
            </div>
        </div>

        <h3>Items</h3>

        <?php while ($row = $items->fetch_assoc()): ?>
            <div class="item">
                <span><?= $row['product_name'] ?> (x<?= $row['quantity'] ?>)</span>
                <span>₱<?= $row['subtotal'] ?></span>
            </div>
        <?php endwhile; ?>

        <div class="payment-info">
            <p class="total">Total: ₱<?= $order['total_amount'] ?></p>
            <p>Cash: ₱<?= number_format($cash, 2) ?></p>
            <p class="highlight">Change: ₱<?= number_format($change, 2) ?></p>
        </div>

        <a href="products.php" class="btn">New Order</a>
        <a href="dashboard.php" class="btn">Back to Dashboard</a>

    </div>

</div>

</body>
</html>