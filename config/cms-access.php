<?php

declare(strict_types=1);

$permissions = [
    'admin.access' => 'Akses panel admin',
    'dashboard.view' => 'Lihat dashboard',
    'activity.view' => 'Lihat aktivitas',
];
foreach (['home' => 'Beranda', 'skin' => 'Skin', 'shape' => 'Shape', 'doctors' => 'Dokter', 'articles' => 'Artikel'] as $module => $label) {
    foreach (['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus', 'publish' => 'Publikasikan'] as $action => $verb) {
        $permissions[$module.'.'.$action] = $verb.' '.$label;
    }
}
$permissions += [
    'settings.view' => 'Lihat pengaturan',
    'settings.update' => 'Ubah pengaturan',
    'media.upload' => 'Unggah media',
    'users.manage' => 'Kelola akun',
    'roles.manage' => 'Kelola peran',
];
$all = array_keys($permissions);

return [
    'permissions' => $permissions,
    // Defaults are applied only once when access control is installed, never during authorization.
    'roles' => [
        'super-admin' => $all,
        'admin' => array_values(array_diff($all, ['users.manage', 'roles.manage'])),
        'editor' => array_values(array_filter($all, static fn (string $permission): bool => in_array($permission, ['admin.access', 'dashboard.view', 'activity.view', 'media.upload'], true) || preg_match('/^(home|skin|shape|doctors|articles)\.(view|create|update)$/D', $permission) === 1)),
        'viewer' => array_values(array_filter($all, static fn (string $permission): bool => in_array($permission, ['admin.access', 'dashboard.view', 'settings.view'], true) || preg_match('/^(home|skin|shape|doctors|articles)\.view$/D', $permission) === 1)),
    ],
];
