<?php
session_start();
require("db_connect.php");

/* =========================
   HANDLE ACTIONS
========================= */

if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit();
}

if (isset($_GET['plus'])) {
    $id = $_GET['plus'];
    $_SESSION['cart'][$id]++;
    header("Location: cart.php");
    exit();
}

if (isset($_GET['minus'])) {
    $id = $_GET['minus'];

    if ($_SESSION['cart'][$id] > 1) {
        $_SESSION['cart'][$id]--;
    } else {
        unset($_SESSION['cart'][$id]);
    }

    header("Location: cart.php");
    exit();
}

    $cart = $_SESSION['cart'] ?? [];

    if (empty($cart)) {
        ?>
        
        <!DOCTYPE html>
        <html>
        <head>
            <title>Cart</title>

            <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

            <style>
                body{
                    margin:0;
                    font-family: 'Poppins', sans-serif;
                    background:#fff5f7;
                    display:flex;
                    justify-content:center;
                    align-items:center;
                    height:100vh;
                }

                .empty-box{
                    background:white;
                    padding:40px;
                    border-radius:20px;
                    text-align:center;
                    box-shadow:0 4px 15px rgba(0,0,0,0.08);
                    max-width:400px;
                }

                .empty-icon{
                    font-size:60px;
                    margin-bottom:10px;
                }

                h2{
                    color:#ff4f87;
                    margin-bottom:10px;
                }

                p{
                    color:gray;
                    margin-bottom:20px;
                }

                .btn{
                    display:inline-block;
                    padding:12px 20px;
                    background:#ff4f87;
                    color:white;
                    text-decoration:none;
                    border-radius:10px;
                    font-weight:500;
                }

                .btn:hover{
                    background:#ff2f72;
                }
            </style>

        </head>
        <body>

            <div class="empty-box">

                <div class="empty-icon">🛒</div>

                <h2>Your cart is empty</h2>

                <p>Add products first before checking out.</p>

                <a href="products.php" class="btn">Go Shopping</a>

            </div>

        </body>
        </html>

        <?php
        exit();
    }
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Cart</title>

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

        /* SIDEBAR */

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

        /* MAIN */

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

        /* TABLE */

        .table-container{
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow-x: auto;
        }

        table{
            width: 100%;
            border-collapse: collapse;
        }

        th{
            background: #ff4f87;
            color: white;
            padding: 14px;
            text-align: left;
        }

        td{
            padding: 14px;
            border-bottom: 1px solid #eee;
        }

        tr:hover{
            background: #fff0f4;
        }

        /* ACTION BUTTONS */

        .btn{
            text-decoration: none;
            padding: 6px 12px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            margin-right: 5px;
            display: inline-block;
        }

        .plus{
            background: #28c76f;
        }

        .minus{
            background: #ffb347;
        }

        .remove{
            background: #ff4d6d;
        }

        .plus:hover{ background:#1faa59; }
        .minus:hover{ background:#ff9f1c; }
        .remove:hover{ background:#e63946; }

        /* PAYMENT */

        .payment-box{
            margin-top: 20px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .payment-box input{
            width: 100%;
            padding: 12px;
            border: 2px solid #ffd1dc;
            border-radius: 10px;
            margin-top: 8px;
        }

        .checkout-btn{
            margin-top: 15px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: #ff4f87;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .checkout-btn:hover{
            background: #ff2f72;
        }

        .total{
            margin-top: 15px;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .empty{
            text-align: center;
            padding: 50px;
            color: gray;
        }

    </style>

</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="logo">🐾</div>

    <div class="menu">
        <a href="dashboard.php">Dashboard</a>
        <a href="patient.php">Patients</a>
        <a href="cart.php">Cart</a>
        <a href="payment.php">Payments</a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <div class="title">Shopping Cart</div>

    <div class="table-container">

        <table>

            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Action</th>
                <th>Subtotal</th>
            </tr>

<?php foreach ($cart as $product_id => $qty): ?>

    <?php
        $stmt = $conn->prepare("SELECT product_name, product_price FROM Products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();

        $subtotal = $product['product_price'] * $qty;
        $total += $subtotal;
    ?>

    <tr>

        <td><?= $product['product_name'] ?></td>
        <td>₱<?= $product['product_price'] ?></td>
        <td><?= $qty ?></td>

        <td>
            <a class="btn minus" href="cart.php?minus=<?= $product_id ?>">➖</a>
            <a class="btn plus" href="cart.php?plus=<?= $product_id ?>">➕</a>
            <a class="btn remove" href="cart.php?remove=<?= $product_id ?>">🗑</a>
        </td>

        <td>₱<?= $subtotal ?></td>

    </tr>

<?php endforeach; ?>

        </table>

        <div class="total">
            Total: ₱<?= $total ?>
        </div>

        <!-- PAYMENT -->
        <div class="payment-box">

            <form action="checkout.php" method="POST">

                <label>Cash Amount</label>
                <input type="number" name="cash" step="0.01" required>

                <button class="checkout-btn" type="submit">
                    Checkout
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>