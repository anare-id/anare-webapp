const siteTranslations = JSON.parse(document.querySelector('#site-translations')?.textContent || '{}');
const text = (key, replacements = {}) => {
    let value = siteTranslations[key] || key;
    Object.entries(replacements).forEach(([name, replacement]) => value = value.split(`:${name}`).join(String(replacement)));
    return value;
};
document.querySelectorAll('[data-language-form]').forEach(form => {
    form.addEventListener('submit', () => {
        form.querySelector('[name="return_to"]').value = location.pathname + location.search + location.hash;
    });
});

const navigation = document.querySelector('.nav-links');
const menuToggle = document.querySelector('.menu-toggle');
const dropdown = document.querySelector('.dropdown');
const dropdownToggle = document.querySelector('.dropdown-toggle');
const servicesMenu = document.querySelector('.dropdown-menu');
const siteHeader = document.querySelector('.site-header');
const mobileViewport = window.matchMedia('(max-width: 800px)');

const syncHeader = () => siteHeader.classList.toggle('is-scrolled', window.scrollY > 24);
let headerFrame = null;
window.addEventListener('scroll', () => {
    if (headerFrame !== null) return;
    headerFrame = requestAnimationFrame(() => {
        syncHeader();
        headerFrame = null;
    });
}, { passive: true });
window.addEventListener('pageshow', syncHeader);
syncHeader();

function setServicesOpen(open) {
    servicesMenu.hidden = !open;
    dropdownToggle.setAttribute('aria-expanded', String(open));
    dropdownToggle.setAttribute('aria-label', text(open ? 'hide_services' : 'show_services'));
}

function setNavigationOpen(open) {
    navigation.classList.toggle('open', open);
    siteHeader.classList.toggle('is-menu-open', open);
    menuToggle.setAttribute('aria-expanded', String(open));
    menuToggle.setAttribute('aria-label', text(open ? 'close_navigation' : 'open_navigation'));
    if (!open) setServicesOpen(false);
}

menuToggle.addEventListener('click', () => {
    setNavigationOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
});

dropdownToggle.addEventListener('click', () => setServicesOpen(servicesMenu.hidden));
dropdown.addEventListener('pointerenter', event => {
    if (!mobileViewport.matches && event.pointerType === 'mouse') setServicesOpen(true);
});
dropdown.addEventListener('pointerleave', () => {
    if (!mobileViewport.matches && !dropdown.contains(document.activeElement)) setServicesOpen(false);
});
dropdown.addEventListener('focusout', () => {
    requestAnimationFrame(() => {
        if (!dropdown.contains(document.activeElement)) setServicesOpen(false);
    });
});

navigation.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', () => setNavigationOpen(false));
});

document.addEventListener('click', event => {
    if (!event.target.closest('.site-header')) setNavigationOpen(false);
    else if (!dropdown.contains(event.target)) setServicesOpen(false);
});

document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    if (!servicesMenu.hidden) {
        setServicesOpen(false);
        dropdownToggle.focus();
    } else if (navigation.classList.contains('open')) {
        setNavigationOpen(false);
        menuToggle.focus();
    }
});

mobileViewport.addEventListener('change', () => setNavigationOpen(false));

