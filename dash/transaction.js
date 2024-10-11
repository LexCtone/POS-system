document.addEventListener('DOMContentLoaded', function() {
    const transactionNoElement = document.getElementById('transactionNo');
    const transactionDateElement = document.getElementById('transactionDate');
    const clockElement = document.getElementById('clock');
    const dateElement = document.getElementById('date');
    const barcodeInput = document.getElementById('barcodeInput');
    const searchProductBtn = document.getElementById('searchProductBtn');
    const settlePaymentBtn = document.querySelector('.settle-btn');
    const settlePaymentModal = document.getElementById('settle_payment');
    const tableBody = document.querySelector('.transaction-table tbody');
    const addDiscountBtn = document.getElementById('addDiscountBtn');
    const discountModal = document.querySelector('.discount-modal');
    const perPurchaseDiscountModal = document.getElementById('perPurchaseDiscountModal');
    const dailySalesModal = document.getElementById('dailySalesModal');
    const dailySalesBtn = document.getElementById('dailySalesBtn');    
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close-button');
    const searchInput = document.querySelector('#search-barcode');
    const productTableBody = document.querySelector('#product-table-body');
    const clearCartBtn = document.querySelector('button.clear-btn:not(#searchProductBtn)');
    const headerTotalSalesElement = document.getElementById('headerTotalSales');
    const modalTotalSalesElement = document.getElementById('modalTotalSales');
    const transactionDateDisplayElement = document.getElementById('transactionDateDisplay');
    let transactionCounter = 1;

    let currentView = 'item'; // Default view

    document.querySelectorAll('.close-button').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });

    document.querySelectorAll('#cancelOrderModal .close, #cancelTransactionModal .close').forEach(closeBtn => {
        closeBtn.addEventListener('click', (event) => {
            event.preventDefault();
            closeModal(event.target.closest('.modal'));
        });
    });
    
    [cancelOrderModal, cancelTransactionModal].forEach(modal => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal(modal);
            }
        });
    });

    function toggleSalesView() {
        currentView = currentView === 'item' ? 'transaction' : 'item';
        const toggleButton = document.getElementById('toggleView');
        toggleButton.innerText = currentView === 'transaction' ? 'Switch to Per Item View' : 'Switch to Transaction View';
        updateTableHeaders();
        filterSales();
    }
    
    function updateTableHeaders() {
        let tableHeader = document.querySelector('#salesTable thead tr');
        if (currentView === 'transaction') {
            tableHeader.innerHTML = `
                <th>#</th>
                <th>Invoice No.</th>
                <th>Date</th>
                <th>Cashier</th>
                <th>Total</th>
                <th>View Details</th>
                <th>Action</th>`;
        } else {
            tableHeader.innerHTML = `
                <th>#</th>
                <th>Invoice No.</th>
                <th>Barcode</th>
                <th>Description</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Discount</th>
                <th>Total</th>
                <th>Cashier</th>
                <th>Action</th>`;
        }
    }
    
    document.getElementById('toggleView').addEventListener('click', toggleSalesView);
    
    filterSales();
    
    function generateTransactionNo() {
        const now = new Date();
        const dateStr = now.toISOString().slice(0, 10).replace(/-/g, '');
        const timeStr = now.getTime().toString().slice(-6);
        const randomStr = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
        const transactionNo = `${dateStr}${timeStr}${randomStr}`;
        transactionNoElement.textContent = transactionNo;
        return transactionNo;
    }

    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = now.toLocaleDateString('en-US', options);
        transactionDateElement.textContent = formattedDate;
        dateElement.textContent = formattedDate;
        const timeString = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        clockElement.textContent = timeString;
    }

    generateTransactionNo();
    updateDateTime();
    setInterval(updateDateTime, 1000);

    function updateTransactionDateDisplay() {
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = now.toLocaleDateString('en-US', options);
        transactionDateDisplayElement.textContent = formattedDate;
    }

    updateTransactionDateDisplay();

    function scheduleNextUpdate() {
        const now = new Date();
        const tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
        const timeUntilMidnight = tomorrow - now;
        setTimeout(() => {
            updateTransactionDateDisplay();
            scheduleNextUpdate();
        }, timeUntilMidnight);
    }

    scheduleNextUpdate();

    function openModal(modal) {
        if (modal) modal.style.display = 'block';
    }

    function closeModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        
        if (modal instanceof HTMLElement) {
            modal.style.display = 'none';
        } else {
            console.warn(`Invalid modal:`, modal);
        }
    }

    function handleCloseButtonClick(event) {
        const modal = event.target.closest('.modal');
        if (modal) closeModal(modal);
    }

    closeButtons.forEach(button => button.addEventListener('click', handleCloseButtonClick));

    searchProductBtn.addEventListener('click', () => {
        const searchModal = document.getElementById('searchProductModal');
        openModal(searchModal);
        fetchProducts();
    });

    settlePaymentBtn.addEventListener('click', () => {
        if (isTransactionTableEmpty()) {
            alert('Cannot settle payment. No products in the transaction table.');
        } else {
            openSettlePaymentModal();
        }
    });
    
    addDiscountBtn.addEventListener('click', () => {
        openPerPurchaseDiscountModal();
    });

    dailySalesBtn.addEventListener('click', (event) => {
        event.preventDefault();
        openModal(dailySalesModal);
        filterSales();
    });

    window.addEventListener('click', (event) => {
        modals.forEach(modal => {
            if (event.target === modal) closeModal(modal);
        });
    });

    barcodeInput.addEventListener('keypress', async (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            const barcode = barcodeInput.value.trim();
            if (barcode) {
                await fetchProduct(barcode);
                barcodeInput.value = '';
            }
        }
    });

    async function fetchProduct(barcode) {
        try {
            const response = await fetch(`../fetch_products.php?barcode=${encodeURIComponent(barcode)}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();
            if (data.error) {
                alert(data.error);
            } else if (Array.isArray(data) && data.length > 0) {
                addProductToTable(data[0]);
            } else if (typeof data === 'object' && data.id) {
                addProductToTable(data);
            } else {
                alert('Product not found');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while fetching product data.');
        }
    }

    function checkStockLevel(quantity) {
        if (quantity === 0) return { status: 'out_of_stock', message: 'Restock needed. Product is out of stock.' };
        if (quantity < 10) return { status: 'critical', message: 'Product has critical stock state.' };
        return { status: 'normal', message: '' };
    }

    function addProductToTable(data) {
        if (!data || !data.id || !data.Barcode || !data.Description || !data.Price || !data.Quantity) {
            console.error('Invalid product data:', data);
            alert('Invalid product data. Please try again.');
            return;
        }
    
        const price = parseFloat(data.Price);
        
        if (isNaN(price) || price <= 0) {
            console.error('Invalid price data:', data.Price);
            alert('Invalid price data. Please try again.');
            return;
        }
    
        const stockStatus = checkStockLevel(data.Quantity);
        if (stockStatus.status === 'out_of_stock') {
            alert(stockStatus.message);
            return;
        }
    
        let existingRow = Array.from(tableBody.querySelectorAll('tr')).find(row => 
            row.dataset.productId === data.id.toString()
        );
    
        if (existingRow) {
            const quantityInput = existingRow.querySelector('.quantity-input');
            const currentQuantity = parseInt(quantityInput.value);
            const newQuantity = currentQuantity + 1;
            
            if (newQuantity > data.Quantity) {
                alert('Cannot add more items than available in stock.');
                return;
            }
            
            quantityInput.value = newQuantity;
            updateRowTotal(existingRow);
        } else {
            const row = document.createElement('tr');
            row.dataset.productId = data.id.toString();
            row.dataset.barcode = data.Barcode;
            row.innerHTML = `
                <td>${transactionCounter}</td>
                <td>${data.Description}</td>
                <td class="price-cell">₱${price.toFixed(2)}</td>
                <td><input type="number" class="quantity-input" value="1" min="1" max="${data.Quantity}" style="width: 50px;"></td>
                <td class="discount-amount-cell">₱0.00</td>
                <td class="total-cell">₱${price.toFixed(2)}</td>
                <td>
                    <button class="remove-btn">Remove</button>
                    <button class="discount-btn">Discount</button>
                </td>
            `;
            row.dataset.originalPrice = price;
            row.dataset.discountPercent = '0';
            tableBody.appendChild(row);
            transactionCounter++;
            
            const quantityInput = row.querySelector('.quantity-input');
            quantityInput.addEventListener('input', () => {
                const newQuantity = parseInt(quantityInput.value);
                if (newQuantity > data.Quantity) {
                    alert('Cannot add more items than available in stock.');
                    quantityInput.value = data.Quantity;
                }
                updateRowTotal(row);
            });
            
            const removeBtn = row.querySelector('.remove-btn');
            removeBtn.addEventListener('click', () => {
                row.remove();
                updateTotalSales();
                updateSettlePaymentButtonState();
            });
            
            const discountBtn = row.querySelector('.discount-btn');
            discountBtn.addEventListener('click', () => {
                openDiscountModal(row);
            });
        }
    
        updateTotalSales();
        updateSettlePaymentButtonState();
    
        if (stockStatus.status === 'critical') {
            alert(stockStatus.message);
        }
    }

    function updateRowTotal(row) {
        const originalPrice = parseFloat(row.dataset.originalPrice);
        const discountPercent = parseFloat(row.dataset.discountPercent) || 0;
        const quantityInput = row.querySelector('.quantity-input');
        const discountAmountCell = row.querySelector('.discount-amount-cell');
        const totalCell = row.querySelector('.total-cell');
        const quantity = parseInt(quantityInput.value) || 1;

        const discountAmount = (originalPrice * discountPercent / 100) * quantity;
        const total = (originalPrice * quantity) - discountAmount;

        discountAmountCell.textContent = `₱${discountAmount.toFixed(2)}`;
        totalCell.textContent = `₱${total.toFixed(2)}`;
        updateTotalSales();
    }

    function updateTotalSales() {
        const totalSales = Array.from(tableBody.querySelectorAll('tr'))
            .reduce((total, row) => {
                const totalCell = row.querySelector('.total-cell');
                const rowTotal = parseFloat(totalCell.textContent.replace('₱', '')) || 0;
                return total + rowTotal;
            }, 0);
        
        if (headerTotalSalesElement) {
            headerTotalSalesElement.textContent = `₱${totalSales.toFixed(2)}`;
        }
        
        const totalAmountElement = document.getElementById('total-amount');
        if (totalAmountElement) {
            totalAmountElement.textContent = `₱${totalSales.toFixed(2)}`;
        }
        
        updateSettlePaymentButtonState();
    }

    function updateModalTotalSales(total) {
        if (modalTotalSalesElement) {
            modalTotalSalesElement.textContent = `Total Sales: ₱${parseFloat(total).toFixed(2)}`;
        }
    }

    function openDiscountModal(row) {
        const productName = row.cells[1].textContent;
        const originalPrice = parseFloat(row.dataset.originalPrice);
        const quantity = parseInt(row.querySelector('.quantity-input').value);
        const discountPercentInput = document.getElementById('discountPercent');
        const discountAmountInput = document.getElementById('discountAmount');
        const totalPriceInput = document.getElementById('totalPrice');

        document.getElementById('discountProductName').textContent = productName;
        totalPriceInput.value = (originalPrice * quantity).toFixed(2);
        discountPercentInput.value = row.dataset.discountPercent || '';
        discountAmountInput.value = '';

        openModal(discountModal);

        const confirmDiscountBtn = document.getElementById('confirmDiscount');
        confirmDiscountBtn.onclick = () => applyDiscount(row);

        discountPercentInput.addEventListener('input', () => calculateDiscount(originalPrice, quantity));
    }

    function calculateDiscount(price, quantity) {
        const discountPercent = parseFloat(document.getElementById('discountPercent').value) || 0;
        const discountAmount = (price * quantity * discountPercent) / 100;
        const discountedTotal = (price * quantity) - discountAmount;

        document.getElementById('discountAmount').value = discountAmount.toFixed(2);
        document.getElementById('totalPrice').value = discountedTotal.toFixed(2);
    }

    function applyDiscount(row) {
        const discountPercent = parseFloat(document.getElementById('discountPercent').value) || 0;

        if (discountPercent < 0 || discountPercent > 100) {
            alert('Please enter a valid discount percentage between 0 and 100.');
            return;
        }

        row.dataset.discountPercent = discountPercent;
        updateRowTotal(row);
        closeModal(discountModal);
    }

    function openPerPurchaseDiscountModal() {
        const totalAmount = parseFloat(headerTotalSalesElement.textContent.replace('₱', ''));
        document.getElementById('perPurchaseTotalAmount').textContent = `₱${totalAmount.toFixed(2)}`;
        document.getElementById('perPurchaseDiscountPercent').value = '';
        document.getElementById('perPurchaseDiscountAmount').value = '';
        document.getElementById('perPurchaseFinalAmount').textContent = `₱${totalAmount.toFixed(2)}`;
        openModal(perPurchaseDiscountModal);
    }

    function calculatePerPurchaseDiscount() {
        const totalAmount = parseFloat(headerTotalSalesElement.textContent.replace('₱', ''));
        const discountPercent = parseFloat(document.getElementById('perPurchaseDiscountPercent').value) || 0;
        
        if (discountPercent < 0 || discountPercent > 100) {
            alert('Please enter a valid discount percentage between 0 and 100.');
            return;
        }

        const discountAmount = (totalAmount * discountPercent) / 100;
        const finalAmount = totalAmount - discountAmount;

        document.getElementById('perPurchaseDiscountAmount').value = discountAmount.toFixed(2);
        document.getElementById('perPurchaseFinalAmount').textContent = `₱${finalAmount.toFixed(2)}`;
    }

    function applyPerPurchaseDiscount() {
        const discountAmount = parseFloat(document.getElementById('perPurchaseDiscountAmount').value) || 0;
    
        if (discountAmount <= 0) {
            alert('Please enter a valid discount amount.');
            return;
        }
    
        const rows = tableBody.querySelectorAll('tr');
        let totalBeforeDiscount = 0;
    
        rows.forEach(row => {
            const totalCell = row.querySelector('.total-cell');
            const rowTotal = parseFloat(totalCell.textContent.replace('₱', ''));
            totalBeforeDiscount += rowTotal;
        });
    
        rows.forEach(row => {
            const totalCell = row.querySelector('.total-cell');
            const discountAmountCell = row.querySelector('.discount-amount-cell');
            const rowTotal = parseFloat(totalCell.textContent.replace('₱', ''));
            const proportion = rowTotal / totalBeforeDiscount;
            const rowDiscount = discountAmount * proportion;
            const newRowTotal = rowTotal - rowDiscount;
    
            const currentDiscount = parseFloat(discountAmountCell.textContent.replace('₱', ''));
            const newDiscount = currentDiscount + rowDiscount;
    
            discountAmountCell.textContent = `₱${newDiscount.toFixed(2)}`;
            totalCell.textContent = `₱${newRowTotal.toFixed(2)}`;
        });
    
        updateTotalSales();
        closeModal(perPurchaseDiscountModal);
    }

    document.getElementById('perPurchaseDiscountPercent').addEventListener('input', calculatePerPurchaseDiscount);
    document.getElementById('applyPerPurchaseDiscount').addEventListener('click', applyPerPurchaseDiscount);

    async function fetchProducts(barcode = '') {
        try {
            const response = await fetch('../fetch_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `barcode=${encodeURIComponent(barcode)}`,
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const products = await response.json();
            displayProducts(products, barcode);
        } catch (error) {
            console.error('Error fetching products:', error);
            alert('An error occurred while fetching products.');
        }
    }

    function displayProducts(products, barcode) {
        productTableBody.innerHTML = '';
        let filteredProducts = products;
        
        if (barcode) {
            filteredProducts = products.filter(product => 
                product.Barcode.toLowerCase().includes(barcode.toLowerCase()) ||
                product.Description.toLowerCase().includes(barcode.toLowerCase())
            );
        }
    
        if (filteredProducts.length === 0) {
            productTableBody.innerHTML = '<tr><td colspan="7">No products found</td></tr>';
            return;
        }
    
        filteredProducts.forEach(product => {
            const row = `
                <tr>
                    <td>${product.id}</td>
                    <td>${product.Barcode}</td>
                    <td>${product.Description}</td>
                    <td>${product.Category || 'N/A'}</td>
                    <td>${product.Price}</td>
                    <td>${product.Quantity}</td>
                    <td><button class="select-product" data-id="${product.id}" data-barcode="${product.Barcode}" data-description="${product.Description}" data-price="${product.Price}" data-category="${product.Category || 'N/A'}" data-quantity="${product.Quantity}">Select</button></td>
                </tr>
            `;
            productTableBody.insertAdjacentHTML('beforeend', row);
        });
    
        productTableBody.querySelectorAll('.select-product').forEach(button => {
            button.addEventListener('click', function() {
                const productData = {
                    id: this.dataset.id,
                    Barcode: this.dataset.barcode,
                    Description: this.dataset.description,
                    Price: this.dataset.price,
                    Category: this.dataset.category,
                    Quantity: parseInt(this.dataset.quantity)
                };
                addProductToTable(productData);
                closeModal(document.getElementById('searchProductModal'));
            });
        });
    }

    searchInput.addEventListener('input', function() {
        const searchValue = this.value.trim();
        fetchProducts(searchValue);
    });

    function openUserSettingsModal() {
        const userSettingsModal = document.getElementById('userSettingsModal');
        openModal(userSettingsModal);
    }

    function closeUserSettingsModal() {
        const userSettingsModal = document.getElementById('userSettingsModal');
        closeModal(userSettingsModal);
    }

    document.getElementById('userSettingsBtn').addEventListener('click', function(e) {
        e.preventDefault();
        openUserSettingsModal();
    });

    document.querySelector('.close-button').addEventListener('click', function() {
        closeUserSettingsModal();
    });

    window.addEventListener('click', function(event) {
        const userSettingsModal = document.getElementById('userSettingsModal');
        if (event.target === userSettingsModal) {
            closeUserSettingsModal();
        }
    });


// Handle password change functionality
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    const passwordError = document.getElementById('passwordError');
    
    // Clear any previous error messages
    passwordError.style.display = 'none';
    passwordError.textContent = '';

    // Validate if the new password is the same as the current password
    if (newPassword === currentPassword) {
        passwordError.textContent = 'New password cannot be the same as the current password. Please set a new password!';
        passwordError.style.display = 'block';
        return;
    }

    // Validate new password and confirmation match
    if (newPassword !== confirmPassword) {
        passwordError.textContent = 'New passwords do not match';
        passwordError.style.display = 'block';
        return;
    }

    // Validate password length (minimum 8 characters)
    if (newPassword.length < 8) {
        passwordError.textContent = 'New password must be at least 8 characters long';
        passwordError.style.display = 'block';
        return;
    }

fetch('change_password.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: `currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
})
.then(response => response.json())
.then(data => {
    // Log the data to inspect the response
    console.log(data); // Inspect the response

    if (data.success) {
        alert(data.message); // Alert the success message
        window.location.href = '/Login.php'; // Redirect to the login page
    } else {
        passwordError.textContent = data.message; // Display the error message
        passwordError.style.display = 'block';
    }
})
.catch(error => {
    passwordError.textContent = 'An error occurred. Please try again.'; // Handle the error
    passwordError.style.display = 'block';
    console.error('Error:', error);
});
});


    function openSettlePaymentModal() {
        const totalAmount = headerTotalSalesElement.textContent;
        document.getElementById('total-amount').textContent = totalAmount;
        document.getElementById('payment-amount').value = '';
        document.getElementById('change-amount').textContent = '₱0.00';
        openModal(settlePaymentModal);
    }
    
    window.addToDisplay = function(value) {
        const display = document.getElementById('payment-amount');
        if (value === '.' && display.value.includes('.')) return;
        display.value += value;
    };
    
    window.clearDisplay = function() {
        document.getElementById('payment-amount').value = '';
        document.getElementById('change-amount').textContent = '₱0.00';
    };
    
    function gatherSaleData() {
        const tableBody = document.querySelector('.transaction-table tbody');
        const rows = tableBody.querySelectorAll('tr');
        console.log('Number of rows in gatherSaleData:', rows.length);
        const sales = [];
        
        rows.forEach(row => {
            const productId = row.dataset.productId;
            const barcode = row.dataset.barcode;
            const description = row.cells[1].textContent.trim();
            const price = parseFloat(row.cells[2].textContent.replace('₱', '').trim());
            const quantity = parseInt(row.querySelector('.quantity-input').value.trim());
            const discountAmount = parseFloat(row.cells[4].textContent.replace('₱', '').trim());
            const total = parseFloat(row.cells[5].textContent.replace('₱', '').trim());
    
            sales.push({
                product_id: productId,
                barcode: barcode,
                description: description,
                price: price,
                quantity: quantity,
                discount_amount: discountAmount,
                total: total
            });
        });
    
        return sales;
    }
    
    function printReceipt(totalAmount, paymentAmount, change) {
        const sales = gatherSaleData();
        console.log('Sales data for receipt:', sales);
    
        const receiptContent = document.getElementById('receiptContent');
        const transactionNo = document.getElementById('transactionNo').textContent;
        const cashierName = document.getElementById('cashierName').textContent;
        const currentDate = new Date().toLocaleString();
    
        let receiptHTML = `
            <div style="font-family: Arial, sans-serif; width: 300px; margin: 0 auto; padding: 20px; border: 1px solid #ccc;">
                <h2 style="text-align: center; margin-bottom: 10px;">Sales Receipt</h2>
                <p style="margin: 5px 0;"><strong>Transaction No:</strong> ${transactionNo}</p>
                <p style="margin: 5px 0;"><strong>Date:</strong> ${currentDate}</p>
                <p style="margin: 5px 0;"><strong>Cashier:</strong> ${cashierName}</p>
                <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                    <thead>
                        <tr>
                            <th style="text-align: left; padding: 5px; border-bottom: 1px solid #000;">Item</th>
                            <th style="text-align: right; padding: 5px; border-bottom: 1px solid #000;">Qty</th>
                            <th style="text-align: right; padding: 5px; border-bottom: 1px solid #000;">Price</th>
                            <th style="text-align: right; padding: 5px; border-bottom: 1px solid #000;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
        `;
    
        if (sales.length === 0) {
            receiptHTML += `
                <tr>
                    <td colspan="4" style="text-align: center; padding: 5px;">No items</td>
                </tr>
            `;
        } else {
            sales.forEach(sale => {
                receiptHTML += `
                    <tr>
                        <td style="text-align: left; padding: 5px;">${sale.description}</td>
                        <td style="text-align: right; padding: 5px;">${sale.quantity}</td>
                        <td style="text-align: right; padding: 5px;">₱${sale.price.toFixed(2)}</td>
                        <td style="text-align: right; padding: 5px;">₱${sale.total.toFixed(2)}</td>
                    </tr>
                `;
            });
        }
    
        receiptHTML += `
                    </tbody>
                </table>
                <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                <p style="text-align: right; margin: 5px 0;"><strong>Total Amount:</strong> ₱${totalAmount.toFixed(2)}</p>
                <p style="text-align: right; margin: 5px 0;"><strong>Payment:</strong> ₱${paymentAmount.toFixed(2)}</p>
                <p style="text-align: right; margin: 5px 0;"><strong>Change:</strong> ₱${change.toFixed(2)}</p>
                <hr style="border: none; border-top: 1px dashed #000; margin: 10px 0;">
                <p style="text-align: center; margin-top: 20px;">Thank you for your purchase!</p>
            </div>
        `;
    
        receiptContent.innerHTML = receiptHTML;
    
        const printReceiptModal = document.getElementById('printReceiptModal');
        openModal(printReceiptModal);

    
        // Set up the event listener for the print button
        const printButton = document.getElementById('printButton');
        if (printButton) {
            printButton.addEventListener('click', function() {
                const printWindow = window.open('', '', 'width=400,height=600');
                printWindow.document.open();
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Print Receipt</title>
                            <style>
                                body { font-family: Arial, sans-serif; }
                                @media print {
                                    body { margin: 0; padding: 0; }
                                    @page { size: 80mm 297mm; margin: 0; }
                                }
                            </style>
                        </head>
                        <body>
                            ${receiptHTML}
                            <script>
                                window.onload = function() {
                                    window.print();
                                    window.onafterprint = function() {
                                        window.close();
                                    };
                                };
                            </script>
                        </body>
                    </html>
                `);
                printWindow.document.close();
            });
        }
    
        // Add event listener to close the modal when clicking outside
        printReceiptModal.addEventListener('click', function(event) {
            if (event.target === printReceiptModal) {
                closeModal('printReceiptModal');
            }
        });
    }
    
    window.calculateChange = async function() {
        const totalAmount = parseFloat(document.getElementById('total-amount').textContent.replace('₱', ''));
        const paymentAmount = parseFloat(document.getElementById('payment-amount').value);
        if (isNaN(paymentAmount)) {
            alert('Please enter a valid payment amount.');
            return;
        }
        if (paymentAmount < totalAmount) {
            alert('Payment amount is less than the total amount.');
            return;
        }
        const change = paymentAmount - totalAmount;
        document.getElementById('change-amount').textContent = '₱' + change.toFixed(2);
    
        try {
            await saveTransaction(totalAmount, paymentAmount, change);
            printReceipt(totalAmount, paymentAmount, change);
            alert('Transaction completed successfully!');
            
            // Close the settle payment modal
            closeModal('settle_payment');
    
            // Clear the transaction table and generate new transaction number
            clearTransactionTable();
            generateTransactionNo();
    
            // The print receipt modal will remain open for the user to interact with
        } catch (error) {
            console.error('Error saving transaction:', error);
            alert('An error occurred while saving the transaction. Receipt printing failed.');
        }
    };
    
    async function saveTransaction(totalAmount, paymentAmount, change) {
        const sales = gatherSaleData();
        console.log('Sales data in saveTransaction:', sales);
        const transactionData = {
            invoice: document.getElementById('transactionNo').textContent,
            sales: sales,
            totalAmount: totalAmount,
            paymentAmount: paymentAmount,
            change: change
        };
    
        try {
            const response = await fetch('save_sales.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(transactionData),
            });
    
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
    
            const result = await response.json();
    
            if (!result.success) {
                throw new Error(result.message || 'Failed to save transaction');
            }
    
            console.log('Transaction saved successfully!');
        } catch (error) {
            console.error('Error saving transaction:', error);
            throw error; // Re-throw the error to be caught in calculateChange
        }
    }

    function clearTransactionTable() {
        const tableBody = document.querySelector('.transaction-table tbody');
        tableBody.innerHTML = '';
        updateTotalSales();
        resetTransaction();
    }

    function resetTransaction() {
        transactionCounter = 1;
        document.getElementById('headerTotalSales').textContent = '₱0.00';
    }

    function clearCart() {
        tableBody.innerHTML = '';
        updateTotalSales();
    }

    clearCartBtn.addEventListener('click', () => {
        if (confirm('Are you sure you want to clear the cart?')) {
            clearCart();
        }
    });

    function isTransactionTableEmpty() {
        return tableBody.children.length === 0;
    }

    function updateSettlePaymentButtonState() {
        settlePaymentBtn.disabled = isTransactionTableEmpty();
    }

    function filterSales() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const cashierId = document.getElementById('cashier').value;

        fetch(`transaction.php?action=fetch_sales&dateFrom=${dateFrom}&dateTo=${dateTo}&cashierId=${cashierId}&view=${currentView}`)
            .then(response => response.json())
            .then(data => {
                const salesDataBody = document.getElementById('salesData');
                salesDataBody.innerHTML = '';

                if (data.sales && data.sales.length > 0) {
                    if (currentView === 'item') {
                        displayItemView(data.sales, salesDataBody);
                    } else {
                        displayTransactionView(data.sales, salesDataBody);
                    }
                    updateModalTotalSales(data.totalSales);
                } else {
                    salesDataBody.innerHTML = `<tr><td colspan="${currentView === 'item' ? '10' : '7'}">No sales data found</td></tr>`;
                    updateModalTotalSales(0);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching sales data.');
            });
    }

    document.querySelector('.filter-controls button').addEventListener('click', filterSales);

    function displayItemView(sales, container) {
        container.innerHTML = '';
        const filteredSales = sales.filter(sale => sale.status !== 'voided');
    
        filteredSales.forEach((sale, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${sale.invoice}</td>
                    <td>${sale.barcode}</td>
                    <td>${sale.description}</td>
                    <td>₱${parseFloat(sale.price).toFixed(2)}</td>
                    <td>${sale.quantity}</td>
                    <td>₱${parseFloat(sale.discount_amount).toFixed(2)}</td>
                    <td>₱${parseFloat(sale.total).toFixed(2)}</td>
                    <td>${sale.cashier_name || 'N/A'}</td>
                    <td><button class="void-button" data-sale='${JSON.stringify(sale)}'>Void</button></td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', row);
        });
    
        document.querySelectorAll('.void-button').forEach(button => {
            button.addEventListener('click', function() {
                try {
                    const saleData = this.getAttribute('data-sale');
                    if (saleData) {
                        const sale = JSON.parse(saleData);
                        openCancelOrderModal(sale);
                    } else {
                        console.error('Sale data is undefined');
                        alert('Error: Sale data is missing. Please try again.');
                    }
                } catch (error) {
                    console.error('Error parsing sale data:', error);
                    alert('Error: Unable to process sale data. Please try again.');
                }
            });
        });
    }

    function openCancelOrderModal(sale) {
        document.getElementById('id').value = sale.id;
        document.getElementById('productCode').value = sale.barcode;
        document.getElementById('description').value = sale.description;
        document.getElementById('transaction').value = sale.invoice;
        document.getElementById('price').value = sale.price;
        document.getElementById('qtyDiscount').value = `${sale.quantity} / ${sale.discount_amount}`;
        document.getElementById('total').value = sale.total;
        document.getElementById('voidBy').value = document.getElementById('cashierName').textContent;
    
        const cancelQtyInput = document.getElementById('cancelQty');
        const quantity = parseInt(sale.quantity);
        
        if (quantity === 1) {
            cancelQtyInput.value = '1';
            cancelQtyInput.readOnly = true;
        } else {
            cancelQtyInput.value = '';
            cancelQtyInput.readOnly = false;
            cancelQtyInput.max = quantity;
            cancelQtyInput.placeholder = `Enter quantity (max ${quantity})`;
        }
    
        closeModal('dailySalesModal');
        openModal(document.getElementById('cancelOrderModal'));
    }
    
    document.getElementById('cancelQty').addEventListener('input', function() {
        const maxQty = parseInt(this.max);
        let enteredQty = parseInt(this.value);
    
        if (isNaN(enteredQty) || enteredQty < 1) {
            this.value = '';
        } else if (enteredQty > maxQty) {
            this.value = maxQty;
        }
    });
    
    function handleCancelOrder(event) {
        event.preventDefault();
        
        function getElementValue(id) {
            const element = document.getElementById(id);
            if (!element) {
                console.error(`Element with id '${id}' not found`);
                return null;
            }
            return element.value;
        }
    
        const saleId = getElementValue('id');
        const productCode = getElementValue('productCode');
        const cancelQtyElement = document.getElementById('cancelQty');
        const cancelQty = cancelQtyElement ? parseInt(cancelQtyElement.value) : null;
        const maxQty = cancelQtyElement ? parseInt(cancelQtyElement.getAttribute('max')) : null;
        const cancelReason = getElementValue('cancelReason');
        const addToInventoryElement = document.getElementById('addToInventory');
        const addToInventory = addToInventoryElement ? addToInventoryElement.value === 'yes' : false;
        const voidBy = getElementValue('voidBy');
        
        // Get the cashier's name instead of their title
        const cashierNameElement = document.getElementById('cashierName');
        const cancelledBy = cashierNameElement ? cashierNameElement.textContent.trim() : 'Unknown Cashier';
    
        console.log('Gathered form data:', { saleId, productCode, cancelQty, maxQty, cancelReason, addToInventory, voidBy, cancelledBy });
    
        if (!cancelQty || cancelQty < 1 || (maxQty !== null && cancelQty > maxQty)) {
            alert(`Please enter a valid cancel quantity${maxQty !== null ? ` between 1 and ${maxQty}` : ''}.`);
            return;
        }
        
        if (!cancelReason) {
            alert('Please provide a reason for the cancellation.');
            return;
        }
        
        if (!saleId || !productCode) {
            alert('Missing Sale ID or Product Code.');
            return;
        }
        
        const requestData = {
            saleId: parseInt(saleId),
            productCode: productCode,
            cancelQty: cancelQty,
            voidBy: voidBy,
            cancelledBy: cancelledBy, // This now contains the cashier's name
            cancelReason: cancelReason,
            addToInventory: addToInventory
        };
    
        // Send the request to the server
        fetch('void_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Item voided successfully.');
                closeModal('cancelOrderModal');
                if (typeof filterSales === 'function') {
                    filterSales();
                }
            } else {
                throw new Error(data.message || 'Failed to void item');
            }
        })
        .catch(error => {
            console.error('Error voiding item:', error);
            alert('An error occurred while voiding the item. Please try again.');
        });
    }
    
    document.querySelector('#cancelOrderModal .cancel-modal-btn').addEventListener('click', handleCancelOrder);

    function displayTransactionView(transactions, container) {
        transactions.forEach((transaction, index) => {
            const row = `
                <tr>
                    <td>${index + 1}</td>
                    <td>${transaction.invoice}</td>
                    <td>${transaction.date}</td>
                    <td>${transaction.cashier_name}</td>
                    <td>₱${parseFloat(transaction.total).toFixed(2)}</td>
                    <td><button class="view-details-button" data-transaction='${JSON.stringify(transaction)}'>View Details</button></td>
                    <td><button class="cancel-transaction-button" data-transaction='${JSON.stringify(transaction)}'>Cancel Transaction</button></td>
                </tr>
            `;
            container.insertAdjacentHTML('beforeend', row);
        });
    
        document.querySelectorAll('.view-details-button').forEach(button => {
            button.addEventListener('click', function() {
                const transaction = JSON.parse(this.dataset.transaction);
                viewTransactionDetails(transaction);
            });
        });
    
        document.querySelectorAll('.cancel-transaction-button').forEach(button => {
            button.addEventListener('click', function() {
                const transaction = JSON.parse(this.dataset.transaction);
                openCancelTransactionModal(transaction);
            });
        });
    }

    function viewTransactionDetails(transaction) {
        console.log(transaction);
    
        const transactModal = document.getElementById('transactionModal');
        const modalContent = document.getElementById('transactionDetailsContent');
        
        modalContent.innerHTML = '';
    
        const transactionHtml = `
            <p><strong>Invoice:</strong> ${transaction.invoice}</p>
            <p><strong>Date:</strong> ${transaction.date}</p>
            <p><strong>Cashier:</strong> ${transaction.cashier_name}</p>
            <p><strong>Total Amount:</strong> ₱${parseFloat(transaction.total).toFixed(2)}</p>
            <h3>Items:</h3>
            <ul>
                ${Array.isArray(transaction.items) && transaction.items.length > 0 ? 
                    transaction.items.map(item => `
                    <li>${item.description} - Qty: ${item.quantity}, Price: ₱${parseFloat(item.price).toFixed(2)}</li>
                    `).join('') 
                : '<li>No items available</li>'}
            </ul>
        `;
        modalContent.innerHTML = transactionHtml;
    
        transactModal.style.display = 'block';
    
        const closeButton = document.querySelector('.close-button');
        closeButton.addEventListener('click', () => {
            transactModal.style.display = 'none';
        });
    
        window.addEventListener('click', function(event) {
            if (event.target === transactModal) {
                transactModal.style.display = 'none';
            }
        });
    }

    function openCancelTransactionModal(transaction) {
        const safeSetValue = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.value = value;
            } else {
                console.warn(`Element with id "${id}" not found`);
            }
        };
    
        safeSetValue('transactionId', transaction.invoice || 'TRX-000000');
        safeSetValue('transactionTotal', `₱${parseFloat(transaction.total).toFixed(2)}`);
    
        const now = new Date();
        safeSetValue('transactionDate', now.toISOString().slice(0, 19).replace('T', ' '));
    
        safeSetValue('transactionCancelReason', '');
    
        closeModal('dailySalesModal');
        openModal(document.getElementById('cancelTransactionModal'));
    }
    
 function handleCancelTransaction() {
    const invoice = document.getElementById('transactionId').value;
    const transactionTotal = parseFloat(document.getElementById('transactionTotal').value.replace('₱', ''));
    const voidBy = document.getElementById('transactionVoidBy').value;
    const cancelReason = document.getElementById('transactionCancelReason').value;

    if (!cancelReason.trim()) {
        alert('Please provide a reason for cancellation.');
        return;
    }

    if (!invoice || !transactionTotal) {
        alert('Invoice and total amount are required.');
        return;
    }

    if (!confirm(`Are you sure you want to cancel transaction ${invoice}?`)) {
        return;
    }

    const requestData = {
        invoice: invoice,
        totalAmount: transactionTotal,
        voidBy: voidBy,
        cancelReason: cancelReason
    };

    fetch('void_transaction.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData),
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`Transaction ${invoice} cancelled successfully.`);
            closeModal('cancelTransactionModal');
            filterSales();
        } else {
            throw new Error(data.message || 'Cancellation failed');
        }
    })
    .catch(error => {
        console.error('Error cancelling transaction:', error);
        alert(`An error occurred while cancelling the transaction: ${error.message}. Please check the server logs for more details.`);
    });
}
    function updateSalesDisplay(invoice) {
        // Find the row with the cancelled invoice
        const row = document.querySelector(`tr[data-invoice="${invoice}"]`);
        if (row) {
            // Update the quantity and total columns
            const quantityCell = row.querySelector('.quantity-cell');
            const totalCell = row.querySelector('.total-cell');
            if (quantityCell) quantityCell.textContent = '0';
            if (totalCell) totalCell.textContent = '₱0.00';
            
            // Optionally, add a visual indicator that the transaction is voided
            row.classList.add('voided-transaction');
        }
    }
    
    document.querySelector('#cancelTransactionModal .cancel-modal-btn').addEventListener('click', handleCancelTransaction);
    
    document.addEventListener('DOMContentLoaded', function() {
        const cancelOrderModal = document.getElementById('cancelOrderModal');
        const cancelTransactionModal = document.getElementById('cancelTransactionModal');

        document.querySelector('#cancelOrderModal .cancel-modal-btn').addEventListener('click', handleCancelOrder);
        document.querySelector('#cancelTransactionModal .cancel-modal-btn').addEventListener('click', handleCancelTransaction);

        document.querySelectorAll('#cancelOrderModal .close, #cancelTransactionModal .close').forEach(closeBtn => {
            closeBtn.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal(event.target.closest('.modal'));
            });
        });

        [cancelOrderModal, cancelTransactionModal].forEach(modal => {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal(modal);
                }
            });
        });

        document.getElementById('cancelQty').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            const maxQty = parseInt(this.max);
            const enteredQty = parseInt(this.value);
            if (enteredQty > maxQty) {
                this.value = maxQty;
            } else if (enteredQty < 1 || isNaN(enteredQty)) {
                this.value = '';
            }
        });
    });
});