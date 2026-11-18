/**
 * GNN Lightbox - Vanilla JS Core
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Inject the required DOM elements into the page (only once)
    const overlay = document.createElement('div');
    overlay.className = 'gnn-lightbox-overlay';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Image Lightbox');

    const loader = document.createElement('div');
    loader.className = 'gnn-lightbox-loader';

    const container = document.createElement('div');
    container.className = 'gnn-lightbox-container';

    const content = document.createElement('div');
    content.className = 'gnn-lightbox-content';

    const img = document.createElement('img');
    img.alt = '';

    const closeBtn = document.createElement('div');
    closeBtn.className = 'gnn-lightbox-close';
    closeBtn.setAttribute('role', 'button');
    closeBtn.setAttribute('aria-label', 'Close');
    closeBtn.setAttribute('tabindex', '0');
    closeBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"></path></svg>';

    const prevBtn = document.createElement('div');
    prevBtn.className = 'gnn-lightbox-prev';
    prevBtn.setAttribute('role', 'button');
    prevBtn.setAttribute('aria-label', 'Previous');
    prevBtn.setAttribute('tabindex', '0');
    prevBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"></path></svg>';

    const nextBtn = document.createElement('div');
    nextBtn.className = 'gnn-lightbox-next';
    nextBtn.setAttribute('role', 'button');
    nextBtn.setAttribute('aria-label', 'Next');
    nextBtn.setAttribute('tabindex', '0');
    nextBtn.innerHTML = '<svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg>';

    // Build the tree
    content.appendChild(img);
    content.appendChild(closeBtn);
    container.appendChild(content);
    overlay.appendChild(loader);
    overlay.appendChild(prevBtn);
    overlay.appendChild(container);
    overlay.appendChild(nextBtn);
    document.body.appendChild(overlay);

    let isOpen = false;
    let closeTimeoutId = null;
    let lastFocusedElement = null;

    // Gallery state: links sharing the same data-lightbox-gallery value
    let galleryLinks = [];
    let galleryIndex = -1;

    // 2. Functions
    const isSafeUrl = (url) => {
        if (!url) {
            return false;
        }
        try {
            const parsed = new URL(url, window.location.href);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:';
        } catch (e) {
            return false;
        }
    };

    const getLinkAlt = (link) => {
        if (link.dataset && link.dataset.lightboxAlt !== undefined) {
            return link.dataset.lightboxAlt;
        }
        if (typeof link.querySelector === 'function') {
            const childImg = link.querySelector('img');
            if (childImg && childImg.alt) {
                return childImg.alt;
            }
        }
        return (typeof link.getAttribute === 'function' && link.getAttribute('title')) || '';
    };

    const updateNavVisibility = () => {
        const hasGallery = galleryLinks.length > 1;
        prevBtn.style.display = hasGallery ? 'flex' : 'none';
        nextBtn.style.display = hasGallery ? 'flex' : 'none';
    };

    const loadImage = (url, altText = '') => {
        if (!isSafeUrl(url)) {
            console.warn('GNN Lightbox: blocked unsafe image URL');
            closeLightbox();
            return;
        }
        overlay.classList.add('gnn-lightbox-loading');
        img.alt = altText;

        img.onload = () => {
            overlay.classList.remove('gnn-lightbox-loading');
        };
        img.onerror = () => {
            overlay.classList.remove('gnn-lightbox-loading');
            console.error('GNN Lightbox: failed to load image (' + url + ')');
        };
        img.src = url;
    };

    const openLightbox = (link) => {
        const url = link.getAttribute('href');
        if (!isSafeUrl(url)) {
            return;
        }

        if (closeTimeoutId) {
            clearTimeout(closeTimeoutId);
            closeTimeoutId = null;
        }

        lastFocusedElement = document.activeElement;
        isOpen = true;
        overlay.classList.add('gnn-lightbox-open');

        // Prevent page scrolling
        document.body.style.overflow = 'hidden';

        // Determine the gallery group
        const galleryName = getGalleryName(link);
        if (galleryName) {
            galleryLinks = Array.from(document.querySelectorAll('a.gnn-lightbox, a.lightbox, a[rel^="lightbox"]'))
                .filter(l => getGalleryName(l) === galleryName);
            galleryIndex = galleryLinks.indexOf(link);
        } else {
            galleryLinks = [link];
            galleryIndex = 0;
        }
        updateNavVisibility();

        loadImage(url, getLinkAlt(link));
        closeBtn.focus({ preventScroll: true });
    };

    const showAt = (index) => {
        if (galleryLinks.length === 0) {
            return;
        }
        galleryIndex = (index + galleryLinks.length) % galleryLinks.length;
        const targetLink = galleryLinks[galleryIndex];
        loadImage(targetLink.getAttribute('href'), getLinkAlt(targetLink));
    };

    const showPrev = () => showAt(galleryIndex - 1);
    const showNext = () => showAt(galleryIndex + 1);

    const closeLightbox = () => {
        isOpen = false;
        overlay.classList.remove('gnn-lightbox-open');
        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus({ preventScroll: true });
        }
        lastFocusedElement = null;
        closeTimeoutId = setTimeout(() => {
            img.src = '';
            img.alt = '';
            document.body.style.overflow = '';
            galleryLinks = [];
            galleryIndex = -1;
            closeTimeoutId = null;
        }, 300); // Wait for the transition to finish
    };

    // 3. Event Listeners

    const standardSupport = !!(window.gnnLightboxSettings && window.gnnLightboxSettings.standardSupport);
    const gutenbergMode = (window.gnnLightboxSettings && window.gnnLightboxSettings.gutenbergMode) || 'ignore';

    // Legacy WP lightbox pattern: rel="lightbox[gallery-name]" -> extracts the gallery name
    let autoGalleryCounter = 0;
    const getGalleryName = (link) => {
        if (link.dataset.lightboxGallery) {
            return link.dataset.lightboxGallery;
        }
        const rel = link.getAttribute('rel') || '';
        const match = rel.match(/^lightbox\[(.+)\]$/i);
        if (match) {
            return match[1];
        }
        // No explicit gallery name given: automatically group links that share
        // a common container, like WordPress's own gallery block markup.
        const container = link.closest('.wp-block-gallery, .gallery, [data-gnn-gallery]');
        if (container) {
            if (!container.dataset.gnnAutoGalleryId) {
                autoGalleryCounter += 1;
                container.dataset.gnnAutoGalleryId = 'gnn-auto-' + autoGalleryCounter;
            }
            return container.dataset.gnnAutoGalleryId;
        }
        return '';
    };

    // Bind all lightbox links on the page (gnn-lightbox class + optional legacy standard)
    const bindLinks = () => {
        const selectors = ['a.gnn-lightbox'];
        if (standardSupport) {
            selectors.push('a.lightbox', 'a[rel="lightbox"]', 'a[rel^="lightbox["]');
        }
        const links = document.querySelectorAll(selectors.join(', '));
        links.forEach(link => {
            // Don't interfere with WordPress's own Gutenberg image block lightbox
            if (link.closest('.wp-lightbox-container')) {
                return;
            }
            // Avoid attaching duplicate listeners
            if (!link.dataset.gnnBound) {
                link.dataset.gnnBound = 'true';
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = link.getAttribute('href');
                    if (url) {
                        openLightbox(link);
                    }
                });
            }
        });
    };

    // Bind links on first load
    bindLinks();

    // Override the Gutenberg image block lightbox (wp-lightbox-container) with ours
    if (gutenbergMode === 'override') {
        document.addEventListener('click', (e) => {
            const wpContainer = e.target.closest('.wp-lightbox-container');
            if (!wpContainer) {
                return;
            }
            const wpImg = wpContainer.querySelector('img');
            if (!wpImg) {
                return;
            }
            // Block WordPress's native lightbox behavior (Interactivity API)
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();

            const url = wpImg.currentSrc || wpImg.src;
            openLightbox({
                dataset: { lightboxAlt: wpImg.alt || '' },
                getAttribute: (attr) => (attr === 'href' ? url : null),
            });
        }, true); // Runs in the capture phase, before WP's own listener
    }

    // Close button clicked
    closeBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        closeLightbox();
    });
    closeBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            closeLightbox();
        }
    });

    // Previous/Next buttons
    prevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        showPrev();
    });
    prevBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            showPrev();
        }
    });
    nextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        showNext();
    });
    nextBtn.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            showNext();
        }
    });

    // Background (overlay) clicked
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay || e.target === container) {
            closeLightbox();
        }
    });

    // Keyboard controls
    document.addEventListener('keydown', (e) => {
        if (!isOpen) {
            return;
        }
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowLeft') {
            showPrev();
        } else if (e.key === 'ArrowRight') {
            showNext();
        }
    });
});