document.querySelectorAll('[data-filter]').forEach(button => {
    button.setAttribute('aria-pressed', String(button.classList.contains('active')));
    button.addEventListener('click', () => {
        const scope = button.closest('.section') || document;
        scope.querySelectorAll('[data-filter]').forEach(item => {
            const active = item === button;
            item.classList.toggle('active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        scope.querySelectorAll('[data-category]').forEach(card => {
            card.hidden = button.dataset.filter !== 'all' && card.dataset.category !== button.dataset.filter;
        });
    });
});

// Keep the selected category visible in the swipeable mobile category bar.
const categoryStrip = document.querySelector('.skin-categories');
if (categoryStrip) {
    const compactCategories = window.matchMedia('(max-width: 1024px)');
    const revealActiveCategory = () => {
        const active = categoryStrip.querySelector('[aria-current]');
        if (!compactCategories.matches || !active) return;
        const strip = categoryStrip.getBoundingClientRect();
        const item = active.getBoundingClientRect();
        categoryStrip.scrollLeft += item.left - strip.left - (strip.width - item.width) / 2;
    };
    compactCategories.addEventListener('change', revealActiveCategory);
    document.fonts.ready.then(revealActiveCategory);
}

document.querySelectorAll('[data-hero-carousel]').forEach(carousel => {
    const track = carousel.querySelector('.hero-slider-track');
    const slides = [...track.querySelectorAll('[data-hero-slide]')];
    const controls = carousel.querySelector('.hero-carousel-controls');
    const dots = [...carousel.querySelectorAll('[data-carousel-dot]')];
    const pauseButton = carousel.querySelector('[data-carousel-pause]');
    const status = carousel.querySelector('[data-carousel-status]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const count = slides.length;
    if (count < 2) {
        controls.hidden = true;
        return;
    }

    const cloneSlide = slide => {
        const clone = slide.cloneNode(true);
        clone.removeAttribute('data-hero-slide');
        clone.setAttribute('data-carousel-clone', '');
        clone.setAttribute('aria-hidden', 'true');
        clone.inert = true;
        const image = clone.querySelector('img');
        if (image) {
            image.alt = '';
            image.loading = 'lazy';
            image.fetchPriority = 'low';
        }
        return clone;
    };
    track.prepend(cloneSlide(slides[count - 1]));
    track.append(cloneSlide(slides[0]));

    const configuredInterval = Number(carousel.dataset.carouselInterval);
    const interval = Math.max(1000, configuredInterval > 0 ? configuredInterval : 5000);
    let current = 0;
    let position = 1;
    let moving = false;
    let userPaused = false;
    let focusPaused = false;
    let inView = carousel.getBoundingClientRect().bottom > 0;
    let timer = null;
    let transitionTimer = null;
    let pointerStart = null;

    const renderPosition = animate => {
        track.classList.toggle('is-instant', !animate || reducedMotion.matches);
        track.style.transform = `translate3d(${-position * 100}%, 0, 0)`;
    };

    const updateActiveSlide = manual => {
        carousel.dataset.activeSlide = String(current);
        slides.forEach((slide, index) => {
            slide.setAttribute('aria-hidden', String(index !== current));
            slide.inert = index !== current;
        });
        dots.forEach((dot, index) => {
            dot.classList.toggle('active', index === current);
            dot.setAttribute('aria-pressed', String(index === current));
        });
        status.setAttribute('aria-live', manual ? 'polite' : 'off');
        status.textContent = text('photo_status', { number: current + 1, total: count });
    };

    const schedule = () => {
        window.clearTimeout(timer);
        timer = null;
        const canPlay = !userPaused && !focusPaused && !reducedMotion.matches && !document.hidden && inView;
        carousel.dataset.autoplay = canPlay ? 'playing' : 'paused';
        pauseButton.classList.toggle('is-paused', userPaused);
        pauseButton.setAttribute('aria-pressed', String(userPaused));
        pauseButton.setAttribute('aria-label', text(userPaused ? 'play_slideshow' : 'pause_slideshow'));
        pauseButton.hidden = reducedMotion.matches;
        if (canPlay && !moving) timer = window.setTimeout(() => step(1, false), interval);
    };

    const finishTransition = () => {
        window.clearTimeout(transitionTimer);
        transitionTimer = null;
        if (position === 0 || position === count + 1) {
            position = position === 0 ? count : 1;
            renderPosition(false);
        }
        moving = false;
        schedule();
    };

    const navigate = (index, physicalPosition, manual) => {
        if (moving || index === current) return;
        window.clearTimeout(timer);
        current = index;
        position = physicalPosition;
        moving = !reducedMotion.matches;
        renderPosition(moving);
        updateActiveSlide(manual);
        if (moving) transitionTimer = window.setTimeout(finishTransition, 1100);
        else finishTransition();
    };

    const step = (direction, manual = true) => {
        navigate((current + direction + count) % count, position + direction, manual);
    };

    carousel.querySelector('[data-carousel-prev]').addEventListener('click', () => step(-1));
    carousel.querySelector('[data-carousel-next]').addEventListener('click', () => step(1));
    dots.forEach((dot, index) => dot.addEventListener('click', () => navigate(index, index + 1, true)));
    pauseButton.addEventListener('click', () => {
        userPaused = !userPaused;
        if (!userPaused) focusPaused = false;
        schedule();
    });
    track.addEventListener('transitionend', event => {
        if (event.target === track && event.propertyName === 'transform') finishTransition();
    });

    carousel.addEventListener('focusin', () => {
        focusPaused = true;
        schedule();
    });
    carousel.addEventListener('focusout', () => requestAnimationFrame(() => {
        focusPaused = carousel.contains(document.activeElement);
        schedule();
    }));
    carousel.addEventListener('keydown', event => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        step(event.key === 'ArrowRight' ? 1 : -1);
    });
    carousel.addEventListener('pointerdown', event => {
        if (event.pointerType !== 'touch' || event.target.closest('a, button')) return;
        pointerStart = { x: event.clientX, y: event.clientY };
    }, { passive: true });
    carousel.addEventListener('pointerup', event => {
        if (!pointerStart) return;
        const dx = event.clientX - pointerStart.x;
        const dy = event.clientY - pointerStart.y;
        pointerStart = null;
        if (Math.abs(dx) >= 50 && Math.abs(dx) > Math.abs(dy) * 1.25) step(dx < 0 ? 1 : -1);
    }, { passive: true });
    carousel.addEventListener('pointercancel', () => pointerStart = null, { passive: true });
    document.addEventListener('visibilitychange', schedule);
    reducedMotion.addEventListener('change', () => {
        if (reducedMotion.matches) finishTransition();
        else schedule();
    });
    if ('IntersectionObserver' in window) {
        new IntersectionObserver(entries => {
            inView = entries[0].isIntersecting && entries[0].intersectionRatio >= .25;
            schedule();
        }, { threshold: [0, .25] }).observe(carousel);
    }

    renderPosition(false);
    updateActiveSlide(false);
    schedule();
});

// Only the pointed/focused doctor is highlighted. On touch screens, the first
// tap reveals the name and the second tap follows that doctor's profile link.
const doctorLinks = [...document.querySelectorAll('.home-doctor-link')];
const clearDoctorSelection = () => doctorLinks.forEach(link => link.classList.remove('is-highlighted'));
const positionDoctorLabel = (link, x, y, centered = false) => {
    const caption = link.querySelector('.doctor-profile-caption');
    const bounds = link.getBoundingClientRect();
    const section = link.closest('.home-doctor-feature').getBoundingClientRect();
    const minX = Math.max(8, section.left + 8);
    const maxX = Math.min(innerWidth - 8, section.right - 8) - caption.offsetWidth;
    const minY = Math.max(8, section.top + 8);
    const maxY = Math.min(innerHeight - 8, section.bottom - 8) - caption.offsetHeight;
    const left = Math.max(minX, Math.min(centered ? x - caption.offsetWidth / 2 : x + 14, maxX));
    const top = Math.max(minY, Math.min(y + 14, maxY));
    link.style.setProperty('--doctor-label-x', `${left - bounds.left}px`);
    link.style.setProperty('--doctor-label-y', `${top - bounds.top}px`);
    link.style.setProperty('--doctor-label-transform', 'none');
};
doctorLinks.forEach(link => {
    let lastPointer = '';
    link.addEventListener('pointerenter', event => {
        if (event.pointerType !== 'mouse') return;
        clearDoctorSelection();
        positionDoctorLabel(link, event.clientX, event.clientY);
    });
    link.addEventListener('pointermove', event => {
        if (event.pointerType === 'mouse') positionDoctorLabel(link, event.clientX, event.clientY);
    });
    link.addEventListener('pointerdown', event => lastPointer = event.pointerType);
    link.addEventListener('focus', () => {
        if (!link.matches(':focus-visible')) return;
        clearDoctorSelection();
        const bounds = link.getBoundingClientRect();
        positionDoctorLabel(link, bounds.left + bounds.width / 2, bounds.top + bounds.height * .45, true);
    });
    link.addEventListener('click', event => {
        if (event.detail === 0) return;
        if (event.pointerType !== 'touch' && lastPointer !== 'touch') return;
        if (link.classList.contains('is-highlighted')) return;
        event.preventDefault();
        clearDoctorSelection();
        link.classList.add('is-highlighted');
        const bounds = link.getBoundingClientRect();
        positionDoctorLabel(link, bounds.left + bounds.width / 2, bounds.top + bounds.height * .45, true);
    });
});
document.addEventListener('click', event => {
    if (!event.target.closest('.home-doctor-link')) clearDoctorSelection();
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape') clearDoctorSelection();
});

// Enhance directory links with native, focus-contained doctor dialogs while
// retaining the profile URL for no-JS use and modified/new-tab clicks.
const doctorModals = [...document.querySelectorAll('[data-doctor-modal]')];
if (doctorModals.length) {
    const reducedModalMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let activeModal = null;
    let modalOpener = null;
    let closeTimer = null;
    let bodyStyles = null;

    const closeDoctorModal = dialog => {
        if (!dialog.open || dialog.classList.contains('is-closing')) return;
        if (reducedModalMotion.matches) {
            dialog.close();
            return;
        }
        dialog.classList.add('is-closing');
        closeTimer = window.setTimeout(() => dialog.close(), 170);
    };

    document.querySelectorAll('[data-doctor-dialog]').forEach(trigger => {
        trigger.addEventListener('click', event => {
            if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const dialog = document.getElementById(trigger.dataset.doctorDialog);
            if (!dialog || typeof dialog.showModal !== 'function') return;
            event.preventDefault();
            if (activeModal?.open) return;

            const gap = Math.max(0, innerWidth - document.documentElement.clientWidth);
            bodyStyles = {
                overflow: document.body.style.overflow,
                paddingRight: document.body.style.paddingRight,
                gap: document.body.style.getPropertyValue('--doctor-modal-scrollbar-gap'),
            };
            const padding = parseFloat(getComputedStyle(document.body).paddingRight) || 0;
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = `${padding + gap}px`;
            document.body.style.setProperty('--doctor-modal-scrollbar-gap', `${gap}px`);
            document.body.classList.add('doctor-modal-open');
            modalOpener = trigger;
            activeModal = dialog;
            dialog.classList.remove('is-closing');
            dialog.querySelectorAll('img').forEach(image => image.loading = 'eager');
            dialog.showModal();
            // Hidden dialogs have no scroll box; reset after showModal so a
            // reopened mobile popup starts at the photo/schedule again.
            dialog.querySelector('.doctor-modal-scroll').scrollTop = 0;
        });
    });

    doctorModals.forEach(dialog => {
        dialog.querySelector('[data-doctor-modal-close]').addEventListener('click', () => closeDoctorModal(dialog));
        dialog.addEventListener('keydown', event => {
            if (event.key !== 'Tab') return;
            const focusable = [...dialog.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])')]
                .filter(element => element.getClientRects().length && getComputedStyle(element).visibility !== 'hidden');
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
                event.preventDefault();
                first?.focus();
            }
        });
        dialog.addEventListener('cancel', event => {
            event.preventDefault();
            closeDoctorModal(dialog);
        });
        dialog.addEventListener('click', event => {
            if (event.target !== dialog) return;
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) closeDoctorModal(dialog);
        });
        dialog.addEventListener('close', () => {
            window.clearTimeout(closeTimer);
            dialog.classList.remove('is-closing');
            if (activeModal !== dialog) return;
            document.body.classList.remove('doctor-modal-open');
            document.body.style.overflow = bodyStyles.overflow;
            document.body.style.paddingRight = bodyStyles.paddingRight;
            if (bodyStyles.gap) document.body.style.setProperty('--doctor-modal-scrollbar-gap', bodyStyles.gap);
            else document.body.style.removeProperty('--doctor-modal-scrollbar-gap');
            const opener = modalOpener;
            activeModal = null;
            modalOpener = null;
            bodyStyles = null;
            if (opener?.isConnected) opener.focus({ preventScroll: true });
        });
    });
}

