import tinymce from 'tinymce/tinymce';

import 'tinymce/icons/default/icons.min.js';
import 'tinymce/themes/silver/theme.min.js';
import 'tinymce/models/dom/model.min.js';
import 'tinymce/skins/ui/oxide/skin.js';
import 'tinymce/skins/ui/oxide/content.js';
import 'tinymce/skins/content/default/content.js';

import 'tinymce/plugins/lists';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/code';
import 'tinymce/plugins/table';
import 'tinymce/plugins/media';
import 'tinymce/plugins/autoresize';

import { initTagInputs } from './tag-input';
import { initPostCategorySelect } from './post-category-select';

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;

const escapeHtml = (value) => value
    .replace(/&/g, '&amp;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

const normalizeEmbeddedMediaHtml = (html) => {
    if (!html || !/<(iframe|video|embed|object)\b/i.test(html)) {
        return html;
    }

    const template = document.createElement('template');
    template.innerHTML = html;

    template.content.querySelectorAll('iframe, video, embed, object').forEach((element) => {
        element.removeAttribute('width');
        element.removeAttribute('height');
        element.removeAttribute('style');
    });

    return template.innerHTML;
};

const responsiveIframeTemplate = (data) => {
    const allowFullscreen = data.allowfullscreen ? ' allowfullscreen' : '';
    const title = data.title ? ` title="${data.title}"` : '';

    return `<iframe src="${data.source}"${title} frameborder="0"${allowFullscreen}></iframe>`;
};

const responsiveVideoTemplate = (data) => {
    let html = `<video controls="controls"${data.poster ? ` poster="${data.poster}"` : ''}>`;
    html += `<source src="${data.source}"${data.sourcemime ? ` type="${data.sourcemime}"` : ''} />`;

    if (data.altsource) {
        html += `<source src="${data.altsource}"${data.altsourcemime ? ` type="${data.altsourcemime}"` : ''} />`;
    }

    html += '</video>';

    return html;
};

const editorContentStyle = [
    'body { font-family: Figtree, sans-serif; font-size: 16px; }',
    '.post-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 8px; margin: 16px 0; }',
    '.post-gallery a { display: block; border-radius: 4px; overflow: hidden; }',
    '.post-gallery img { width: 100%; height: 120px; object-fit: cover; display: block; }',
    'iframe, video { display: block; width: 100%; max-width: 100%; aspect-ratio: 16 / 9; height: auto; border: 0; border-radius: 8px; margin: 16px 0; }',
    'audio { display: block; width: 100%; margin: 16px 0; }',
].join(' ');

const buildGalleryHtml = (urls) => {
    const items = urls.map((url, index) => {
        const safeUrl = escapeHtml(url);
        const alt = escapeHtml(`Gallery image ${index + 1}`);

        return `<a href="${safeUrl}"><img src="${safeUrl}" alt="${alt}"></a>`;
    }).join('');

    return `<div class="post-gallery" data-component="gallery">${items}</div><p></p>`;
};

const csrfFetch = (url, options = {}) => {
    const headers = {
        'X-CSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers || {}),
    };

    return fetch(url, { ...options, headers });
};

const uploadEditorImage = async (file, mediaUploadUrl) => {
    const body = new FormData();
    body.append('file', file);

    const response = await csrfFetch(mediaUploadUrl, { method: 'POST', body });

    if (!response.ok) {
        throw new Error('upload failed');
    }

    const data = await response.json();

    return data.location;
};

const initCoverUpload = () => {
    const coverInput = document.getElementById('cover-image');
    const coverPreview = document.getElementById('cover-preview');
    const dropzone = document.getElementById('cover-dropzone');

    if (!coverInput || !coverPreview || !dropzone) {
        return;
    }

    const renderPreview = (src) => {
        coverPreview.innerHTML = `
            <div class="relative aspect-video max-w-xl overflow-hidden rounded-lg border border-gray-200">
                <img src="${src}" alt="Cover preview" class="w-full h-full object-cover">
            </div>
        `;
    };

    const handleFile = (file) => {
        if (!file || !file.type.startsWith('image/')) {
            return;
        }

        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        coverInput.files = dataTransfer.files;

        const reader = new FileReader();
        reader.onload = (event) => {
            renderPreview(event.target.result);
            document.getElementById('post-form')?.dispatchEvent(new CustomEvent('autosave-schedule'));
        };
        reader.readAsDataURL(file);
    };

    dropzone.addEventListener('click', () => coverInput.click());
    coverInput.addEventListener('change', () => handleFile(coverInput.files?.[0]));

    dropzone.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('border-gray-500');
    });

    dropzone.addEventListener('dragleave', () => {
        dropzone.classList.remove('border-gray-500');
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('border-gray-500');
        handleFile(event.dataTransfer.files?.[0]);
    });

};

const getAiWritingConfig = () => {
    const element = document.getElementById('ai-writing-config');

    if (!element?.textContent) {
        return null;
    }

    try {
        return JSON.parse(element.textContent);
    } catch {
        return null;
    }
};

