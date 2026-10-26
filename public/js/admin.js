(() => {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    const icon = name => `<svg class="icon" aria-hidden="true"><use href="#a-${name}"/></svg>`;
    const safeImage = url => /^(https?:\/\/|\/[^/])/i.test(String(url)) ? url : '';
    const safeLink = url => /^(https?:\/\/|\/[^/])/i.test(String(url)) ? url : '#';

    $('[data-password-toggle]')?.addEventListener('click', event => {
        const button = event.currentTarget;
        const password = $('#password');
        const visible = password.type === 'password';
        password.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
        button.setAttribute('aria-label', visible ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
        button.innerHTML = icon(visible ? 'eye-off' : 'eye');
    });
    $('.login-form')?.addEventListener('submit', event => {
        const form = event.currentTarget;
        if (form.dataset.submitting) return event.preventDefault();
        form.dataset.submitting = 'true';
        $('button[type="submit"]', form).disabled = true;
    });

    const stateNode = $('#admin-state');
    if (!stateNode) return;

    const copy = value => JSON.parse(JSON.stringify(value));
    let state = JSON.parse(stateNode.textContent);
    const account = { name: document.body.dataset.userName, username: document.body.dataset.userUsername };
    let mutationPending = false;
    let uploadsPending = 0;
    const section = document.body.dataset.adminSection;
    const collection = { dokter: 'doctors', artikel: 'articles' }[section] || section;
    const labels = { skin: 'Anare Skin', shape: 'Anare Shape', doctors: 'Dokter', articles: 'Artikel', services: 'Layanan beranda', reviews: 'Testimoni', 'hero:home': 'Hero beranda', 'hero:skin': 'Banner Skin', 'hero:shape': 'Banner Shape', 'hero:doctors': 'Hero dokter' };
    const singleLabels = { skin: 'treatment', shape: 'treatment', doctors: 'dokter', articles: 'artikel', services: 'layanan', reviews: 'testimoni' };
    const moduleFor = key => ({ services: 'home', reviews: 'home', beranda: 'home', dokter: 'doctors', artikel: 'articles' }[key] || (key.startsWith('hero:') ? key.split(':')[1] : key));
    const can = permission => (state.access?.permissions || []).includes(permission);
    const listFor = key => (key.startsWith('hero:') ? state.heroes?.[key.split(':')[1]] : state[key]) || [];
    const canView = key => can(`${moduleFor(key)}.view`);
    const canChange = (key, operation, item = null) => can(`${moduleFor(key)}.${operation}`) && (!item || item.status !== 'published' || can(`${moduleFor(key)}.publish`));
    const canSettings = group => ['contact', 'social'].includes(group) ? can('settings.update') : can(`${group}.update`) && can(`${group}.publish`);
    const canCategory = (operation, item = null) => can(`${collection}.${operation}`) && (!item || !listFor(collection).some(entry => entry.category === item.slug && entry.status === 'published') || can(`${collection}.publish`));
    const deny = () => toast('Anda tidak memiliki izin untuk tindakan ini.', true);

    function applyAccessControls() {
        $$('[data-create]').forEach(button => { if (!canChange(button.dataset.create, 'create')) button.remove(); });
        for (const operation of ['edit', 'delete', 'duplicate']) {
            $$(`[data-${operation}]`).forEach(button => {
                const key = button.dataset[operation];
                const item = listFor(key).find(entry => entry.id === button.dataset.id);
                const allowed = operation === 'duplicate' ? canView(key) && canChange(key, 'create') : canChange(key, operation === 'edit' ? 'update' : 'delete', item);
                if (!allowed) button.remove();
            });
        }
        $$('[data-page-settings], [data-settings-edit]').forEach(button => { if (!canSettings(button.dataset.pageSettings || button.dataset.settingsEdit)) button.remove(); });
        $$('[data-create-category]').forEach(button => { if (!canCategory('update')) button.remove(); });
        for (const operation of ['edit', 'delete']) {
            $$(`[data-${operation}-category]`).forEach(button => {
                const item = (state.category_records[collection] || []).find(entry => entry.id === button.dataset[`${operation}Category`]);
                if (!canCategory(operation === 'edit' ? 'update' : 'delete', item)) button.remove();
            });
        }
    }
    const status = value => `<span class="status-badge ${value === 'published' ? '' : 'draft'}">${value === 'published' ? 'Dipublikasikan' : 'Draft'}</span>`;
    const categoryLabel = (key, value) => state.categories[key]?.[value] || value || '—';
    const dateLabel = date => date && !Number.isNaN(new Date(date).valueOf()) ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date)) : 'Data awal';
    let currentPage = 1;
    let homeTab = 'hero';
    let editorContext = null;

    function toast(message, error = false) {
        const item = document.createElement('div');
        item.className = `toast${error ? ' error' : ''}`;
        item.innerHTML = `${icon(error ? 'info' : 'check')}<span>${escape(message)}</span>`;
        $('#toast-region').append(item);
        setTimeout(() => item.remove(), 5000);
    }

    async function request(path, method = 'GET', data = null) {
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').content };
        const options = { method, headers, credentials: 'same-origin' };
        if (data instanceof FormData) options.body = data;
        else if (data !== null) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        }
        const response = await fetch(path, options);
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = Object.values(result.errors || {}).flat().join('\n') || result.message || (response.status === 419 ? 'Sesi berakhir. Muat ulang halaman dan masuk kembali.' : response.status === 401 ? 'Silakan masuk kembali untuk melanjutkan.' : 'Perubahan belum tersimpan. Silakan coba lagi.');
            const error = new Error(message);
            error.fields = result.errors || {};
            throw error;
        }
        return result;
    }

    function formError(form, error) {
        let element = $('.form-error', form);
        if (!element) {
            element = document.createElement('div');
            element.className = 'form-error';
            element.setAttribute('role', 'alert');
            form.prepend(element);
        }
        element.hidden = false;
        element.textContent = error.message;
        $$('[aria-invalid]', form).forEach(input => input.removeAttribute('aria-invalid'));
        for (const name of Object.keys(error.fields || {})) {
            const input = [...form.elements].find(input => input.name === name);
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                const panel = input.closest('[data-language-panel]');
                if (panel) editorLanguage(panel.dataset.languagePanel);
            }
        }
        element.scrollIntoView({ block: 'nearest' });
    }

    async function mutate(path, method, data, message, form = null) {
        if (mutationPending || uploadsPending) return false;
        mutationPending = true;
        const controls = form ? $$('button, input, textarea, select', form).filter(control => !control.disabled) : [];
        controls.forEach(control => control.disabled = true);
        if (form) $('.form-error', form)?.setAttribute('hidden', '');
        try {
            const result = await request(path, method, data);
            state = result.state;
            render();
            if (message) toast(message);
            return true;
        } catch (error) {
            if (form) formError(form, error);
            else toast(error.message, true);
            return false;
        } finally {
            mutationPending = false;
            controls.forEach(control => control.disabled = false);
        }
    }

    const contentPath = (key, id = null) => `/admin/content/${encodeURIComponent(key.replace(':', '-'))}${id ? `/${encodeURIComponent(id)}` : ''}`;
    const categoryPath = (group, id = null) => `/admin/categories/${encodeURIComponent(group)}${id ? `/${encodeURIComponent(id)}` : ''}`;

    function activityMarkup(limit = 4) {
        if (!can('activity.view')) return '';
        if (!state.activity.length) return `<div class="activity-empty">${icon('clock')}<h3>Workspace siap digunakan</h3><p>Belum ada perubahan. Mulai dengan mengedit salah satu konten website.</p></div>`;
        return state.activity.slice(0, limit).map(item => `<div class="activity-item"><span class="activity-dot">${icon('check')}</span><div><strong>${escape(item.title)}</strong><small>${escape(dateLabel(item.date))} · ${escape(new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(item.date)))}</small></div></div>`).join('');
    }

    function renderDashboard() {
        const cards = [['skin', 'Treatment Skin', 'treatment', 'skin'], ['shape', 'Treatment Shape', 'treatment', 'shape'], ['doctors', 'Tim dokter', 'profil', 'doctor'], ['articles', 'Artikel', 'artikel', 'article']];
        $('#dashboard-stats').innerHTML = cards.filter(([key]) => canView(key)).map(([key, label, unit, symbol]) => `<a class="stat-card" href="/admin/${{ doctors: 'dokter', articles: 'artikel' }[key] || key}"><div class="stat-card-top">${label}<span class="stat-icon">${icon(symbol)}</span></div><div class="stat-value"><strong>${listFor(key).length.toString().padStart(2, '0')}</strong><span>${unit}</span></div><div class="stat-card-bottom"><span class="status-dot"></span>${listFor(key).filter(item => item.status === 'published').length} dipublikasikan</div></a>`).join('');
        const pages = [['beranda', 'Beranda', 'Hero, layanan, testimoni & lokasi', 'home'], ['skin', 'Anare Skin', `${listFor('skin').length} treatment · ${Object.keys(state.categories.skin || {}).length} kategori`, 'skin'], ['shape', 'Anare Shape', `${listFor('shape').length} treatment · ${Object.keys(state.categories.shape || {}).length} kategori`, 'shape'], ['dokter', 'Dokter', `${listFor('doctors').length} profil · Jadwal, pendidikan & keahlian`, 'doctor'], ['artikel', 'Artikel', `${listFor('articles').length} artikel · Konten edukasi & informasi`, 'article']];
        $('#page-overview').innerHTML = pages.filter(([key]) => canView(key)).map(([key, title, description, symbol]) => `<a class="overview-row" href="/admin/${key}"><span class="overview-icon">${icon(symbol)}</span><span class="overview-copy"><strong>${title}</strong><small>${escape(description)}</small></span><span class="overview-status"><span class="status-dot"></span>Siap dikelola</span>${icon('chevron')}</a>`).join('');
        if ($('#activity-list')) $('#activity-list').innerHTML = activityMarkup();
    }

    function rowActions(key, item) {
        return `<div class="table-actions"><button type="button" class="icon-button" data-preview="${key}" data-id="${escape(item.id)}" aria-label="Pratinjau ${escape(item.title)}" title="Pratinjau">${icon('eye')}</button><button type="button" class="icon-button" data-edit="${key}" data-id="${escape(item.id)}" aria-label="Edit ${escape(item.title)}" title="Edit">${icon('edit')}</button><button type="button" class="icon-button" data-duplicate="${key}" data-id="${escape(item.id)}" aria-label="Duplikat ${escape(item.title)}" title="Duplikat">${icon('copy')}</button><button type="button" class="icon-button" data-delete="${key}" data-id="${escape(item.id)}" aria-label="Hapus ${escape(item.title)}" title="Hapus">${icon('trash')}</button></div>`;
    }

    function renderCollection() {
        const items = state[collection];
        const published = items.filter(item => item.status === 'published').length;
        $('#collection-summary').innerHTML = [[items.length, 'Total '+(singleLabels[collection] || 'konten'), 'grid'], [published, 'Dipublikasikan', 'check'], [items.length - published, 'Draft', 'article']].map(([value, label, symbol]) => `<div class="summary-card"><span class="stat-icon">${icon(symbol)}</span><div><strong>${String(value).padStart(2, '0')}</strong><small>${label}</small></div></div>`).join('');
        $('#collection-count').textContent = items.length;
        const categoryFilter = $('#category-filter');
        if (categoryFilter) {
            const selected = categoryFilter.value;
            categoryFilter.replaceChildren(new Option('Semua kategori', ''));
            Object.entries(state.categories[collection] || {}).forEach(([value, label]) => categoryFilter.add(new Option(label, value)));
            categoryFilter.value = Object.hasOwn(state.categories[collection] || {}, selected) ? selected : '';
        }
        const search = $('#content-search').value.trim().toLocaleLowerCase('id');
        const filter = $('#status-filter').value;
        const category = categoryFilter?.value;
        const filtered = items.filter(item => (!search || `${item.title} ${item.title_en} ${item.slug} ${item.specialty || ''}`.toLocaleLowerCase('id').includes(search)) && (!filter || item.status === filter) && (!category || item.category === category));
        const perPage = 6;
        const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
        currentPage = Math.min(currentPage, totalPages);
        const start = (currentPage - 1) * perPage;
        $('#content-table-body').innerHTML = filtered.slice(start, start + perPage).map(item => `<tr><td><div class="table-item"><img class="table-thumb ${collection === 'doctors' ? 'doctor-thumb' : ''}" src="${escape(safeImage(item.image))}" alt="" loading="lazy"><span><strong class="table-title">${escape(item.title)}</strong><small class="table-slug">${collection === 'doctors' ? escape(item.specialty) : '/'+collection+'/'+escape(item.slug)}</small></span></div></td><td>${collection === 'doctors' ? `<div class="schedule-cell">${escape(item.schedule || 'Jadwal belum diisi')}</div>` : `<span class="table-category">${escape(categoryLabel(collection, item.category))}</span>`}</td><td>${status(item.status)}</td><td>${escape(dateLabel(item.updated_at))}</td><td>${rowActions(collection, item)}</td></tr>`).join('');
        $('#collection-empty').hidden = filtered.length !== 0;
        $('.table-scroll').hidden = filtered.length === 0;
        $('#table-range').textContent = filtered.length ? `Menampilkan ${start + 1}–${Math.min(start + perPage, filtered.length)} dari ${filtered.length} ${singleLabels[collection]}` : 'Tidak ada hasil';
        $('#page-number').textContent = currentPage;
        $('#page-previous').disabled = currentPage <= 1;
        $('#page-next').disabled = currentPage >= totalPages;
        if ($('#category-chips')) $('#category-chips').innerHTML = (state.category_records[collection] || []).map(item => `<div class="category-chip"><button class="text-link" type="button" data-filter-category="${escape(item.slug)}">${escape(item.title)} <span>${items.filter(entry => entry.category === item.slug).length}</span></button><button class="icon-button" type="button" data-edit-category="${escape(item.id)}" aria-label="Edit kategori ${escape(item.title)}">${icon('edit')}</button><button class="icon-button" type="button" data-delete-category="${escape(item.id)}" aria-label="Hapus kategori ${escape(item.title)}">${icon('trash')}</button></div>`).join('');
        if ($('#category-count')) $('#category-count').textContent = `${(state.category_records[collection] || []).length} KATEGORI`;
        const banners = $('#collection-banners');
        if (banners) banners.innerHTML = `<div class="section-intro"><div><h2>Hero & banner</h2><p>Kelola gambar dan pesan halaman.</p></div><button type="button" class="button button-dark" data-create="hero:${collection}">${icon('plus')}Tambah banner</button></div><div class="hero-cards">${heroCards(`hero:${collection}`)}</div>`;
        applyAccessControls();
    }

    function heroCards(key, slideLabels = true) {
        return listFor(key).map((item, index) => `<article class="panel hero-card"><div class="hero-card-image"><img src="${escape(safeImage(item.image))}" alt="${escape(item.title)}" loading="lazy">${slideLabels ? `<span class="slide-label">SLIDE ${String(index + 1).padStart(2, '0')}</span>` : ''}${status(item.status)}</div><div class="hero-card-content"><h3>${escape(item.title)}</h3><p>${escape(item.description || 'Belum ada deskripsi. Tambahkan konten melalui editor.')}</p><div class="hero-card-actions">${rowActions(key, item)}</div></div></article>`).join('');
    }

    function renderHome() {
        const target = $('#home-content');
        if (homeTab === 'hero') {
            target.innerHTML = `<div class="section-intro"><div><h2>Slideshow beranda</h2><p>Foto dan pesan pertama yang menyambut pengunjung.</p></div><button type="button" class="button button-dark" data-create="hero:home">${icon('plus')}Tambah slide</button></div><div class="hero-cards">${heroCards('hero:home')}</div><p class="section-hint">${icon('info')}${listFor('hero:home').filter(item => item.status === 'published').length} slide dipublikasikan${state.settings.home?.slide_interval ? ` dengan interval ${escape(state.settings.home.slide_interval)} detik` : ''}.</p><div class="home-secondary-grid">${state.settings.home ? `<div class="panel home-mini-card"><span class="stat-icon">${icon('clock')}</span><div><h3>Pengaturan slideshow</h3><p>Judul section dan interval pergantian foto.</p></div><button type="button" class="icon-button" data-settings-edit="home" aria-label="Atur slideshow">${icon('settings')}</button></div>` : ''}${can('settings.view') ? `<div class="panel home-mini-card"><span class="stat-icon">${icon('globe')}</span><div><h3>Lokasi & Instagram</h3><p>Kontak, jam klinik, dan tautan akun sosial.</p></div><a class="icon-button" href="/admin/pengaturan" aria-label="Lihat kontak dan Instagram">${icon('arrow')}</a></div>` : ''}</div>`;
        } else if (homeTab === 'services') {
            target.innerHTML = `<div class="section-intro"><div><h2>Layanan unggulan</h2><p>Kartu Anare Clinic dan Anare Shape pada beranda.</p></div><button type="button" class="button button-dark" data-create="services">${icon('plus')}Tambah layanan</button></div><div class="hero-cards">${heroCards('services', false)}</div>`;
        } else if (homeTab === 'doctors') {
            target.innerHTML = canView('doctors') ? `<div class="section-intro"><div><h2>Dokter pilihan</h2><p>Profil dokter yang ditampilkan pada beranda.</p></div><a class="button button-white" href="/admin/dokter">Lihat dokter ${icon('arrow')}</a></div><div class="doctor-picker-grid">${listFor('doctors').map(item => `<article class="panel doctor-pick"><img src="${escape(safeImage(item.home_image || item.image))}" alt="" loading="lazy"><div><h3>${escape(item.title)}</h3>${status(item.status)}</div>${canChange('doctors', 'update', item) ? `<label class="switch-control"><input type="checkbox" data-feature-doctor="${escape(item.id)}" ${item.featured ? 'checked' : ''} aria-label="Tampilkan ${escape(item.title)} di beranda"></label>` : `<span class="quiet-label">${item.featured ? 'PILIHAN BERANDA' : 'TIDAK DITAMPILKAN'}</span>`}</article>`).join('')}</div><p class="section-hint">${icon('info')}Dokter berstatus dipublikasikan dan dipilih akan tampil pada beranda.</p>` : '';
        } else if (homeTab === 'reviews') {
            target.innerHTML = `<div class="section-intro"><div><h2>Ulasan klien</h2><p>Kelola nama, penilaian, dan cerita pengalaman klien.</p></div><button type="button" class="button button-dark" data-create="reviews">${icon('plus')}Tambah ulasan</button></div><div class="review-cards">${state.reviews.map(item => `<article class="panel review-card"><span class="review-stars">${'★'.repeat(Math.min(5, Math.max(1, Number(item.rating) || 5)))}</span><p>${escape(item.description)}</p><div class="review-card-footer"><strong>${escape(item.title)}</strong>${rowActions('reviews', item)}</div></article>`).join('')}</div>`;
        } else {
            target.innerHTML = `<section class="panel settings-panel"><div class="panel-heading"><div><h2>Pengaturan section beranda</h2><p>Judul dan deskripsi yang membentuk halaman utama.</p></div></div>${settingsForm('home')}</section>`;
        }
        applyAccessControls();
    }

    const settingsSchema = {
        home: [['services_title', 'Judul layanan — Indonesia'], ['services_title_en', 'Services title — English'], ['doctors_title', 'Judul dokter — Indonesia'], ['doctors_title_en', 'Doctors title — English'], ['doctors_description', 'Deskripsi dokter — Indonesia', 'textarea'], ['doctors_description_en', 'Doctors description — English', 'textarea'], ['doctors_invitation', 'Ajakan konsultasi — Indonesia'], ['doctors_invitation_en', 'Consultation invitation — English'], ['instagram_title', 'Judul Instagram — Indonesia'], ['instagram_title_en', 'Instagram title — English'], ['reviews_title', 'Judul ulasan — Indonesia'], ['reviews_title_en', 'Reviews title — English'], ['location_title', 'Judul lokasi — Indonesia'], ['location_title_en', 'Location title — English'], ['slide_interval', 'Interval slideshow (detik)', 'number']],
        contact: [['name', 'Nama klinik — Indonesia'], ['name_en', 'Clinic name — English'], ['email', 'Email', 'email'], ['whatsapp', 'Nomor WhatsApp', 'tel'], ['days', 'Hari operasional — Indonesia'], ['days_en', 'Opening days — English'], ['hours', 'Jam operasional — Indonesia'], ['hours_en', 'Opening hours — English'], ['maps', 'Tautan Google Maps', 'url'], ['address', 'Alamat lengkap — Indonesia', 'textarea'], ['address_en', 'Address — English', 'textarea']],
        social: [['clinic', 'Instagram Anare Clinic', 'url'], ['shape', 'Instagram Anare Shape', 'url'], ['skin', 'Instagram Anare Skin', 'url']],
        skin: [['title', 'Judul halaman — Indonesia'], ['title_en', 'Page title — English'], ['description', 'Deskripsi — Indonesia', 'textarea'], ['description_en', 'Description — English', 'textarea'], ['per_page', 'Treatment per halaman', 'number']],
        shape: [['title', 'Judul halaman — Indonesia'], ['title_en', 'Page title — English'], ['description', 'Deskripsi — Indonesia', 'textarea'], ['description_en', 'Description — English', 'textarea'], ['per_page', 'Treatment per halaman', 'number']],
        doctors: [['title', 'Judul halaman — Indonesia'], ['title_en', 'Page title — English'], ['description', 'Deskripsi — Indonesia', 'textarea'], ['description_en', 'Description — English', 'textarea']],
    };

    function field(name, label, value = '', type = 'text', full = false, extra = '') {
        const input = type === 'textarea' ? `<textarea name="${name}" ${extra.includes('rows=') ? '' : 'rows="4"'} maxlength="50000" ${extra}>${escape(value)}</textarea>` : `<input type="${type}" name="${name}" value="${escape(value)}" ${type === 'number' ? 'step="1"' : 'maxlength="2000"'} ${extra}>`;
        return `<label class="field${full || type === 'textarea' ? ' full' : ''}"><span>${label}</span>${input}</label>`;
    }

    function settingsFields(group) {
        return `<div class="field-grid">${settingsSchema[group].map(([name, label, type = 'text']) => field(name, label, state.settings[group]?.[name], type, group === 'social', ['name', 'title', 'services_title'].includes(name) ? 'required' : '')).join('')}</div>`;
    }

    function settingsForm(group) {
        if (!state.settings[group]) return '';
        const writable = canSettings(group);
        return `<form class="settings-form" data-settings-form="${group}"><fieldset class="settings-fields" ${writable ? '' : 'disabled'}>${settingsFields(group)}</fieldset>${writable ? `<div class="settings-form-footer"><button type="submit" class="button button-dark">${icon('check')}Simpan perubahan</button></div>` : '<p class="editor-hint">Hanya baca. Anda tidak memiliki izin mengubah pengaturan ini.</p>'}</form>`;
    }

    function renderSettings() {
        $('#contact-settings').innerHTML = settingsForm('contact');
        $('#social-settings').innerHTML = settingsForm('social');
    }

    function render() {
        $$('[data-sidebar-count]').forEach(el => el.textContent = listFor(el.dataset.sidebarCount).length);
        if (['users', 'roles', 'permissions'].includes(section)) return;
        if (section === 'dashboard') renderDashboard();
        else if (section === 'beranda') renderHome();
        else if (section === 'pengaturan') renderSettings();
        else if (['skin', 'shape', 'dokter', 'artikel'].includes(section)) renderCollection();
        applyAccessControls();
    }

    const editor = $('#editor-dialog');
    const editorForm = $('#content-editor');
    const preview = $('#preview-dialog');
    const confirmDialog = $('#confirm-dialog');
    function openDialog(dialog) {
        dialog.showModal();
        document.body.classList.add('dialog-open');
    }
    $$('dialog').forEach(dialog => {
        dialog.addEventListener('cancel', event => {
            if (mutationPending || uploadsPending) event.preventDefault();
        });
        dialog.addEventListener('close', () => {
            if (!$('dialog[open]')) document.body.classList.remove('dialog-open');
            requestAnimationFrame(() => {
                if (document.activeElement === document.body && !$('dialog[open]')) {
                    const editButton = editorContext && $$('[data-edit]').find(button => button.dataset.edit === editorContext.key && button.dataset.id === editorContext.id);
                    (editButton || $('[data-create]') || $('#admin-main')).focus();
                }
            });
        });
        dialog.addEventListener('click', event => {
            const box = dialog.getBoundingClientRect();
            if (!mutationPending && !uploadsPending && event.target === dialog && (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom)) dialog.close();
        });
    });

    const managementDialog = $('[data-management-dialog]');
    if (managementDialog) {
        managementDialog.addEventListener('close', () => {
            history.replaceState(null, '', managementDialog.dataset.returnUrl);
        });
        openDialog(managementDialog);
        $('.form-error', managementDialog)?.focus();
        managementDialog.addEventListener('change', event => {
            const group = event.target.closest('.permission-group');
            if (!group || !event.target.matches('input[type="checkbox"]')) return;
            $('[data-permission-selected]', group).textContent = $$('input[type="checkbox"]:checked', group).length;
        });
    }

    function selectField(name, label, value, options) {
        return `<label class="field"><span>${label}</span><select name="${name}">${Object.entries(options).map(([key, text]) => `<option value="${escape(key)}" ${key === value ? 'selected' : ''}>${escape(text)}</option>`).join('')}</select></label>`;
    }

    function imageField(item, name = 'image', title = 'Foto konten') {
        const records = [...state.skin, ...state.shape, ...state.doctors, ...state.articles, ...state.services, ...Object.values(state.heroes).flat()];
        const imageNames = ['image', 'catalog_image', 'home_image', 'schedule_image', 'mobile_image', 'comparison_before', 'comparison_after'];
        const library = [...new Set([item[name], ...records.flatMap(record => imageNames.map(key => record[key]))].filter(url => safeImage(url)))];
        return `<div class="editor-section-title">${title}</div><div class="image-field" data-image-field="${name}"><img ${item[name] ? `src="${escape(safeImage(item[name]))}"` : 'hidden'} alt="Pratinjau ${title}"><div><strong>Pilih foto dari galeri${can('media.upload') ? ' atau perangkat' : ''}</strong>${can('media.upload') ? `<p>JPG, PNG, WebP, GIF · Maks. 2 MB</p><label class="button button-white image-upload-label">${icon('upload')}Unggah foto<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-image-upload="${name}" aria-label="Unggah ${title}"></label>` : ''}<button type="button" class="text-link" data-image-choice="" data-image-name="${name}">Hapus foto</button><input type="hidden" name="${name}" value="${escape(item[name] || '')}"></div></div><div class="image-library" aria-label="Galeri ${title}">${library.map(url => `<button type="button" data-image-choice="${escape(url)}" data-image-name="${name}" class="${url === item[name] ? 'selected' : ''}" aria-label="Gunakan foto ${escape(url.split('/').pop())}"><img src="${escape(safeImage(url))}" alt="" loading="lazy"></button>`).join('')}</div>`;
    }

    function openEditor(key, id = null) {
        const original = id ? listFor(key).find(item => item.id === id) : null;
        if (id && !original) return;
        if (!canView(key) || !canChange(key, id ? 'update' : 'create', original)) return deny();
        const item = copy(original || { title: '', title_en: '', description: '', description_en: '', slug: '', category: Object.keys(state.categories[key] || {})[0] || '', status: 'draft', image: '', date: new Date().toISOString().slice(0, 10), author: 'Anare Clinic', rating: '5', featured: false });
        editorContext = { key, id, item };
        $('#editor-title').textContent = `${id ? 'Edit' : 'Tambah'} ${key.startsWith('hero:') ? 'slide' : singleLabels[key] || 'konten'}`;
        $('#editor-eyebrow').textContent = labels[key] || 'CONTENT EDITOR';
        const isHero = key.startsWith('hero:');
        let html = `<div class="editor-language-tabs" role="tablist" aria-label="Bahasa konten"><button class="active" type="button" role="tab" aria-selected="true" data-editor-language="id"><span class="language-flag">ID</span>Bahasa Indonesia</button><button type="button" role="tab" aria-selected="false" tabindex="-1" data-editor-language="en"><span class="language-flag">EN</span>English</button></div>`;
        html += `<div data-language-panel="id"><div class="field-grid">${field('title', key === 'doctors' ? 'Nama dokter *' : key === 'reviews' ? 'Nama klien *' : 'Judul / nama konten *', item.title, 'text', true, 'required')}${field('description', key === 'articles' ? 'Ringkasan artikel' : key === 'doctors' ? 'Biografi singkat' : key === 'reviews' ? 'Isi ulasan' : 'Deskripsi', item.description, 'textarea')}${key === 'articles' ? field('body', 'Isi artikel', item.body || item.description, 'textarea', true, 'rows="9"') : ''}${key === 'doctors' ? field('education', 'Pendidikan & pengalaman', item.education, 'textarea') + field('expertise', 'Keahlian (satu poin per baris)', item.expertise, 'textarea') : ''}</div></div>`;
        html += `<div data-language-panel="en" hidden><div class="field-grid">${field('title_en', key === 'doctors' ? 'Doctor’s name' : key === 'reviews' ? 'Client’s name' : 'Title / content name', item.title_en, 'text', true)}${field('description_en', key === 'articles' ? 'Article summary' : 'Description', item.description_en, 'textarea')}${key === 'articles' ? field('body_en', 'Article content', item.body_en, 'textarea') : ''}${key === 'doctors' ? field('education_en', 'Education & experience', item.education_en, 'textarea') + field('expertise_en', 'Expertise (one item per line)', item.expertise_en, 'textarea') : ''}</div></div>`;
        html += `<div class="editor-section-title">Detail konten</div><div class="field-grid">${selectField('status', 'Status publikasi', item.status, can(`${moduleFor(key)}.publish`) ? { published: 'Dipublikasikan', draft: 'Draft' } : { draft: 'Draft' })}`;
        if (state.categories[key]) html += selectField('category', 'Kategori', item.category, state.categories[key]);
        if (['skin', 'shape', 'doctors', 'articles'].includes(key)) html += field('slug', 'Slug / alamat halaman *', item.slug, 'text', false, 'required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" title="Gunakan huruf kecil, angka, dan tanda hubung"');
        if (['skin', 'shape'].includes(key)) html += field('subtitle', 'Subjudul — Indonesia', item.subtitle) + field('subtitle_en', 'Subtitle — English', item.subtitle_en) + field('duration', 'Durasi treatment — Indonesia', item.duration) + field('duration_en', 'Treatment duration — English', item.duration_en) + field('image_position', 'Posisi foto detail', item.image_position || 'center');
        if (key === 'doctors') html += field('specialty', 'Spesialisasi — Indonesia', item.specialty) + field('specialty_en', 'Specialty — English', item.specialty_en) + field('schedule', 'Jadwal praktik — Indonesia', item.schedule, 'textarea') + field('schedule_en', 'Practice schedule — English', item.schedule_en, 'textarea') + field('home_sort_order', 'Urutan dokter di beranda', item.home_sort_order ?? 0, 'number', false, 'min="0"');
        if (key === 'articles') html += field('date', 'Tanggal artikel', item.date, 'date') + field('author', 'Penulis', item.author);
        if (key === 'reviews') html += selectField('rating', 'Penilaian', String(item.rating || 5), { 5: '5 bintang', 4: '4 bintang', 3: '3 bintang', 2: '2 bintang', 1: '1 bintang' });
        if (isHero || ['services', 'reviews'].includes(key)) html += field('url', key === 'reviews' ? 'Tautan ulasan' : 'Tautan tombol', item.url || '');
        if (isHero) html += field('button', 'Label tombol — Indonesia', item.button) + field('button_en', 'Button label — English', item.button_en) + field('position', 'Posisi foto desktop', item.position || 'center') + field('mobile_position', 'Posisi foto mobile', item.mobile_position || 'center') + selectField('fit', 'Penyesuaian foto', item.fit || 'cover', { cover: 'Cover', contain: 'Contain' }) + field('background', 'Warna latar', item.background || '');
        if (isHero && key === 'hero:doctors') html += `<label class="field full"><span>Gunakan tampilan foto tim</span><span class="switch-control"><input type="checkbox" name="team" ${item.team ? 'checked' : ''}></span></label>`;
        if (['doctors', 'articles'].includes(key)) html += `<label class="field full"><span>Tampilkan di beranda</span><span class="switch-control"><input type="checkbox" name="featured" ${item.featured ? 'checked' : ''} aria-label="Tampilkan di beranda"></span></label>`;
        html += field('sort_order', 'Urutan tampilan', item.sort_order ?? 0, 'number', false, 'min="0"');
        html += '</div>';
        if (key !== 'reviews') html += imageField(item);
        if (key === 'doctors') html += imageField(item, 'home_image', 'Foto dokter beranda') + imageField(item, 'schedule_image', 'Foto & jadwal praktik');
        if (isHero) html += imageField(item, 'mobile_image', 'Foto hero mobile');
        if (['skin', 'shape'].includes(key)) {
            html += imageField(item, 'catalog_image', 'Foto katalog') + imageField(item, 'comparison_before', 'Foto sebelum treatment') + imageField(item, 'comparison_after', 'Foto sesudah treatment');
            html += `<div class="editor-section-title">Informasi detail treatment</div><div class="field-grid">${['pre', 'post', 'downtime'].map(name => field(name, `${{ pre: 'Pre-condition', post: 'Post-condition', downtime: 'Downtime' }[name]} — Indonesia`, item[name], 'textarea') + field(`${name}_en`, `${{ pre: 'Pre-condition', post: 'Post-condition', downtime: 'Downtime' }[name]} — English`, item[`${name}_en`], 'textarea')).join('')}${field('comparison_position', 'Posisi foto perbandingan', item.comparison_position || 'center')}<label class="field full"><span>Foto perbandingan ilustratif</span><span class="switch-control"><input type="checkbox" name="comparison_illustrative" ${item.comparison_illustrative ? 'checked' : ''}></span></label></div>`;
        }
        html += '<p class="editor-hint">Konten dipublikasikan akan tampil di website setelah disimpan. Gunakan status Draft untuk menyimpannya tanpa ditampilkan.</p><div class="form-error editor-error" role="alert" hidden></div>';
        $('#editor-body').innerHTML = html;
        openDialog(editor);
    }

    function editorLanguage(language) {
        $$('[data-editor-language]').forEach(button => {
            const active = button.dataset.editorLanguage === language;
            button.classList.toggle('active', active);
            button.setAttribute('aria-selected', String(active));
            button.tabIndex = active ? 0 : -1;
        });
        $$('[data-language-panel]').forEach(panel => panel.hidden = panel.dataset.languagePanel !== language);
    }

    function editorError(message) {
        const element = $('.editor-error', editor);
        element.hidden = false;
        element.textContent = message;
        element.scrollIntoView({ block: 'nearest' });
    }

    editorForm.addEventListener('invalid', event => {
        const panel = event.target.closest('[data-language-panel]');
        if (panel) editorLanguage(panel.dataset.languagePanel);
    }, true);

    editorForm.addEventListener('input', event => {
        if (event.target.name === 'title' && !editorContext?.id && $('[name="slug"]', editorForm)) {
            const slugField = $('[name="slug"]', editorForm);
            if (!slugField.dataset.manual) slugField.value = event.target.value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }
        if (event.target.name === 'slug') event.target.dataset.manual = 'true';
    });

    editorForm.addEventListener('submit', async event => {
        event.preventDefault();
        if (!editorContext || mutationPending || uploadsPending || !editorForm.reportValidity()) return;
        const { key, id } = editorContext;
        const fields = Object.fromEntries(new FormData(editorForm));
        for (const name in fields) if (typeof fields[name] === 'string') fields[name] = fields[name].trim();
        $$('input[type="checkbox"][name]', editorForm).forEach(input => fields[input.name] = input.checked);
        let path;
        let method = id ? 'PUT' : 'POST';
        if (key.startsWith('settings:')) {
            path = `/admin/settings/${encodeURIComponent(key.split(':')[1])}`;
            method = 'PUT';
        } else if (key.startsWith('category:')) path = categoryPath(key.split(':')[1], id);
        else path = contentPath(key, id);
        if (fields.url && safeLink(fields.url) === '#') return editorError('Gunakan tautan https:// atau path lokal seperti /skin.');
        if (await mutate(path, method, fields, 'Perubahan berhasil disimpan.', editorForm)) editor.close();
    });

    function editSettings(group) {
        if (!state.settings[group] || !canSettings(group)) return deny();
        editorContext = { key: `settings:${group}` };
        $('#editor-title').textContent = group === 'home' ? 'Pengaturan beranda' : `Pengaturan ${labels[group] || group}`;
        $('#editor-eyebrow').textContent = 'WEBSITE SETTINGS';
        $('#editor-body').innerHTML = `${settingsFields(group)}<p class="editor-hint">Perubahan diterapkan pada website setelah disimpan.</p><div class="form-error editor-error" role="alert" hidden></div>`;
        openDialog(editor);
    }

    function editCategory(id = null) {
        const item = id ? state.category_records[collection].find(item => item.id === id) : { title: '', title_en: '', slug: '' };
        if (!item) return;
        if (!canCategory('update', id ? item : null)) return deny();
        editorContext = { key: `category:${collection}`, id };
        $('#editor-title').textContent = `${id ? 'Edit' : 'Tambah'} kategori`;
        $('#editor-eyebrow').textContent = 'KATEGORI KONTEN';
        $('#editor-body').innerHTML = `<div class="field-grid">${field('title', 'Nama kategori — Indonesia *', item.title, 'text', true, 'required')}${field('title_en', 'Category name — English', item.title_en, 'text', true)}${field('slug', 'Slug kategori *', item.slug, 'text', true, 'required pattern="[a-z0-9]+(?:-[a-z0-9]+)*"')}</div><p class="editor-hint">Kategori yang masih digunakan konten tidak dapat dihapus. Pindahkan konten ke kategori lain terlebih dahulu.</p><div class="form-error editor-error" role="alert" hidden></div>`;
        openDialog(editor);
    }

    function showPreview(key, id) {
        if (!canView(key)) return deny();
        const item = listFor(key).find(item => item.id === id);
        if (!item) return;
        $('#preview-title').textContent = labels[key] || 'Pratinjau konten';
        const details = [['schedule', 'JADWAL PRAKTIK'], ['education', 'PENDIDIKAN'], ['expertise', 'KEAHLIAN'], ['pre', 'PRE-CONDITION'], ['post', 'POST-CONDITION'], ['downtime', 'DOWNTIME']].filter(([name]) => item[name]).map(([name, label]) => `<h4>${label}</h4><p>${escape(item[name])}</p>`).join('');
        const image = item.schedule_image && key === 'doctors' ? item.schedule_image : item.image;
        $('#preview-body').innerHTML = `<article class="preview-content">${image ? `<img src="${escape(safeImage(image))}" alt="${escape(item.title)}" style="${key === 'doctors' ? 'object-fit:contain; background:#f4f3ef;' : ''}">` : ''}${status(item.status)}<h3>${escape(item.title)}</h3>${item.duration ? `<span class="table-category">${icon('clock')} ${escape(item.duration)}</span>` : ''}<p>${escape(item.description || 'Deskripsi belum diisi.')}</p>${item.body ? `<div class="preview-details"><p>${escape(item.body)}</p></div>` : ''}${details ? `<div class="preview-details">${details}</div>` : ''}${item.title_en ? `<div class="preview-details"><h4>ENGLISH CONTENT</h4><h3>${escape(item.title_en)}</h3><p>${escape(item.description_en || 'English description not yet added.')}</p></div>` : ''}<p class="preview-note">Pratinjau konten tersimpan. Konten berstatus Draft tidak ditampilkan pada website.</p></article>`;
        openDialog(preview);
    }

    function confirmAction(title, description, label, callback) {
        $('#confirm-title').textContent = title;
        $('#confirm-description').textContent = description;
        $('[value="confirm"]', confirmDialog).textContent = label;
        confirmDialog.returnValue = '';
        confirmDialog.addEventListener('close', () => {
            if (confirmDialog.returnValue === 'confirm') callback();
        }, { once: true });
        openDialog(confirmDialog);
        $('[value="cancel"]', confirmDialog).focus();
    }

    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-confirm-role]');
        if (!form || form.dataset.confirmed === 'true') return;
        event.preventDefault();
        confirmAction('Hapus role?', `Role “${form.dataset.confirmRole}” akan dihapus dari database. Tindakan ini tidak dapat dibatalkan.`, 'Hapus role', () => {
            form.dataset.confirmed = 'true';
            form.requestSubmit();
        });
    });

    function setImage(name, value) {
        const area = $(`[data-image-field="${name}"]`, editor);
        if (!area) return;
        $('img', area).src = safeImage(value);
        $('img', area).hidden = !safeImage(value);
        $(`[name="${name}"]`, area).value = value;
        $$(`[data-image-name="${name}"]`, editor).forEach(button => button.classList.toggle('selected', button.dataset.imageChoice === value));
    }

    editor.addEventListener('change', async event => {
        const input = event.target.closest('[data-image-upload]');
        if (!input?.files.length) return;
        if (!can('media.upload')) { input.value = ''; return deny(); }
        const file = input.files[0];
        if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            input.value = '';
            return editorError('Pilih gambar JPG, PNG, WebP, atau GIF dengan ukuran maksimal 2 MB.');
        }
        const context = editorContext;
        const submit = $('button[type="submit"]', editorForm);
        uploadsPending++;
        input.disabled = true;
        submit.disabled = true;
        try {
            const data = new FormData();
            data.append('image', file);
            const result = await request('/admin/uploads', 'POST', data);
            if (editor.open && editorContext === context) setImage(input.dataset.imageUpload, result.url);
        } catch (error) {
            if (editorContext === context) formError(editorForm, error);
        } finally {
            uploadsPending--;
            input.disabled = false;
            input.value = '';
            submit.disabled = uploadsPending > 0 || mutationPending;
        }
    });

    document.addEventListener('click', event => {
        if (mutationPending || uploadsPending) return;
        const close = event.target.closest('[data-dialog-close]');
        if (close) close.closest('dialog').close();
        const create = event.target.closest('[data-create]');
        if (create) openEditor(create.dataset.create);
        const edit = event.target.closest('[data-edit]');
        if (edit) openEditor(edit.dataset.edit, edit.dataset.id);
        const view = event.target.closest('[data-preview]');
        if (view) showPreview(view.dataset.preview, view.dataset.id);
        const remove = event.target.closest('[data-delete]');
        if (remove) {
            const key = remove.dataset.delete;
            const item = listFor(key).find(item => item.id === remove.dataset.id);
            if (!item) return;
            if (!canChange(key, 'delete', item)) return deny();
            confirmAction('Hapus konten?', `“${item.title}” akan dihapus dari database dan tidak lagi tampil di website.`, 'Hapus konten', () => mutate(contentPath(key, item.id), 'DELETE', null, 'Konten berhasil dihapus.'));
        }
        const duplicate = event.target.closest('[data-duplicate]');
        if (duplicate) {
            const key = duplicate.dataset.duplicate;
            if (!canView(key) || !canChange(key, 'create')) return deny();
            mutate(`${contentPath(key, duplicate.dataset.id)}/duplicate`, 'POST', null, 'Salinan konten berhasil dibuat sebagai draft.');
        }
        const language = event.target.closest('[data-editor-language]');
        if (language) editorLanguage(language.dataset.editorLanguage);
        const imageChoice = event.target.closest('[data-image-choice]');
        if (imageChoice) setImage(imageChoice.dataset.imageName, imageChoice.dataset.imageChoice);
        const settings = event.target.closest('[data-page-settings], [data-settings-edit]');
        if (settings) editSettings(settings.dataset.pageSettings || settings.dataset.settingsEdit);
        const tab = event.target.closest('[data-home-tab]');
        if (tab) {
            homeTab = tab.dataset.homeTab;
            $$('[data-home-tab]').forEach(button => {
                const active = button === tab;
                button.classList.toggle('active', active);
                button.setAttribute('aria-selected', String(active));
                button.tabIndex = active ? 0 : -1;
            });
            renderHome();
        }
        if (event.target.closest('[data-create-category]')) editCategory();
        const editCategoryButton = event.target.closest('[data-edit-category]');
        if (editCategoryButton) editCategory(editCategoryButton.dataset.editCategory);
        const deleteCategoryButton = event.target.closest('[data-delete-category]');
        if (deleteCategoryButton) {
            const item = state.category_records[collection].find(item => item.id === deleteCategoryButton.dataset.deleteCategory);
            if (!item || !canCategory('delete', item)) return deny();
            confirmAction('Hapus kategori?', `Kategori “${item.title}” akan dihapus. Pindahkan konten yang memakai kategori ini terlebih dahulu.`, 'Hapus kategori', () => mutate(categoryPath(collection, item.id), 'DELETE', null, 'Kategori berhasil dihapus.'));
        }
        const category = event.target.closest('[data-filter-category]');
        if (category) {
            $('#category-filter').value = category.dataset.filterCategory;
            currentPage = 1;
            renderCollection();
            $('.content-panel').scrollIntoView({ block: 'start', behavior: 'smooth' });
        }
        if (event.target.closest('[data-reset-filters]')) {
            $('#content-search').value = '';
            $('#status-filter').value = '';
            if ($('#category-filter')) $('#category-filter').value = '';
            currentPage = 1;
            renderCollection();
        }
    });

    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-settings-form]');
        if (!form) return;
        event.preventDefault();
        if (!canSettings(form.dataset.settingsForm)) return deny();
        if (mutationPending || uploadsPending || !form.reportValidity()) return;
        const fields = Object.fromEntries(new FormData(form));
        mutate(`/admin/settings/${encodeURIComponent(form.dataset.settingsForm)}`, 'PUT', fields, 'Pengaturan berhasil disimpan.', form);
    });

    document.addEventListener('change', async event => {
        const feature = event.target.closest('[data-feature-doctor]');
        if (!feature) return;
        const doctor = state.doctors.find(item => item.id === feature.dataset.featureDoctor);
        if (!doctor) return;
        if (!canChange('doctors', 'update', doctor)) { feature.checked = doctor.featured; return deny(); }
        const checked = feature.checked;
        feature.checked = doctor.featured;
        feature.disabled = true;
        if (await mutate(contentPath('doctors', doctor.id), 'PUT', { featured: checked }, 'Pilihan dokter di beranda diperbarui.')) {
            $$('[data-feature-doctor]').find(input => input.dataset.featureDoctor === doctor.id)?.focus();
        }
        feature.disabled = false;
    });

    $('#content-search')?.addEventListener('input', () => { currentPage = 1; renderCollection(); });
    ['#category-filter', '#status-filter'].forEach(selector => $(selector)?.addEventListener('change', () => { currentPage = 1; renderCollection(); }));
    $('#page-previous')?.addEventListener('click', () => { currentPage--; renderCollection(); });
    $('#page-next')?.addEventListener('click', () => { currentPage++; renderCollection(); });

    const sidebar = $('#admin-sidebar');
    const sidebarToggle = $('[data-sidebar-toggle]');
    const mobile = matchMedia('(max-width: 800px)');
    function sidebarOpen(open, focus = true) {
        document.body.classList.toggle('sidebar-open', open);
        $('[data-sidebar-close]').hidden = !open;
        sidebarToggle.setAttribute('aria-expanded', String(open));
        sidebarToggle.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
        sidebar.inert = mobile.matches && !open;
        if (open && focus) $('a', sidebar).focus();
        else if (focus) sidebarToggle.focus();
    }
    sidebarToggle.addEventListener('click', () => sidebarOpen(!document.body.classList.contains('sidebar-open')));
    $('[data-sidebar-close]').addEventListener('click', () => sidebarOpen(false));
    mobile.addEventListener('change', () => sidebarOpen(false, false));
    sidebarOpen(false, false);

    const popover = $('#topbar-popover');
    let activePopover = '';
    $$('[data-topbar-panel]').forEach(button => {
        button.setAttribute('aria-expanded', 'false');
        button.addEventListener('click', () => {
            const type = button.dataset.topbarPanel;
            const open = activePopover !== type || popover.hidden;
            activePopover = open ? type : '';
            popover.hidden = !open;
            $$('[data-topbar-panel]').forEach(item => item.setAttribute('aria-expanded', String(open && item === button)));
            if (type === 'activity') popover.innerHTML = `<h3>Aktivitas workspace</h3>${activityMarkup(3)}`;
            else if (type === 'help') popover.innerHTML = `<h3>Kelola konten website</h3><p>Halaman dan tindakan tersedia sesuai izin akun Anda. Gunakan pratinjau untuk melihat konten, atau ikon pensil untuk mengedit draft jika diizinkan.</p><p>Publikasi konten hanya tersedia bagi akun dengan izin publikasi.</p>${can('settings.view') ? '<a class="button button-white" href="/admin/pengaturan">Pengaturan website</a>' : ''}`;
            else popover.innerHTML = `<h3>${escape(account.name)}</h3><p>${escape(account.username)} · ${escape((state.access?.roles || []).join(', ') || 'Tanpa peran')}</p><button class="button button-white danger-text" type="button" data-profile-logout>${icon('logout')}Keluar akun</button>`;
        });
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('[data-topbar-panel], #topbar-popover')) {
            popover.hidden = true;
            activePopover = '';
            $$('[data-topbar-panel]').forEach(button => button.setAttribute('aria-expanded', 'false'));
        }
        if (event.target.closest('[data-profile-logout]')) $('.logout-button').form.requestSubmit();
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !$('dialog[open]')) {
            if (document.body.classList.contains('sidebar-open')) sidebarOpen(false);
            if (!popover.hidden) {
                popover.hidden = true;
                $(`[data-topbar-panel="${activePopover}"]`)?.focus();
                $$('[data-topbar-panel]').forEach(button => button.setAttribute('aria-expanded', 'false'));
                activePopover = '';
            }
        }
        if (event.key === 'Tab' && document.body.classList.contains('sidebar-open')) {
            const elements = $$('a,button,summary', sidebar).filter(element => element.getClientRects().length && !element.disabled);
            if (event.shiftKey && document.activeElement === elements[0]) { event.preventDefault(); elements.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === elements.at(-1)) { event.preventDefault(); elements[0].focus(); }
        }
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key) && event.target.matches('[role="tab"]')) {
            event.preventDefault();
            const tabs = $$('[role="tab"]', event.target.parentElement);
            const index = tabs.indexOf(event.target);
            const next = event.key === 'Home' ? tabs[0] : event.key === 'End' ? tabs.at(-1) : tabs[(index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length];
            next.click();
            next.focus();
        }
    });

    $$('[data-home-tab]').forEach((tab, index) => tab.tabIndex = index ? -1 : 0);
    render();
})();
