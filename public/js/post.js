document.addEventListener('DOMContentLoaded', () => {
    const article = document.querySelector('[data-post-id]');
    const likeBtn = document.getElementById('like-btn');
    const bookmarkBtn = document.getElementById('bookmark-btn');
    const bookmarkLabel = document.getElementById('bookmark-label');
    const bookmarkIconOutline = document.getElementById('bookmark-icon-outline');
    const bookmarkIconFilled = document.getElementById('bookmark-icon-filled');
    const listPickerMenu = document.getElementById('list-picker-menu');
    const listPickerItems = document.getElementById('list-picker-items');
    const listPickerEmpty = document.getElementById('list-picker-empty');
    const createListBtn = document.getElementById('create-list-btn');
    const createListModal = document.getElementById('create-list-modal');
    const createListBackdrop = document.getElementById('create-list-modal-backdrop');
    const createListForm = document.getElementById('create-list-form');
    const createListName = document.getElementById('create-list-name');
    const createListDescription = document.getElementById('create-list-description');
    const createListPrivate = document.getElementById('create-list-private');
    const createListNameCount = document.getElementById('create-list-name-count');
    const createListDescriptionCount = document.getElementById('create-list-description-count');
    const createListSubmit = document.getElementById('create-list-submit');
    const createListCancel = document.getElementById('create-list-cancel');
    const followBtn = document.getElementById('follow-btn');
    const progressBar = document.getElementById('reading-progress');

    const updateBookmarkState = (bookmarked) => {
        if (!bookmarkBtn) return;
        bookmarkBtn.dataset.bookmarked = bookmarked ? '1' : '0';
        if (bookmarkLabel) bookmarkLabel.textContent = bookmarked ? 'Saved' : 'Save';
        bookmarkIconOutline?.classList.toggle('hidden', bookmarked);
        bookmarkIconFilled?.classList.toggle('hidden', !bookmarked);
    };

    const closeListPicker = () => {
        listPickerMenu?.classList.add('hidden');
        bookmarkBtn?.setAttribute('aria-expanded', 'false');
    };

    const openListPicker = () => {
        listPickerMenu?.classList.remove('hidden');
        bookmarkBtn?.setAttribute('aria-expanded', 'true');
    };

    const toggleListPicker = () => {
        if (listPickerMenu?.classList.contains('hidden')) {
            openListPicker();
        } else {
            closeListPicker();
        }
    };

    const openCreateListModal = () => {
        createListModal?.classList.remove('hidden');
        document.body.classList.add('overflow-y-hidden');
        createListName?.focus();
    };

    const closeCreateListModal = () => {
        createListModal?.classList.add('hidden');
        document.body.classList.remove('overflow-y-hidden');
        createListForm?.reset();
        if (createListNameCount) createListNameCount.textContent = '0';
        if (createListDescriptionCount) createListDescriptionCount.textContent = '0';
        if (createListSubmit) createListSubmit.disabled = true;
    };

    const syncCreateSubmitState = () => {
        if (!createListSubmit || !createListName) return;
        createListSubmit.disabled = createListName.value.trim() === '';
    };

    const appendListRow = (list, checked = false) => {
        if (!listPickerItems) return;

        listPickerEmpty?.remove();

        const label = document.createElement('label');
        label.className = 'flex items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50 cursor-pointer';
        label.innerHTML = `
            <span class="relative flex shrink-0">
                <input type="checkbox" class="list-picker-checkbox peer sr-only" data-list-id="${list.id}" data-default="0" data-private="${list.is_private ? '1' : '0'}" ${checked ? 'checked' : ''}>
                <span class="flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-white peer-checked:border-gray-900 peer-checked:bg-gray-900 [&>svg]:hidden peer-checked:[&>svg]:block">
                    <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" class="h-2.5 w-2.5 fill-white">
                        <path d="m0 6.313 3.704 3.705.904.904.66-1.095 5.296-8.795L8.85 0 3.554 8.795l1.563-.191-3.704-3.705z"></path>
                    </svg>
                </span>
            </span>
            <span class="flex-1 text-sm text-gray-900"></span>
        `;
        label.querySelector('.flex-1').textContent = list.name;

        if (list.is_private) {
            const lock = document.createElement('span');
            lock.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="13" viewBox="0 0 10 13" class="shrink-0 text-gray-400" aria-label="Private list">
                    <path fill="currentColor" fill-rule="evenodd" d="M2.727 3.082C2.727 1.905 3.74.935 5 .935s2.273.973 2.273 2.147v2.436H2.727zM8.19 5.518h-.007V3.082C8.182 1.378 6.747 0 5 0 3.252 0 1.818 1.373 1.818 3.082v2.436h-.007c-.48.002-.941.2-1.28.55S0 6.892 0 7.387v3.744c0 .246.045.489.136.715.09.227.224.433.392.607s.368.311.588.405.457.142.695.142h6.378c.48-.002.941-.2 1.28-.55s.53-.824.531-1.319V7.387c0-.246-.045-.489-.136-.715a1.9 1.9 0 0 0-.392-.607 1.8 1.8 0 0 0-.588-.405 1.8 1.8 0 0 0-.695-.142" clip-rule="evenodd"></path>
                </svg>
            `;
            label.appendChild(lock.firstElementChild);
        }

        listPickerItems.appendChild(label);
    };

    const applySaveState = (data) => {
        updateBookmarkState(data.bookmarked);

        const defaultCheckbox = listPickerItems?.querySelector('.list-picker-checkbox[data-default="1"]');
        if (defaultCheckbox && typeof data.in_default_list === 'boolean') {
            defaultCheckbox.checked = data.in_default_list;
        }
    };

    if (article && likeBtn) {
        likeBtn.addEventListener('click', async () => {
            const response = await window.csrfFetch(`/posts/${article.dataset.postId}/like`, { method: 'POST' });
            if (!response.ok) return;

            const data = await response.json();
            document.getElementById('like-count').textContent = data.likes_count;
            likeBtn.dataset.liked = data.liked ? '1' : '0';
            likeBtn.classList.toggle('text-green-700', data.liked);
            likeBtn.classList.toggle('text-gray-900', !data.liked);
            const likeLabel = document.getElementById('like-label');
            if (likeLabel) {
                likeLabel.textContent = data.liked ? 'Unlike' : 'Like';
            }
        });
    }

    if (bookmarkBtn && listPickerMenu) {
        bookmarkBtn.addEventListener('click', (event) => {
            event.stopPropagation();
            toggleListPicker();
        });

        document.addEventListener('click', (event) => {
            const picker = document.getElementById('reading-list-picker');
            if (picker?.contains(event.target)) return;
            closeListPicker();
        });
    }

    if (article && listPickerItems) {
        listPickerItems.addEventListener('change', async (event) => {
            const checkbox = event.target.closest('.list-picker-checkbox');
            if (!checkbox) return;

            const listId = checkbox.dataset.listId;
            const response = await window.csrfFetch(`/me/lists/${listId}/posts/${article.dataset.postId}`, { method: 'POST' });
            if (!response.ok) {
                checkbox.checked = !checkbox.checked;
                return;
            }

            const data = await response.json();
            checkbox.checked = data.saved;
            applySaveState(data);
        });
    }

    createListBtn?.addEventListener('click', () => {
        closeListPicker();
        openCreateListModal();
    });

    createListCancel?.addEventListener('click', closeCreateListModal);
    createListBackdrop?.addEventListener('click', closeCreateListModal);

    createListName?.addEventListener('input', () => {
        if (createListNameCount) createListNameCount.textContent = String(createListName.value.length);
        syncCreateSubmitState();
    });

    createListDescription?.addEventListener('input', () => {
        if (createListDescriptionCount) createListDescriptionCount.textContent = String(createListDescription.value.length);
    });

    if (article && createListForm) {
        createListForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            const name = createListName?.value.trim();
            if (!name) return;

            const response = await window.csrfFetch('/me/lists', {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    description: createListDescription?.value.trim() || null,
                    is_private: createListPrivate?.checked ?? false,
                    post_id: Number(article.dataset.postId),
                }),
            });

            if (!response.ok) return;

            const data = await response.json();
            appendListRow(data.list, true);
            applySaveState(data);
            closeCreateListModal();
            openListPicker();
        });
    }

    const applyFollowButtonState = (button, following) => {
        button.dataset.following = following ? '1' : '0';
        button.textContent = following
            ? (button.dataset.labelFollowing || 'Following')
            : (button.dataset.labelFollow || 'Follow');

        button.classList.toggle('bg-gray-900', following);
        button.classList.toggle('text-white', following);
        button.classList.toggle('hover:bg-gray-800', following);
        button.classList.toggle('border', !following);
        button.classList.toggle('border-gray-900', !following);
        button.classList.toggle('text-gray-900', !following);
        button.classList.toggle('hover:bg-gray-50', !following);
    };

    const bindFollowButton = (button, url) => {
        button.addEventListener('click', async () => {
            const response = await window.csrfFetch(url, { method: 'POST' });
            if (!response.ok) return;

            const data = await response.json();
            applyFollowButtonState(button, data.following);
        });
    };

    if (followBtn) {
        bindFollowButton(followBtn, `/users/${followBtn.dataset.userId}/follow`);
    }

    const subscribeBtn = document.getElementById('subscribe-btn');
    const subscribeDelivery = document.getElementById('subscribe-delivery');

    if (subscribeBtn) {
        subscribeBtn.addEventListener('click', async () => {
            const isSubscribed = subscribeBtn.dataset.subscribed === '1';
            const body = !isSubscribed && subscribeDelivery?.value
                ? JSON.stringify({ delivery: subscribeDelivery.value })
                : undefined;
            const response = await window.csrfFetch(`/users/${subscribeBtn.dataset.userId}/subscribe`, {
                method: 'POST',
                headers: body ? { 'Content-Type': 'application/json' } : {},
                body,
            });
            if (!response.ok) return;

            const data = await response.json();
            subscribeBtn.dataset.subscribed = data.subscribed ? '1' : '0';
            subscribeBtn.textContent = data.subscribed ? 'Subscribed' : 'Subscribe';
            subscribeBtn.classList.toggle('bg-indigo-600', data.subscribed);
            subscribeBtn.classList.toggle('text-white', data.subscribed);
            subscribeBtn.classList.toggle('hover:bg-indigo-700', data.subscribed);
            subscribeBtn.classList.toggle('border', !data.subscribed);
            subscribeBtn.classList.toggle('border-gray-300', !data.subscribed);
            subscribeBtn.classList.toggle('text-gray-900', !data.subscribed);
            subscribeBtn.classList.toggle('hover:bg-gray-50', !data.subscribed);
        });
    }

    if (subscribeDelivery) {
        subscribeDelivery.addEventListener('change', async () => {
            const response = await window.csrfFetch(`/users/${subscribeDelivery.dataset.userId}/subscribe`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ delivery: subscribeDelivery.value }),
            });

            if (!response.ok) {
                window.location.reload();
            }
        });
    }

    const followCategoryBtn = document.getElementById('follow-category-btn');
    if (followCategoryBtn) {
        bindFollowButton(followCategoryBtn, `/categories/${followCategoryBtn.dataset.categoryId}/follow`);
    }

    const followTagBtn = document.getElementById('follow-tag-btn');
    if (followTagBtn) {
        bindFollowButton(followTagBtn, `/tags/${followTagBtn.dataset.tagId}/follow`);
    }

    const followMagazineBtn = document.getElementById('follow-magazine-btn');
    if (followMagazineBtn) {
        bindFollowButton(followMagazineBtn, `/magazines/${followMagazineBtn.dataset.magazineId}/follow`);
    }

    if (progressBar) {
        window.addEventListener('scroll', () => {
            const scrollTop = window.scrollY;
            const docHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
            progressBar.style.width = `${progress}%`;
        });
    }
});
