<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'sqlite',
    'database' => __DIR__ . '/database/database.sqlite',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "=== ACTIVITIES DATA ===\n";
$activities = Capsule::table('activities')->get();
foreach ($activities as $activity) {
    echo "ID: {$activity->id}, Title: {$activity->title}, Status: {$activity->status}, Created: {$activity->created_at}\n";
}

echo "\n=== ACTIVITY UPDATES DATA ===\n";
$updates = Capsule::table('activity_updates')->get();
foreach ($updates as $update) {
    echo "ID: {$update->id}, Activity ID: {$update->activity_id}, Status: {$update->status}, Created: {$update->created_at}\n";
}

echo "\n=== USERS DATA ===\n";
$users = Capsule::table('users')->get();
foreach ($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Role: {$user->role}\n";
}
