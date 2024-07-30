<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "csd_system";

session_start();

// Establish database connection
$conn = mysqli_connect($servername, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Pagination variables
$results_per_page = 10; // Number of items per page

// Determine current page number
if (!isset($_GET['page'])) {
    $page = 1;
} else {
    $page = $_GET['page'];
}

// Calculate SQL LIMIT starting row number for the pagination formula
$start_limit = ($page - 1) * $results_per_page;

// Search functionality
$search = "";
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Handle adding items to order list
if (isset($_POST['Add_To_Order'])) {
    $item = [
        'itemId' => $_POST['itemId'],
        'name' => $_POST['name'],
        'category' => $_POST['category'],
        'description' => $_POST['description'],
        'price' => $_POST['price'],
        'stock_quantity' => $_POST['stock_quantity'],
        'remarks' => $_POST['remarks'],
        'unit' => $_POST['unit'],
        'selected_quantity' => $_POST['selected_quantity']
    ];

    if (!isset($_SESSION['order_list'])) {
        $_SESSION['order_list'] = [];
    }

    $_SESSION['order_list'][] = $item;
}

// Handle removing items from order list
if (isset($_POST['Remove_From_Order'])) {
    $index = $_POST['index'];
    if (isset($_SESSION['order_list'][$index])) {
        unset($_SESSION['order_list'][$index]);
        $_SESSION['order_list'] = array_values($_SESSION['order_list']); // Re-index the array
    }
}

// Handle adding items to cart
if (isset($_POST['Add_To_Cart'])) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    foreach ($_SESSION['order_list'] as $item) {
        $_SESSION['cart'][] = $item;
    }
    // Clear the order list after adding to the cart
    $_SESSION['order_list'] = [];
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="bootstrap.min1.css">
    <link rel="stylesheet" href="all.min.css">
    <link rel="stylesheet" href="dataTables.dataTables.min.css">
    <title>User Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f0f4f8;
            transition: background 0.5s ease-in-out;
        }

        .container {
            margin-top: 20px;
            display: flex;
        }

        .main-content {
            flex: 3;
            margin-right: 20px;
        }

        .order-list {
            flex: 1;
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .header-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            flex-wrap: wrap;
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
        }

        .header-actions h2 {
            margin: 0;
            font-weight: bold;
            color: #333;
            transition: color 0.5s ease-in-out;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px 20px;
            background-color: #ffffff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background-color: #ffffff;
            display: flex;
            flex-direction: column;
            position: relative;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card:hover {
            transform: scale(1.03);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .card img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-top: 20px;
            margin: auto;
            padding-top: 4px;
        }

        .card-body {
            padding: 15px;
            flex: 1;
        }

        .card-title {
            font-size: 1.1em;
            margin-bottom: 10px;
            color: #333;
            background-color: #e3f2fd;
            padding: 5px;
            border-radius: 3px;
        }

        .card-text {
            font-size: 0.76em;
            color: #666;
            background-color: #fafafa;
            padding: 5px;
            border-radius: 3px;
            margin-bottom: 5px;
            display: flex;
            justify-content: space-between;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background-color: #e1f5fe;
            border-top: 1px solid #ddd;
        }

        .card-footer .btn {
            transition: background-color 0.3s ease-in-out, transform 0.3s ease-in-out;
            padding: 0.375rem 0.75rem;
            font-size: 0.8em;
            margin-left: 30px;
        }

        .card-footer .btn:hover {
            transform: scale(1.05);
        }

        .select-quantity {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .select-quantity input {
            width: 60px;
            text-align: center;
        }

        @media (max-width: 900px) {
            .header-actions {
                flex-direction: column;
                align-items: flex-start;
            }

            .card-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }

        #add-btn {
            background-color: #ffcc80;
            border-color: #ffcc80;
        }

        #add-btn:hover {
            background-color: #ffb74d;
        }

        #print-btn {
            background-color: #9575cd;
            border-color: #9575cd;
        }

        #print-btn:hover {
            background-color: #7e57c2;
        }

        #logout-btn {
            background-color: #ef5350;
            border-color: #ef5350;
        }

        #logout-btn:hover {
            background-color: #e53935;
        }

        .btn-orders {
            background-color: #28a745;
            border-color: #28a745;
            color: #fff;
            margin-right: 3px;
            transition: background-color 0.3s, border-color 0.3s, transform 0.3s;
        }

        .btn-orders:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .order-list h4 {
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .order-list-item {
            border-bottom: 1px solid #ddd;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-list-item span {
            flex: 1;
        }

        .order-list-item button {
            margin-left: 10px;
            background-color: #ef5350;
            border: none;
            color: white;
            padding: 5px 10px;
            cursor: pointer;
        }

        .order-list-item button:hover {
            background-color: #e53935;
        }

        .order-summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #e3f2fd;
            border-radius: 5px;
        }

        .order-summary h5 {
            margin: 0;
            font-size: 1.1em;
        }
    </style>
