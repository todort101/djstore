<?php
// admin/includes/admin_flash.php
if (session_status() === PHP_SESSION_NONE) session_start();
$flash = getFlash();
if ($flash):
    $type = htmlspecialchars($flash['type']);
    $msg  = htmlspecialchars($flash['message']);
    echo "<div class=\"alert alert-{$type}\" style=\"margin-bottom:24px\">{$msg}</div>";
endif;