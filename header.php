<?php
if (!isset($pdo)) {
    exit('Missing application context.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry Farm Management</title>
    <link rel="stylesheet" href="style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">
    <?php
    // Register favicon links if a custom icon is available in assets/
    $favPng = 'assets/chicken-icon.png';
    $favJpg = 'assets/chicken-icon.jpg';
    $favJpeg = 'assets/chicken-icon.jpeg';
    if (file_exists(__DIR__ . '/' . $favPng)) {
        echo '<link rel="icon" type="image/png" href="' . $favPng . '">';
        echo '<link rel="apple-touch-icon" href="' . $favPng . '">';
    } elseif (file_exists(__DIR__ . '/' . $favJpeg)) {
        echo '<link rel="icon" type="image/jpeg" href="' . $favJpeg . '">';
        echo '<link rel="apple-touch-icon" href="' . $favJpeg . '">';
    } elseif (file_exists(__DIR__ . '/' . $favJpg)) {
        echo '<link rel="icon" type="image/jpeg" href="' . $favJpg . '">';
        echo '<link rel="apple-touch-icon" href="' . $favJpg . '">';
    }
    ?>
</head>
<body>
<header>
    <div class="container header-row">
        <a href="index.php?page=public" class="brand-link">
        <div class="brand-block">
            <div id="appLogo" class="brand-mark" aria-hidden="true">
                <?php
                // Prefer a user-provided icon in /assets if available (png or jpg). Otherwise fall back to built-in SVG.
                $logoPng = 'assets/chicken-icon.png';
                $logoJpg = 'assets/chicken-icon.jpg';
                $logoJpeg = 'assets/chicken-icon.jpeg';
                if (file_exists(__DIR__ . '/' . $logoPng)) {
                    echo '<img src="' . $logoPng . '" alt="Simgci logo" width="72" height="72">';
                } elseif (file_exists(__DIR__ . '/' . $logoJpeg)) {
                    echo '<img src="' . $logoJpeg . '" alt="Simgci logo" width="72" height="72">';
                } elseif (file_exists(__DIR__ . '/' . $logoJpg)) {
                    echo '<img src="' . $logoJpg . '" alt="Simgci logo" width="72" height="72">';
                } else {
                    ?>
                    <!-- Chicken head icon as app logo (fallback SVG) -->
                    <svg width="72" height="72" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Simgci chicken logo">
                        <rect width="64" height="64" rx="12" fill="#f4fff8"/>
                        <g transform="translate(8,8)">
                            <path d="M22 4c6 0 11 5 11 11 0 6-5 11-11 11-2 0-4-1-6-2-1 2-3 3-5 3-3 0-6-3-6-6 0-4 4-8 9-8 1 0 2 0 3 1 2-3 5-6 10-8z" fill="#13553f"/>
                            <circle cx="30" cy="15" r="2.5" fill="#fff"/>
                            <path d="M20 20c2 1 5 1 7 0" stroke="#fff" stroke-width="1.5" stroke-linecap="round" fill="none"/>
                            <path d="M18 14c-1-1-2-2-4-2" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" fill="none"/>
                        </g>
                    </svg>
                    <?php
                }
                ?>
            </div>
            <div>
                <h1>SIMGCI POULTRY FARM</h1>
                <p class="subtitle">Track broilers, eggs, customers, orders, and progress from one dashboard.</p>
            </div>
        </div>
        </a>
        <div class="top-actions">
            <div class="theme-toggle" title="Toggle dark mode">
                <div id="themeSwitch" class="switch" role="button" tabindex="0" aria-pressed="false" aria-label="Toggle dark mode">
                    <div class="knob"></div>
                </div>
            </div>
            <?php if (!empty($_SESSION['user'])): ?>
                <span class="user-badge">Logged in as <?php echo h($_SESSION['user']); ?></span>
                <a class="button secondary" href="index.php?page=logout">Logout</a>
            <?php else: ?>
                <a class="button" href="index.php?page=login">Login</a>
                <a class="button secondary" href="index.php?page=register">Register</a>
            <?php endif; ?>
        </div>
    </div>
    <nav class="tab-bar left-nav" role="tablist">
        <?php
        // Role-based navigation
        if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
            echo build_menu_item('dashboard', 'Dashboard');
            echo build_menu_item('customers', 'Customers');
            echo build_menu_item('orders', 'Orders');
            echo build_menu_item('inventory', 'Inventory');
            echo build_menu_item('products', 'Products');
            echo build_menu_item('broilers', 'Broilers');
            echo build_menu_item('eggs', 'Eggs');
            echo build_menu_item('notifications', 'Updates');
            echo build_menu_item('reports', 'Reports');
        } elseif (!empty($_SESSION['role']) && $_SESSION['role'] === 'customer') {
            // Customer sees only customer-related pages
            echo build_menu_item('orders', 'My Orders');
            echo build_menu_item('products', 'Products');
            echo build_menu_item('notifications', 'Updates');
            echo build_menu_item('public', 'Public');
        } else {
            // Anonymous visitor
            echo build_menu_item('public', 'Public');
            echo build_menu_item('products', 'Products');
            echo build_menu_item('notifications', 'Updates');
        }
        ?>
    </nav>
</header>
<script>
// Theme toggle: persist preference in localStorage
(function(){
    var switchEl = document.getElementById('themeSwitch');
    function applyMode(mode){
        if(mode === 'dark') document.documentElement.classList.add('dark-mode');
        else document.documentElement.classList.remove('dark-mode');
        switchEl.classList.toggle('on', mode === 'dark');
        switchEl.setAttribute('aria-pressed', mode === 'dark');
    }
    var saved = localStorage.getItem('simgci-theme');
    if(saved) applyMode(saved);
    else if(window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) applyMode('dark');

    function toggle(){
        var isDark = document.documentElement.classList.contains('dark-mode');
        var next = isDark ? 'light' : 'dark';
        applyMode(next);
        localStorage.setItem('simgci-theme', next);
    }
    switchEl.addEventListener('click', toggle);
    switchEl.addEventListener('keydown', function(e){ if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(); } });
})();
    // Triple-click on the app logo opens the hidden admin login entry
    (function(){
        var logo = document.getElementById('appLogo');
        if(!logo) return;
        var clicks = 0, timer = null;
        function showToast(msg){
            var el = document.createElement('div');
            el.className = 'mini-toast';
            el.textContent = msg;
            document.body.appendChild(el);
            setTimeout(function(){ el.classList.add('visible'); }, 10);
            setTimeout(function(){ el.classList.remove('visible'); setTimeout(function(){ el.remove(); }, 300); }, 900);
        }
        logo.addEventListener('click', function(){
            clicks++;
            if(clicks === 3){
                showToast('Admin login discovered');
                setTimeout(function(){ window.location.href = 'index.php?page=admin_login'; }, 300);
                clicks = 0;
                clearTimeout(timer);
                timer = null;
                return;
            }
            if(timer) clearTimeout(timer);
            timer = setTimeout(function(){ clicks = 0; timer = null; }, 800);
        });
    })();
    // Live UI: gently cycle the hue variable to create a subtle animated accent
    (function(){
        var hue = 140;
        var direction = 1;
        function step(){
            hue += direction * 0.6;
            if (hue > 200) direction = -1;
            if (hue < 100) direction = 1;
            document.documentElement.style.setProperty('--hue', Math.round(hue));
        }
        setInterval(step, 180);
    })();
</script>
<main class="container">