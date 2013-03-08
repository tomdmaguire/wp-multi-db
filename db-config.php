<?php

$parts = explode(':', DB_HOST);
$hosts = array($parts[0]);
$port = isset($parts[1]) ? $parts[1] : '3306';

// Define hosts
// $hosts[] = '0.0.0.0';
// $hosts[] = '1.1.1.1';