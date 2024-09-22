<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Right Sidebar Example</title>
    <link rel="stylesheet" href="Cashier_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <h2 class="ProductHeader">St Vincent Hardware</h2>
    
    <div class="sidebar">
        <h3> 
            <ul class="menu">
                <li><a href="Cashier_dashboard.php"><i class='fa fa-plus'></i>Dashboard</a></li>
                <li><a href="transaction.php"><i class='fa fa-plus'></i> New Transaction</a></li>
                <li><a href="#"><i class='fa fa-percent'></i> Add Discount</a></li>
                <li><a href="#"><i class='fa fa-chart-line'></i> Daily Sales</a></li>
                <li><a href="#"><i class='fa fa-trash'></i> Clear Cart</a></li>
                <li><a href="#"><i class='fa fa-cogs'></i> User Settings</a></li>
                <li><a href="#"><i class='fa fa-sign-out'></i> Logout</a></li>
            </ul>
        </h3>
    </div>

    <div class="container">
        <div class="orange" id="box1">
            <i class='fa fa-chart-line'></i> 10,500.00 <br> DAILY SALES
        </div>
        <div class="yellow" id="box2">
            <i class='fa fa-box'></i> 5,782 <br> STOCK ON HAND
        </div>
        <div class="green" id="box3">
            <i class='fa fa-exclamation-triangle'></i> 3<br> CRITICAL ITEMS
        </div>
    </div>

    <div class="pie-chart">
        <div class="slice" style="--percentage: 30;"></div>
        <div class="slice" style="--percentage: 20;"></div>
        <div class="text-at-40">2023 SALES </div>
        <div class="text-at-60">2024 SALES</div>
        <div class="slice" style="--percentage: 50;"></div>
        <div class="inner-circle"></div>
    </div>

    <div class="small-container">
        <div class="first box-container">2023</div>
        <div class="second box-container">2024</div>
    </div>

    <div class="sales">
        <h2>0.00</h2>
    </div>

    <div class="boxes">
        <div id="clock" class="time"></div>
        <div id="date" class="date"></div>
        <div class="total">Sales TOTAL  0.00 </div>
        <div class="vat">Vat TOTAL  0.00 </div>
    </div>

    <script src="dashboard.js"></script>
</body>
</html>
