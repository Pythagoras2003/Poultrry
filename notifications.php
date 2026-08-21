<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'send') {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($title === '' || $message === '') {
        flash('Notification title and message are required.', 'error');
    } else {
        $pdo->prepare('INSERT INTO notifications (title, message, created_at) VALUES (?, ?, ?)')
            ->execute([$title, $message, date('Y-m-d H:i:s')]);
        flash('Notification saved for customers.');
        header('Location: index.php?page=notifications');
        exit;
    }
}
$notifications = get_rows($pdo, 'SELECT * FROM notifications ORDER BY created_at DESC');
?>
<div class="grid">
    <div class="card small-card">
        <h2>Send update</h2>
        <form action="index.php?page=notifications&action=send" method="post">
            <label>Title</label>
            <input type="text" name="title" required>
            <label>Message</label>
            <textarea name="message" required></textarea>
            <button type="submit">Save update</button>
        </form>
    </div>
    <div class="card small-card">
        <h2>Update log</h2>
        <table>
            <thead><tr><th>Date</th><th>Title</th><th>Message</th><th>Share</th></tr></thead>
            <tbody>
            <?php foreach ($notifications as $note): ?>
                <?php $waText = rawurlencode($note['title'] . ' - ' . $note['message']); ?>
                <tr>
                    <td><?php echo h($note['created_at']); ?></td>
                    <td><?php echo h($note['title']); ?></td>
                    <td><?php echo h($note['message']); ?></td>
                    <td><a class="button whatsapp-button" href="https://wa.me/?text=<?php echo $waText; ?>" target="_blank" rel="noopener">Share</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
