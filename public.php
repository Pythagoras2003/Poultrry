<?php
$batch = get_row($pdo, 'SELECT * FROM broiler_batches ORDER BY created_at DESC LIMIT 1');
$products = get_rows($pdo, 'SELECT * FROM products WHERE category IN ("broiler", "eggs") ORDER BY category, name');
$notifications = get_rows($pdo, 'SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5');

// Handle public order submission: create customer if needed and record order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'public_order') {
    // Only logged-in customers may place orders now
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please login to place orders.', 'redirect' => true, 'url' => 'index.php?page=login']);
            exit;
        }
        flash('Please login to place orders.', 'error');
        header('Location: index.php?page=login');
        exit;
    }
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = max(1, intval($_POST['quantity'] ?? 1));
    $delivery_instructions = trim($_POST['delivery_instructions'] ?? '');

    if ($name === '' || $phone === '') {
        flash('Please provide your name and phone number.', 'error');
    } elseif ($product_id <= 0) {
        flash('Please select a product to order.', 'error');
    } else {
        // find existing customer by phone or create
        $existing = get_row($pdo, 'SELECT * FROM customers WHERE phone = ? LIMIT 1', [$phone]);
        if ($existing) {
            $customer_id = $existing['id'];
            // append notes
            $notes = trim($existing['notes'] . "\nPublic order: " . $delivery_instructions);
            $pdo->prepare('UPDATE customers SET notes = ? WHERE id = ?')->execute([$notes, $customer_id]);
        } else {
            $notes = "Public order - delivery: $delivery_instructions";
            $stmt = $pdo->prepare('INSERT INTO customers (name, phone, source, notes, created_at) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$name, $phone, 'public', $notes, date('Y-m-d H:i:s')]);
            $customer_id = $pdo->lastInsertId();
        }

        $product = get_row($pdo, 'SELECT * FROM products WHERE id = ?', [$product_id]);
        if (!$product) {
            flash('Selected product not found.', 'error');
        } elseif ($product['stock'] < $quantity) {
            flash('Not enough stock available for that product.', 'error');
        } else {
            $total = $product['price'] * $quantity;
            $stmt = $pdo->prepare('INSERT INTO orders (customer_id, product_id, quantity, total, status, delivery_instructions, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([$customer_id, $product_id, $quantity, $total, 'pending', $delivery_instructions ?: null, date('Y-m-d H:i:s')]);
                $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?')->execute([$quantity, $product_id]);

                // Prepare notification payload
                $orderId = $pdo->lastInsertId();
                $waText = "Order #{$orderId}: {$product['name']} x{$quantity} - E" . number_format($product['price'],2) . ". Customer: {$name} ({$phone}). Delivery: {$delivery_instructions}.";
                $waLink = 'https://wa.me/?text=' . rawurlencode($waText);

                // Attempt to send admin email (best-effort)
                $emailSent = false;
                if (defined('ADMIN_EMAIL') && filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
                    $subject = "New public order #{$orderId}";
                    $body = "Order ID: {$orderId}\nProduct: {$product['name']}\nQuantity: {$quantity}\nTotal: E" . number_format($total,2) . "\nCustomer: {$name} ({$phone})\nDelivery instructions: {$delivery_instructions}\n";
                    $headers = 'From: no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "\r\n" . 'Content-Type: text/plain; charset=utf-8';
                    try {
                        $emailSent = @mail(ADMIN_EMAIL, $subject, $body, $headers);
                    } catch (Exception $e) {
                        $emailSent = false;
                    }
                }

                // If AJAX request, return JSON response for client to show confirmation
                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') || (!empty($_POST['ajax']) && $_POST['ajax'] == '1');
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Order placed', 'order_id' => $orderId, 'wa' => $waLink, 'email_sent' => $emailSent]);
                    exit;
                }

                flash('Thank you! Your order has been placed. We will contact you to confirm delivery details.');
                header('Location: index.php');
                exit;
        }
    }
}
?>
<div class="card hero-card">
    <div>
        <div class="eyebrow">Live farm overview</div>
        <h2>Fresh produce. Fast replies. Friendly service.</h2>
        <p>Browse available broilers and eggs, place orders quickly, and get notified via WhatsApp — all from your phone. Our live UI keeps you informed with color-driven status updates.</p>
        <div style="margin-top:12px;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
            <a class="button" href="index.php?page=login">Login</a>
            <a class="button secondary" href="index.php?page=register">Register</a>
            <?php if (!empty($_SESSION['user'])): ?>
                <a class="button" href="index.php?page=orders">My Orders</a>
            <?php endif; ?>
            <a class="button whatsapp-button" href="#" onclick="alert('Share our farm on WhatsApp!');return false;">Share</a>
        </div>
        <div class="hero-pills">
            <span class="hero-pill">Mobile-ready layout</span>
            <span class="hero-pill">Instant WhatsApp sharing</span>
            <span class="hero-pill">Fresh stock updates</span>
        </div>
    </div>
    <div class="hero-metrics">
        <div class="metric-box">
            <strong><?php echo count($products); ?></strong>
            <span>products ready to share</span>
        </div>
        <div class="metric-box">
            <strong><?php echo count($notifications); ?></strong>
            <span>recent updates available</span>
        </div>
    </div>
