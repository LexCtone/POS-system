document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('brand-modal');
  const updateModal = document.getElementById('update-brand-modal');
  const openModalButton = document.getElementById('add-brand-button');
  const closeButton = document.querySelector('.close-button');
  const updateCloseButton = updateModal.querySelector('.close-button');

  // Open the brand modal
  openModalButton.addEventListener('click', () => {
    modal.style.display = 'block';
  });

  // Close the brand modal
  closeButton.addEventListener('click', () => {
    modal.style.display = 'none';
  });

  // Close the update brand modal
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

  // Form submission for adding a new brand
  const form = document.getElementById('brand-form');
  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const brandName = document.getElementById('brand-name').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_brand.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        console.log('Brand added successfully');
        modal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to add brand');
      }
    };
    xhr.send('brand-name=' + encodeURIComponent(brandName));
  });

  // Open the update modal and populate fields
  document.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('update-button')) {
      const id = event.target.getAttribute('data-id');
      const brandName = event.target.getAttribute('data-brand');

      document.getElementById('update-brand-id').value = id;
      document.getElementById('update-brand-name').value = brandName;

      updateModal.style.display = 'block';
    }
  });

  // Form submission for updating a brand
  const updateForm = document.getElementById('update-brand-form');
  updateForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const id = document.getElementById('update-brand-id').value;
    const brandName = document.getElementById('update-brand-name').value;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'update_brand.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
      if (xhr.status === 200) {
        console.log('Brand updated successfully');
        updateModal.style.display = 'none';
        location.reload();
      } else {
        console.error('Failed to update brand');
      }
    };
    xhr.send('brand-id=' + encodeURIComponent(id) + '&brand-name=' + encodeURIComponent(brandName));
  });
});
