document.addEventListener('DOMContentLoaded', () => {
  let rowId = 1; // Global variable to keep track of row IDs

  // Generate a random 11-digit number
  // Generate a random number within the range of a signed 32-bit integer
  const generateRandomNumber = () => Math.floor(Math.random() * (999999999 - 100000000 + 1)) + 100000000; // Generates a random 9-digit number

  // Event listener for the generate link
  document.getElementById('generateLink').addEventListener('click', function (event) {
    event.preventDefault();
    const referenceNoField = document.getElementById('referenceNo');
    if (referenceNoField) {
      referenceNoField.value = generateRandomNumber();
    }
  });

  // Fetch and populate products in the modal
  const fetchProducts = async () => {
    try {
      const response = await fetch('fetch_products.php');
      if (!response.ok) throw new Error(`Network response was not ok ${response.statusText}`);
  
      const contentType = response.headers.get('Content-Type');
      if (!contentType || !contentType.includes('application/json')) {
        throw new Error('Expected JSON, but received different content type.');
      }
  
      const products = await response.json();
      const tbody = document.getElementById('productModalTable').querySelector('tbody');
      tbody.innerHTML = ''; // Clear existing rows
      products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${product.id}</td>
          <td>${product.Barcode}</td>
          <td>${product.Description}</td>
          <td>${product.Quantity}</td>
          <td><button class="btn-select" data-id="${product.id}" data-barcode="${product.Barcode}" data-description="${product.Description}" data-quantity="${product.Quantity}">Select</button></td>
        `;
        tbody.appendChild(row);
      });
  
      // Add event listeners for select buttons
      addSelectButtonListeners();
  
    } catch (error) {
      console.error('Error fetching products:', error);
      alert('An error occurred while fetching products. Please try again.');
    }
  };
  
  // Open and close modal
  document.querySelector('.browse-products-link').addEventListener('click', (event) => {
    event.preventDefault();
    fetchProducts();
    document.getElementById('product-modal').style.display = 'block';
  });

  document.querySelector('#product-modal .close').addEventListener('click', () => {
    document.getElementById('product-modal').style.display = 'none';
  });

  window.addEventListener('click', (event) => {
    if (event.target === document.getElementById('product-modal')) {
      document.getElementById('product-modal').style.display = 'none';
    }
  });

  // Add selected product to the stock entry table
  const selectProduct = (productId, Barcode, description, quantity) => {
    const referenceNo = document.getElementById('referenceNo').value;
    const stockInBy = document.getElementById('stockInBy').value;
    const stockInDate = document.getElementById('stockInDate').value;
    const vendor = document.getElementById('vendor').options[document.getElementById('vendor').selectedIndex].text;
  
    const tableBody = document.querySelector('#product-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td>${rowId++}</td>
      <td>${referenceNo}</td>
      <td>${Barcode}</td>
      <td>${description}</td>
      <td><input type="number" class="product-quantity" value="${quantity}" min="0"></td> <!-- Editable quantity -->
      <td>${stockInDate}</td>
      <td>${stockInBy}</td>
      <td>${vendor}</td>
      <td><button class="remove-button">Remove</button></td>
    `;
    tableBody.appendChild(newRow);
  
    // Add visual feedback to the button
    const selectButton = document.querySelector(`button[data-id='${productId}']`);
    selectButton.disabled = true; // Disable the button after selection
    selectButton.innerText = 'Selected'; // Change button text
  
    addRemoveButtonListeners(); // Re-add event listeners for remove buttons
  };

  // Add event listeners to remove buttons
  const addRemoveButtonListeners = () => {
    document.querySelectorAll('.remove-button').forEach(button => {
      button.addEventListener('click', function () {
        removeRow(this);
      });
    });
  };

  // Add event listeners to select buttons
  const addSelectButtonListeners = () => {
    document.querySelectorAll('.btn-select').forEach(button => {
      button.addEventListener('click', function () {
        const productId = this.getAttribute('data-id');
        const Barcode = this.getAttribute('data-barcode');
        const description = this.getAttribute('data-description');
        const quantity = this.getAttribute('data-quantity');
        selectProduct(productId, Barcode, description, quantity);
        document.getElementById('product-modal').style.display = 'none'; // Close the modal after selection
      });
    });
  };

  // Remove a row
  const removeRow = (button) => {
    button.closest('tr').remove();
    updateRowIds(); // Update row IDs after removal
  };

  // Update row IDs
  const updateRowIds = () => {
    const rows = document.querySelectorAll('#product-table tbody tr');
    rowId = 1;
    rows.forEach(row => {
      row.children[0].innerText = rowId++;
    });
  };

  // Clear the table after a successful save
  const clearProductTable = () => {
    const tableBody = document.querySelector('#product-table tbody');
    tableBody.innerHTML = ''; // Clear all rows
    rowId = 1; // Reset row ID counter
  };

  // Save table data
  document.getElementById('save-button').addEventListener('click', () => {
    const referenceNo = document.getElementById('referenceNo').value;
    const stockInBy = document.getElementById('stockInBy').value;
    const vendor = document.getElementById('vendor').value;
    const stockInDate = document.getElementById('stockInDate').value;

    const products = [];
    const rows = document.querySelectorAll('#product-table tbody tr');
    rows.forEach(row => {
      const cells = row.querySelectorAll('td');
      const product = {
        Barcode: cells[2].innerText,
        description: cells[3].innerText,
        quantity: parseInt(row.querySelector('.product-quantity').value, 10) // Get the value from the input field
      };
      products.push(product);
    });

    const data = {
      referenceNo,
      stockInBy,
      vendor,
      stockInDate,
      products
    };

    fetch('process_stock_entry.php', {
      method: 'POST',
      body: JSON.stringify(data),
      headers: {
        'Content-Type': 'application/json'
      }
    })
    .then(response => response.json())
    .then(result => {
      if (result.success) {
        alert(result.success);
        clearProductTable(); // Clear the table after saving
      } else {
        alert(result.error);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
  });

  // Fetch vendor details when the vendor is changed
  const vendorSelect = document.getElementById('vendor');
  if (vendorSelect) {
    vendorSelect.addEventListener('change', async (event) => {
      const vendorId = event.target.value;
      if (vendorId) {
        const url = `/get_vendor_details.php?id=${vendorId}`;
        console.log(`Fetching vendor details from: ${url}`);

        try {
          const response = await fetch(url);
          if (!response.ok) throw new Error(`Network response was not ok: ${response.statusText}`);

          const vendorDetails = await response.json();
          if (vendorDetails && !vendorDetails.error) {
            document.getElementById('contactPerson').value = vendorDetails.contact || '';
            document.getElementById('address').value = vendorDetails.address || '';
          } else {
            console.error('Vendor details error:', vendorDetails.error);
          }
        } catch (error) {
          console.error('Error fetching vendor details:', error);
        }
      }
    });
  } else {
    console.error('Element with ID "vendor" not found');
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    var modal = document.getElementById('adjustmentHistoryModal');
    if (event.target == modal) {
      document.body.removeChild(modal);
    }
  }
});