// Google review text is server-rendered and escaped in the shared Blade partial.
// Refresh once when the section approaches the viewport; failures retain it.
document.querySelectorAll('[data-google-reviews]').forEach(section => {
    const content = section.querySelector('[data-google-reviews-content]');
    const status = section.querySelector('[data-google-reviews-status]');
    let loaded = false;
    const load = async () => {
        if (loaded) return;
        loaded = true;
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 10000);
        content.setAttribute('aria-busy', 'true');
        status.textContent = section.dataset.loadingLabel;
        try {
            const response = await fetch(section.dataset.reviewsUrl, {
                headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal,
            });
            if (!response.ok) throw new Error('Google reviews unavailable');
            const data = await response.json();
            if (typeof data.html !== 'string' || !['reviews', 'empty'].includes(data.mode)) throw new Error('Invalid review response');
            if (content.innerHTML.trim() !== data.html.trim()) {
                const template = document.createElement('template');
                template.innerHTML = data.html;
                content.replaceChildren(template.content);
            }
            section.dataset.reviewSource = data.source;
            status.textContent = data.source === 'api' ? section.dataset.selectedLabel : section.dataset.savedLabel;
        } catch {
            status.textContent = section.dataset.savedLabel;
        } finally {
            window.clearTimeout(timeout);
            content.setAttribute('aria-busy', 'false');
        }
    };
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            observer.disconnect();
            load();
        }, { rootMargin: '300px' });
        observer.observe(section);
    } else load();
});