const getSelectedAiTool = () => {
    const select = document.getElementById('ai-service');
    const option = select?.selectedOptions?.[0];

    if (!option) {
        return null;
    }

    return {
        url: option.dataset.url,
    };
};

const buildAiWritingPrompt = (form, editor) => {
    const config = getAiWritingConfig();
    const mode = document.getElementById('ai-task-mode')?.value || 'writing';
    const template = config?.modes?.[mode]?.template;

    if (!template) {
        return '';
    }

    const title = form.querySelector('input[name="title"]')?.value?.trim() || '(untitled)';
    const subtitle = form.querySelector('input[name="subtitle"]')?.value?.trim() || '(none)';
    const categorySelect = form.querySelector('select[name="category_id"]');
    const category = categorySelect?.selectedOptions?.[0]?.textContent?.trim() || '(none)';
    const tags = form.querySelector('input[name="tags"]')?.value?.trim() || '(none)';

    let body = editor.getContent({ format: 'text' }).trim() || '(empty)';
    const maxBodyLength = 12000;

    if (body.length > maxBodyLength) {
        body = `${body.slice(0, maxBodyLength)}\n\n[Draft truncated — paste remaining text manually if needed]`;
    }

    let prompt = template
        .replaceAll(':title', title)
        .replaceAll(':subtitle', subtitle)
        .replaceAll(':category', category)
        .replaceAll(':tags', tags)
        .replaceAll(':body', body);

    const customPrefix = document.getElementById('ai-custom-prompt')?.value?.trim();

    if (customPrefix) {
        prompt = `${customPrefix}\n\n${prompt}`;
    }

    return prompt;
};

const copyPromptToClipboard = async (button, prompt) => {
    try {
        await navigator.clipboard.writeText(prompt);
        const original = button.textContent;
        button.textContent = 'Copied';
        setTimeout(() => {
            button.textContent = original;
        }, 1500);
    } catch {
        window.prompt('Copy this prompt:', prompt);
    }
};

const initAiWritingTools = (form, editor) => {
    const container = document.getElementById('ai-writing-tools');
    const openButton = document.getElementById('open-ai-prompt');
    const copyButton = document.getElementById('copy-ai-prompt');

    if (!container) {
        return;
    }

    openButton?.addEventListener('click', async () => {
        const tool = getSelectedAiTool();

        if (!tool?.url) {
            return;
        }

        const prompt = buildAiWritingPrompt(form, editor);
        await copyPromptToClipboard(openButton, prompt);
        window.open(tool.url, '_blank', 'noopener,noreferrer');
    });

    copyButton?.addEventListener('click', async () => {
        const prompt = buildAiWritingPrompt(form, editor);
        await copyPromptToClipboard(copyButton, prompt);
    });
};

const initShareCopy = () => {
    const button = document.getElementById('copy-share-url');
    const input = document.getElementById('share-url');

    if (!button || !input) {
        return;
    }

    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(input.value);
        button.textContent = 'Copied';
        setTimeout(() => {
            button.textContent = 'Copy';
        }, 1500);
    });
};

let clientRevision = 0;

const buildAutosaveBody = (form, editor) => {
    const coverInput = document.getElementById('cover-image');
    const hasCover = coverInput?.files?.length > 0;

    if (hasCover) {
        const formData = new FormData();
        const postId = form.querySelector('input[name="post_id"]')?.value;

        if (postId) {
            formData.append('post_id', postId);
        }

        formData.append('title', form.querySelector('input[name="title"]')?.value || '');
        formData.append('subtitle', form.querySelector('input[name="subtitle"]')?.value || '');
        formData.append('body', editor.getContent());
        formData.append('category_id', form.querySelector('select[name="category_id"]')?.value || '');
        formData.append('tags', form.querySelector('input[name="tags"]')?.value || '');
        formData.append('client_revision', String(clientRevision));
        formData.append('cover_image', coverInput.files[0]);

        return { body: formData, json: false };
    }

    return {
        json: true,
        body: JSON.stringify({
            post_id: form.querySelector('input[name="post_id"]')?.value || null,
            title: form.querySelector('input[name="title"]')?.value || '',
            subtitle: form.querySelector('input[name="subtitle"]')?.value || '',
            body: editor.getContent(),
            category_id: form.querySelector('select[name="category_id"]')?.value || '',
            tags: form.querySelector('input[name="tags"]')?.value || '',
            client_revision: clientRevision,
        }),
    };
};

