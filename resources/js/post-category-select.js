export function initPostCategorySelect() {
    const categorySelect = document.getElementById('post-category-select');
    if (!categorySelect) {
        return;
    }

    const magazineSelect = document.getElementById('post-magazine-select');
    const options = [...categorySelect.options];

    const sync = () => {
        const selectedMagazine = magazineSelect?.value ?? '';
        let hasVisibleSelection = false;

        options.forEach((option) => {
            if (! option.value) {
                option.hidden = false;
                option.disabled = false;

                if (option.selected) {
                    hasVisibleSelection = true;
                }

                return;
            }

            const magazineId = option.dataset.magazineId ?? '';
            const visible = selectedMagazine === ''
                ? magazineId === ''
                : magazineId === selectedMagazine;

            option.hidden = ! visible;
            option.disabled = ! visible;

            if (visible && option.selected) {
                hasVisibleSelection = true;
            }
        });

        if (! hasVisibleSelection) {
            categorySelect.value = '';
        }
    };

    magazineSelect?.addEventListener('change', sync);
    sync();
}