// Real thumbnails are server-rendered from the fetched cache. Optional account
// tokens refresh the combined gallery without embedding provider profile panels.
document.querySelectorAll('[data-instagram-feed]').forEach(section => {
    const content = section.querySelector('[data-instagram-content]');
    const status = section.querySelector('[data-instagram-status]');
    const fallback = section.querySelector('[data-instagram-fallback]');
    let loaded = false;
    const bindImageFallback = (image, card) => {
        image.addEventListener('error', () => {
            image.remove();
            card.classList.add('instagram-post-unavailable');
            const label = document.createElement('span');
            label.textContent = text('instagram_open');
            card.append(label);
        }, { once: true });
    };
    content.querySelectorAll('.instagram-post img').forEach(image => bindImageFallback(image, image.closest('a')));

    const showPosts = posts => {
        const valid = posts.filter(post => post && typeof post.image_url === 'string' && typeof post.permalink === 'string').slice(0, 6);
        if (!valid.length) return false;
        const existing = [...content.querySelectorAll('.instagram-post')];
        if (existing.length === valid.length && existing.every((card, index) => card.href === valid[index].permalink && card.querySelector('img')?.src === valid[index].image_url && card.title === (valid[index].caption || ''))) {
            status.textContent = text('instagram_gallery');
            return true;
        }
        const grid = document.createElement('div');
        grid.className = 'home-instagram-grid instagram-post-grid';
        valid.forEach(post => {
            const username = post.username || 'Instagram';
            const card = document.createElement('a');
            card.className = 'instagram-post';
            card.href = post.permalink;
            card.target = '_blank';
            card.rel = 'noopener noreferrer';
            card.dataset.instagramAccount = username;
            card.setAttribute('aria-label', text('instagram_post', { username }));
            if (post.caption) card.title = post.caption;
            const image = document.createElement('img');
            image.src = post.image_url;
            image.alt = post.caption || text('instagram_alt', { username });
            image.loading = 'lazy';
            image.decoding = 'async';
            image.width = 512;
            image.height = 512;
            bindImageFallback(image, card);
            card.append(image);
            grid.append(card);
        });
        content.replaceChildren(grid);
        fallback.classList.add('sr-only');
        section.dataset.instagramMode = 'feed';
        status.textContent = text('instagram_gallery');
        return true;
    };

    const keepCached = () => {
        if (content.querySelector('.instagram-post')) {
            status.textContent = text('instagram_cached');
            return;
        }
        fallback.classList.remove('sr-only');
        section.dataset.instagramMode = 'empty';
        status.textContent = text('instagram_profiles');
    };

    const load = async () => {
        if (loaded) return;
        loaded = true;
        content.setAttribute('aria-busy', 'true');
        status.textContent = text('instagram_loading');
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 20000);
        try {
            const response = await fetch(section.dataset.feedUrl, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                signal: controller.signal,
            });
            if (!response.ok) throw new Error('Instagram feed unavailable');
            const data = await response.json();
            if (!(data.mode === 'feed' && Array.isArray(data.posts) && showPosts(data.posts))) keepCached();
        } catch {
            keepCached();
        } finally {
            window.clearTimeout(timeout);
            content.setAttribute('aria-busy', 'false');
        }
    };

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            if (!entries.some(entry => entry.isIntersecting)) return;
            observer.disconnect();
            load();
        }, { rootMargin: '300px' });
        observer.observe(section);
    } else load();
});
