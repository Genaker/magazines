/** Tag input with autocomplete suggestions from existing site tags. */
export function initTagInputs(root = document) {
    root.querySelectorAll('[data-tag-input]').forEach((container) => {
        const input = container.querySelector('[data-tag-input-field]');
        const list = container.querySelector('[data-tag-suggestions]');

        if (!input || !list || input.dataset.tagInputReady === '1') {
            return;
        }

        input.dataset.tagInputReady = '1';

        const suggestUrl = input.dataset.suggestUrl;
        let debounceTimer = null;
        let activeIndex = -1;
        let suggestions = [];

        const hideSuggestions = () => {
            list.classList.add('hidden');
            list.innerHTML = '';
            suggestions = [];
            activeIndex = -1;
        };

        const currentFragment = () => {
            const parts = input.value.split(',');
            return parts[parts.length - 1].trim();
        };

        const applySuggestion = (name) => {
            const parts = input.value.split(',').map((part) => part.trim()).filter(Boolean);
            parts.pop();
            parts.push(name);
            input.value = parts.length ? `${parts.join(', ')}, ` : `${name}, `;
            hideSuggestions();
            input.focus();
        };

        const renderSuggestions = (items) => {
            suggestions = items;
            activeIndex = -1;

            if (items.length === 0) {
                hideSuggestions();
                return;
            }

            list.innerHTML = '';
            items.forEach((name, index) => {
                const item = document.createElement('li');
                item.className = 'cursor-pointer px-3 py-2 text-sm text-gray-800 hover:bg-gray-100';
                item.textContent = name;
                item.dataset.index = String(index);
                item.setAttribute('role', 'option');
                item.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                    applySuggestion(name);
                });
                list.appendChild(item);
            });

            list.classList.remove('hidden');
        };

        const fetchSuggestions = async () => {
            const fragment = currentFragment();

            if (!fragment || fragment.length < 1) {
                hideSuggestions();
                return;
            }

            const url = new URL(suggestUrl, window.location.origin);
            url.searchParams.set('q', fragment);

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (!response.ok) {
                    hideSuggestions();
                    return;
                }

                const existing = input.value
                    .split(',')
                    .map((part) => part.trim().toLowerCase())
                    .filter(Boolean);

                const items = (await response.json())
                    .filter((name) => !existing.includes(String(name).trim().toLowerCase()));

                renderSuggestions(items);
            } catch {
                hideSuggestions();
            }
        };

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(fetchSuggestions, 200);
        });

        input.addEventListener('keydown', (event) => {
            if (list.classList.contains('hidden') || suggestions.length === 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                activeIndex = Math.min(activeIndex + 1, suggestions.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
            } else if (event.key === 'Enter' && activeIndex >= 0) {
                event.preventDefault();
                applySuggestion(suggestions[activeIndex]);
                return;
            } else if (event.key === 'Escape') {
                hideSuggestions();
                return;
            } else {
                return;
            }

            [...list.children].forEach((child, index) => {
                child.classList.toggle('bg-gray-100', index === activeIndex);
            });
        });

        input.addEventListener('blur', () => {
            setTimeout(hideSuggestions, 150);
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initTagInputs());
} else {
    initTagInputs();
}
