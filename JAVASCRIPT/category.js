document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('category-modal');
  const updateModal = document.getElementById('update-category-modal');
  const openModalButton = document.getElementById('add-product-button');
  const closeButton = document.querySelector('.close-button');
  const updateCloseButton = updateModal.querySelector('.close-button');

  // Open the category modal
  openModalButton.addEventListener('click', () => {
    modal.style.display = 'block';
  });

  // Close the category modal
  closeButton.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // Close the update category modal
  updateCloseButton.addEventListener('click', () => {
    updateModal.style.display = 'none';
  });

  // Close modals when clicking outside
  window.addEventListener('click', (event) => {
    if (event.target === modal) {
      modal.style.display = 'none';
    } else if (event.target === updateModal) {
      updateModal.style.display = 'none';
    }
  });

  // Form submission for adding a new category
  const form = document.getElementById('category-form');
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const categoryName = document.getElementById('category-name').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_category.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        console.log('Category added successfully');
        modal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to add category');
      }
    };
    xhr.send('category-name=' + encodeURIComponent(categoryName));
  });

  // Open the update modal and populate fields
  document.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('update-button')) {
      const id = event.target.getAttribute('data-id');
      const categoryName = event.target.getAttribute('data-category');

      document.getElementById('update-category-id').value = id;
      document.getElementById('update-category-name').value = categoryName;

      updateModal.style.display = 'block';
    }
  });

  // Form submission for updating a category
  const updateForm = document.getElementById('update-category-form');
  updateForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const categoryId = document.getElementById('update-category-id').value;
    const updatedCategoryName = document.getElementById('update-category-name').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_category.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        console.log('Category updated successfully');
        updateModal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to update category');
      }
    };
    xhr.send('category-id=' + encodeURIComponent(categoryId) + '&category-name=' + encodeURIComponent(updatedCategoryName));
  });
});

document.addEventListener('DOMContentLoaded', () => {
  const searchInput = document.getElementById('search-input');
  const categoryTable = document.getElementById('product-table');

  searchInput.addEventListener('input', () => {
    const searchTerm = searchInput.value.toLowerCase();
    const rows = categoryTable.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) { // Start from 1 to skip the header row
      const cells = rows[i].getElementsByTagName('td');
      let rowText = '';
      for (let j = 0; j < cells.length; j++) {
        rowText += cells[j].innerText.toLowerCase();
      }

      if (rowText.includes(searchTerm)) {
        rows[i].style.display = '';
      } else {
        rows[i].style.display = 'none';
      }
    }
  });
});
