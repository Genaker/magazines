<?php

return [
    'registration' => [
        'label' => 'Open registration',
        'description' => 'Anyone can sign up without an invite code.',
        'default' => true,
    ],
    'registration_invites' => [
        'label' => 'Registration invites',
        'description' => 'When open registration is off, allow sign-up with invite codes (closed communities).',
        'default' => true,
    ],
    'magic_link_login' => [
        'label' => 'Magic link login',
        'description' => 'Passwordless email sign-in link and code.',
        'default' => true,
    ],
    'magazines' => [
        'label' => 'Magazines',
        'description' => 'Magazine communities, submissions, and follows.',
        'default' => true,
    ],
    'comments' => [
        'label' => 'Comments',
        'description' => 'Discussion on posts (built-in or Disqus — configure in Comments settings below).',
        'default' => true,
    ],
    'category_requests' => [
        'label' => 'Category requests',
        'description' => 'Let users request new categories.',
        'default' => true,
    ],
    'reading_lists' => [
        'label' => 'Reading lists',
        'description' => 'Save posts to lists and bookmarks.',
        'default' => true,
    ],
    'user_reports' => [
        'label' => 'User reports',
        'description' => 'Report authors and admin review queue.',
        'default' => true,
    ],
    'ai_writing_tools' => [
        'label' => 'AI writing tools',
        'description' => 'Editor assist, grammar check, and AI prompts.',
        'default' => true,
    ],
    'author_subdomains' => [
        'label' => 'Author subdomain pages',
        'description' => 'Serve each author at {username}.your-domain (e.g. jane.example.com) with optional redirects from /@username.',
        'default' => false,
    ],
    'magazine_subdomains' => [
        'label' => 'Magazine subdomain pages',
        'description' => 'Serve each magazine at {slug}.your-domain (e.g. the-commons.example.com). Approved magazine posts use the magazine subdomain, not the author subdomain.',
        'default' => false,
    ],
    'multi_tenancy' => [
        'label' => 'Multi-tenancy',
        'description' => 'Enable domain-based tenants, tenant admin scopes, and platform tenant management.',
        'default' => false,
    ],
];
