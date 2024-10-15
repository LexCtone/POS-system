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
    .catch(error => {
      console.error('Error:', error);
      displayErrorMessage('An error occurred while fetching data. Please try again.');
    });
}

function updateTable(data) {
  const tableBody = document.querySelector('#sales-table tbody');
  tableBody.innerHTML = '';
  if (data.length === 0) {
    const row = tableBody.insertRow();
    const cell = row.insertCell(0);
    cell.colSpan = 5;
    cell.textContent = 'No records found';
    cell.style.textAlign = 'center';
    cell.style.fontStyle = 'italic';
  } else {
    data.forEach((item, index) => {
      const row = tableBody.insertRow();
      row.insertCell(0).textContent = index + 1;
      row.insertCell(1).textContent = item.barcode;
      row.insertCell(2).textContent = item.description;
      row.insertCell(3).textContent = item.total_qty;
      row.insertCell(4).textContent = '₱' + parseFloat(item.total_sales).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
  }
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

function displayErrorMessage(message) {
  const errorDiv = document.getElementById('errorMessage');
  if (!errorDiv) {
    const newErrorDiv = document.createElement('div');
    newErrorDiv.id = 'errorMessage';
    newErrorDiv.style.color = 'red';
    newErrorDiv.style.marginTop = '10px';
    document.querySelector('.form-group').appendChild(newErrorDiv);
  }
  errorDiv.textContent = message;
}

// Initial load
document.addEventListener('DOMContentLoaded', function() {
  loadData();
  
  // Add event listeners to form elements
  document.getElementById('startDate').addEventListener('change', loadData);
  document.getElementById('endDate').addEventListener('change', loadData);
  document.getElementById('sortBy').addEventListener('change', loadData);
  document.getElementById('sortOrder').addEventListener('change', loadData);
});