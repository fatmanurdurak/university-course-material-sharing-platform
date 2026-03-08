<?php
require_once "config.php";

session_destroy();  // forget the user
header("Location: index.php");
exit();
