document.addEventListener('DOMContentLoaded', () => {
  // Modal elements for adding product
  const modal = document.getElementById('product-modal');
  const openModalButton = document.getElementById('add-product-button');
  const closeButton = modal ? modal.querySelector('.close-button') : null;
  

  if (openModalButton && modal) {
    openModalButton.addEventListener('click', () => {
      modal.style.display = 'block';
    });
  }

  if (closeButton && modal) {
    closeButton.addEventListener('click', () => {
      modal.style.display = 'none';
    });
  }

  // Close the modal when clicking outside of it
  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });

  // Get the search input element
  const searchInput = document.getElementById('search-input');
  const productTable = document.getElementById('product-table');

  if (searchInput && productTable) {
    searchInput.addEventListener('input', function() {
      const searchTerm = this.value.toLowerCase();
      const rows = productTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

      Array.from(rows).forEach(row => {
        const cells = row.getElementsByTagName('td');
        let rowMatches = false;

        Array.from(cells).forEach(cell => {
          if (cell.textContent.toLowerCase().includes(searchTerm)) {
            rowMatches = true;
          }
        });

        row.style.display = rowMatches ? '' : 'none';
      });
    });
  }

  // Add collapsible submenu functionality for Product
  const productMenu = document.querySelector('.submenu');
  const productLink = document.querySelector('a[href="Product.php"]');

  if (productLink && productMenu) {
    productLink.addEventListener('click', function(e) {
      e.preventDefault();
      productMenu.style.display = productMenu.style.display === 'block' ? 'none' : 'block';
    });
  }

  // Existing functionality for barcode input
  const barcodeInput = document.getElementById('barcode');
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
              console.log('Products fetched:', products);
              // Update UI with fetched products
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
    products.forEach(product => {
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

  // Form for adding products
  const form = document.getElementById('product-form');
  if (form) {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(form);

      try {
        const response = await fetch('add_product.php', {
          method: 'POST',
          body: formData
        });

        if (response.ok) {
          console.log('Product added successfully');
          if (modal) modal.style.display = 'none';
          location.reload();
        } else {
          console.error('Failed to add product');
        }
      } catch (error) {
        console.error('Error:', error);
      }
    });
  }

  // Handle modal for updating products
  const updateModal = document.getElementById('update-product-modal');
  const updateCloseButton = updateModal ? updateModal.querySelector('.close-button') : null;
  const cancelUpdateBtn = document.getElementById('cancel-update');

  if (updateCloseButton && updateModal) {
    updateCloseButton.addEventListener('click', () => {
      updateModal.style.display = 'none';
    });
  }

  if (cancelUpdateBtn && updateModal) {
    cancelUpdateBtn.addEventListener('click', () => {
      updateModal.style.display = 'none';
    });
  }

  // Update buttons - Delegate the event listener to the document
  document.addEventListener('click', function(event) {
    if (event.target.classList.contains('update-button')) {
      const button = event.target;
      const productId = button.getAttribute('data-id');
      const barcode = button.getAttribute('data-barcode');
      const description = button.getAttribute('data-description');
      const brand = button.getAttribute('data-brand');
      const category = button.getAttribute('data-category');
      const price = button.getAttribute('data-price');

      // Populate the update form with the current product's details
      const updateProductId = document.getElementById('update-product-id');
      const updateBarcode = document.getElementById('update-barcode');
      const updateDescription = document.getElementById('update-description');
      const updateBrand = document.getElementById('update-brand');
      const updateCategory = document.getElementById('update-category');
      const updatePrice = document.getElementById('update-price');

      if (updateProductId) updateProductId.value = productId;
      if (updateBarcode) updateBarcode.value = barcode;
      if (updateDescription) updateDescription.value = description;
      if (updateBrand) updateBrand.value = brand;
      if (updateCategory) updateCategory.value = category;
      if (updatePrice) updatePrice.value = price;

      // Show the update product modal
      if (updateModal) updateModal.style.display = 'block';
    }
  });

  // Handle update product form submission
  const updateForm = document.getElementById('update-product-form');
  const updateFormFeedback = document.getElementById('update-form-feedback');
  const updateLoading = document.getElementById('update-loading');

  if (updateForm) {
    updateForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const formData = new FormData(updateForm);

      if (updateLoading) updateLoading.style.display = 'block';
      if (updateFormFeedback) updateFormFeedback.textContent = '';

      try {
        const response = await fetch('update_product.php', {
          method: 'POST',
          body: formData,
        });

        if (response.ok) {
          const result = await response.json();
          if (result.success) {
            if (updateFormFeedback) updateFormFeedback.textContent = 'Product updated successfully';
            if (updateModal) updateModal.style.display = 'none';
            location.reload();
          } else {
            if (updateFormFeedback) updateFormFeedback.textContent = result.message || 'Failed to update product';
          }
        } else {
          if (updateFormFeedback) updateFormFeedback.textContent = 'Failed to update product';
        }
      } catch (error) {
        console.error('Error updating product:', error);
        if (updateFormFeedback) updateFormFeedback.textContent = 'An error occurred while updating the product';
      } finally {
        if (updateLoading) updateLoading.style.display = 'none';
      }
    });
  }
});
