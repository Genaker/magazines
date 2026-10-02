<?php

namespace App\Support;

use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

/** Collect and validate custom field values against admin-defined schemas. */
class CustomFields
{
    /**
     * @param  array<string, mixed>|null  $input
     * @return array<string, mixed>|null
     */
    public static function collect(?array $input, string $context): ?array
    {
        $definitions = CustomFieldSchema::forContext($context);

        if ($definitions === []) {
            return null;
        }

        $input = $input ?? [];
        $values = [];

        foreach ($definitions as $definition) {
            $key = $definition['key'];
            $raw = Arr::get($input, $key);

            if ($definition['type'] === CustomFieldSchema::TYPE_CHECKBOX) {
                $values[$key] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);

                continue;
            }

            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                continue;
            }

            if ($definition['type'] === CustomFieldSchema::TYPE_NUMBER) {
                $values[$key] = is_numeric($raw) ? 0 + $raw : $raw;

                continue;
            }

            $values[$key] = is_string($raw) ? trim($raw) : $raw;
        }

        return $values === [] ? null : $values;
    }

    /** @return array<string, array<int|string, mixed>> */
    public static function validationRules(string $context): array
    {
        $rules = [
            'custom_fields' => ['nullable', 'array'],
        ];

        foreach (CustomFieldSchema::forContext($context) as $definition) {
            $field = 'custom_fields.'.$definition['key'];
            $required = $definition['required'] ? ['required'] : ['nullable'];

            $rules[$field] = match ($definition['type']) {
                CustomFieldSchema::TYPE_TEXTAREA => [...$required, 'string', 'max:5000'],
                CustomFieldSchema::TYPE_URL => [...$required, 'string', 'url', 'max:255'],
                CustomFieldSchema::TYPE_NUMBER => [...$required, 'numeric'],
                CustomFieldSchema::TYPE_CHECKBOX => ['sometimes', 'boolean'],
                default => [...$required, 'string', 'max:1000'],
            };
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>|null  $stored
     * @return list<array{label: string, value: string, type: string}>
     */
    public static function labeledValues(?array $stored, string $context): array
    {
        if ($stored === null || $stored === []) {
            return [];
        }

        $labeled = [];

        foreach (CustomFieldSchema::forContext($context) as $definition) {
            if (! array_key_exists($definition['key'], $stored)) {
                continue;
            }

            $value = $stored[$definition['key']];

            if ($definition['type'] === CustomFieldSchema::TYPE_CHECKBOX) {
                if (! filter_var($value, FILTER_VALIDATE_BOOLEAN)) {
                    continue;
                }

                $labeled[] = [
                    'label' => $definition['label'],
                    'value' => 'Yes',
                    'type' => $definition['type'],
                ];

                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $labeled[] = [
                'label' => $definition['label'],
                'value' => (string) $value,
                'type' => $definition['type'],
            ];
        }

        return $labeled;
    }

    /**
     * @return array<string, mixed>|null
     *
     * @throws ValidationException
     *
     * @deprecated Use collect() with admin-defined fields.
     */
    public static function parse(?string $json, string $field = 'custom_fields'): ?array
    {
        if ($json === null || trim($json) === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                $field => 'Custom fields must be valid JSON object.',
            ]);
        }

        if (Arr::isList($decoded)) {
            throw ValidationException::withMessages([
                $field => 'Custom fields must be a JSON object, not an array.',
            ]);
        }

        return $decoded;
    }
}
