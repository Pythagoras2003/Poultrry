<?php
// A lightweight entry point for admins discovered via the hidden triple-click on the logo.
// Redirects to the main login page where credentials are validated.
header('Location: index.php?page=login');
exit;
