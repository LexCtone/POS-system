document.addEventListener('DOMContentLoaded', () => {
  const loadDataButton = document.querySelector('.load-data-button');
  const printPreviewButton = document.querySelector('.print-preview-button');
  const startDateInput = document.getElementById('startDate');
  const endDateInput = document.getElementById('endDate');
  const vendorSelect = document.getElementById('vendor');

  loadDataButton.addEventListener('click', loadData);
  printPreviewButton.addEventListener('click', printPreview);
});

function loadData() {
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  const soldBy = document.getElementById('vendor').value;

  const loadingMessage = showLoadingMessage();

  fetch(`fetch_sales_history.php?startDate=${startDate}&endDate=${endDate}&soldBy=${soldBy}`)
    .then(response => response.json())
    .then(data => {
      updateTable(data.sales);
      updateSalesInfo(data.totalActiveSales, data.totalVoidedSales, data.voidedTransactions.length);
      hideLoadingMessage(loadingMessage);
    })
    .catch(error => {
      console.error('Error:', error);
      hideLoadingMessage(loadingMessage);
      alert('An error occurred while loading data. Please try again.');
    });
}

function showLoadingMessage() {
  document.body.style.cursor = 'wait';
  const loadingMessage = document.createElement('div');
  loadingMessage.textContent = 'Loading...';
  loadingMessage.style.position = 'fixed';
  loadingMessage.style.top = '50%';
  loadingMessage.style.left = '50%';
  loadingMessage.style.transform = 'translate(-50%, -50%)';
  loadingMessage.style.padding = '10px';
  loadingMessage.style.background = 'rgba(0, 0, 0, 0.7)';
  loadingMessage.style.color = 'white';
  loadingMessage.style.borderRadius = '5px';
  document.body.appendChild(loadingMessage);
  return loadingMessage;
}

function hideLoadingMessage(loadingMessage) {
  document.body.style.cursor = 'default';
  document.body.removeChild(loadingMessage);
}

function updateTable(sales) {
  const tableBody = document.querySelector('table tbody');
  tableBody.innerHTML = '';

  sales.forEach((sale, index) => {
    const row = tableBody.insertRow();
    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${sale.invoice}</td>
      <td>${sale.barcode}</td>
      <td>${sale.description}</td>
      <td>₱${parseFloat(sale.price).toFixed(2)}</td>
      <td>${sale.quantity}</td>
      <td>₱${parseFloat(sale.discount_amount).toFixed(2)}</td>
      <td>₱${parseFloat(sale.total).toFixed(2)}</td>
      <td>${sale.sale_date}</td>
      <td>${sale.cashier_name}</td>
      <td>${sale.status}</td>
    `;
    if (sale.status === 'Voided') {
      row.classList.add('voided-transaction');
    }
  });
}

function updateSalesInfo(totalActiveSales) {
  document.getElementById('totalActiveSales').textContent = parseFloat(totalActiveSales).toFixed(2);
}

function printPreview() {
  const printWindow = window.open('', '_blank');
  
  const styles = Array.from(document.styleSheets)
    .map(styleSheet => {
      try {
        return Array.from(styleSheet.cssRules)
          .map(rule => rule.cssText)
          .join('\n');
      } catch (e) {
        console.log('Access to stylesheet blocked by CORS policy');
        return '';
      }
    })
    .join('\n');

  const tableContent = document.querySelector('.table-container').innerHTML;
  const salesInfo = document.querySelector('.sales-info').innerHTML;

  const printContent = `
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Sales History Print Preview</title>
      <style>
        ${styles}
        .voided-transaction {
          background-color: #ffcccc;
        }
      </style>
    </head>
    <body>
      <h1>Sales History</h1>
      ${salesInfo}
      ${tableContent}
      <script>
        window.onload = function() {
          window.print();
          window.onafterprint = function() {
            window.close();
          }
        }
      </script>
    </body>
    </html>
  `;

  printWindow.document.open();
  printWindow.document.write(printContent);
  printWindow.document.close();
}