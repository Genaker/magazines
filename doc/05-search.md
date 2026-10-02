# Search

## Overview

Global search finds **published posts**, **authors**, and **magazines** by keyword. Available from the navigation bar on every page.

| | |
|---|---|
| **Route** | `GET /search?q={query}` |
| **Who** | Everyone |

## Drivers

| `SEARCH_DRIVER` | Behavior |
|-----------------|----------|
| `database` (default in `.env`) | **MySQL FULLTEXT** or **PostgreSQL** (`tsvector` or optional **pg_search** BM25) |
| `postgres` | PostgreSQL stack: **pg_search** BM25 + optional **pgvector** hybrid |
| `redis` | **RediSearch** on Redis Stack — full-text + optional vector hybrid |

When `redis` is set but RediSearch is unavailable, the app falls back to the database driver.

The database driver uses **MySQL FULLTEXT** or **PostgreSQL `tsvector`** indexes (migration `V00036_add_fulltext_search_indexes`). Requires MySQL, MariaDB, or PostgreSQL — other drivers throw at query time. PHPUnit uses SQL `LIKE` only inside open MySQL/MariaDB transactions because InnoDB FULLTEXT does not index uncommitted rows.

Docker Compose sets `SEARCH_DRIVER=redis` and uses **Redis Stack**. The optional **postgres** service is a **custom Postgres 16 image** (`docker/postgres/Dockerfile`: official `postgres:16-bookworm` + **pg_search** + **pgvector** only). PHPUnit and `composer test:postgres` use this image by default.

**ParadeDB** (full bundle with PostGIS, etc.) is supported as an drop-in alternative:

```bash
POSTGRES_IMAGE=paradedb/paradedb:0.24.0-pg16 docker compose up -d postgres --no-build --pull always
```

Recreate the `postgres_data` volume when switching Postgres images.

## PostgreSQL pg_search (BM25)

