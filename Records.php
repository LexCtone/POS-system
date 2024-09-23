<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Records</title>
  <link rel="stylesheet" href="CSS\Records.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <header>
    <h2 class="StockHeader">Records</h2>
  </header>
  
  <nav class="sidebar">
    <header>
      <img src="profile.png" alt="profile"/>
      <br>ADMINISTRATOR
    </header>
    <ul>
      <li><a href="Dashboard.php"><i class='fa-solid fa-house' style='font-size:30px'></i>Home</a></li>
      <li><a href="Product.php"><i class='fas fa-archive' style='font-size:30px'></i>Product</a></li>
      <li><a href="Vendor.php"><i class='fa-solid fa-user' style='font-size:30px'></i>Vendor</a></li>
      <li><a href="StockEntry.php"><i class='fa-solid fa-arrow-trend-up' style='font-size:30px'></i>Stock Entry</a></li>
      <li><a href="Brand.php"><i class='fa-solid fa-tag' style='font-size:30px'></i>Brand</a></li>
      <li><a href="Category.php"><i class='fa-solid fa-layer-group' style='font-size:30px'></i>Category</a></li>
      <li><a href="Records.php"><i class='fa-solid fa-database' style='font-size:30px'></i>Records</a></li>
      <li><a href="SalesHistory.php"><i class='fa-solid fa-clock-rotate-left' style='font-size:30px'></i>Sales History</a></li>
      <li><a href="UserSettings.php"><i class='fa-solid fa-gear' style='font-size:30px'></i>User Settings</a></li>
      <li><a href="Login.php"><i class='fa-solid fa-arrow-right-from-bracket' style='font-size:30px'></i>Logout</a></li>
    </ul>
  </nav>
  <div class="container">
    <div class="account-box">
      <div class="button-container">
        <button class="btn" onclick="location.href='Records.php'">Top Selling</button>
        <button class="btn" onclick="location.href='SoldItems.php'">Sold Items</button>
        <button class="btn" onclick="location.href='CriticalStocks.php'">Critical Stocks</button>
        <button class="btn" onclick="location.href='InventoryList.php'">Inventory List</button>
        <button class="btn" onclick="location.href='CancelledOrder.php'">Cancelled Order</button>
        <button class="btn" onclick="location.href='StockHistory.php'">Stock In History</button>
      </div>
      <div style="margin-top: 10px; border-bottom: 2px solid #ccc;"></div>
      <div class="form">
        <div class="form-group">
          <label for="startDate" class="date-label">Filter by</label>
          <input type="date" id="startDate" name="startDate" class="date-input">
          <input type="date" id="endDate" name="endDate" class="date-input">
          <select id="sortBy" class="vendor" name="sortBy">
            <option value="" selected>Sort by</option>
            <option value="quantity">Quantity</option>
            <option value="total_amount">Total Amount</option>
          </select>
          <select id="sortOrder" class="vendor" name="sortOrder">
            <option value="DESC" selected>Descending</option>
            <option value="ASC">Ascending</option>
          </select>
          <div class="load-data-button" onclick="loadData()">
            <i class="fa fa-refresh"></i>
            <span class="load-data-text">Load Data</span>
          </div>
          <div class="print-preview-button" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span class="print-preview-text">Print Preview</span>
          </div>
        </div>
      </div>

      <div class="content">
        <!-- Left Column: Table -->
        <div class="table-container">
          <table id="salesTable">
            <thead>
              <tr>
                <th>#</th>
                <th>BARCODE</th>
                <th>DESCRIPTION</th>
                <th>QUANTITY</th>
                <th>TOTAL SALES</th>
              </tr>
            </thead>
            <tbody>
              <!-- Table body will be populated by JavaScript -->
            </tbody>
          </table>
        </div>
        <!-- Right Column: Chart -->
        <div class="chart-container">
          <div class="chart-legend" id="chartLegend"></div>
          <canvas id="salesChart"></canvas>
        </div>
      </div>
    </div>
  </div>

  <style>
  .legend-item-hidden {
    text-decoration: line-through;
  }
  </style>

  <script>
  let salesChart;

  function loadData() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    const sortBy = document.getElementById('sortBy').value;
    const sortOrder = document.getElementById('sortOrder').value;

    fetch(`get_sales_data.php?startDate=${startDate}&endDate=${endDate}&sortBy=${sortBy}&sortOrder=${sortOrder}`)
      .then(response => response.json())
      .then(data => {
        updateTable(data);
        updateChart(data);
      })
      .catch(error => console.error('Error:', error));
  }

  function updateTable(data) {
    const tableBody = document.querySelector('#salesTable tbody');
    tableBody.innerHTML = '';
    data.forEach((item, index) => {
      const row = tableBody.insertRow();
      row.insertCell(0).textContent = index + 1;
      row.insertCell(1).textContent = item.barcode;
      row.insertCell(2).textContent = item.description;
      row.insertCell(3).textContent = item.total_qty;
      row.insertCell(4).textContent = '₱' + parseFloat(item.total_sales).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
  }

  function updateChart(data) {
    const labels = data.map(item => item.description);
    const salesData = data.map(item => item.total_sales);
    const backgroundColors = data.map(() => `rgba(${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, ${Math.floor(Math.random() * 255)}, 0.8)`);

    if (salesChart) {
      salesChart.destroy();
    }

    const ctx = document.getElementById('salesChart').getContext('2d');
    salesChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: salesData,
          backgroundColor: backgroundColors,
          hoverBackgroundColor: backgroundColors
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'right',
            labels: {
              generateLabels: function(chart) {
                const data = chart.data;
                if (data.labels.length && data.datasets.length) {
                  return data.labels.map(function(label, i) {
                    const meta = chart.getDatasetMeta(0);
                    const style = meta.controller.getStyle(i);
                    const value = chart.config.data.datasets[0].data[i];
                    return {
                      text: label + ': ₱' + parseFloat(value).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}),
                      fillStyle: style.backgroundColor,
                      strokeStyle: style.borderColor,
                      lineWidth: style.borderWidth,
                      hidden: isNaN(value) || meta.data[i].hidden,
                      index: i
                    };
                  });
                }
                return [];
              },
              usePointStyle: true,
              pointStyle: 'rectRounded',
            },
            onClick: function(e, legendItem, legend) {
              const index = legendItem.index;
              const ci = legend.chart;
              const meta = ci.getDatasetMeta(0);
              const alreadyHidden = (meta.data[index].hidden === true);

              meta.data[index].hidden = !alreadyHidden;
              legendItem.hidden = !alreadyHidden;
              e.native.target.classList.toggle('legend-item-hidden');

              ci.update();
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                let label = context.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed !== null) {
                  label += '₱' + context.parsed.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                }
                return label;
              }
            }
          }
        }
      }
    });
  }
  // Initial load
  loadData();
  </script>
</body>
</html>