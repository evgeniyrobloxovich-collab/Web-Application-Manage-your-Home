<?php
require_once 'includes/session.php';

Session::destroy();
header('Location: index.php');
exit();