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
    let transactionCounter = 1;

    // User Settings Modal
    const userSettingsModal = document.getElementById('userSettingsModal');
    const userSettingsBtn = document.querySelector('.sidebar .menu li:nth-child(5) a');
    const userSettingsCloseBtn = userSettingsModal.querySelector('.close-button');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const passwordError = document.getElementById('passwordError');

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

    function openModal(modal) {
        if (modal) modal.style.display = 'block';
    }

    function closeModal(modal) {
        if (modal) modal.style.display = 'none';
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
        const discountAmountInput = document.getElementById('discountAmount');
        const totalPriceInput = document.getElementById('totalPrice');

        if (discountPercent < 0 || discountPercent > 100) {
            discountAmountInput.value = 'Invalid %';
            totalPriceInput.value = (price * quantity).toFixed(2);
            return;
        }

        const discountAmount = (price * quantity * discountPercent) / 100;
        const discountedTotal = (price * quantity) - discountAmount;

        discountAmountInput.value = discountAmount.toFixed(2);
        totalPriceInput.value = discountedTotal.toFixed(2);
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
    
        // Calculate total before discount
        rows.forEach(row => {
            const totalCell = row.querySelector('.total-cell');
            const rowTotal = parseFloat(totalCell.textContent.replace('₱', ''));
            totalBeforeDiscount += rowTotal;
        });
    
        // Apply discount proportionally to each row
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

    // Add event listeners for the new discount modal
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
    
    window.calculateChange = function() {
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

        saveTransaction(totalAmount, paymentAmount, change);
    };

    function gatherSaleData() {
        const rows = document.querySelectorAll('.transaction-table tbody tr');
        const sales = [];
        
        rows.forEach(row => {
            const productId = row.dataset.productId;
            const barcode = row.dataset.barcode;
            const description = row.cells[1].textContent.trim();
            const price = parseFloat(row.cells[2].textContent.replace('₱', '').trim());
            const quantityInput = row.querySelector('.quantity-input');
            const quantity = quantityInput ? parseInt(quantityInput.value.trim()) : 0;
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

    async function saveTransaction(totalAmount, paymentAmount, change) {
        const sales = gatherSaleData();
        const transactionData = {
            invoice: document.getElementById('transactionNo').textContent,
            sales: sales
        };
    
        console.log('Sending transaction data:', JSON.stringify(transactionData));
    
        try {
            const response = await fetch('update_quantities.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(transactionData)
            });
    
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
    
            const responseText = await response.text();
            console.log('Raw response:', responseText);
    
            let result;
            try {
                result = JSON.parse(responseText);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                throw new Error('Invalid JSON response from server');
            }
    
            console.log('Parsed server response:', result);
    
            if (result.success) {
                console.log('Transaction successful, about to show alert');
                alert('Transaction saved successfully!');
                console.log('Alert should have been shown');
                clearCart();
                closeModal(settlePaymentModal);
                
                // Generate a new transaction number
                const newTransactionNo = generateTransactionNo();
                console.log('New transaction number generated:', newTransactionNo);
                
                // Reset the transaction counter
                transactionCounter = 1;
            } else {
                console.error('Server reported error:', result);
                alert('Error saving transaction: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error in saveTransaction:', error);
            alert('An error occurred while saving the transaction: ' + error.message);
        }
    }
    
    function clearCart() {
        console.log('Clearing cart');
        tableBody.innerHTML = '';
        updateTotalSales();
        console.log('Cart cleared');
    }
    
    function closeModal(modal) {
        console.log('Closing modal:', modal);
        if (modal) {
            modal.style.display = 'none';
            console.log('Modal closed');
        } else {
            console.log('Modal not found');
        }
    }

    function clearCart() {
        tableBody.innerHTML = '';
        updateTotalSales();
        // transactionCounter is now reset in saveTransaction
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

        fetch(`transaction.php?action=fetch_sales&dateFrom=${dateFrom}&dateTo=${dateTo}&cashierId=${cashierId}`)
            .then(response => response.json())
            .then(data => {
                const salesDataBody = document.getElementById('salesData');
                salesDataBody.innerHTML = '';

                if (data.sales && data.sales.length > 0) {
                    data.sales.forEach((sale, index) => {
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
                                <td>${sale.cashier_name}</td>
                            </tr>
                        `;
                        salesDataBody.insertAdjacentHTML('beforeend', row);
                    });
                    updateModalTotalSales(data.totalSales);
                } else {
                    salesDataBody.innerHTML = '<tr><td colspan="9">No sales data found</td></tr>';
                    updateModalTotalSales(0);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching sales data.');
            });
    }

    document.querySelector('.filter-controls button').addEventListener('click', filterSales);

    // User Settings Modal
    userSettingsBtn.addEventListener('click', function(e) {
        e.preventDefault();
        userSettingsModal.style.display = 'block';
    });

    userSettingsCloseBtn.addEventListener('click', function() {
        userSettingsModal.style.display = 'none';
    });

    changePasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (newPassword !== confirmPassword) {
            passwordError.textContent = 'New passwords do not match';
            passwordError.style.display = 'block';
            return;
        }

        if (newPassword.length < 8) {
            passwordError.textContent = 'New password must be at least 8 characters long';
            passwordError.style.display = 'block';
            return;
        }

        // Send password change request to server
        fetch('change_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `currentPassword=${encodeURIComponent(currentPassword)}&newPassword=${encodeURIComponent(newPassword)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Password changed successfully');
                userSettingsModal.style.display = 'none';
                changePasswordForm.reset();
            } else {
                passwordError.textContent = data.message;
                passwordError.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            passwordError.textContent = 'An error occurred. Please try again.';
            passwordError.style.display = 'block';
        });
    });
});