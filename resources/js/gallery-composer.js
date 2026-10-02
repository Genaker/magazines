document.addEventListener('DOMContentLoaded', () => {
    const composer = document.getElementById('gallery-composer');
    const dropzone = document.getElementById('gallery-dropzone');
    const input = document.getElementById('gallery-images-input');
    const preview = document.getElementById('gallery-preview');

    if (! composer || ! dropzone || ! input || ! preview) {
        return;
    }

    const labels = {
        caption: preview.dataset.captionLabel ?? 'Photo description',
        placeholder: preview.dataset.captionPlaceholder ?? '',
        remove: preview.dataset.removeLabel ?? 'Remove',
        cover: preview.dataset.coverLabel ?? 'Cover',
        photoNumber: preview.dataset.photoNumberLabel ?? 'Photo __NUMBER__',
        drag: preview.dataset.dragLabel ?? 'Drag to reorder',
    };

    /** @type {{ file: File, caption: string, key: string }[]} */
    let pendingItems = [];

    /** @type {number | null} */
    let dragIndex = null;

    dropzone.addEventListener('click', () => input.click());

    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-gray-500', 'bg-gray-50');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-gray-500', 'bg-gray-50');
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-gray-500', 'bg-gray-50');
        addFiles(event.dataTransfer?.files);
    });

    input.addEventListener('change', () => addFiles(input.files));

    preview.addEventListener('input', (event) => {
        const textarea = event.target.closest('.gallery-photo-card__caption');

        if (! textarea) {
            return;
        }

        updateCounter(textarea);
        syncPendingCaption(textarea);
        updateThumbnailAlt(textarea);
    });

    preview.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.gallery-photo-card__remove-btn');

        if (! removeBtn) {
            return;
        }

        event.preventDefault();
        const card = removeBtn.closest('.gallery-photo-card');

        if (! card) {
            return;
        }

        if (card.classList.contains('gallery-photo-card--existing')) {
            toggleExistingRemoval(card);
            return;
        }

        const index = pendingCardIndex(card);

        if (index >= 0) {
            pendingItems.splice(index, 1);
            syncInput();
            renderPendingItems();
        }
    });

    preview.addEventListener('dragstart', (event) => {
        const card = event.target.closest('.gallery-photo-card--pending');

        if (! card) {
            return;
        }

        dragIndex = pendingCardIndex(card);
        card.classList.add('opacity-50');
        event.dataTransfer?.setData('text/plain', String(dragIndex));
        event.dataTransfer.effectAllowed = 'move';
    });

    preview.addEventListener('dragend', (event) => {
        event.target.closest('.gallery-photo-card')?.classList.remove('opacity-50');
        dragIndex = null;
        clearDropIndicators();
    });

    preview.addEventListener('dragover', (event) => {
        const target = event.target.closest('.gallery-photo-card--pending');

        if (target === null || dragIndex === null) {
            return;
        }

        event.preventDefault();
        clearDropIndicators();
        target.classList.add('ring-2', 'ring-gray-400');
    });

    preview.addEventListener('dragleave', (event) => {
        event.target.closest('.gallery-photo-card--pending')?.classList.remove('ring-2', 'ring-gray-400');
    });

    preview.addEventListener('drop', (event) => {
        const target = event.target.closest('.gallery-photo-card--pending');

        if (target === null || dragIndex === null) {
            return;
        }

        event.preventDefault();
        clearDropIndicators();

        const targetIndex = pendingCardIndex(target);

        if (targetIndex < 0 || targetIndex === dragIndex) {
            return;
        }

        const [moved] = pendingItems.splice(dragIndex, 1);
        pendingItems.splice(targetIndex, 0, moved);
        syncInput();
        renderPendingItems();
    });

    initExistingCards();

    function fileKey(file) {
        return `${file.name}:${file.size}:${file.lastModified}`;
    }

    function addFiles(fileList) {
        if (! fileList) {
            return;
        }

        Array.from(fileList).forEach((file) => {
            if (! file.type.startsWith('image/')) {
                return;
            }

            pendingItems.push({
                file,
                caption: '',
                key: fileKey(file),
            });
        });

        syncInput();
        renderPendingItems();
    }

    function renderPendingItems() {
        preview.querySelectorAll('.gallery-photo-card--pending').forEach((node) => node.remove());

        const existingCount = preview.querySelectorAll('.gallery-photo-card--existing:not(.gallery-photo-card--removed)').length;

        pendingItems.forEach((item, index) => {
            preview.append(buildPendingCard(item, existingCount + index + 1));
        });

        refreshCoverBadges();
    }

    function buildPendingCard(item, displayIndex) {
        const card = document.createElement('li');
        card.className = 'gallery-photo-card gallery-photo-card--pending rounded-lg border border-gray-200 bg-white p-4';
        card.draggable = true;
        card.dataset.fileKey = item.key;

        const objectUrl = URL.createObjectURL(item.file);

        card.innerHTML = `
            <div class="flex gap-4">
                <div class="flex flex-col items-center gap-2 shrink-0">
                    <button type="button" class="gallery-photo-card__drag-handle cursor-grab text-gray-400 hover:text-gray-600 p-1" tabindex="-1" aria-hidden="true" title="${escapeHtml(labels.drag)}">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                        </svg>
                    </button>
                    <div class="relative">
                        <img src="${objectUrl}" alt="" class="gallery-photo-card__thumb w-28 h-28 rounded-lg object-cover bg-gray-100">
                    </div>
                    <span class="gallery-photo-card__number text-xs font-medium text-gray-500">${escapeHtml(formatPhotoNumber(displayIndex))}</span>
                </div>
                <div class="min-w-0 flex-1 flex flex-col gap-2">
                    <label class="text-sm font-medium text-gray-900">${escapeHtml(labels.caption)}</label>
                    <textarea name="gallery_captions[]" rows="3" maxlength="500" placeholder="${escapeHtml(labels.placeholder)}" class="gallery-photo-card__caption w-full rounded-md border border-gray-300 px-3 py-2 text-sm resize-y min-h-[4.5rem]">${escapeHtml(item.caption)}</textarea>
                    <div class="flex flex-wrap items-center justify-between gap-2 mt-auto">
                        <span class="gallery-photo-card__counter text-xs text-gray-500" data-max="500">0 / 500</span>
                        <button type="button" class="gallery-photo-card__remove-btn text-sm text-red-700 hover:text-red-900 hover:underline">${escapeHtml(labels.remove)}</button>
                    </div>
                </div>
            </div>
        `;

        const textarea = card.querySelector('.gallery-photo-card__caption');
        updateCounter(textarea);
        updateThumbnailAlt(textarea);

        return card;
    }

    function syncPendingCaption(textarea) {
        const card = textarea.closest('.gallery-photo-card--pending');

        if (! card) {
            return;
        }

        const index = pendingCardIndex(card);

        if (index >= 0) {
            pendingItems[index].caption = textarea.value;
        }
    }

    function syncInput() {
        const transfer = new DataTransfer();
        pendingItems.forEach(({ file }) => transfer.items.add(file));
        input.files = transfer.files;
    }

    function pendingCardIndex(card) {
        const key = card.dataset.fileKey;

        if (! key) {
            return Array.from(preview.querySelectorAll('.gallery-photo-card--pending')).indexOf(card);
        }

        return pendingItems.findIndex((item) => item.key === key);
    }

    function formatPhotoNumber(number) {
        return labels.photoNumber.replace('__NUMBER__', String(number));
    }

    function refreshCoverBadges() {
        const visibleCards = preview.querySelectorAll('.gallery-photo-card:not(.gallery-photo-card--removed)');

        visibleCards.forEach((card, index) => {
            const numberEl = card.querySelector('.gallery-photo-card__number');
            const thumb = card.querySelector('.gallery-photo-card__thumb');
            const thumbWrap = thumb?.parentElement;

            if (numberEl) {
                numberEl.textContent = formatPhotoNumber(index + 1);
            }

            thumbWrap?.querySelector('.gallery-photo-card__cover')?.remove();

            if (index === 0 && thumbWrap) {
                const badge = document.createElement('span');
                badge.className = 'gallery-photo-card__cover absolute bottom-1 left-1 right-1 rounded bg-black/70 px-1.5 py-0.5 text-center text-[10px] font-medium uppercase tracking-wide text-white';
                badge.textContent = labels.cover;
                thumbWrap.append(badge);
            }
        });
    }

    function toggleExistingRemoval(card) {
        const checkbox = card.querySelector('.gallery-photo-card__remove-input');
        const marked = card.classList.toggle('gallery-photo-card--removed');

        if (checkbox) {
            checkbox.checked = marked;
        }

        card.classList.toggle('opacity-50', marked);
        refreshCoverBadges();
    }

    function initExistingCards() {
        preview.querySelectorAll('.gallery-photo-card--existing .gallery-photo-card__caption').forEach((textarea) => {
            updateCounter(textarea);
            updateThumbnailAlt(textarea);
        });

        preview.querySelectorAll('.gallery-photo-card--existing').forEach((card) => {
            const checkbox = card.querySelector('.gallery-photo-card__remove-input');

            if (checkbox?.checked) {
                card.classList.add('gallery-photo-card--removed', 'opacity-50');
            }
        });

        refreshCoverBadges();
    }

    function updateCounter(textarea) {
        const counter = textarea.closest('.gallery-photo-card')?.querySelector('.gallery-photo-card__counter');
        const max = Number(counter?.dataset.max ?? 500);
        const length = textarea.value.length;

        if (counter) {
            counter.textContent = `${length} / ${max}`;
            counter.classList.toggle('text-amber-600', length >= max * 0.9);
            counter.classList.toggle('text-gray-500', length < max * 0.9);
        }
    }

    function updateThumbnailAlt(textarea) {
        const card = textarea.closest('.gallery-photo-card');
        const thumb = card?.querySelector('.gallery-photo-card__thumb');
        const text = textarea.value.trim();

        if (thumb) {
            thumb.alt = text || labels.caption;
        }
    }

    function clearDropIndicators() {
        preview.querySelectorAll('.gallery-photo-card--pending').forEach((card) => {
            card.classList.remove('ring-2', 'ring-gray-400');
        });
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
});
