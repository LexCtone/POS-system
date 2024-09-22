document.addEventListener('DOMContentLoaded', () => {
  // Modal elements
  const modal = document.getElementById('product-modal');
  const updateModal = document.getElementById('update-product-modal');

  // Buttons
  const openModalButton = document.getElementById('add-product-button');
  const closeButton = modal.querySelector('.close-button');
  const updateCloseButton = updateModal.querySelector('.close-button');

  // Open and close modals
  openModalButton.addEventListener('click', () => {
    modal.style.display = 'block';
  });

  closeButton.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  updateCloseButton.addEventListener('click', () => {
    updateModal.style.display = 'none';
  });

  // Close modals when clicking outside of them
  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    } else if (event.target === updateModal) {
      updateModal.style.display = 'none';
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

  // Search functionality
  const searchInput = document.getElementById('search-input');
  const productTable = document.getElementById('product-table');

  searchInput.addEventListener('input', () => {
    const searchTerm = searchInput.value.toLowerCase();
    const rows = productTable.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
      const cells = rows[i].getElementsByTagName('td');
      let rowText = '';
      for (let j = 0; j < cells.length; j++) {
        rowText += cells[j].innerText.toLowerCase();
      }

      rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
    }
  });

  // Update product functionality
  document.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('update-button')) {
      const id = event.target.getAttribute('data-id');
      const barcode = event.target.getAttribute('data-barcode');
      const description = event.target.getAttribute('data-description');
      const brand = event.target.getAttribute('data-brand');
      const category = event.target.getAttribute('data-category');
      const price = event.target.getAttribute('data-price');

      document.getElementById('update-product-id').value = id;
      document.getElementById('update-barcode').value = barcode;
      document.getElementById('update-description').value = description;

      // Get the dropdown elements
      const updateBrandSelect = document.getElementById('update-brand');
      const updateCategorySelect = document.getElementById('update-category');

      // Set selected values for brand and category dropdowns
      updateBrandSelect.value = brand;
      updateCategorySelect.value = category;

      // Ensure that the correct option is selected
      Array.from(updateBrandSelect.options).forEach(option => {
        if (option.value === brand) {
          option.selected = true;
        }
      });
      Array.from(updateCategorySelect.options).forEach(option => {
        if (option.value === category) {
          option.selected = true;
        }
      });

      document.getElementById('update-price').value = price;

      updateModal.style.display = 'block';
    }
  });

  // Form for updating products
  const updateForm = document.getElementById('update-product-form');
  updateForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(updateForm);

    try {
      const response = await fetch('update_product.php', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        console.log('Product updated successfully');
        updateModal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to update product');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  });
});
