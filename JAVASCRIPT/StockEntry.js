document.addEventListener('DOMContentLoaded', () => {
  let rowId = 1; // Global variable to keep track of row IDs

  function loadPODetails(poNumber) {
    if (!poNumber) {
        console.error('No PO number provided');
        return;
    }

    
    
    console.log('Fetching PO details for:', poNumber);
  
    fetch('get_po_details.php?po=' + encodeURIComponent(poNumber))
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok: ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received PO details:', data);

            if (data.error) {
                throw new Error(data.error);
            }

            // Auto-fill vendor
            const vendorSelect = document.getElementById('vendor');
            if (vendorSelect && data.vendor) {
                const option = Array.from(vendorSelect.options).find(opt => opt.textContent === data.vendor);
                if (option) {
                    vendorSelect.value = option.value;
                    vendorSelect.dispatchEvent(new Event('change'));
                }
            }
            
            // Clear existing items
            const table = document.getElementById('product-table').getElementsByTagName('tbody')[0];
            table.innerHTML = '';
            
            // Add PO items to the table
            if (data.items && Array.isArray(data.items)) {
                data.items.forEach(item => {
                    const row = table.insertRow();
                    row.innerHTML = `
                        <td>${rowId++}</td>
                        <td>${document.getElementById('referenceNo').value}</td>
                        <td>${item.barcode || ''}</td>
                        <td>${item.description || ''}</td> <!-- Display the description here -->
                        <td><input type="number" class="product-quantity" value="${item.quantity || 0}" min="0" max="${item.quantity || 0}"></td>
                        <td>${document.getElementById('stockInDate').value}</td>
                        <td>${document.getElementById('stockInBy').value}</td>
                        <td>${vendorSelect ? vendorSelect.options[vendorSelect.selectedIndex].text : ''}</td>
                        <td><button class="remove-button">Remove</button></td>
                    `;
                });
                
                addRemoveButtonListeners();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading PO details: ' + error.message);
        });
}


  // Generate a random 11-digit number
  const generateRandomNumber = () => Math.floor(Math.random() * (999999999 - 100000000 + 1)) + 100000000;

  // Automatically generate a reference number on page load
  const referenceNoField = document.getElementById('referenceNo');
  if (referenceNoField) {
    referenceNoField.value = generateRandomNumber();
  }

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

  const selectProduct = (productId, Barcode, description, quantity) => {
    const tableBody = document.querySelector('#product-table tbody');
    const newRow = document.createElement('tr');
    newRow.innerHTML = `
      <td>${rowId++}</td>
      <td>${document.getElementById('referenceNo').value}</td>
      <td>${Barcode}</td>
      <td>${description || 'N/A'}</td> <!-- Ensure description is displayed -->
      <td><input type="number" class="product-quantity" value="${quantity}" min="0"></td>
      <td>${document.getElementById('stockInDate').value}</td>
      <td>${document.getElementById('stockInBy').value}</td>
      <td>${document.getElementById('vendor').options[document.getElementById('vendor').selectedIndex].text}</td>
      <td><button class="remove-button">Remove</button></td>
    `;
    tableBody.appendChild(newRow);
    addRemoveButtonListeners();
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
        document.getElementById('product-modal').style.display = 'none';
      });
    });
  };

  // Remove a row
  const removeRow = (button) => {
    button.closest('tr').remove();
    updateRowIds();
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
    tableBody.innerHTML = '';
    rowId = 1;
  };

  // Save table data
  document.getElementById('save-button').addEventListener('click', () => {
    const referenceNo = document.getElementById('referenceNo').value;
    const stockInBy = document.getElementById('stockInBy').value;
    const vendor = document.getElementById('vendor').value;
    const stockInDate = document.getElementById('stockInDate').value;
    
    const saveToPurchaseOrder = confirm("Do you want to save this entry as a Purchase Order?");

    const products = [];
    const rows = document.querySelectorAll('#product-table tbody tr');
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const product = {
            Barcode: cells[2].innerText,
            description: cells[3].innerText,
            quantity: parseInt(row.querySelector('.product-quantity').value, 10)
        };
        products.push(product);
    });

    const data = {
        referenceNo,
        stockInBy,
        vendor,
        stockInDate,
        products,
        saveToPurchaseOrder
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
            clearProductTable();
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
        const url = `get_vendor_details.php?id=${vendorId}`;
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

  // Add event listener for PO reference select
  const poReferenceSelect = document.getElementById('poReference');
  
  if (poReferenceSelect) {
    poReferenceSelect.addEventListener('change', (event) => {
      const poNumber = event.target.value;
      console.log('Selected PO:', poNumber);
      if (poNumber) {
        loadPODetails(poNumber);
      }
    });
  } else {
    console.error('Element with ID "poReference" not found');
  }
  
});