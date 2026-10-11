<?php
require_once "env.php";

send_cors_headers();
header("location: ". _UI_URL_);
?>