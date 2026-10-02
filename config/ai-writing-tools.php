<?php

return [
    'modes' => [
        'writing' => [
            'label' => 'Assist writing',
            'template' => <<<'TEXT'
Help me write or improve an article for Magazines — a platform for local communities, publishers, and bloggers.

Title: :title
Subtitle: :subtitle
Category: :category
Tags: :tags

Current draft:
:body

Please improve clarity, structure, and flow. Suggest a stronger opening and ending. Return polished content I can paste into my editor (HTML paragraphs are fine).
TEXT,
        ],
        'grammar' => [
            'label' => 'Check grammar',
            'template' => <<<'TEXT'
Please check the grammar, spelling, and punctuation of this article for Magazines. Fix errors and list what you changed. Keep the meaning and tone; do not rewrite unless needed for correctness.

Title: :title
Subtitle: :subtitle
Category: :category
Tags: :tags

Text to check:
:body
TEXT,
        ],
    ],

    /*
     * Opens the service homepage; the prompt is copied to the clipboard for paste.
     */
    'tools' => [
        ['id' => 'chatgpt', 'name' => 'ChatGPT', 'url' => 'https://chatgpt.com/'],
        ['id' => 'claude', 'name' => 'Claude', 'url' => 'https://claude.ai/new'],
        ['id' => 'gemini', 'name' => 'Gemini', 'url' => 'https://gemini.google.com/app'],
        ['id' => 'grok', 'name' => 'Grok', 'url' => 'https://grok.com/'],
        ['id' => 'perplexity', 'name' => 'Perplexity', 'url' => 'https://www.perplexity.ai/'],
        ['id' => 'copilot', 'name' => 'Copilot', 'url' => 'https://copilot.microsoft.com/'],
        ['id' => 'mistral', 'name' => 'Mistral', 'url' => 'https://chat.mistral.ai/chat'],
        ['id' => 'deepseek', 'name' => 'DeepSeek', 'url' => 'https://chat.deepseek.com/'],
    ],
];
