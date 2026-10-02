<div id="reading-list-picker" class="relative">
    <button
        id="bookmark-btn"
        type="button"
        data-bookmarked="{{ $bookmarked ? '1' : '0' }}"
        aria-expanded="false"
        aria-controls="list-picker-menu"
        class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-900 hover:text-gray-700"
    >
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" class="w-5 h-5" aria-hidden="true">
            <path
                id="bookmark-icon-outline"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
                d="M5 3h14a1 1 0 0 1 1 1v17l-8-4.5L4 21V4a1 1 0 0 1 1-1z"
                @class(['hidden' => $bookmarked])
            />
            <path
                id="bookmark-icon-filled"
                fill="currentColor"
                d="M5 3h14a1 1 0 0 1 1 1v17l-8-4.5L4 21V4a1 1 0 0 1 1-1z"
                @class(['hidden' => ! $bookmarked])
            />
        </svg>
        <span id="bookmark-label">{{ $bookmarked ? 'Saved' : 'Save' }}</span>
    </button>

    <div
        id="list-picker-menu"
        class="hidden absolute left-0 top-full z-30 mt-2 w-72 rounded-lg border border-gray-200 bg-white shadow-xl"
        role="dialog"
        aria-label="Save to list"
    >
        <div class="p-3">
            <div id="list-picker-items" class="space-y-1 max-h-56 overflow-y-auto">
                @forelse ($readingLists as $list)
                    <label class="flex items-center gap-3 rounded-md px-2 py-2 hover:bg-gray-50 cursor-pointer">
                        <span class="relative flex shrink-0">
                            <input
                                type="checkbox"
                                class="list-picker-checkbox peer sr-only"
                                data-list-id="{{ $list->id }}"
                                data-default="{{ $list->is_default ? '1' : '0' }}"
                                data-private="{{ $list->is_private ? '1' : '0' }}"
                                @checked($savedListIds->contains($list->id))
                            >
                            <span class="flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-white peer-checked:border-gray-900 peer-checked:bg-gray-900 [&>svg]:hidden peer-checked:[&>svg]:block">
                                <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" class="h-2.5 w-2.5 fill-white">
                                    <path d="m0 6.313 3.704 3.705.904.904.66-1.095 5.296-8.795L8.85 0 3.554 8.795l1.563-.191-3.704-3.705z"></path>
                                </svg>
                            </span>
                        </span>
                        <span class="flex-1 text-sm text-gray-900">{{ $list->name }}</span>
                        @if ($list->is_private)
                            <svg xmlns="http://www.w3.org/2000/svg" width="10" height="13" viewBox="0 0 10 13" class="shrink-0 text-gray-400" aria-label="Private list">
                                <path fill="currentColor" fill-rule="evenodd" d="M2.727 3.082C2.727 1.905 3.74.935 5 .935s2.273.973 2.273 2.147v2.436H2.727zM8.19 5.518h-.007V3.082C8.182 1.378 6.747 0 5 0 3.252 0 1.818 1.373 1.818 3.082v2.436h-.007c-.48.002-.941.2-1.28.55S0 6.892 0 7.387v3.744c0 .246.045.489.136.715.09.227.224.433.392.607s.368.311.588.405.457.142.695.142h6.378c.48-.002.941-.2 1.28-.55s.53-.824.531-1.319V7.387c0-.246-.045-.489-.136-.715a1.9 1.9 0 0 0-.392-.607 1.8 1.8 0 0 0-.588-.405 1.8 1.8 0 0 0-.695-.142" clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </label>
                @empty
                    <p id="list-picker-empty" class="px-2 py-2 text-sm text-gray-500">No lists yet.</p>
                @endforelse
            </div>
        </div>
        <div class="border-t border-gray-100 px-3 py-2">
            <button
                id="create-list-btn"
                type="button"
                class="text-sm font-medium text-gray-900 hover:text-gray-700"
            >
                Create new list
            </button>
        </div>
    </div>
</div>

<div
    id="create-list-modal"
    class="hidden fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
    role="dialog"
    aria-modal="true"
    aria-labelledby="create-list-modal-title"
>
    <div id="create-list-modal-backdrop" class="fixed inset-0 bg-gray-500/75"></div>
    <div class="relative mx-auto mt-16 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 id="create-list-modal-title" class="text-xl font-bold text-gray-900 mb-4">Create new list</h2>

        <form id="create-list-form" class="space-y-4">
            <div>
                <label for="create-list-name" class="sr-only">List name</label>
                <input
                    id="create-list-name"
                    type="text"
                    name="name"
                    maxlength="60"
                    required
                    placeholder="Give it a name"
                    class="w-full rounded-md border-gray-300 shadow-sm"
                >
                <p class="mt-1 text-right text-xs text-gray-500"><span id="create-list-name-count">0</span>/60</p>
            </div>

            <div>
                <label for="create-list-description" class="sr-only">Description</label>
                <textarea
                    id="create-list-description"
                    name="description"
                    maxlength="280"
                    rows="2"
                    placeholder="Description"
                    class="w-full rounded-md border-gray-300 shadow-sm"
                ></textarea>
                <p class="mt-1 text-right text-xs text-gray-500"><span id="create-list-description-count">0</span>/280</p>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <span class="relative flex shrink-0">
                    <input id="create-list-private" type="checkbox" name="is_private" class="peer sr-only">
                    <span class="flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-white peer-checked:border-gray-900 peer-checked:bg-gray-900 [&>svg]:hidden peer-checked:[&>svg]:block">
                        <svg xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 11 11" class="h-2.5 w-2.5 fill-white">
                            <path d="m0 6.313 3.704 3.705.904.904.66-1.095 5.296-8.795L8.85 0 3.554 8.795l1.563-.191-3.704-3.705z"></path>
                        </svg>
                    </span>
                </span>
                <span class="text-sm text-gray-900">Make it private</span>
            </label>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button id="create-list-cancel" type="button" class="text-sm font-medium text-gray-700 hover:text-gray-900">
                    Cancel
                </button>
                <button
                    id="create-list-submit"
                    type="submit"
                    disabled
                    class="rounded-full bg-gray-900 px-5 py-2 text-sm font-medium text-white disabled:cursor-not-allowed disabled:bg-gray-300"
                >
                    Create
                </button>
            </div>
        </form>
    </div>
</div>
