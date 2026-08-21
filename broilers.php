<?php
function calculate_expected_sell_date($start_date, $age_weeks) {
    if ($start_date !== '') {
        $sellDate = strtotime($start_date . ' + 42 days');
    } else {
        $remaining_weeks = max(0, 6 - $age_weeks);
        $sellDate = strtotime('+' . $remaining_weeks . ' weeks');
    }
    return date('Y-m-d', $sellDate);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'save') {
    $name = trim($_POST['name'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $age_weeks = max(0, intval($_POST['age_weeks'] ?? 0));
    $pending_count = max(0, intval($_POST['pending_count'] ?? 0));
    $expected_sell_date = trim($_POST['expected_sell_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($name === '' || $start_date === '') {
        flash('Batch name and start date are required.', 'error');
    } else {
        if ($expected_sell_date === '') {
            $expected_sell_date = calculate_expected_sell_date($start_date, $age_weeks);
        }
        $pdo->prepare('INSERT INTO broiler_batches (name, start_date, age_weeks, pending_count, expected_sell_date, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$name, $start_date, $age_weeks, $pending_count, $expected_sell_date, $notes, date('Y-m-d H:i:s')]);
        flash('Broiler batch saved.');
        header('Location: index.php?page=broilers');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'update') {
    $id = intval($_POST['batch_id'] ?? 0);
    $age_weeks = max(0, intval($_POST['age_weeks'] ?? 0));
    $pending_count = max(0, intval($_POST['pending_count'] ?? 0));
    $expected_sell_date = trim($_POST['expected_sell_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    if ($id <= 0) {
        flash('Invalid batch selected.', 'error');
    } else {
        if ($expected_sell_date === '') {
            $batch = get_row($pdo, 'SELECT start_date FROM broiler_batches WHERE id = ?', [$id]);
            $expected_sell_date = calculate_expected_sell_date($batch['start_date'], $age_weeks);
        }
        $pdo->prepare('UPDATE broiler_batches SET age_weeks = ?, pending_count = ?, expected_sell_date = ?, notes = ? WHERE id = ?')
            ->execute([$age_weeks, $pending_count, $expected_sell_date, $notes, $id]);
        flash('Broiler batch updated.');
        header('Location: index.php?page=broilers');
        exit;
    }
}
$batch = get_row($pdo, 'SELECT * FROM broiler_batches ORDER BY created_at DESC LIMIT 1');
?>
<div class="grid">
    <div class="card small-card">
        <h2>New broiler batch</h2>
        <form id="new-broiler-form" action="index.php?page=broilers&action=save" method="post">
            <label>Batch name</label>
            <input type="text" name="name" required>
            <label>Start date</label>
            <input id="new-start-date" type="date" name="start_date" required>
            <label>Age (weeks)</label>
            <input id="new-age-weeks" type="number" name="age_weeks" value="0" min="0">
            <label>Pending count</label>
            <input type="number" name="pending_count" value="0" min="0">
            <label>Expected sell date</label>
            <input id="new-sell-date" type="date" name="expected_sell_date" readonly>
            <label>Notes</label>
            <textarea name="notes"></textarea>
            <button type="submit">Save batch</button>
        </form>
    </div>
    <div class="card small-card">
        <h2>Current batch</h2>
        <?php if ($batch): ?>
            <form id="update-broiler-form" action="index.php?page=broilers&action=update" method="post">
                <input type="hidden" name="batch_id" value="<?php echo h($batch['id']); ?>">
                <label>Name</label>
                <input type="text" value="<?php echo h($batch['name']); ?>" disabled>
                <label>Start date</label>
                <input id="update-start-date" type="date" value="<?php echo h($batch['start_date']); ?>" disabled>
                <label>Age (weeks)</label>
                <input id="update-age-weeks" type="number" name="age_weeks" value="<?php echo h($batch['age_weeks']); ?>" min="0">
                <label>Pending count</label>
                <input type="number" name="pending_count" value="<?php echo h($batch['pending_count']); ?>" min="0">
                <label>Expected sell date</label>
                <input id="update-sell-date" type="date" name="expected_sell_date" value="<?php echo h($batch['expected_sell_date']); ?>" readonly>
                <label>Notes</label>
                <textarea name="notes"><?php echo h($batch['notes']); ?></textarea>
                <button type="submit">Update current batch</button>
            </form>
        <?php else: ?>
            <p>No current batch available. Add one to start tracking growth progress.</p>
        <?php endif; ?>
    </div>
</div>
<script>
function calculateSellDate(startDate, ageWeeks) {
    if (startDate) {
        const start = new Date(startDate);
        start.setDate(start.getDate() + 42);
        return start.toISOString().slice(0, 10);
    }
    const remainingWeeks = Math.max(0, 6 - ageWeeks);
    const today = new Date();
    today.setDate(today.getDate() + remainingWeeks * 7);
    return today.toISOString().slice(0, 10);
}

function updateSellDate(formPrefix) {
    const startInput = document.getElementById(formPrefix + '-start-date');
    const ageInput = document.getElementById(formPrefix + '-age-weeks');
    const sellInput = document.getElementById(formPrefix + '-sell-date');
    if (!sellInput) return;
    const startDate = startInput ? startInput.value : '';
    const ageWeeks = ageInput ? parseInt(ageInput.value, 10) || 0 : 0;
    sellInput.value = calculateSellDate(startDate, ageWeeks);
}

const newAge = document.getElementById('new-age-weeks');
const newStart = document.getElementById('new-start-date');
if (newAge && newStart) {
    newAge.addEventListener('input', () => updateSellDate('new'));
    newStart.addEventListener('change', () => updateSellDate('new'));
    updateSellDate('new');
}
const updateAge = document.getElementById('update-age-weeks');
if (updateAge) {
    updateAge.addEventListener('input', () => updateSellDate('update'));
    updateSellDate('update');
}
</script>