const initAutosave = (form, editor, statusEl) => {
    const autosaveUrl = form.dataset.autosaveUrl;

    if (!autosaveUrl || !statusEl) {
        return null;
    }

    let timer = null;
    let saving = false;

    const save = async () => {
        if (saving) {
            return;
        }

        saving = true;
        statusEl.textContent = 'Saving…';

        try {
            const payload = buildAutosaveBody(form, editor);
            const response = await csrfFetch(autosaveUrl, {
                method: 'POST',
                headers: payload.json ? { 'Content-Type': 'application/json' } : {},
                body: payload.body,
            });

            if (response.status === 409) {
                statusEl.textContent = 'Newer version saved elsewhere';
                return;
            }

            if (!response.ok) {
                statusEl.textContent = 'Autosave failed';
                return;
            }

            const data = await response.json();
            const postIdInput = form.querySelector('input[name="post_id"]');

            if (postIdInput && data.post_id) {
                postIdInput.value = data.post_id;
            }

            if (typeof data.revision === 'number') {
                clientRevision = data.revision;
            }

            if (form.dataset.updateUrl && data.post_id) {
                form.action = form.dataset.updateUrl.replace('__POST__', data.post_id);
                form.querySelector('input[name="_method"]')?.remove();

                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'PUT';
                form.appendChild(methodInput);
            }

            if (data.cover_url) {
                const coverPreview = document.getElementById('cover-preview');
                if (coverPreview) {
                    coverPreview.innerHTML = `
                        <div class="relative aspect-video max-w-xl overflow-hidden rounded-lg border border-gray-200">
                            <img src="${data.cover_url}" alt="Cover preview" class="w-full h-full object-cover">
                        </div>
                    `;
                }
            }

            statusEl.textContent = `Saved ${new Date(data.saved_at).toLocaleTimeString()}`;
        } catch {
            statusEl.textContent = 'Autosave failed';
        } finally {
            saving = false;
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(save, 2000);
    };

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', schedule);
        field.addEventListener('change', schedule);
    });

    editor.on('change', schedule);
    setInterval(save, 15000);

    return save;
};

document.addEventListener('DOMContentLoaded', () => {
    const textarea = document.getElementById('editor-textarea');
    const form = document.getElementById('post-form')
        ?? document.getElementById('gallery-form')
        ?? document.getElementById('video-form');
    const statusEl = document.getElementById('autosave-status');

    if (! textarea || ! form) {
        return;
    }

    initCoverUpload();
    initShareCopy();

    const mediaUploadUrl = form.dataset.mediaUploadUrl;

    tinymce.init({
        target: textarea,
        license_key: 'gpl',
        skin_url: 'default',
        content_css: 'default',
        height: 420,
        menubar: false,
        plugins: 'lists link image media code table autoresize',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image media gallery | blockquote code | removeformat',
        media_live_embeds: true,
        media_dimensions: false,
        iframe_template_callback: responsiveIframeTemplate,
        video_template_callback: responsiveVideoTemplate,
        extended_valid_elements: 'div[class|data-component],a[href],img[src|alt],iframe[src|frameborder|allow|allowfullscreen|title|class],video[controls|poster|src|class|preload],audio[controls|src|class|preload],source[src|type],object[data|type|class],embed[src|type|class]',
        content_style: editorContentStyle,
        automatic_uploads: true,
        images_upload_handler: mediaUploadUrl
            ? (blobInfo) => new Promise((resolve, reject) => {
                const body = new FormData();
                body.append('file', blobInfo.blob(), blobInfo.filename());

                csrfFetch(mediaUploadUrl, { method: 'POST', body })
                    .then((response) => (response.ok ? response.json() : Promise.reject()))
                    .then((data) => resolve(data.location))
                    .catch(() => reject('Image upload failed'));
            })
            : undefined,
        setup: (editor) => {
            editor.ui.registry.addButton('gallery', {
                text: 'Gallery',
                tooltip: 'Insert image gallery',
                onAction: () => {
                    if (!mediaUploadUrl) {
                        return;
                    }

                    const input = document.createElement('input');
                    input.type = 'file';
                    input.accept = 'image/*';
                    input.multiple = true;
                    input.onchange = async () => {
                        const files = [...(input.files || [])];

                        if (files.length < 2) {
                            editor.windowManager.alert('Select at least 2 images for a gallery.');
                            return;
                        }

                        editor.setProgressState(true);

                        try {
                            const urls = [];

                            for (const file of files) {
                                urls.push(await uploadEditorImage(file, mediaUploadUrl));
                            }

                            editor.insertContent(buildGalleryHtml(urls));
                            editor.fire('change');
                        } catch {
                            editor.windowManager.alert('Gallery upload failed. Try again.');
                        } finally {
                            editor.setProgressState(false);
                        }
                    };
                    input.click();
                },
            });

            editor.on('init', () => {
                initAiWritingTools(form, editor);
                const schedule = initAutosave(form, editor, statusEl);
                form.addEventListener('autosave-schedule', () => schedule?.());
            });

            editor.on('BeforeSetContent', (event) => {
                event.content = normalizeEmbeddedMediaHtml(event.content);
            });

            editor.on('PostProcess', (event) => {
                if (event.get) {
                    event.content = normalizeEmbeddedMediaHtml(event.content);
                }
            });
        },
    });

    form.addEventListener('submit', (event) => {
        const editor = tinymce.get('editor-textarea');

        if (!editor) {
            return;
        }

        editor.save();

        const html = editor.getContent();
        const text = editor.getContent({ format: 'text' }).trim();
        const hasGallery = html.includes('data-component="gallery"');
        const hasEmbeddedMedia = /<iframe|<video|<audio/i.test(html);

        if (text === '' && !hasGallery && !hasEmbeddedMedia) {
            event.preventDefault();
            alert('Please write something before publishing.');
        }
    });

    initTagInputs();
    initPostCategorySelect();
});