</div>
<div class="grid">
    <div class="card small-card">
        <div class="section-heading">
            <h2>Current broiler batch</h2>
            <span class="table-pill ready">Live</span>
        </div>
        <?php if ($batch): ?>
            <p><strong>Name:</strong> <?php echo h($batch['name']); ?></p>
            <p><strong>Start date:</strong> <?php echo h($batch['start_date']); ?></p>
            <p><strong>Age:</strong> <?php echo h($batch['age_weeks']); ?> weeks</p>
            <p><strong>Pending:</strong> <?php echo h($batch['pending_count']); ?> birds</p>
            <p><strong>Sell date:</strong> <?php echo h($batch['expected_sell_date']); ?></p>
            <p><strong>Notes:</strong> <?php echo h($batch['notes']); ?></p>
        <?php else: ?>
            <p>The current broiler batch information is not yet available.</p>
        <?php endif; ?>
    </div>
    <div class="card small-card">
        <div class="section-heading">
            <h2>Latest updates</h2>
            <span class="table-pill warning">Fresh</span>
        </div>
        <ul class="item-list">
            <?php foreach ($notifications as $note): ?>
                <?php $shareText = rawurlencode($note['title'] . ' - ' . $note['message']); ?>
                <li>
                    <strong><?php echo h($note['title']); ?></strong>
                    <span><?php echo h($note['message']); ?></span>
                    <a class="button whatsapp-button" href="https://wa.me/?text=<?php echo $shareText; ?>" target="_blank" rel="noopener">Share</a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
<div class="card">
    <div class="section-heading">
        <h2>Available products</h2>
        <span class="table-pill ready">Fast share</span>
    </div>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php $status = $product['stock'] > 20 ? 'Ready' : ($product['stock'] > 5 ? 'Low' : 'Almost gone'); ?>
            <?php $text = rawurlencode("Farm update: {$product['name']} - E" . number_format($product['price'], 2) . " (Stock: {$product['stock']} - {$status}). Reply to book."); ?>
            <article class="product-card">
                <h3><?php echo h($product['name']); ?></h3>
                <div class="price">E<?php echo number_format($product['price'], 2); ?></div>
                <p>
                    <strong>Stock:</strong> <?php echo h($product['stock']); ?>
                    <span class="status-label status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo h($status); ?></span>
                </p>
                <a class="button whatsapp-button" href="https://wa.me/?text=<?php echo $text; ?>" target="_blank" rel="noopener">Share</a>
                <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
                    <button class="button" type="button" data-product-id="<?php echo h($product['id']); ?>" data-product-name="<?php echo h($product['name']); ?>" data-product-price="<?php echo h(number_format($product['price'], 2, '.', '')); ?>" style="margin-top:10px;">Order this product</button>
                <?php else: ?>
                    <a class="button" href="index.php?page=login" style="margin-top:10px;">Login to order</a>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
</div>