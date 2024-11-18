    
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
      const barcode = nextBarcode;  // Assuming nextBarcode is provided
      // Add start and stop characters for Code 39 symbology
      generatedBarcodeInput.value = `*${barcode}*`;  // Add '*' to the barcode value
  }

  // Apply barcode font to the generated barcode field
  if (generatedBarcodeInput) {
      generatedBarcodeInput.style.fontFamily = 'LibreBarcode39ExtendedText-Regular';
      generatedBarcodeInput.style.fontSize = '5rem';  // Adjust for readability
      generatedBarcodeInput.style.textAlign = 'center';  // Center the text
  }


  // Handle opening and closing modals
  if (openModalButton && modal) {
      openModalButton.addEventListener('click', () => modal.style.display = 'block');
  }
  if (closeButton && modal) {
      closeButton.addEventListener('click', () => modal.style.display = 'none');
  }
  if (updateCloseButton && updateModal) {
      updateCloseButton.addEventListener('click', () => updateModal.style.display = 'none');
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
                rows.forEach(row => {
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

        // Collapsible submenu functionality for Product section
        const productMenu = document.querySelector('.submenu');
        const productLink = document.querySelector('a[href="Product.php"]');
        if (productLink && productMenu) {
            productLink.addEventListener('click', (e) => {
                e.preventDefault();
                productMenu.style.display = (productMenu.style.display === 'block') ? 'none' : 'block';
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

        // Form submission for adding products
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
                        modal.style.display = 'none';
                        location.reload();
                    } else {
                        console.error('Failed to add product');
                    }
                } catch (error) {
                    console.error('Error:', error);
                }
            });
        }

        // Handle the update button click and populate the update modal
        document.addEventListener('click', (event) => {
            if (event.target.classList.contains('update-button')) {
                const button = event.target;
                const productId = button.getAttribute('data-id');
                const barcode = button.getAttribute('data-barcode');
                const description = button.getAttribute('data-description');
                const brand = button.getAttribute('data-brand');
                const category = button.getAttribute('data-category');
                const price = button.getAttribute('data-price');

                document.getElementById('update-product-id').value = productId;
                document.getElementById('update-barcode').value = barcode;
                document.getElementById('update-description').value = description;
                document.getElementById('update-brand').value = brand;
                document.getElementById('update-category').value = category;
                document.getElementById('update-price').value = price;

                updateModal.style.display = 'block';
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
                        body: formData
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
    });
