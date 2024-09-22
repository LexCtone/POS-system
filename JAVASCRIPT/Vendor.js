document.addEventListener('DOMContentLoaded', () => {
  const updateModal = document.getElementById('update-vendor-modal');
  const addModal = document.getElementById('add-vendor-modal');
  const closeUpdateButton = document.querySelector('#update-vendor-modal .close-button');
  const closeAddButton = document.querySelector('#add-vendor-modal .close-button');
  const updateForm = document.getElementById('update-vendor-form');
  const addForm = document.getElementById('add-vendor-form');
  const openUpdateButtons = document.querySelectorAll('.update-button');
  const openAddButton = document.getElementById('add-vendor-button');
  const vendorTable = document.getElementById('vendor-table'); // Assuming you have a table with id 'vendor-table'

  // Function to open the update modal with vendor data
  function openUpdateModal(vendor) {
    document.getElementById('update-vendor-id').value = vendor.id;
    document.getElementById('update-vendor-name').value = vendor.vendorName;
    document.getElementById('update-contact-person').value = vendor.contactPerson;
    document.getElementById('update-contact-number').value = vendor.contactNumber;
    document.getElementById('update-address').value = vendor.address;
    document.getElementById('update-email').value = vendor.email;
    document.getElementById('update-fax').value = vendor.fax;
    updateModal.style.display = 'block';
  }

  // Function to open the add modal
  function openAddModal() {
    addModal.style.display = 'block';
  }

  // Function to add a vendor row to the table
  function addVendorToTable(vendor) {
    const row = document.createElement('tr');
    row.innerHTML = `
      <td>${vendor.vendor}</td>
      <td>${vendor.contact}</td>
      <td>${vendor.telephone}</td>
      <td>${vendor.address}</td>
      <td>${vendor.email}</td>
      <td>${vendor.fax}</td>
      <td><button class="update-button" data-id="${vendor.id}" data-vendor-name="${vendor.vendor}" data-contact-person="${vendor.contact}" data-contact-number="${vendor.telephone}" data-address="${vendor.address}" data-email="${vendor.email}" data-fax="${vendor.fax}">Update</button></td>
    `;
    vendorTable.appendChild(row);
  }

  // Event listener for the close buttons
  closeUpdateButton.addEventListener('click', () => {
    updateModal.style.display = 'none';
  });

  closeAddButton.addEventListener('click', () => {
    addModal.style.display = 'none';
  });

  // Event listener for clicking outside the modals
  window.addEventListener('click', (event) => {
    if (event.target === updateModal) {
      updateModal.style.display = 'none';
    } else if (event.target === addModal) {
      addModal.style.display = 'none';
    }
  });

  // Event listener for the update buttons
  document.addEventListener('click', (event) => {
    if (event.target && event.target.classList.contains('update-button')) {
      const vendor = {
        id: event.target.getAttribute('data-id'),
        vendorName: event.target.getAttribute('data-vendor-name'),
        contactPerson: event.target.getAttribute('data-contact-person'),
        contactNumber: event.target.getAttribute('data-contact-number'),
        address: event.target.getAttribute('data-address'),
        email: event.target.getAttribute('data-email'),
        fax: event.target.getAttribute('data-fax')
      };
      openUpdateModal(vendor);
    }
  });

  const searchInput = document.getElementById('search-input');
  const VendorTable = document.getElementById('vendor-table');

  searchInput.addEventListener('input', () => {
    const searchTerm = searchInput.value.toLowerCase();
    const rows = VendorTable.getElementsByTagName('tr');

    for (let i = 1; i < rows.length; i++) {
      const cells = rows[i].getElementsByTagName('td');
      let rowText = '';
      for (let j = 0; j < cells.length; j++) {
        rowText += cells[j].innerText.toLowerCase();
      }

      rows[i].style.display = rowText.includes(searchTerm) ? '' : 'none';
    }
  });  

  // Event listener for the add vendor button
  openAddButton.addEventListener('click', openAddModal);

  // Form submission for updating vendor
  updateForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(updateForm);

    try {
      const response = await fetch('update_vendor.php', {
        method: 'POST',
        body: formData
      });

      if (response.ok) {
        console.log('Vendor updated successfully');
        updateModal.style.display = 'none';
        location.reload(); // Reload page to reflect updates
      } else {
        console.error('Failed to update vendor');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  });

  // Form submission for adding vendor
  addForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const formData = new FormData(addForm);
  
    try {
      const response = await fetch('add_vendor.php', {
        method: 'POST',
        body: formData
      });
  
      if (response.ok) {
        console.log('Vendor added successfully');
        addModal.style.display = 'none';
        location.reload(); // Reload page to reflect the new vendor
      } else {
        console.error('Failed to add vendor');
      }
    } catch (error) {
      console.error('Error:', error);
    }
  });
});  