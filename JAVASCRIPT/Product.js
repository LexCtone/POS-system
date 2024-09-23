document.addEventListener('DOMContentLoaded', () => {
  // Modal elements for adding product
  const modal = document.getElementById('product-modal');
  const openModalButton = document.getElementById('add-product-button');
  const closeButton = modal.querySelector('.close-button');

  // Open the modal when the button is clicked
  openModalButton.addEventListener('click', () => {
    modal.style.display = 'block';
  });

  // Close the modal when the close button is clicked
  closeButton.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // Close the modal when clicking outside of it
  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    }
  });

  // Get the search input element
  const searchInput = document.getElementById('search-input');
  const resultsContainer = document.getElementById('search-results');
  const productTable = document.getElementById('product-table');

  // Handle search functionality
  searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = productTable.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    Array.from(rows).forEach(row => {
      const cells = row.getElementsByTagName('td');
      let rowMatches = false;

      // Check each cell's text content for a match
      Array.from(cells).forEach(cell => {
        if (cell.textContent.toLowerCase().includes(searchTerm)) {
          rowMatches = true; // If any cell matches, flag the row as matching
        }
      });

      // Show or hide the row based on whether it matches the search term
      row.style.display = rowMatches ? '' : 'none';
    });
  });

  // Get the barcode input element
  const barcodeInput = document.getElementById('barcode');
  barcodeInput.addEventListener('keypress', async (event) => {
    if (event.key === 'Enter') {
      event.preventDefault(); // Prevent form submission
      const barcode = barcodeInput.value.trim();

      // Make sure to check if barcode is not empty
      if (barcode) {
        try {
          const response = await fetch(`fetch_products.php?barcode=${barcode}`);
          if (response.ok) {
            const products = await response.json();
            console.log('Products fetched:', products);
            // You can update your UI with the fetched products here
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

  // Form for adding products
  const form = document.getElementById('product-form');
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
        modal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to add product');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  });

  // Handle modal for updating products
  const updateModal = document.getElementById('update-product-modal');
  const updateCloseButton = updateModal.querySelector('.close-button');

  // Close the update modal when the close button is clicked
  updateCloseButton.addEventListener('click', () => {
    updateModal.style.display = 'none';
  });

  // Update buttons - Delegate the event listener to update buttons created dynamically
  resultsContainer.addEventListener('click', function(event) {
    if (event.target.classList.contains('update-button')) {
      const button = event.target;
      const productId = button.getAttribute('data-id');
      const barcode = button.getAttribute('data-barcode');
      const description = button.getAttribute('data-description');
      const brand = button.getAttribute('data-brand');
      const category = button.getAttribute('data-category');
      const price = button.getAttribute('data-price');
      const quantity = button.getAttribute('data-quantity');

      // Populate the update form with the current product's details
      document.getElementById('update-product-id').value = productId;
      document.getElementById('update-barcode').value = barcode;
      document.getElementById('update-description').value = description;
      document.getElementById('update-brand').value = brand;
      document.getElementById('update-category').value = category;
      document.getElementById('update-price').value = price;
      document.getElementById('update-quantity').value = quantity; // Ensure this input exists

      // Show the update product modal
      updateModal.style.display = 'block';
    }
  });

  // Handle update product form submission
  const updateForm = document.getElementById('update-product-form');
  updateForm.addEventListener('submit', async (event) => {
    event.preventDefault(); // Prevent default form submission
    const formData = new FormData(updateForm);

    try {
      const response = await fetch('update_product.php', {
        method: 'POST',
        body: formData,
      });

      if (response.ok) {
        console.log('Product updated successfully');
        updateModal.style.display = 'none';
        location.reload(); // Reload the page to see updates
      } else {
        console.error('Failed to update product');
      }
    } catch (error) {
      console.error('Error updating product:', error);
    }
  });
});
