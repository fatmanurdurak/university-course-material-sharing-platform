<?php
require_once "config.php";
$pageTitle = "Login Type";
include "header.php";
?>

<div style="max-width:400px; margin:40px auto; border:1px solid #ddd; border-radius:6px; overflow:hidden;">

    <div style="background:#337ab7; color:white; padding:12px; text-align:center; font-weight:bold;">
        Önlisans / Lisans / Enstitü
    </div>

    <a href="login.php?role=student" 
       style="display:block; padding:14px; text-align:center; border-bottom:1px solid #ddd; text-decoration:none;">
        Öğrenci Girişi
    </a>

    <a href="login.php?role=professor" 
       style="display:block; padding:14px; text-align:center; text-decoration:none;">
        Akademisyen Girişi
    </a>

</div>

<?php include "footer.php"; ?>
