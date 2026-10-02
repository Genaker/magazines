@php
    $aiConfig = [
        'modes' => config('ai-writing-tools.modes'),
        'tools' => config('ai-writing-tools.tools'),
    ];
@endphp
<script type="application/json" id="ai-writing-config">@json($aiConfig)</script>

<div id="ai-writing-tools" class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
    <div class="mb-3">
        <p class="text-sm font-medium text-gray-900">AI writing & grammar assistants</p>
        <p class="text-xs text-gray-600 mt-1 max-w-xl">
            Add optional instructions, pick a task and AI service, then copy your prompt and open the chat — paste the prompt into the input box.
        </p>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 mb-3">
        <div>
            <label for="ai-task-mode" class="block text-xs font-medium text-gray-700 mb-1">Task</label>
            <select id="ai-task-mode" class="w-full rounded-md border-gray-300 text-sm">
                @foreach (config('ai-writing-tools.modes') as $modeId => $mode)
                    <option value="{{ $modeId }}">{{ $mode['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="ai-service" class="block text-xs font-medium text-gray-700 mb-1">AI service</label>
            <select id="ai-service" class="w-full rounded-md border-gray-300 text-sm">
                @foreach (config('ai-writing-tools.tools') as $tool)
                    <option value="{{ $tool['id'] }}" data-url="{{ $tool['url'] }}">
                        {{ $tool['name'] }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mb-3">
        <label for="ai-custom-prompt" class="block text-xs font-medium text-gray-700 mb-1">
            Your instructions <span class="font-normal text-gray-500">(optional, prepended to the prompt)</span>
        </label>
        <textarea
            id="ai-custom-prompt"
            rows="2"
            class="w-full rounded-md border-gray-300 text-sm"
            placeholder="e.g. Keep it under 800 words, friendly tone for a local community newsletter…"
        ></textarea>
    </div>

    <div class="flex flex-wrap gap-2">
        <button
            type="button"
            id="open-ai-prompt"
            class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800"
            title="Paste with Cmd+V / Ctrl+V into the chat"
        >
            Copy & open
        </button>
        <button
            type="button"
            id="copy-ai-prompt"
            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100"
        >
            Copy prompt
        </button>
    </div>
</div>
