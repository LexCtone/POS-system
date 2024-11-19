document.addEventListener('DOMContentLoaded', () => {
  // Modal elements for adding and updating products
  const modal = document.getElementById('product-modal');
  const openModalButton = document.getElementById('add-product-button');
  const closeButton = modal ? modal.querySelector('.close-button') : null;
  const updateModal = document.getElementById('update-product-modal');
  const updateCloseButton = updateModal ? updateModal.querySelector('.close-button') : null;

  // Fetch next barcode value from PHP and set it in the input fields
  const barcodeInput = document.getElementById('barcode');
  const generatedBarcodeInput = document.getElementById('generatedBarcode');

  if (barcodeInput && generatedBarcodeInput) {
    const barcode = nextBarcode; // Assuming nextBarcode is provided
    // Add start and stop characters for Code 39 symbology
    generatedBarcodeInput.value = `*${barcode}*`; // Add '*' to the barcode value
  }

  // Apply barcode font to the generated barcode field
  if (generatedBarcodeInput) {
    generatedBarcodeInput.style.fontFamily = 'LibreBarcode39ExtendedText-Regular';
    generatedBarcodeInput.style.fontSize = '5rem'; // Adjust for readability
    generatedBarcodeInput.style.textAlign = 'center'; // Center the text
  }

  // Handle opening and closing modals
  if (openModalButton && modal) {
    openModalButton.addEventListener('click', () => (modal.style.display = 'block'));
  }
  if (closeButton && modal) {
    closeButton.addEventListener('click', () => (modal.style.display = 'none'));
  }
  if (updateCloseButton && updateModal) {
    updateCloseButton.addEventListener('click', () => (updateModal.style.display = 'none'));
  }
  // Close modals when clicking outside of them
  window.addEventListener('click', (event) => {
    if (event.target === modal) modal.style.display = 'none';
    if (event.target === updateModal) updateModal.style.display = 'none';
  });

  // Search functionality for products
  const searchInput = document.getElementById('search-input');
  const productTable = document.getElementById('product-table');
  if (searchInput && productTable) {
    searchInput.addEventListener('input', () => {
      const searchTerm = searchInput.value.toLowerCase();
      const rows = productTable.querySelectorAll('tbody tr');
      rows.forEach((row) => {
        const cells = row.getElementsByTagName('td');
        let rowMatches = false;
        Array.from(cells).forEach((cell) => {
          if (cell.textContent.toLowerCase().includes(searchTerm)) {
            rowMatches = true;
          }
        });
        row.style.display = rowMatches ? '' : 'none';
      });
    });
  }

  // Collapsible submenu functionality for Product section
  const productMenu = document.querySelector('.submenu');
  const productLink = document.querySelector('a[href="Product.php"]');
  if (productLink && productMenu) {
    productLink.addEventListener('click', (e) => {
      e.preventDefault();
      productMenu.style.display = productMenu.style.display === 'block' ? 'none' : 'block';
    });
  }

  // Barcode input functionality
  if (barcodeInput) {
    barcodeInput.addEventListener('keypress', async (event) => {
      if (event.key === 'Enter') {
        event.preventDefault();
        const barcode = barcodeInput.value.trim();
        if (barcode) {
          try {
            const response = await fetch(`fetch_products.php?barcode=${barcode}`);
            if (response.ok) {
              const products = await response.json();
              updateProductTable(products);
            } else {
              console.error('Failed to fetch products');
            }
          } catch (error) {
            console.error('Error:', error);
          }
        } else {
          console.warn('Barcode input is empty');
        }
      }
    });
  }

  // Function to update the product table with fetched products
  function updateProductTable(products) {
    const tableBody = document.querySelector('#product-table tbody');
    if (!tableBody) return;
    tableBody.innerHTML = '';
    products.forEach((product) => {
      const row = document.createElement('tr');
      row.innerHTML = `
          <td>${product.id}</td>
          <td>${product.Barcode}</td>
          <td>${product.Description}</td>
          <td>${product.Brand}</td>
          <td>${product.Category}</td>
          <td>${product.Price}</td>
          <td>${product.Quantity}</td>
          <td>
              <button class="update-button" data-id="${product.id}" data-barcode="${product.Barcode}" 
                  data-description="${product.Description}" data-brand="${product.Brand}" 
                  data-category="${product.Category}" data-price="${product.Price}">
                  Update
              </button>
          </td>
      `;
      tableBody.appendChild(row);
    });
  }

  const form = document.getElementById('product-form');
  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);

      try {
        const response = await fetch('add_product.php', {
          method: 'POST',
          body: formData,
        });

        let result;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.indexOf('application/json') !== -1) {
          result = await response.json();
        } else {
          throw new Error('Received non-JSON response from server');
        }

        if (result.success) {
          console.log('Product added successfully');
          alert(`Product added successfully. 
                 Barcode: ${result.barcode}
                 Generated Barcode: ${result.generatedBarcode}`);
          if (window.modal) window.modal.style.display = 'none';
          location.reload();
        } else {
          console.error('Failed to add product:', result.message);
          alert('Failed to add product: ' + result.message);
        }
      } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while adding the product. Please check the console for more details.');
      }
    });
  }

  document.addEventListener('click', (event) => {
    if (event.target.classList.contains('update-button')) {
      const button = event.target;

      // Retrieve all product data from the button's dataset
      const productId = button.getAttribute('data-id');
      const barcode = button.getAttribute('data-barcode');
      const generatedBarcode = `*${barcode}*`; // Add Code 39 symbology
      const description = button.getAttribute('data-description');
      const brand = button.getAttribute('data-brand');
      const category = button.getAttribute('data-category');
      const price = button.getAttribute('data-price');
      const vendorId = button.getAttribute('data-vendor-id'); // Vendor ID
      const costPrice = button.getAttribute('data-cost-price'); // Cost price data

      // Populate the update modal fields
      document.getElementById('update-product-id').value = productId;
      document.getElementById('update-barcode').value = barcode;
      document.getElementById('update-generatedBarcode').value = generatedBarcode;
      document.getElementById('update-description').value = description;
      document.getElementById('update-brand').value = brand;
      document.getElementById('update-category').value = category;
      document.getElementById('update-price').value = price;
      document.getElementById('update-cost-price').value = costPrice;

      const vendorSelect = document.getElementById('update-vendor');
      if (vendorSelect) {
        console.log(
          'Available vendor options:',
          Array.from(vendorSelect.options).map((option) => option.value)
        );
      }

    // Apply barcode font styling to the Generated Barcode field
    const generatedBarcodeInput = document.getElementById('update-generatedBarcode');
    if (generatedBarcodeInput) {
        generatedBarcodeInput.style.fontFamily = 'LibreBarcode39ExtendedText-Regular';
        generatedBarcodeInput.style.fontSize = '5rem'; // Adjust for readability
        generatedBarcodeInput.style.textAlign = 'center'; // Center the text
    }                     
                

      const updateModal = document.getElementById('update-product-modal');
      if (updateModal) {
        updateModal.style.display = 'block';
      }
    }
  });

  // Handle update product form submission
  const updateForm = document.getElementById('update-product-form');
  if (updateForm) {
    updateForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(updateForm);
      try {
        const response = await fetch('update_product.php', {
          method: 'POST',
          body: formData,
        });
        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            console.log('Product updated successfully');
            updateModal.style.display = 'none';
            location.reload();
          } else {
            console.error('Failed to update product:', result.message);
          }
        } else {
          console.error('Failed to update product');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    });
  }

  // Dropdown filtering functionality
  const brandFilter = document.getElementById('brand-filter');
  const categoryFilter = document.getElementById('category-filter');
  const vendorFilter = document.getElementById('vendor-filter');

  if (productTable && (brandFilter || categoryFilter || vendorFilter)) {
    const dropdownFilters = [brandFilter, categoryFilter, vendorFilter];
    dropdownFilters.forEach((dropdown) => {
      if (dropdown) {
        dropdown.addEventListener('change', filterTable);
      }
    });

    function filterTable() {
        const brandValue = brandFilter?.value.toLowerCase() || 'all';
        const categoryValue = categoryFilter?.value.toLowerCase() || 'all';
        const vendorValue = vendorFilter?.value.toLowerCase() || 'all';
      
        const rows = productTable.querySelectorAll('tbody tr');
        rows.forEach((row) => {
          const brandCell = row.querySelector('td:nth-child(4)'); // Brand column
          const categoryCell = row.querySelector('td:nth-child(5)'); // Category column
          const vendorCell = row.querySelector('td:nth-child(9)'); // Vendor column (updated index)
      
          const vendorText = vendorCell ? vendorCell.textContent.toLowerCase().trim() : 'unknown';
      
          const matchesBrand =
            brandValue === 'all' || (brandCell && brandCell.textContent.toLowerCase() === brandValue);
          const matchesCategory =
            categoryValue === 'all' ||
            (categoryCell && categoryCell.textContent.toLowerCase() === categoryValue);
          const matchesVendor =
            vendorValue === 'all' || vendorText === vendorValue;
      
          // Show or hide the row based on filter matches
          row.style.display = matchesBrand && matchesCategory && matchesVendor ? '' : 'none';
        });
      }
      
  }
});
