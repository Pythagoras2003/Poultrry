</main>
<footer>
    <div class="container footer-row">
        <span>&copy; <?php echo date('Y'); ?> Simgci Poultry Farm</span>
        <span>Designed for broiler and egg sales, customer updates, and financial tracking.</span>
    </div>
    <div class="watermark" aria-hidden="true"></div>
</footer>
    <!-- Order modal (shared) -->
    <div id="orderModal" class="modal" aria-hidden="true">
        <div class="modal-dialog">
            <button class="modal-close" aria-label="Close">×</button>
            <h2 id="modalTitle">Place order</h2>
            <div class="modal-product" style="margin-bottom:10px;">
                <strong id="modalProductName"></strong>
                <div id="modalProductPrice" class="hint"></div>
            </div>
            <form id="orderForm" action="index.php?page=public" method="post">
                <input type="hidden" name="action" value="public_order">
                <input type="hidden" name="product_id" id="modalProductId" value="">
                <label>Name</label>
                <input type="text" name="name" id="modalName" required>
                <div class="hint">Enter the full name so we can contact you.</div>
                <label>Quantity</label>
                <input type="number" name="quantity" id="modalQuantity" value="1" min="1" required>
                <label>Phone</label>
                <input type="text" name="phone" id="modalPhone" required placeholder="e.g. +260971234567">
                <div class="hint">Include country code if possible (e.g. +260...)</div>
                <label>Delivery instructions</label>
                <textarea name="delivery_instructions" id="modalDelivery" placeholder="e.g. Deliver to gate B, call on arrival"></textarea>
                <div style="margin-top:12px;display:flex;gap:8px;align-items:center;">
                    <div id="modalTotal" style="font-weight:700;">Total: E0.00</div>
                    <div style="flex:1"></div>
                    <button type="submit" id="orderSubmit" class="button">Submit order</button>
                    <button type="button" class="button secondary modal-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Inventory edit modal (admin only) -->
    <div id="invModal" class="modal" aria-hidden="true">
        <div class="modal-dialog">
            <button class="modal-close" aria-label="Close">×</button>
            <h2 id="invModalTitle">Edit product</h2>
            <form id="invForm" action="index.php?page=inventory" method="post">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="product_id" id="invProductId" value="">
                <label>Product name</label>
                <input type="text" name="name" id="invName" required>
                <label>Price (E)</label>
                <input type="number" step="0.01" name="price" id="invPrice" required>
                <label>Stock</label>
                <input type="number" name="stock" id="invStock" value="0" min="0" required>
                <div style="display:flex;gap:10px;margin-top:12px;">
                    <button type="submit" class="button">Save</button>
                    <button type="button" id="invDelete" class="button secondary">Delete</button>
                </div>
            </form>
        </div>
    </div>
    <!-- Add product modal (admin only) -->
    <?php if (is_admin()): ?>
    <div id="addProductModal" class="modal" aria-hidden="true">
        <div class="modal-dialog">
            <button class="modal-close" aria-label="Close">×</button>
            <h2>Add product</h2>
            <form id="addProductForm" action="index.php?page=inventory" method="post">
                <input type="hidden" name="action" value="create">
                <label>Product name</label>
                <input type="text" name="name" id="addName" required>
                <label>Price (E)</label>
                <input type="number" step="0.01" name="price" id="addPrice" value="0.00" required>
                <label>Stock</label>
                <input type="number" name="stock" id="addStock" value="0" min="0" required>
                <label>Category</label>
                <select name="category" id="addCategory">
                    <option value="eggs">Eggs</option>
                    <option value="broiler">Broiler</option>
                    <option value="other">Other</option>
                </select>
                <div style="display:flex;gap:10px;margin-top:12px;">
                    <button type="submit" class="button">Create</button>
                    <button type="button" class="button secondary add-cancel">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php
    // Provide logged-in customer info to the modal so we can prefill name/phone
    $prefill_customer = null;
    if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer' && !empty($_SESSION['customer_id'])) {
        $prefill_customer = get_row($pdo, 'SELECT name, phone FROM customers WHERE id = ?', [$_SESSION['customer_id']]);
    }
    ?>

    <script>
    // Modal behaviour and client-side validation for public orders
    (function(){
        var modal = document.getElementById('orderModal');
        var form = document.getElementById('orderForm');
        var title = document.getElementById('modalTitle');
        var prodId = document.getElementById('modalProductId');
        var nameEl = document.getElementById('modalName');
        var qtyEl = document.getElementById('modalQuantity');
        var phoneEl = document.getElementById('modalPhone');
        var deliveryEl = document.getElementById('modalDelivery');
        var productNameEl = document.getElementById('modalProductName');
        var productPriceEl = document.getElementById('modalProductPrice');
        var totalEl = document.getElementById('modalTotal');
        var submitBtn = document.getElementById('orderSubmit');
        function show(){ modal.setAttribute('aria-hidden','false'); document.documentElement.classList.add('modal-open'); }
        function hide(){ modal.setAttribute('aria-hidden','true'); document.documentElement.classList.remove('modal-open'); }
        var prefill = <?php echo json_encode($prefill_customer ?: new stdClass()); ?>;

        function computeTotal(price, qty){
            var p = parseFloat(price) || 0; var q = parseInt(qty) || 0; return (p * q).toFixed(2);
        }

        document.querySelectorAll('button[data-product-id]').forEach(function(btn){
            btn.addEventListener('click', function(e){
                var id = btn.getAttribute('data-product-id');
                var name = btn.getAttribute('data-product-name');
                var price = btn.getAttribute('data-product-price') || '0';
                title.textContent = 'Order: ' + name;
                productNameEl.textContent = name;
                productPriceEl.textContent = 'Price: E' + parseFloat(price).toFixed(2);
                prodId.value = id;
                qtyEl.value = 1;
                totalEl.textContent = 'Total: E' + computeTotal(price, 1);
                // Prefill with logged-in customer's info when available
                nameEl.value = prefill.name || '';
                phoneEl.value = prefill.phone || '';
                deliveryEl.value = '';
                // reset form view
                var dialog = modal.querySelector('.modal-dialog');
                dialog.querySelector('form').style.display = '';
                var existing = dialog.querySelector('.order-confirmation');
                if (existing) existing.remove();
                show();
                // focus name
                setTimeout(function(){ nameEl.focus(); }, 250);
            });
        });

        // update total when qty changes
        qtyEl && qtyEl.addEventListener('input', function(){
            var btn = document.querySelector('button[data-product-id][data-product-id="' + prodId.value + '"]');
            var price = btn ? (btn.getAttribute('data-product-price') || '0') : '0';
            totalEl.textContent = 'Total: E' + computeTotal(price, qtyEl.value);
        });

        // close handlers
        document.querySelector('#orderModal .modal-close').addEventListener('click', hide);
        document.querySelectorAll('#orderModal .modal-cancel').forEach(function(b){ b.addEventListener('click', hide); });
        modal.addEventListener('click', function(e){ if(e.target === modal) hide(); });

        form.addEventListener('submit', function(e){
            e.preventDefault();
            var name = nameEl.value.trim();
            var phone = phoneEl.value.trim();
            var qty = parseInt(qtyEl.value) || 0;
            if(name === '' || phone === ''){ alert('Name and phone are required.'); nameEl.focus(); return; }
            if(qty <= 0){ alert('Quantity must be at least 1'); qtyEl.focus(); return; }
            // sanitize phone: keep digits and plus
            var normalized = phone.replace(/[^0-9+]/g, '');
            phoneEl.value = normalized;

            // show loading
            submitBtn.classList.add('loading'); submitBtn.disabled = true;

            var fd = new FormData(form);
            fd.append('ajax', '1');
            fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    submitBtn.classList.remove('loading'); submitBtn.disabled = false;
                    if (!data) { alert('There was a problem placing your order.'); return; }
                    if (!data.success) {
                        if (data.redirect && data.url) {
                            alert(data.message || 'Please login to continue.');
                            window.location.href = data.url;
                            return;
                        }
                        alert(data.message || 'There was a problem placing your order.');
                        return;
                    }
                    // Show confirmation view inside modal
                    var dialog = modal.querySelector('.modal-dialog');
                    dialog.querySelector('form').style.display = 'none';
                    var conf = document.createElement('div');
                    conf.className = 'order-confirmation';
                    var html = '<p class="success">Thank you — your order has been placed (ID: ' + (data.order_id || '') + ').</p>';
                    html += '<p>Open or copy the WhatsApp confirmation below to share:</p>';
                    html += '<p style="display:flex;gap:8px;flex-wrap:wrap;"><a class="button whatsapp-button" id="waOpen" target="_blank" rel="noopener" href="' + (data.wa || '#') + '">Open WhatsApp</a>';
                    html += '<button class="button secondary" id="waCopy">Copy link</button></p>';
                    if (data.email_sent) html += '<p class="hint">Admin email notification sent.</p>'; else html += '<p class="hint">Admin email notification not sent (server may not be configured).</p>';
                    html += '<p class="hint">This dialog will close automatically.</p>';
                    conf.innerHTML = html;
                    dialog.appendChild(conf);
                    // copy handler
                    var copyBtn = conf.querySelector('#waCopy');
                    copyBtn && copyBtn.addEventListener('click', function(){
                        var url = data.wa || '';
                        if (navigator.clipboard && url) {
                            navigator.clipboard.writeText(url).then(function(){ alert('Link copied to clipboard'); });
                        } else alert('Copy not supported');
                    });
                    // auto-close after a short delay
                    setTimeout(function(){ hide(); }, 6000);
                }).catch(function(){ submitBtn.classList.remove('loading'); submitBtn.disabled = false; alert('Could not reach server. Please try again.'); });
        });
        // no min-date handling required for delivery instructions textarea
    })();
    // Inventory modal logic (admin only)
    (function(){
        var invModal = document.getElementById('invModal');
        if(!invModal) return;
        var invForm = document.getElementById('invForm');
        var invId = document.getElementById('invProductId');
        var invName = document.getElementById('invName');
        var invPrice = document.getElementById('invPrice');
        var invStock = document.getElementById('invStock');
        var invDelete = document.getElementById('invDelete');
        function show(){ invModal.setAttribute('aria-hidden','false'); document.documentElement.classList.add('modal-open'); }
        function hide(){ invModal.setAttribute('aria-hidden','true'); document.documentElement.classList.remove('modal-open'); }
        document.querySelectorAll('button[data-inv-edit-id]').forEach(function(btn){
            btn.addEventListener('click', function(){
                var id = btn.getAttribute('data-inv-edit-id');
                var name = btn.getAttribute('data-inv-name');
                var price = btn.getAttribute('data-inv-price');
                var stock = btn.getAttribute('data-inv-stock') || '0';
                invId.value = id; invName.value = name; invPrice.value = price; invStock.value = stock;
                show();
            });
        });
        invModal.querySelector('.modal-close').addEventListener('click', hide);
        invModal.addEventListener('click', function(e){ if(e.target === invModal) hide(); });

        invForm.addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(invForm);
            fd.append('ajax', '1');
            fetch(invForm.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(!data || !data.success){ alert(data && data.message ? data.message : 'Failed to update product'); return; }
                    // update table row in-place
                    var row = document.querySelector('tr[data-product-id="' + data.id + '"]');
                    if(row){
                        row.querySelector('.p-name').textContent = data.name;
                        row.querySelector('.p-price').textContent = 'E' + parseFloat(data.price).toFixed(2);
                        row.querySelector('.p-stock').textContent = data.stock;
                        var stock = parseInt(data.stock) || 0;
                        row.querySelector('.p-value').textContent = 'E' + (stock * parseFloat(data.price)).toFixed(2);
                    }
                    hide();
                }).catch(function(){ alert('Could not reach server'); });
        });

        invDelete.addEventListener('click', function(){
            if(!confirm('Remove this product from inventory?')) return;
            var fd = new FormData(); fd.append('action','delete'); fd.append('product_id', invId.value); fd.append('ajax','1');
            fetch('index.php?page=inventory', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(!data || !data.success){ alert(data && data.message ? data.message : 'Failed to delete'); return; }
                    var row = document.querySelector('tr[data-product-id="' + data.id + '"]');
                    if(row) row.remove();
                    hide();
                }).catch(function(){ alert('Could not reach server'); });
        });
    })();
    // Add product modal and CSV import handlers (admin only)
    (function(){
        var addBtn = document.getElementById('addProductBtn');
        var addModal = document.getElementById('addProductModal');
        if (!addBtn || !addModal) return;
        var addForm = document.getElementById('addProductForm');
        var closeBtns = addModal.querySelectorAll('.modal-close, .add-cancel');
        function show(){ addModal.setAttribute('aria-hidden','false'); document.documentElement.classList.add('modal-open'); }
        function hide(){ addModal.setAttribute('aria-hidden','true'); document.documentElement.classList.remove('modal-open'); }
        addBtn.addEventListener('click', function(){ addForm.reset(); show(); setTimeout(function(){ addForm.querySelector('input[name="name"]').focus(); }, 200); });
        addModal.addEventListener('click', function(e){ if (e.target === addModal) hide(); });
        closeBtns.forEach(function(b){ b.addEventListener('click', hide); });
        addForm.addEventListener('submit', function(e){
            e.preventDefault();
            var fd = new FormData(addForm); fd.append('ajax','1');
            fetch(addForm.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r){ return r.json(); })
                .then(function(data){
                    if(!data || !data.success){ alert(data && data.message ? data.message : 'Failed to create product'); return; }
                    // insert new row into table
                    var tbody = document.querySelector('table tbody');
                    if(tbody){
                        var tr = document.createElement('tr'); tr.setAttribute('data-product-id', data.id);
                        tr.innerHTML = '<td class="p-name">'+data.name+'</td>' +
                                       '<td class="p-stock">'+data.stock+'</td>' +
                                       '<td class="p-price">E'+parseFloat(data.price).toFixed(2)+'</td>' +
                                       '<td class="p-value">E'+(parseInt(data.stock)||0 * parseFloat(data.price)).toFixed(2)+'</td>' +
                                       '<td><button class="button" data-inv-edit-id="'+data.id+'" data-inv-name="'+data.name+'" data-inv-price="'+parseFloat(data.price).toFixed(2)+'" data-inv-stock="'+data.stock+'">Edit</button></td>';
                        tbody.appendChild(tr);
                    }
                    hide();
                }).catch(function(){ alert('Could not reach server'); });
        });

        // CSV import button
        var importBtn = document.getElementById('importCsvBtn');
        var fileInput = document.getElementById('csvFileInput');
        if(importBtn && fileInput){
            importBtn.addEventListener('click', function(){ fileInput.click(); });
            fileInput.addEventListener('change', function(){
                if(!fileInput.files || !fileInput.files.length) return; if(!confirm('Import CSV file? This may overwrite existing products with matching IDs.')) return;
                var fd = new FormData(); fd.append('csvfile', fileInput.files[0]); fd.append('action','import_csv'); fd.append('ajax','1');
                fetch('index.php?page=inventory', { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function(r){ return r.json(); })
                    .then(function(data){
                        if(!data || !data.success){ alert(data && data.message ? data.message : 'Import failed'); return; }
                        alert('Imported ' + (data.count || 0) + ' rows. Reloading.');
                        window.location.reload();
                    }).catch(function(){ alert('Could not reach server'); });
            });
        }
    })();
    </script>
</body>
</html>
