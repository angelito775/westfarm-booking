<?php
session_start();
session_unset();
session_destroy();

// Customer logout — send back to the public home page
header("Location: ../public/index.php");
exit();
