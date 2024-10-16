document.addEventListener('DOMContentLoaded', () => {
  const loadDataButton = document.querySelector('.load-data-button');
  const printPreviewButton = document.querySelector('.print-preview-button');
  const startDateInput = document.getElementById('startDate');
  const endDateInput = document.getElementById('endDate');
  const vendorSelect = document.getElementById('vendor');
  const statusSelect = document.getElementById('status');

  loadDataButton.addEventListener('click', loadData);
  printPreviewButton.addEventListener('click', printPreview);
  
  const today = new Date();
  const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
  startDateInput.value = formatDate(thirtyDaysAgo);
  endDateInput.value = formatDate(today);

  loadData();
});

function loadData() {
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  const soldBy = document.getElementById('vendor').value;
  const status = document.getElementById('status').value;

  const loadingMessage = showLoadingMessage();

  fetch(`fetch_sales_history.php?startDate=${startDate}&endDate=${endDate}&soldBy=${soldBy}&status=${status}`)
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      return response.json();
    })
    .then(data => {
      if (data.error) {
        throw new Error(data.message || 'An error occurred while fetching data.');
      }
      updateTable(data.sales);
      updateSalesInfo(data.totalActiveSales, data.totalVoidedSales, data.voidedTransactions.length);
      hideLoadingMessage(loadingMessage);
    })
    .catch(error => {
      console.error('Error:', error);
      hideLoadingMessage(loadingMessage);
      alert('An error occurred while loading data: ' + error.message);
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
  loadingMessage.style.zIndex = '9999';
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

  if (sales.length === 0) {
    const row = tableBody.insertRow();
    const cell = row.insertCell();
    cell.colSpan = 11;
    cell.textContent = 'No data available for the selected criteria.';
    cell.style.textAlign = 'center';
    return;
  }

  sales.forEach((sale, index) => {
    const row = tableBody.insertRow();
    row.innerHTML = `
      <td>${index + 1}</td>
      <td>${sale.invoice}</td>
      <td>${sale.barcode}</td>
      <td>${sale.description}</td>
      <td>₱${parseFloat(sale.price).toFixed(2)}</td>
      <td>${sale.status === 'Voided' ? sale.void_quantity : sale.quantity}</td>
      <td>₱${parseFloat(sale.discount_amount).toFixed(2)}</td>
      <td>₱${parseFloat(sale.total).toFixed(2)}</td>
      <td>${formatDateTime(sale.sale_date)}</td>
      <td>${sale.cashier_name}</td>
      <td>${sale.status}</td>
    `;
    if (sale.status === 'Voided') {
      row.classList.add('voided-transaction');
    }
  });
}

function updateSalesInfo(totalActiveSales, totalVoidedSales, voidedTransactionsCount) {
  document.getElementById('totalActiveSales').textContent = parseFloat(totalActiveSales).toFixed(2);
  
  const totalVoidedSalesElement = document.getElementById('totalVoidedSales');
  const voidedTransactionsCountElement = document.getElementById('voidedTransactionsCount');
  
  if (totalVoidedSalesElement) {
    totalVoidedSalesElement.textContent = parseFloat(totalVoidedSales).toFixed(2);
  }
  
  if (voidedTransactionsCountElement) {
    voidedTransactionsCountElement.textContent = voidedTransactionsCount;
  }
}

function printPreview() {
  // Add the print-mode class to apply print styles
  document.body.classList.add('print-mode');

  // Trigger the print dialog
  window.print();

  // Remove the print-mode class to restore the normal view
  document.body.classList.remove('print-mode');
}


function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function formatDateTime(dateTimeString) {
  const date = new Date(dateTimeString);
  return date.toLocaleString('en-US', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });
}