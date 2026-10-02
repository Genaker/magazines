<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Admin-defined custom field schemas stored in site_settings. */
class CustomFieldSchema
{
    public const CONTEXT_USER = 'user';

    public const CONTEXT_POST = 'post';

    public const CONTEXT_MAGAZINE = 'magazine';

    public const TYPE_TEXT = 'text';

    public const TYPE_TEXTAREA = 'textarea';

    public const TYPE_URL = 'url';

    public const TYPE_NUMBER = 'number';

    public const TYPE_CHECKBOX = 'checkbox';

    /** @return array<string, string> */
    public static function contextLabels(): array
    {
        return [
            self::CONTEXT_USER => 'User profiles',
            self::CONTEXT_POST => 'Posts',
            self::CONTEXT_MAGAZINE => 'Magazines',
        ];
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_TEXT => 'Text',
            self::TYPE_TEXTAREA => 'Long text',
            self::TYPE_URL => 'URL',
            self::TYPE_NUMBER => 'Number',
            self::TYPE_CHECKBOX => 'Checkbox',
        ];
    }

    /** @return list<array{key: string, label: string, type: string, help: string, required: bool}> */
    public static function forContext(string $context): array
    {
        $json = SiteSetting::getValue(self::settingKey($context));

        if ($json === null || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded)) {
            return [];
        }

        return self::normalize($decoded, $context, false);
    }

    /** @return array<string, list<array{key: string, label: string, type: string, help: string, required: bool}>> */
    public static function all(): array
    {
        $all = [];

        foreach (array_keys(self::contextLabels()) as $context) {
            $all[$context] = self::forContext($context);
        }

        return $all;
    }

    /**
     * @param  array<string, mixed>  $submitted
     * @return list<array{key: string, label: string, type: string, help: string, required: bool}>
     */
    public static function normalize(array $submitted, string $context, bool $strict = true): array
    {
        if (! array_key_exists($context, self::contextLabels())) {
            throw ValidationException::withMessages([
                'custom_field_definitions' => 'Unknown custom field context.',
            ]);
        }

        $definitions = [];
        $keys = [];

        foreach ($submitted as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = Str::lower(trim((string) ($row['key'] ?? '')));
            $label = trim((string) ($row['label'] ?? ''));
            $type = (string) ($row['type'] ?? self::TYPE_TEXT);
            $help = trim((string) ($row['help'] ?? ''));
            $required = filter_var($row['required'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if ($key === '' && $label === '') {
                continue;
            }

            if ($key === '' || $label === '') {
                if ($strict) {
                    throw ValidationException::withMessages([
                        "custom_field_definitions.{$context}.{$index}.key" => 'Each custom field needs a key and label.',
                    ]);
                }

                continue;
            }

            if (! preg_match('/^[a-z][a-z0-9_]*$/', $key)) {
                if ($strict) {
                    throw ValidationException::withMessages([
                        "custom_field_definitions.{$context}.{$index}.key" => 'Field keys must start with a letter and use lowercase letters, numbers, and underscores.',
                    ]);
                }

                continue;
            }

            if (isset($keys[$key])) {
                if ($strict) {
                    throw ValidationException::withMessages([
                        "custom_field_definitions.{$context}.{$index}.key" => "Duplicate field key \"{$key}\".",
                    ]);
                }

                continue;
            }

            if (! array_key_exists($type, self::typeLabels())) {
                $type = self::TYPE_TEXT;
            }

            $keys[$key] = true;
            $definitions[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'help' => $help,
                'required' => $required,
            ];
        }

        if (count($definitions) > 20) {
            throw ValidationException::withMessages([
                'custom_field_definitions' => 'At most 20 custom fields are allowed per context.',
            ]);
        }

        return $definitions;
    }

    /** @param  array<string, list<array<string, mixed>>>  $definitionsByContext */
    public static function saveAll(array $definitionsByContext): void
    {
        foreach (array_keys(self::contextLabels()) as $context) {
            $rows = $definitionsByContext[$context] ?? [];
            self::save($context, self::normalize($rows, $context));
        }
    }

    /** @param  list<array{key: string, label: string, type: string, help: string, required: bool}>  $definitions */
    public static function save(string $context, array $definitions): void
    {
        SiteSetting::setValue(
            self::settingKey($context),
            $definitions === [] ? null : json_encode($definitions, JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string, array<int|string, mixed>> */
    public static function adminValidationRules(): array
    {
        $rules = [
            'custom_field_definitions' => ['nullable', 'array'],
        ];

        foreach (array_keys(self::contextLabels()) as $context) {
            $rules["custom_field_definitions.{$context}"] = ['nullable', 'array', 'max:20'];
            $rules["custom_field_definitions.{$context}.*.key"] = ['nullable', 'string', 'max:50', 'regex:/^[a-z][a-z0-9_]*$/'];
            $rules["custom_field_definitions.{$context}.*.label"] = ['nullable', 'string', 'max:100'];
            $rules["custom_field_definitions.{$context}.*.type"] = ['nullable', 'string', Rule::in(array_keys(self::typeLabels()))];
            $rules["custom_field_definitions.{$context}.*.help"] = ['nullable', 'string', 'max:255'];
            $rules["custom_field_definitions.{$context}.*.required"] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    private static function settingKey(string $context): string
    {
        return 'custom_fields_'.$context;
    }
}
