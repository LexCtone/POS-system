document.addEventListener('DOMContentLoaded', function() {
  let currentView = 'item';
  const toggleViewButton = document.getElementById('toggleView');
  const tableHeader = document.getElementById('tableHeader');
  const tableBody = document.getElementById('tableBody');
  const startDateInput = document.getElementById('startDate');
  const endDateInput = document.getElementById('endDate');
  const loadDataButton = document.querySelector('.load-data-button');
  const printPreviewButton = document.querySelector('.print-preview-button');

  function updateTableHeaders() {
    if (currentView === 'item') {
      tableHeader.innerHTML = `
        <th>#</th>
        <th>Transaction #</th>
        <th>Barcode</th>
        <th>Description</th>
        <th>Price</th>
        <th>Qty</th>
        <th>Total</th>
        <th>Date</th>
        <th>Cancelled by</th>
        <th>Reason</th>
      `;
    } else {
      tableHeader.innerHTML = `
        <th>#</th>
        <th>Transaction #</th>
        <th>Date</th>
        <th>Total Amount</th>
        <th>Cancelled By</th>
        <th>Reason</th>
      `;
    }
  }

  function fetchData() {
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    const url = currentView === 'item' ? 'fetch_item_voids.php' : 'fetch_transaction_voids.php';

    return fetch(`${url}?startDate=${startDate}&endDate=${endDate}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          throw new Error(data.error);
        }
        return data;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        alert('An error occurred while fetching data. Please try again.');
        return [];
      });
  }

  function updateTableContent() {
    fetchData().then(data => {
      tableBody.innerHTML = '';
      data.forEach((item, index) => {
        const row = document.createElement('tr');
        if (currentView === 'item') {
          row.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.invoice || 'N/A'}</td>
            <td>${item.barcode || 'N/A'}</td>
            <td>${item.description || 'N/A'}</td>
            <td>${item.price ? '₱' + parseFloat(item.price).toFixed(2) : 'N/A'}</td>
            <td>${item.void_quantity}</td>
            <td>${item.price ? '₱' + (parseFloat(item.price) * parseInt(item.void_quantity)).toFixed(2) : 'N/A'}</td>
            <td>${new Date(item.void_date).toLocaleString()}</td>
            <td>${item.cancelled_by || 'N/A'}</td>
            <td>${item.reason}</td>
          `;
        } else {
          row.innerHTML = `
            <td>${index + 1}</td>
            <td>${item.invoice || 'N/A'}</td>
            <td>${new Date(item.void_date).toLocaleString()}</td>
            <td>₱${parseFloat(item.total_amount).toFixed(2)}</td>
            <td>${item.void_by}</td>
            <td>${item.reason}</td>
          `;
        }
        tableBody.appendChild(row);
      });
    });
  }

  function toggleView() {
    currentView = currentView === 'item' ? 'transaction' : 'item';
    toggleViewButton.textContent = currentView === 'item' ? 'Switch to Transaction View' : 'Switch to Item View';
    updateTableHeaders();
    updateTableContent();
  }

  toggleViewButton.addEventListener('click', toggleView);

  loadDataButton.addEventListener('click', updateTableContent);

  printPreviewButton.addEventListener('click', function() {
    window.print();
  });

  // Initial setup
  updateTableHeaders();
  updateTableContent();
});