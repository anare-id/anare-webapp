(() => {
    'use strict';

    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
    const escape = value => String(value ?? '').replace(/[&<>"']/g, char => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[char]));
    const icon = name => `<svg class="icon" aria-hidden="true"><use href="#a-${name}"/></svg>`;
    const safeImage = url => /^(https?:\/\/|\/[^/]|data:image\/(png|jpeg|webp|gif);base64,)/i.test(String(url)) ? url : '';
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
    $('[data-fill-login]')?.addEventListener('click', () => {
        $('#username').value = 'admin';
        $('#password').value = 'anare123';
        $('.login-submit').focus();
    });
    $('[data-login-help]')?.addEventListener('click', () => {
        $('[data-login-help-text]').hidden = !$('[data-login-help-text]').hidden;
    });

    const seedNode = $('#admin-seed');
    if (!seedNode) return;

    const seed = JSON.parse(seedNode.textContent);
    const storageKey = 'anare.admin.demo.v1';
    const copy = value => JSON.parse(JSON.stringify(value));
    let state = copy(seed);
    let storageNotice = '';
    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (saved?.version === 1 && saved.data && ['skin', 'shape', 'doctors', 'articles', 'services', 'reviews', 'activity'].every(key => Array.isArray(saved.data[key])) && saved.data.settings && saved.data.heroes && saved.data.categories) state = saved.data;
    } catch (_) {
        storageNotice = 'Data demo tidak dapat dimuat. Workspace menggunakan data awal.';
    }
    const section = document.body.dataset.adminSection;
    const collection = { dokter: 'doctors', artikel: 'articles' }[section] || section;
    const labels = { skin: 'Anare Skin', shape: 'Anare Shape', doctors: 'Dokter', articles: 'Artikel', services: 'Layanan beranda', reviews: 'Testimoni', 'hero:home': 'Hero beranda', 'hero:skin': 'Banner Skin', 'hero:shape': 'Banner Shape', 'hero:doctors': 'Hero dokter' };
    const singleLabels = { skin: 'treatment', shape: 'treatment', doctors: 'dokter', articles: 'artikel', services: 'layanan', reviews: 'testimoni' };
    const routes = { home: '/', skin: '/skin', shape: '/shape', doctors: '/doctors', articles: '/articles' };
    const listFor = (key, source = state) => key.startsWith('hero:') ? source.heroes[key.split(':')[1]] : source[key];
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

    function persist(next, message) {
        try {
            localStorage.setItem(storageKey, JSON.stringify({ version: 1, data: next }));
            state = next;
            render();
            if (message) toast(message);
            return true;
        } catch (_) {
            const message = 'Data belum tersimpan. Penyimpanan browser mungkin penuh atau diblokir. Coba gambar yang lebih kecil.';
            if ($('#editor-dialog').open) editorError(message);
            else toast(message, true);
            return false;
        }
    }

    function activity(next, title) {
        next.activity.unshift({ title, date: new Date().toISOString() });
        next.activity = next.activity.slice(0, 15);
    }

    function activityMarkup(limit = 4) {
        if (!state.activity.length) return `<div class="activity-empty">${icon('clock')}<h3>Workspace siap digunakan</h3><p>Belum ada perubahan. Mulai dengan mengedit salah satu konten website.</p></div>`;
        return state.activity.slice(0, limit).map(item => `<div class="activity-item"><span class="activity-dot">${icon('check')}</span><div><strong>${escape(item.title)}</strong><small>${escape(dateLabel(item.date))} · ${escape(new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(item.date)))}</small></div></div>`).join('');
    }

    function renderDashboard() {
        const cards = [['skin', 'Treatment Skin', 'treatment', 'skin'], ['shape', 'Treatment Shape', 'treatment', 'shape'], ['doctors', 'Tim dokter', 'profil', 'doctor'], ['articles', 'Artikel', 'artikel', 'article']];
        $('#dashboard-stats').innerHTML = cards.map(([key, label, unit, symbol]) => `<a class="stat-card" href="/admin/${{ doctors: 'dokter', articles: 'artikel' }[key] || key}"><div class="stat-card-top">${label}<span class="stat-icon">${icon(symbol)}</span></div><div class="stat-value"><strong>${state[key].length.toString().padStart(2, '0')}</strong><span>${unit}</span></div><div class="stat-card-bottom"><span class="status-dot"></span>${state[key].filter(item => item.status === 'published').length} dipublikasikan di demo</div></a>`).join('');
        const pages = [['beranda', 'Beranda', 'Hero, layanan, testimoni & lokasi', 'home'], ['skin', 'Anare Skin', `${state.skin.length} treatment · ${Object.keys(state.categories.skin).length} kategori`, 'skin'], ['shape', 'Anare Shape', `${state.shape.length} treatment · ${Object.keys(state.categories.shape).length} kategori`, 'shape'], ['dokter', 'Dokter', `${state.doctors.length} profil · Jadwal, pendidikan & keahlian`, 'doctor'], ['artikel', 'Artikel', `${state.articles.length} artikel · Konten edukasi & informasi`, 'article']];
        $('#page-overview').innerHTML = pages.map(([key, title, description, symbol]) => `<a class="overview-row" href="/admin/${key}"><span class="overview-icon">${icon(symbol)}</span><span class="overview-copy"><strong>${title}</strong><small>${escape(description)}</small></span><span class="overview-status"><span class="status-dot"></span>Siap dikelola</span>${icon('chevron')}</a>`).join('');
        $('#activity-list').innerHTML = activityMarkup();
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
        if (categoryFilter && categoryFilter.options.length === 1) {
            Object.entries(state.categories[collection] || {}).forEach(([value, label]) => categoryFilter.add(new Option(label, value)));
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
        if ($('#category-chips')) $('#category-chips').innerHTML = Object.entries(state.categories[collection]).map(([key, name]) => `<button class="category-chip" type="button" data-filter-category="${escape(key)}">${escape(name)}<span>${items.filter(item => item.category === key).length}</span></button>`).join('');
    }

    function heroCards(key, slideLabels = true) {
        return listFor(key).map((item, index) => `<article class="panel hero-card"><div class="hero-card-image"><img src="${escape(safeImage(item.image))}" alt="${escape(item.title)}" loading="lazy">${slideLabels ? `<span class="slide-label">SLIDE ${String(index + 1).padStart(2, '0')}</span>` : ''}${status(item.status)}</div><div class="hero-card-content"><h3>${escape(item.title)}</h3><p>${escape(item.description || 'Belum ada deskripsi. Tambahkan konten melalui editor.')}</p><div class="hero-card-actions"><button type="button" class="button button-white" data-edit="${key}" data-id="${escape(item.id)}">${icon('edit')}Edit ${slideLabels ? 'slide' : 'konten'}</button><button type="button" class="icon-button" data-preview="${key}" data-id="${escape(item.id)}" aria-label="Pratinjau ${escape(item.title)}">${icon('eye')}</button><button type="button" class="icon-button" data-delete="${key}" data-id="${escape(item.id)}" aria-label="Hapus ${escape(item.title)}">${icon('trash')}</button></div></div></article>`).join('');
    }

    function renderHome() {
        const target = $('#home-content');
        if (homeTab === 'hero') {
            target.innerHTML = `<div class="section-intro"><div><h2>Slideshow beranda</h2><p>Foto dan pesan pertama yang menyambut pengunjung.</p></div><button type="button" class="button button-dark" data-create="hero:home">${icon('plus')}Tambah slide</button></div><div class="hero-cards">${heroCards('hero:home')}</div><p class="section-hint">${icon('info')}Slideshow customer memakai ${seed.heroes.home.length} foto dengan interval ${escape(state.settings.home.slide_interval)} detik. Konten di atas adalah salinan demo yang bisa diedit.</p><div class="home-secondary-grid"><div class="panel home-mini-card"><span class="stat-icon">${icon('clock')}</span><div><h3>Pengaturan slideshow</h3><p>Judul section dan interval pergantian foto.</p></div><button type="button" class="icon-button" data-settings-edit="home" aria-label="Atur slideshow">${icon('settings')}</button></div><div class="panel home-mini-card"><span class="stat-icon">${icon('globe')}</span><div><h3>Lokasi & Instagram</h3><p>Kontak, jam klinik, dan tautan akun sosial.</p></div><a class="icon-button" href="/admin/pengaturan" aria-label="Atur kontak dan Instagram">${icon('arrow')}</a></div></div>`;
        } else if (homeTab === 'services') {
            target.innerHTML = `<div class="section-intro"><div><h2>Layanan unggulan</h2><p>Kartu Anare Clinic dan Anare Shape pada beranda.</p></div><button type="button" class="button button-dark" data-create="services">${icon('plus')}Tambah layanan</button></div><div class="hero-cards">${heroCards('services', false)}</div>`;
        } else if (homeTab === 'doctors') {
            target.innerHTML = `<div class="section-intro"><div><h2>Dokter pilihan</h2><p>Tentukan profil dokter yang ditampilkan pada beranda demo.</p></div><a class="button button-white" href="/admin/dokter">Kelola dokter ${icon('arrow')}</a></div><div class="doctor-picker-grid">${state.doctors.map(item => `<article class="panel doctor-pick"><img src="${escape(safeImage(item.image))}" alt="" loading="lazy"><div><h3>${escape(item.title)}</h3>${status(item.status)}</div><label class="switch-control"><input type="checkbox" data-feature-doctor="${escape(item.id)}" ${item.featured ? 'checked' : ''} aria-label="Tampilkan ${escape(item.title)} di beranda"></label></article>`).join('')}</div><p class="section-hint">${icon('info')}Perubahan pilihan hanya berlaku pada workspace demo. Foto dan urutan di website customer mengikuti data asli.</p>`;
        } else if (homeTab === 'reviews') {
            target.innerHTML = `<div class="section-intro"><div><h2>Ulasan klien</h2><p>Kelola nama, penilaian, dan cerita pengalaman klien.</p></div><button type="button" class="button button-dark" data-create="reviews">${icon('plus')}Tambah ulasan</button></div><div class="review-cards">${state.reviews.map(item => `<article class="panel review-card"><span class="review-stars">${'★'.repeat(Math.min(5, Math.max(1, Number(item.rating) || 5)))}</span><p>${escape(item.description)}</p><div class="review-card-footer"><strong>${escape(item.title)}</strong>${rowActions('reviews', item)}</div></article>`).join('')}</div>`;
        } else {
            target.innerHTML = `<section class="panel settings-panel"><div class="panel-heading"><div><h2>Pengaturan section beranda</h2><p>Judul dan deskripsi yang membentuk halaman utama.</p></div></div>${settingsForm('home')}</section>`;
        }
    }

    const settingsSchema = {
        home: [['services_title', 'Judul layanan'], ['doctors_title', 'Judul dokter'], ['doctors_description', 'Deskripsi dokter', 'textarea'], ['instagram_title', 'Judul Instagram'], ['reviews_title', 'Judul ulasan'], ['slide_interval', 'Interval slideshow (detik)', 'number']],
        contact: [['name', 'Nama klinik'], ['email', 'Email (contoh demo)', 'email'], ['whatsapp', 'Nomor WhatsApp', 'tel'], ['days', 'Hari operasional'], ['hours', 'Jam operasional'], ['maps', 'Tautan Google Maps', 'url'], ['address', 'Alamat lengkap', 'textarea']],
        social: [['clinic', 'Instagram Anare Clinic', 'url'], ['shape', 'Instagram Anare Shape', 'url'], ['skin', 'Instagram Anare Skin', 'url']],
        skin: [['title', 'Judul banner — Indonesia'], ['title_en', 'Judul banner — English'], ['description', 'Deskripsi banner', 'textarea'], ['per_page', 'Treatment per halaman', 'number']],
        shape: [['title', 'Judul banner — Indonesia'], ['title_en', 'Judul banner — English'], ['description', 'Deskripsi banner', 'textarea'], ['per_page', 'Treatment per halaman', 'number']],
    };

    function field(name, label, value = '', type = 'text', full = false, extra = '') {
        const input = type === 'textarea' ? `<textarea name="${name}" ${extra.includes('rows=') ? '' : 'rows="4"'} maxlength="15000" ${extra}>${escape(value)}</textarea>` : `<input type="${type}" name="${name}" value="${escape(value)}" ${type === 'number' ? 'min="1" max="30"' : 'maxlength="500"'} ${extra}>`;
        return `<label class="field${full || type === 'textarea' ? ' full' : ''}"><span>${label}</span>${input}</label>`;
    }

    function settingsFields(group) {
        return `<div class="field-grid">${settingsSchema[group].map(([name, label, type = 'text']) => field(name, label, state.settings[group][name], type, group === 'social', ['name', 'title', 'services_title'].includes(name) ? 'required' : '')).join('')}</div>`;
    }

    function settingsForm(group) {
        return `<form class="settings-form" data-settings-form="${group}">${settingsFields(group)}<div class="settings-form-footer"><button type="submit" class="button button-dark">${icon('check')}Simpan perubahan</button></div></form>`;
    }

    function renderSettings() {
        $('#contact-settings').innerHTML = settingsForm('contact');
        $('#social-settings').innerHTML = settingsForm('social');
    }

    function render() {
        $$('[data-sidebar-count]').forEach(el => el.textContent = state[el.dataset.sidebarCount].length);
        if (section === 'dashboard') renderDashboard();
        else if (section === 'beranda') renderHome();
        else if (section === 'pengaturan') renderSettings();
        else renderCollection();
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
        dialog.addEventListener('close', () => {
            if (!$('dialog[open]')) document.body.classList.remove('dialog-open');
            requestAnimationFrame(() => {
                if (document.activeElement === document.body && !$('dialog[open]')) {
                    const editButton = editorContext && $$('[data-edit]').find(button => button.dataset.edit === editorContext.key && button.dataset.id === editorContext.id);
                    (editButton || $('[data-create], [data-reset-workspace]') || $('#admin-main')).focus();
                }
            });
        });
        dialog.addEventListener('click', event => {
            const box = dialog.getBoundingClientRect();
            if (event.target === dialog && (event.clientX < box.left || event.clientX > box.right || event.clientY < box.top || event.clientY > box.bottom)) dialog.close();
        });
    });

    function selectField(name, label, value, options) {
        return `<label class="field"><span>${label}</span><select name="${name}">${Object.entries(options).map(([key, text]) => `<option value="${escape(key)}" ${key === value ? 'selected' : ''}>${escape(text)}</option>`).join('')}</select></label>`;
    }

    function imageField(item, name = 'image', title = 'Foto konten') {
        const library = [...new Set([item[name], ...seed.skin.map(item => item.image), ...seed.shape.map(item => item.image), ...seed.doctors.map(item => name === 'schedule_image' ? item.schedule_image : item.image), ...seed.heroes.home.map(item => item.image), ...seed.services.map(item => item.image)].filter(Boolean))];
        return `<div class="editor-section-title">${title}</div><div class="image-field" data-image-field="${name}"><img src="${escape(safeImage(item[name] || library[0]))}" alt="Pratinjau ${title}"><div><strong>Pilih foto dari perangkat atau galeri</strong><p>JPG, PNG, WebP, GIF · Maks. 2 MB · Demo lokal</p><label class="button button-white image-upload-label">${icon('upload')}Unggah foto<input type="file" accept="image/png,image/jpeg,image/webp,image/gif" data-image-upload="${name}" aria-label="Unggah ${title}"></label><input type="hidden" name="${name}" value="${escape(item[name] || '')}"></div></div><div class="image-library" aria-label="Galeri ${title}">${library.map(url => `<button type="button" data-image-choice="${escape(url)}" data-image-name="${name}" class="${url === item[name] ? 'selected' : ''}" aria-label="Gunakan foto ${escape(url.split('/').pop())}"><img src="${escape(safeImage(url))}" alt="" loading="lazy"></button>`).join('')}</div>`;
    }

    function openEditor(key, id = null) {
        const original = id ? listFor(key).find(item => item.id === id) : null;
        if (id && !original) return;
        const item = copy(original || { title: '', title_en: '', description: '', description_en: '', slug: '', category: Object.keys(state.categories[key] || {})[0] || '', status: 'draft', image: key === 'doctors' ? seed.doctors[0].image : seed.skin[0].image, date: new Date().toISOString().slice(0, 10), author: 'Anare Clinic', rating: '5', featured: false });
        editorContext = { key, id, item };
        $('#editor-title').textContent = `${id ? 'Edit' : 'Tambah'} ${key.startsWith('hero:') ? 'slide' : singleLabels[key] || 'konten'}`;
        $('#editor-eyebrow').textContent = labels[key] || 'CONTENT EDITOR';
        const isHero = key.startsWith('hero:');
        let html = `<div class="editor-language-tabs" role="tablist" aria-label="Bahasa konten"><button class="active" type="button" role="tab" aria-selected="true" data-editor-language="id"><span class="language-flag">ID</span>Bahasa Indonesia</button><button type="button" role="tab" aria-selected="false" tabindex="-1" data-editor-language="en"><span class="language-flag">EN</span>English</button></div>`;
        html += `<div data-language-panel="id"><div class="field-grid">${field('title', key === 'doctors' ? 'Nama dokter *' : key === 'reviews' ? 'Nama klien *' : 'Judul / nama konten *', item.title, 'text', true, 'required')}${field('description', key === 'articles' ? 'Ringkasan artikel' : key === 'doctors' ? 'Biografi singkat' : key === 'reviews' ? 'Isi ulasan' : 'Deskripsi', item.description, 'textarea')}${key === 'articles' ? field('body', 'Isi artikel', item.body || item.description, 'textarea', true, 'rows="9"') : ''}${key === 'doctors' ? field('education', 'Pendidikan & pengalaman', item.education, 'textarea') + field('expertise', 'Keahlian (satu poin per baris)', item.expertise, 'textarea') : ''}</div></div>`;
        html += `<div data-language-panel="en" hidden><div class="field-grid">${field('title_en', key === 'doctors' ? 'Doctor’s name' : key === 'reviews' ? 'Client’s name' : 'Title / content name', item.title_en, 'text', true)}${field('description_en', key === 'articles' ? 'Article summary' : 'Description', item.description_en, 'textarea')}${key === 'articles' ? field('body_en', 'Article content', item.body_en, 'textarea') : ''}${key === 'doctors' ? field('education_en', 'Education & experience', item.education_en, 'textarea') + field('expertise_en', 'Expertise (one item per line)', item.expertise_en, 'textarea') : ''}</div></div>`;
        html += `<div class="editor-section-title">Detail konten</div><div class="field-grid">${selectField('status', 'Status di workspace demo', item.status, { published: 'Dipublikasikan', draft: 'Draft' })}`;
        if (state.categories[key]) html += selectField('category', 'Kategori', item.category, state.categories[key]);
        if (['skin', 'shape', 'doctors', 'articles'].includes(key)) html += field('slug', 'Slug / alamat halaman *', item.slug, 'text', false, 'required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" title="Gunakan huruf kecil, angka, dan tanda hubung"');
        if (['skin', 'shape'].includes(key)) html += field('duration', 'Durasi treatment', item.duration || '', 'text', false, 'placeholder="Contoh: 30 Menit"');
        if (key === 'doctors') html += field('specialty', 'Spesialisasi / label profil', item.specialty) + field('schedule', 'Jadwal praktik — Indonesia', item.schedule, 'textarea') + field('schedule_en', 'Practice schedule — English', item.schedule_en, 'textarea');
        if (key === 'articles') html += field('date', 'Tanggal artikel', item.date, 'date') + field('author', 'Penulis', item.author);
        if (key === 'reviews') html += selectField('rating', 'Penilaian', String(item.rating || 5), { 5: '5 bintang', 4: '4 bintang', 3: '3 bintang', 2: '2 bintang', 1: '1 bintang' });
        if (isHero || key === 'services') html += field('url', 'Tautan tombol', item.url || (key === 'services' ? '/skin' : 'https://wa.me/628113210288'));
        if (isHero) html += field('button', 'Label tombol', item.button || 'Buat Janji') + field('position', 'Posisi foto', item.position || 'center');
        if (['doctors', 'articles'].includes(key)) html += `<label class="field full"><span>Tampilkan di beranda</span><span class="switch-control"><input type="checkbox" name="featured" ${item.featured ? 'checked' : ''} aria-label="Tampilkan di beranda"></span></label>`;
        html += '</div>';
        if (key !== 'reviews') html += imageField(item);
        if (key === 'doctors') html += imageField(item, 'schedule_image', 'Foto & jadwal praktik');
        if (['skin', 'shape'].includes(key)) html += `<div class="editor-section-title">Informasi detail treatment</div><div class="field-grid">${field('pre', 'Pre-condition', item.pre, 'textarea')}${field('post', 'Post-condition', item.post, 'textarea')}${field('downtime', 'Downtime', item.downtime, 'textarea')}</div>`;
        html += '<p class="editor-hint">Konten ini adalah data demo. Tombol simpan memperbarui tampilan admin dan pratinjau di browser ini, belum memublikasikan perubahan ke website customer.</p><div class="form-error editor-error" role="alert" hidden></div>';
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

    editorForm.addEventListener('submit', event => {
        event.preventDefault();
        if (!editorContext || !editorForm.reportValidity()) return;
        const { key, id, item } = editorContext;
        if (key.startsWith('settings:')) {
            const group = key.split(':')[1];
            const next = copy(state);
            next.settings[group] = Object.fromEntries(new FormData(editorForm));
            activity(next, `Pengaturan ${group === 'home' ? 'beranda' : labels[group] || group} diperbarui`);
            if (persist(next, 'Pengaturan demo berhasil disimpan.')) editor.close();
            return;
        }
        const fields = Object.fromEntries(new FormData(editorForm));
        for (const key in fields) if (typeof fields[key] === 'string') fields[key] = fields[key].trim();
        if (!fields.title) return editorError('Nama atau judul konten tidak boleh kosong.');
        if (fields.slug && listFor(key).some(entry => entry.id !== id && entry.slug === fields.slug)) return editorError('Slug ini sudah digunakan. Gunakan alamat halaman yang berbeda.');
        if (fields.url && safeLink(fields.url) === '#') return editorError('Gunakan tautan https:// atau path lokal seperti /skin.');
        const next = copy(state);
        const updated = { ...item, ...fields, title_en: fields.title_en || fields.title, id: id || `demo-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`, updated_at: new Date().toISOString() };
        if (['doctors', 'articles'].includes(key)) updated.featured = new FormData(editorForm).has('featured');
        const items = listFor(key, next);
        if (id) items[items.findIndex(entry => entry.id === id)] = updated;
        else items.unshift(updated);
        activity(next, `${id ? 'Memperbarui' : 'Menambahkan'} ${updated.title}`);
        if (persist(next, `${id ? 'Perubahan' : 'Konten baru'} berhasil disimpan di demo.`)) editor.close();
    });

    function editSettings(group) {
        editorContext = { key: `settings:${group}` };
        $('#editor-title').textContent = group === 'home' ? 'Pengaturan beranda' : `Pengaturan ${labels[group] || group}`;
        $('#editor-eyebrow').textContent = 'WEBSITE SETTINGS';
        $('#editor-body').innerHTML = `${settingsFields(group)}<p class="editor-hint">Perubahan disimpan di browser untuk demo UI, belum diterapkan pada website customer.</p><div class="form-error editor-error" role="alert" hidden></div>`;
        openDialog(editor);
    }

    function showPreview(key, id) {
        const item = listFor(key).find(item => item.id === id);
        if (!item) return;
        $('#preview-title').textContent = labels[key] || 'Pratinjau konten';
        const details = [['schedule', 'JADWAL PRAKTIK'], ['education', 'PENDIDIKAN'], ['expertise', 'KEAHLIAN'], ['pre', 'PRE-CONDITION'], ['post', 'POST-CONDITION'], ['downtime', 'DOWNTIME']].filter(([name]) => item[name]).map(([name, label]) => `<h4>${label}</h4><p>${escape(item[name])}</p>`).join('');
        const image = item.schedule_image && key === 'doctors' ? item.schedule_image : item.image;
        $('#preview-body').innerHTML = `<article class="preview-content">${image ? `<img src="${escape(safeImage(image))}" alt="${escape(item.title)}" style="${key === 'doctors' ? 'object-fit:contain; background:#f4f3ef;' : ''}">` : ''}${status(item.status)}<h3>${escape(item.title)}</h3>${item.duration ? `<span class="table-category">${icon('clock')} ${escape(item.duration)}</span>` : ''}<p>${escape(item.description || 'Deskripsi belum diisi.')}</p>${item.body ? `<div class="preview-details"><p>${escape(item.body)}</p></div>` : ''}${details ? `<div class="preview-details">${details}</div>` : ''}${item.title_en ? `<div class="preview-details"><h4>ENGLISH CONTENT</h4><h3>${escape(item.title_en)}</h3><p>${escape(item.description_en || 'English description not yet added.')}</p></div>` : ''}<p class="preview-note">Ini pratinjau data demo, bukan perubahan pada website customer.</p></article>`;
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

    function setImage(name, value) {
        const area = $(`[data-image-field="${name}"]`, editor);
        if (!area) return;
        $('img', area).src = safeImage(value);
        $(`[name="${name}"]`, area).value = value;
        $$(`[data-image-name="${name}"]`, editor).forEach(button => button.classList.toggle('selected', button.dataset.imageChoice === value));
    }

    editor.addEventListener('change', async event => {
        const input = event.target.closest('[data-image-upload]');
        if (!input?.files.length) return;
        const file = input.files[0];
        if (!['image/png', 'image/jpeg', 'image/webp', 'image/gif'].includes(file.type) || file.size > 2 * 1024 * 1024) {
            input.value = '';
            return editorError('Pilih gambar JPG, PNG, WebP, atau GIF dengan ukuran maksimal 2 MB.');
        }
        try {
            const data = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
            const image = new Image();
            image.src = data;
            await image.decode();
            if (editor.open) setImage(input.dataset.imageUpload, data);
        } catch (_) { editorError('Gambar tidak dapat dibaca. Silakan pilih berkas gambar lain.'); }
    });

    document.addEventListener('click', event => {
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
            confirmAction('Hapus konten?', `“${item.title}” akan dihapus dari workspace demo ini. Website customer tidak berubah.`, 'Hapus konten', () => {
                const next = copy(state);
                const items = listFor(key, next);
                items.splice(items.findIndex(entry => entry.id === item.id), 1);
                activity(next, `Menghapus ${item.title}`);
                persist(next, 'Konten berhasil dihapus dari demo.');
            });
        }
        const duplicate = event.target.closest('[data-duplicate]');
        if (duplicate) {
            const key = duplicate.dataset.duplicate;
            const item = listFor(key).find(item => item.id === duplicate.dataset.id);
            const next = copy(state);
            const suffix = Date.now().toString(36);
            listFor(key, next).unshift({ ...copy(item), id: `copy-${suffix}`, slug: `${item.slug || 'konten'}-copy-${suffix}`, title: `${item.title} (Salinan)`, title_en: `${item.title_en || item.title} (Copy)`, status: 'draft', updated_at: new Date().toISOString() });
            activity(next, `Menduplikasi ${item.title}`);
            persist(next, 'Salinan konten berhasil dibuat sebagai draft.');
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
        if (event.target.closest('[data-reset-workspace]')) confirmAction('Reset workspace demo?', 'Seluruh perubahan lokal, konten tambahan, dan aktivitas akan dikembalikan ke data awal website.', 'Reset data demo', () => persist(copy(seed), 'Workspace demo dikembalikan ke data awal.'));
    });

    document.addEventListener('submit', event => {
        const form = event.target.closest('[data-settings-form]');
        if (!form) return;
        event.preventDefault();
        if (!form.reportValidity()) return;
        const next = copy(state);
        next.settings[form.dataset.settingsForm] = Object.fromEntries(new FormData(form));
        activity(next, 'Pengaturan website diperbarui');
        persist(next, 'Pengaturan berhasil disimpan di demo.');
    });

    document.addEventListener('change', event => {
        const feature = event.target.closest('[data-feature-doctor]');
        if (!feature) return;
        const next = copy(state);
        const doctor = next.doctors.find(item => item.id === feature.dataset.featureDoctor);
        doctor.featured = feature.checked;
        activity(next, `Pilihan beranda ${doctor.title} diperbarui`);
        if (persist(next, 'Pilihan dokter di beranda demo diperbarui.')) {
            $$('[data-feature-doctor]').find(input => input.dataset.featureDoctor === doctor.id)?.focus();
        } else feature.checked = !feature.checked;
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
            else if (type === 'help') popover.innerHTML = '<h3>Halo, ada yang bisa dibantu?</h3><p>Pilih halaman di sidebar, lalu klik ikon pensil untuk mengedit. Gunakan tab ID / EN untuk melengkapi dua bahasa.</p><p>Perubahan tersimpan di browser ini. Mode demo belum terhubung ke database atau publikasi website.</p><a class="button button-white" href="/admin/pengaturan">Pengaturan workspace</a>';
            else popover.innerHTML = `<h3>Testing Admin</h3><p>Akun demo · admin</p><p>Workspace ini menggunakan login contoh untuk peninjauan UI.</p><button class="button button-white danger-text" type="button" data-profile-logout>${icon('logout')}Keluar akun</button>`;
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
            const elements = $$('a,button', sidebar);
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
    if (storageNotice) toast(storageNotice, true);
})();
