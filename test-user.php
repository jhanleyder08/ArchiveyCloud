<?php

require 'bootstrap/app.php';
$app = app();

use App\Models\User;

$user = User::where('email', 'admin@archiveycloud.com')->first();
echo "Email: " . $user->email . "\n";
echo "Role ID: " . $user->role_id . "\n";
echo "Role Name: " . ($user->role ? $user->role->name : 'Sin rol') . "\n";
