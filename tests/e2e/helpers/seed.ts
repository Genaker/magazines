/** Canonical demo seed data (DemoContentSeeder). */
export const seedPosts = {
  whyWriting: {
    title: 'Why Writing in Public Matters',
    slug: 'why-writing-in-public-matters',
    author: 'demoauthor',
    path: '/@demoauthor/why-writing-in-public-matters',
    subtitle: 'Sharing ideas beyond the draft folder',
  },
  laravel: {
    title: 'Getting Started with Laravel',
    slug: 'getting-started-with-laravel',
    author: 'techwriter',
    path: '/@techwriter/getting-started-with-laravel',
  },
  draftLocalAi: {
    title: 'Draft: The Future of Local AI',
    slug: 'draft-the-future-of-local-ai',
    author: 'techwriter',
    path: '/@techwriter/draft-the-future-of-local-ai',
  },
  photoWalk: {
    title: 'Weekend Photo Walk: City Lights',
    slug: 'weekend-photo-walk-city-lights',
    author: 'cityreporter',
    path: '/@cityreporter/weekend-photo-walk-city-lights',
  },
} as const;

export const seedTags = {
  /** Post tags attach to the second "writing" row (slug writing-1) after seed dedupes. */
  writing: {
    slug: 'writing-1',
    path: '/tag/writing-1',
    heading: '#writing',
  },
} as const;

export const seedMagazine = {
  name: 'The Commons',
  slug: 'the-commons',
} as const;

/** Tenant demo seed (TenantDemoSeeder). */
export const tenantSeed = {
  host: 'tenant1.lvh.me',
  defaultHost: 'default.lvh.me',
  authorUsername: 'tenant1author',
  authorEmail: 'author@tenant1.test',
  adminEmail: 'admin@tenant1.test',
  magazineName: 'Tenant One Weekly',
  magazineSlug: 'tenant1-weekly',
  welcomePostTitle: 'Welcome to Tenant 1',
  welcomePostPath: '/@tenant1author/welcome-to-tenant-1',
  password: 'password',
} as const;
