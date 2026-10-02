<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CustomFieldSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function edit(): View
    {
        return view('admin.custom-fields.edit', [
            'definitions' => CustomFieldSchema::all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(CustomFieldSchema::adminValidationRules());

        CustomFieldSchema::saveAll($data['custom_field_definitions'] ?? []);

        return redirect()
            ->route('admin.custom-fields.edit')
            ->with('status', 'custom-fields-updated');
    }
}