[pg_search](https://docs.paradedb.com/) (ParadeDB) adds Lucene-style **BM25 relevance scoring** inside PostgreSQL — field boosts work like Elasticsearch.

```env
DB_CONNECTION=pgsql
SEARCH_DRIVER=postgres          # or database with SEARCH_PG_SEARCH_ENABLED=true
SEARCH_PG_SEARCH_ENABLED=true
SEARCH_PG_SEARCH_MATCH=any      # any (|||) or all (&&&)
```

| Env | Default | Purpose |
|-----|---------|---------|
| `SEARCH_PG_SEARCH_ENABLED` | `false` | Use BM25 indexes instead of `tsvector` |
| `SEARCH_PG_SEARCH_MATCH` | `any` | Token match: any term (`|||`) or all terms (`&&&`) |
| `SEARCH_PG_SEARCH_POSTS_INDEX` | `posts_bm25_idx` | BM25 index on `posts` |
| `SEARCH_PG_SEARCH_AUTHORS_INDEX` | `author_aliases_bm25_idx` | BM25 index on `author_aliases` |
| `SEARCH_PG_SEARCH_MAGAZINES_INDEX` | `magazines_bm25_idx` | BM25 index on `magazines` |

BM25 indexes are created by migration and `php artisan search:ensure-indexes`. They update automatically when rows change — no manual reindex for full-text.

Field weights reuse the same env vars as RediSearch: `SEARCH_WEIGHT_TITLE`, `SEARCH_WEIGHT_SUBTITLE`, etc.

## PostgreSQL pgvector (optional)

When embeddings are enabled, store vectors in `posts.search_embedding` and run hybrid search (BM25 + KNN):

```env
SEARCH_PGVECTOR_ENABLED=true
SEARCH_EMBEDDING_PROVIDER=gemini
SEARCH_SEMANTIC_ENABLED=true
```

Run `php artisan search:reindex` after enabling semantic search to backfill embeddings.

## Semantic search (Redis or PostgreSQL)

Set an embedding provider and API key:

```env
SEARCH_DRIVER=redis
SEARCH_EMBEDDING_PROVIDER=gemini   # or openai
GEMINI_API_KEY=your-key
SEARCH_SEMANTIC_ENABLED=true
SEARCH_EMBEDDING_DIMENSIONS=768
```

| Provider | Config |
|----------|--------|
| **Gemini** | `GEMINI_API_KEY`, `GEMINI_EMBEDDING_MODEL` (default `text-embedding-004`) |
| **OpenAI** | `OPENAI_API_KEY`, `OPENAI_EMBEDDING_MODEL`, optional `OPENAI_API_BASE` for compatible gateways |

Without a provider (`SEARCH_EMBEDDING_PROVIDER=none`), search uses full-text only. Related posts on story pages use shared **tags/categories**.

## Index maintenance

Posts are indexed on publish/update via `IndexPostForSearch` when `SEARCH_DRIVER=redis` or `postgres` (pgvector embeddings only).

```bash
php artisan search:ensure-indexes
php artisan search:reindex
```

Run `search:reindex` after enabling semantic search or changing embedding dimensions (Redis or pgvector).

## Related stories

Published post pages show **Related stories** (`RecommendationService`): vector similarity when embeddings are enabled, otherwise tag/category overlap.

## Field configuration

Configure which post fields feed full-text vs embedding indexes (`config/search.php`):

| Env | Default | Purpose |
|-----|---------|---------|
| `SEARCH_FULLTEXT_FIELDS` | `title,subtitle,body,tags,author_name,category_name` | Full-text / BM25 fields |
| `SEARCH_EMBEDDING_FIELDS` | `title,subtitle,body,tags` | Text sent to embedding API |
| `SEARCH_POST_BODY_MAX_WORDS` | `100` | Default body truncation for both |
| `SEARCH_FULLTEXT_BODY_MAX_WORDS` | falls back to above | Full-text body limit (`0` = full post) |
| `SEARCH_EMBEDDING_BODY_MAX_WORDS` | falls back to above | Embedding body limit (`0` = full post) |

RediSearch / BM25 field weights: `SEARCH_WEIGHT_TITLE`, `SEARCH_WEIGHT_SUBTITLE`, `SEARCH_WEIGHT_BODY`, `SEARCH_WEIGHT_AUTHOR`, `SEARCH_WEIGHT_CATEGORY`.

## Text preprocessing

Before indexing or querying, text is normalized to save tokens and improve relevance:

- HTML stripped (`SEARCH_STRIP_HTML`, default on)
- Whitespace collapsed
- Optional **stop-word removal** (`config/search-stop-words-en.php`) — on for embedding input and search queries by default; off for full-text index by default
- Embeddings lowercased (`SEARCH_LOWERCASE_EMBEDDING`)
- Short tokens dropped (`SEARCH_MIN_TOKEN_LENGTH`, default `2`)

Env flags: `SEARCH_REMOVE_STOP_WORDS_EMBEDDING`, `SEARCH_REMOVE_STOP_WORDS_FULLTEXT`, `SEARCH_REMOVE_STOP_WORDS_QUERY`.

## What is searched

### Posts

- Configurable fields (see above); body truncated to N words by default
- Only **published**, **feed-visible** posts

### Authors

- Display name, username, bio

Banned users are excluded from author results.

## Integration tests

Redis Search tests live in `RedisSearchIntegrationTest` (`@group redis-search`). They skip when:

- the **phpredis** extension is not loaded (default on macOS PHP), or
- Redis does not have the **RediSearch** module (plain `redis:7-alpine` is not enough — use **Redis Stack**).

PostgreSQL pg_search tests live in `PostgresPgSearchTest` (`@group postgres-search`). They skip when:

- the database is not PostgreSQL, or
- **pg_search** is not installed (use **ParadeDB** Docker image)

Run against Docker:

```bash
composer test              # MariaDB (default)
composer test:postgres     # PostgreSQL + pg_search tests when ParadeDB is running
composer test:redis-search # @group redis-search only
```

Requires Redis Stack with `seccomp=unconfined` in `docker-compose.yml` (Docker Desktop otherwise exits 134 on startup). **RediSearch indexes must use Redis database 0** — set `REDIS_SEARCH_DB=0` (default).

## Key files

- `SearchService`, `RedisSearchDriver`, `PostgresSearchDriver`, `DatabaseSearchDriver`
- `PgSearchQueries`, `PgSearchSupport`, `PgVectorSearch`
- `SearchPostContent`, `SearchTextPreprocessor`, `PostSearchDocumentBuilder`
- `EmbeddingService`, `GeminiEmbeddingProvider`, `OpenAiEmbeddingProvider`
- `config/search.php`, `config/search-stop-words-en.php`, `SearchController`

## Related docs

- [Architecture](14-architecture.md) — Redis Stack and `REDIS_SEARCH_*`
- [Posts & authoring](03-posts-and-authoring.md)
