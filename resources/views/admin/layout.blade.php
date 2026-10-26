<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page['title'] }} — Anare Admin</title>
    <link rel="icon" href="{{ asset('images/logo-biru.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;450;500;550;600;650;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v={{ filemtime(public_path('css/admin.css')) }}">
    <script src="{{ asset('js/admin.js') }}?v={{ filemtime(public_path('js/admin.js')) }}" defer></script>
</head>
@php
    $accessPermissions = $seed['access']['permissions'] ?? [];
    $accessRoles = $seed['access']['roles'] ?? [];
    $canAccess = static fn (string $permission): bool => in_array($permission, $accessPermissions, true);
    $isSuperAdmin = in_array('super-admin', $accessRoles, true);
    $workspacePermissions = ['dashboard' => 'dashboard.view', 'beranda' => 'home.view', 'skin' => 'skin.view', 'shape' => 'shape.view', 'dokter' => 'doctors.view', 'artikel' => 'articles.view'];
    $workspaceHome = collect($workspacePermissions)->keys()->first(fn ($key) => $canAccess($workspacePermissions[$key]));
    $workspaceHomeUrl = $workspaceHome === 'dashboard' ? route('admin.dashboard') : ($workspaceHome ? route('admin.section', $workspaceHome) : route('home'));
@endphp
<body class="admin-app" data-admin-section="{{ $section }}" data-user-name="{{ auth()->user()->name }}" data-user-username="{{ auth()->user()->username }}">
    @include('admin.icons')
    <a href="#admin-main" class="admin-skip">Lewati navigasi</a>
    <button class="sidebar-backdrop" aria-label="Tutup navigasi" data-sidebar-close hidden></button>
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Navigasi admin">
        <a href="{{ $workspaceHomeUrl }}" class="sidebar-brand"><span class="brand-mark">A<span>✦</span></span><span><strong>ANARE</strong><small>Admin workspace</small></span></a>
        <div class="sidebar-section-label">WORKSPACE</div>
        <nav class="sidebar-nav">
            @foreach(['dashboard' => ['Dashboard', 'grid'], 'beranda' => ['Beranda', 'home'], 'skin' => ['Anare Skin', 'skin'], 'shape' => ['Anare Shape', 'shape'], 'dokter' => ['Dokter', 'doctor'], 'artikel' => ['Artikel', 'article']] as $key => [$label, $icon])
                @if($canAccess($workspacePermissions[$key]))
                <a href="{{ $key === 'dashboard' ? route('admin.dashboard') : route('admin.section', $key) }}" class="sidebar-link {{ $section === $key ? 'active' : '' }}" @if($section === $key) aria-current="page" @endif><svg class="icon"><use href="#a-{{ $icon }}"/></svg><span>{{ $label }}</span>@if(in_array($key, ['skin', 'shape', 'dokter', 'artikel']))<small data-sidebar-count="{{ ['dokter' => 'doctors', 'artikel' => 'articles'][$key] ?? $key }}"></small>@endif</a>
                @endif
            @endforeach
        </nav>
        <div class="sidebar-section-label sidebar-secondary-label">WEBSITE</div>
        <nav class="sidebar-nav">
            @if($canAccess('settings.view'))<a href="{{ route('admin.section', 'pengaturan') }}" class="sidebar-link {{ $section === 'pengaturan' ? 'active' : '' }}" @if($section === 'pengaturan') aria-current="page" @endif><svg class="icon"><use href="#a-settings"/></svg><span>Pengaturan</span></a>@endif
            <a href="{{ route('home') }}" target="_blank" rel="noopener" class="sidebar-link"><svg class="icon"><use href="#a-globe"/></svg><span>Lihat website</span><svg class="icon sidebar-external"><use href="#a-external"/></svg></a>
        </nav>
        @if($isSuperAdmin && ($canAccess('users.manage') || $canAccess('roles.manage')))
            <div class="sidebar-section-label sidebar-secondary-label">AKSES</div>
            <nav class="sidebar-nav" aria-label="Management User">
                <details class="management-nav" @if(in_array($section, ['users', 'roles', 'permissions'])) open @endif>
                    <summary class="sidebar-link"><svg class="icon"><use href="#a-doctor"/></svg><span>Management User</span><svg class="icon management-chevron"><use href="#a-chevron"/></svg></summary>
                    <div class="management-subnav">
                        @if($canAccess('users.manage'))<a href="{{ route('admin.users.index') }}" class="sidebar-link {{ $section === 'users' ? 'active' : '' }}" @if($section === 'users') aria-current="page" @endif>User</a>@endif
                        @if($canAccess('roles.manage'))
                            <a href="{{ route('admin.roles.index') }}" class="sidebar-link {{ $section === 'roles' ? 'active' : '' }}" @if($section === 'roles') aria-current="page" @endif>Role</a>
                            <a href="{{ route('admin.permissions.index') }}" class="sidebar-link {{ $section === 'permissions' ? 'active' : '' }}" @if($section === 'permissions') aria-current="page" @endif>Hak Akses</a>
                        @endif
                    </div>
                </details>
            </nav>
        @endif
        <div class="sidebar-bottom"><div class="sidebar-demo"><span class="status-dot"></span><div><strong>Website CMS</strong><small>Konten tersimpan di database</small></div></div><form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="sidebar-link logout-button" type="submit"><svg class="icon"><use href="#a-logout"/></svg>Keluar akun</button></form></div>
    </aside>
    <div class="admin-shell">
        <header class="admin-topbar">
            <div class="topbar-left"><button type="button" class="icon-button mobile-menu" data-sidebar-toggle aria-label="Buka navigasi" aria-controls="admin-sidebar" aria-expanded="false"><svg class="icon"><use href="#a-menu"/></svg></button><span class="topbar-breadcrumb"><span class="breadcrumb-root">Workspace</span><svg class="icon"><use href="#a-chevron"/></svg><strong>{{ $page['title'] }}</strong></span></div>
            <div class="topbar-actions">@if($canAccess('activity.view'))<button type="button" class="icon-button notification-button" aria-label="Lihat aktivitas terbaru" data-topbar-panel="activity"><svg class="icon"><use href="#a-bell"/></svg><i></i></button>@endif<button type="button" class="icon-button" aria-label="Bantuan CMS" data-topbar-panel="help"><svg class="icon"><use href="#a-help"/></svg></button><span class="topbar-divider"></span><button type="button" class="profile-button" data-topbar-panel="profile" aria-label="Menu akun {{ auth()->user()->name }}"><span class="profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 2)) }}</span><span><strong>{{ auth()->user()->name }}</strong><small>{{ implode(', ', $accessRoles) ?: 'Tanpa peran' }}</small></span><svg class="icon"><use href="#a-chevron"/></svg></button></div>
            <div class="topbar-popover" id="topbar-popover" hidden></div>
        </header>
        <main class="admin-main" id="admin-main" tabindex="-1">
            <div class="page-heading"><div><div class="page-eyebrow">ANARE CONTENT MANAGEMENT</div><h1>{{ $page['title'] }}</h1><p>{{ $page['description'] }}</p></div><a class="button button-white website-shortcut" href="{{ route('home') }}" target="_blank" rel="noopener"><svg class="icon"><use href="#a-external"/></svg>Lihat website</a></div>
            <div class="demo-notice"><svg class="icon"><use href="#a-info"/></svg><span>Perubahan disimpan ke database. Konten berstatus Dipublikasikan akan tampil pada website.</span><span class="notice-storage">WEBSITE CMS</span></div>
            @unless(in_array($section, ['users', 'roles', 'permissions']))<noscript><div class="form-error">Aktifkan JavaScript untuk menggunakan editor konten.</div></noscript>@endunless
            @yield('content')
            <footer class="workspace-footer"><span>© {{ date('Y') }} Anare Clinic</span><span>Dirancang untuk merawat setiap detail.</span><span>Content Management System</span></footer>
        </main>
    </div>
    <dialog class="editor-dialog" id="editor-dialog" aria-labelledby="editor-title"><form id="content-editor"><header class="dialog-header"><div><span class="page-eyebrow" id="editor-eyebrow">CONTENT EDITOR</span><h2 id="editor-title">Edit konten</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup editor"><svg class="icon"><use href="#a-close"/></svg></button></header><div class="editor-body" id="editor-body"></div><footer class="dialog-footer"><span><span class="status-dot"></span> Simpan untuk menerapkan perubahan</span><div><button type="button" class="button button-white" data-dialog-close>Batal</button><button type="submit" class="button button-dark"><svg class="icon"><use href="#a-check"/></svg>Simpan perubahan</button></div></footer></form></dialog>
    <dialog class="preview-dialog" id="preview-dialog" aria-labelledby="preview-title"><header class="dialog-header"><div><span class="page-eyebrow">CONTENT PREVIEW</span><h2 id="preview-title">Pratinjau konten</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Tutup pratinjau"><svg class="icon"><use href="#a-close"/></svg></button></header><div id="preview-body"></div></dialog>
    <dialog class="confirm-dialog" id="confirm-dialog" aria-labelledby="confirm-title"><form method="dialog"><div class="confirm-icon"><svg class="icon"><use href="#a-trash"/></svg></div><h2 id="confirm-title">Hapus konten?</h2><p id="confirm-description"></p><div class="confirm-actions"><button class="button button-white" value="cancel">Batal</button><button class="button button-danger" value="confirm">Hapus konten</button></div></form></dialog>
    <div class="toast-region" id="toast-region" role="status" aria-live="polite"></div>
    <script id="admin-state" type="application/json">{!! json_encode($seed, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
@stack('scripts')
</body>
</html>
