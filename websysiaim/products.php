<?php
session_start();
require("db_connect.php");

/* INIT CART */
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

/* ADD TO CART */
if (isset($_POST['buy'])) {
    $product_id = $_POST['product_id'];

    /* CHECK STOCK FIRST */
    $stmt = $conn->prepare("SELECT stock FROM Products WHERE product_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $resultStock = $stmt->get_result();
    $product = $resultStock->fetch_assoc();

    

    if ($product && $product['stock'] > 0) {

        if (!isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id] = 1;
            require_once 'functions.php';

sendToGoogleSheet(
    "Add to Cart",
    "Product ID: " . $product_id,
    "Added to cart (Qty: " . ($_SESSION['cart'][$product_id] ?? 1) . ")",
    "Pending"
);
        } else {
            $_SESSION['cart'][$product_id]++;

            /* prevent exceeding stock */
            if ($_SESSION['cart'][$product_id] > $product['stock']) {
                $_SESSION['cart'][$product_id] = $product['stock'];
            }
        }

    } else {
        echo "<script>alert('Out of stock!');</script>";
    }

    header("Location: products.php");
    exit();
}

/* ADD STOCK */
if (isset($_POST['add_stock'])) {

    $product_id = $_POST['product_id'];
    $stock_added = $_POST['stock_added'];

    $stock_added = (int)$stock_added;

    if ($stock_added > 0) {

        $stmt = $conn->prepare("
            UPDATE Products
            SET stock = stock + ?
            WHERE product_id = ?
        ");

        $stmt->bind_param("ii", $stock_added, $product_id);
        $stmt->execute();
    }
}

/* FETCH PRODUCTS */
$result = $conn->query("SELECT * FROM Products");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Products</title>

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

        .topbar{
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .title{
            font-size: 30px;
            font-weight: 600;
            color: #ff4f87;
        }

        .profile{
            background: white;
            padding: 10px 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        /* CART LINK */
        .cart-link{
            display: inline-block;
            margin-bottom: 20px;
            text-decoration: none;
            background: #5c7cfa;
            color: white;
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 500;
        }

        .cart-link:hover{
            background: #3b5bdb;
        }

        /* PRODUCT CARD */
        .product{
            background: white;
            padding: 18px;
            margin-bottom: 15px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);

            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product img{
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
        }

        .product b{
            font-size: 16px;
            color: #333;
        }

        .price{
            color: #ff4f87;
            font-weight: 600;
            margin-top: 5px;
        }

        /* BUTTON */
        button{
            padding: 10px 16px;
            background: #ff4f87;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 500;
            transition: 0.3s;
        }

        button:hover{
            background: #ff2f72;
        }

        /* PRODUCT GRID */
        .product-grid{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        /* PRODUCT CARD FIX */
        .product{
            background: white;
            padding: 18px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);

            display: flex;
            flex-direction: column;

            min-height: 420px;
        }   

        /* FIX RIGHT SIDE OVERFLOW ISSUE */
        .product div{
            width: 100%;
        }

        /* BUTTON ALIGNMENT */
        .product form{
            margin-top: 10px;
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
        <a href="bookings.php">Bookings</a>
        <a href="payment.php">Payments</a>
        <a href="products.php">Products</a>
    </div>

</div>

<!-- MAIN -->
<div class="main">

    <div class="topbar">
        <div class="title">Products</div>

        <div class="profile">
            <?php echo $_SESSION['staff_firstname'] ?? "User"; ?>
        </div>
    </div>

    <!-- CART -->
    <a href="cart.php" class="cart-link">
        🛒 View Cart (<?php echo array_sum($_SESSION['cart']); ?>)
    </a>

    <!-- PRODUCTS -->
     <div class="product-grid">
    <?php while ($row = $result->fetch_assoc()) { ?>
    <div class="product">

        <!-- PRODUCT IMAGE -->
        <img src="uploads/<?php echo $row['product_image']; ?>">

        <!-- PRODUCT INFO -->
        <div>
            <b style="display:block; margin-top:10px;">
                <?php echo $row['product_name']; ?>
            </b>

            <div class="price">₱<?php echo $row['product_price']; ?></div>

            <div style="color: gray; font-size: 13px;">
                Stock: <?php echo $row['stock']; ?>
            </div>
        </div>

        <!-- ADD TO CART -->
        <form method="POST">
            <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">

            <button type="submit" name="buy"
                <?php if ($row['stock'] <= 0) echo "disabled style='background: gray; cursor: not-allowed;'"; ?>>
                Add to Cart
            </button>
        </form>

<!-- ADD STOCK BUTTON (NEW) -->
<form method="POST" style="margin-top:8px;">
    <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">

    <input type="number"
           name="stock_added"
           min="1"
           placeholder="Enter stock"
           required
           style="width:100px; padding:5px; margin-right:5px;">

    <button type="submit" name="add_stock" style="background:#28a745;">
        ➕ Add Stock
    </button>
</form>
</div>
    <?php } ?>

</div>

</body>
</html>