</head>

<body>
    <div class="header-actions">
        <h2>User Dashboard</h2>
        <div>
            <a href="admin_logout.php" class="btn btn-primary" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <a href="javascript:void(0);" class="btn btn-primary" id="print-btn"><i class="fas fa-print"></i> Print</a>
        </div>
    </div>

    <div class="container">
        <div class="main-content">
            <div class="header-actions">
                <div>
                    <a href="manage_user.php" class="btn btn-primary" id="add-btn"><i class="fas fa-plus"></i> Add User</a>
                </div>
                <form action="user_dashboard.php" method="get" class="form-inline">
                    <div class="form-group mx-sm-3 mb-2">
                        <input type="text" class="form-control" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary mb-2"><i class="fas fa-search"></i> Search</button>
                </form>
            </div>

            <div class="card-grid">
                <?php
                $sql = "SELECT * FROM `items`";
                if (!empty($search)) {
                    $sql .= " WHERE name LIKE '%$search%'";
                }
                $sql .= " LIMIT $start_limit, $results_per_page";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                ?>
                        <div class="card">
                            <img src="food.jpg" class="card-img-top" alt="Item Image">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $row['name']; ?></h5>
                                <p class="card-text">Category: <?php echo $row['category']; ?></p>
                                <p class="card-text">Description: <?php echo $row['description']; ?></p>
                                <p class="card-text">Price: <?php echo $row['price']; ?></p>
                                <p class="card-text">Stock Quantity: <?php echo $row['stock_quantity']; ?></p>
                                <p class="card-text">Remarks: <?php echo $row['remarks']; ?></p>
                                <p class="card-text">Unit: <?php echo $row['unit']; ?></p>
                                <form action="user_dashboard.php" method="post">
                                    <input type="hidden" name="itemId" value="<?php echo $row['itemId']; ?>">
                                    <input type="hidden" name="name" value="<?php echo $row['name']; ?>">
                                    <input type="hidden" name="category" value="<?php echo $row['category']; ?>">
                                    <input type="hidden" name="description" value="<?php echo $row['description']; ?>">
                                    <input type="hidden" name="price" value="<?php echo $row['price']; ?>">
                                    <input type="hidden" name="stock_quantity" value="<?php echo $row['stock_quantity']; ?>">
                                    <input type="hidden" name="remarks" value="<?php echo $row['remarks']; ?>">
                                    <input type="hidden" name="unit" value="<?php echo $row['unit']; ?>">
                                    <div class="select-quantity">
                                        <label for="quantity">Quantity:</label>
                                        <input type="number" id="quantity" name="selected_quantity" min="1" max="<?php echo $row['stock_quantity']; ?>" value="1">
                                    </div>
                                    <div class="card-footer">
                                        <button type="submit" class="btn btn-primary" name="Add_To_Order"><i class="fas fa-plus"></i> Add to Order List</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                <?php
                    }
                } else {
                    echo "<p>No items found</p>";
                }
                ?>
            </div>

            <div class="pagination mt-3">
                <?php
                $sql = "SELECT COUNT(*) AS total FROM `items`";
                if (!empty($search)) {
                    $sql .= " WHERE name LIKE '%$search%'";
                }
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $total_pages = ceil($row['total'] / $results_per_page);

                for ($i = 1; $i <= $total_pages; $i++) {
                    echo '<a href="user_dashboard.php?page=' . $i . '" class="btn btn-secondary mr-2' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
                }
                ?>
            </div>
        </div>

        <div class="order-list">
            <h4>Order List</h4>
            <?php if (!empty($_SESSION['order_list'])) : ?>
                <?php foreach ($_SESSION['order_list'] as $index => $item) : ?>
                    <div class="order-list-item">
                        <span><?php echo $item['name']; ?> (x<?php echo $item['selected_quantity']; ?>)</span>
                        <form action="user_dashboard.php" method="post">
                            <input type="hidden" name="index" value="<?php echo $index; ?>">
                            <button type="submit" name="Remove_From_Order">Remove</button>
                        </form>
                    </div>
                <?php endforeach; ?>
                <div class="order-summary">
                    <h5>Total Items: <?php echo count($_SESSION['order_list']); ?></h5>
                </div>
                <form action="user_dashboard.php" method="post">
                    <button type="submit" class="btn btn-primary btn-block mt-2" name="Add_To_Cart"><i class="fas fa-shopping-cart"></i> Add to My Cart</button>
                </form>
            <?php else : ?>
                <p>No items in order list</p>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>

<?php
$conn->close();
?>
