<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RegistrationInvite;
use App\Models\SiteSetting;
use App\Support\AdminPath;
use App\Support\AuthorSubdomain;
use App\Support\Features;
use App\Support\HomeLayout;
use App\Support\MediaSettings;
use App\Support\RegistrationGate;
use App\Support\SiteLocale;
use App\Support\SubdomainSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public function edit(): View
    {
        return view('admin.settings.edit', [
            'siteName' => SiteSetting::getValue('site_name', config('app.name')),
            'siteTagline' => SiteSetting::getValue('site_tagline', ''),
            'featureDefinitions' => Features::definitions(),
            'features' => Features::all(),
            'commentsUseDisqus' => filter_var(SiteSetting::getValue('comments_use_disqus', '0'), FILTER_VALIDATE_BOOLEAN),
            'disqusShortname' => SiteSetting::getValue('disqus_shortname', ''),
            'registrationInvites' => RegistrationInvite::query()
                ->with('creator')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get(),
            'closedRegistration' => RegistrationGate::requiresInviteCode(),
            'homeLayout' => HomeLayout::current(),
            'homeLayoutOptions' => HomeLayout::options(),
            'localeOptions' => SiteLocale::labels(),
            'enabledLocales' => SiteLocale::enabled(),
            'defaultLocale' => SiteLocale::default(),
            'postMedia' => MediaSettings::forPreset(MediaSettings::PRESET_POST),
            'galleryMedia' => MediaSettings::forPreset(MediaSettings::PRESET_GALLERY),
            'authorSubdomainStoredHost' => SiteSetting::getValue('author_subdomain_base_host', ''),
            'authorSubdomainEffectiveHost' => AuthorSubdomain::baseHost(),
            'authorSubdomainRedirect' => AuthorSubdomain::redirectEnabled(),
            'adminPath' => AdminPath::prefix(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $availableLocales = SiteLocale::available();

        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:100'],
            'site_tagline' => ['nullable', 'string', 'max:255'],
            'features' => ['nullable', 'array'],
            'features.*' => ['boolean'],
            'comments_use_disqus' => ['sometimes', 'boolean'],
            'disqus_shortname' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::requiredIf($request->boolean('comments_use_disqus')),
            ],
            'home_layout' => ['required', Rule::enum(HomeLayout::class)],
            'locales_enabled' => ['required', 'array', 'min:1'],
            'locales_enabled.*' => ['string', Rule::in($availableLocales)],
            'locale_default' => ['required', 'string', Rule::in($availableLocales)],
            'author_subdomain_base_host' => ['nullable', 'string', 'max:253', 'regex:/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?$/i'],
            'author_subdomain_redirect' => ['sometimes', 'boolean'],
            ...MediaSettings::validationRules(MediaSettings::PRESET_POST),
            ...MediaSettings::validationRules(MediaSettings::PRESET_GALLERY),
        ]);

        $enabledLocales = array_values(array_unique($data['locales_enabled']));

        if (! in_array($data['locale_default'], $enabledLocales, true)) {
            return redirect()
                ->route('admin.settings.edit')
                ->withErrors(['locale_default' => 'The default language must be one of the enabled languages.'])
                ->withInput();
        }

        foreach ([MediaSettings::PRESET_POST, MediaSettings::PRESET_GALLERY] as $preset) {
            $sm = (int) $data["media_{$preset}_width_sm"];
            $md = (int) $data["media_{$preset}_width_md"];
            $lg = (int) $data["media_{$preset}_width_lg"];

            if (! MediaSettings::widthsAreOrdered($sm, $md, $lg)) {
                return redirect()
                    ->route('admin.settings.edit')
                    ->withErrors([
                        "media_{$preset}_width_md" => 'Small, medium, and large widths must be in ascending order.',
                    ])
                    ->withInput();
            }
        }

        SiteSetting::setValue('site_name', $data['site_name']);
        SiteSetting::setValue('site_tagline', $data['site_tagline'] ?? '');
        HomeLayout::set(HomeLayout::from($data['home_layout']));
        SiteLocale::setEnabled($enabledLocales);
        SiteLocale::setDefault($data['locale_default']);
        SiteSetting::setValue('comments_use_disqus', $request->boolean('comments_use_disqus') ? '1' : '0');
        SiteSetting::setValue('disqus_shortname', $data['disqus_shortname'] ?? '');
        MediaSettings::saveFromValidated(MediaSettings::PRESET_POST, $data, $request->boolean('media_post_do_not_resize'));
        MediaSettings::saveFromValidated(MediaSettings::PRESET_GALLERY, $data, $request->boolean('media_gallery_do_not_resize'));

        if ($request->collect()->keys()->contains('author_subdomain_base_host')) {
            AuthorSubdomain::setBaseHost($request->input('author_subdomain_base_host') ?: null);
            SubdomainSession::configure();
        }

        AuthorSubdomain::setRedirect($request->boolean('author_subdomain_redirect'));

        $submittedFeatures = $data['features'] ?? [];

        foreach (array_keys(Features::definitions()) as $feature) {
            if (array_key_exists($feature, $submittedFeatures)) {
                Features::set($feature, (bool) $submittedFeatures[$feature]);
            }
        }

        SubdomainSession::configure();

        return redirect()->route('admin.settings.edit')->with('status', 'settings-updated');
    }
}
