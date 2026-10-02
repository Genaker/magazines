# syntax=docker/dockerfile:1
# Lean PostgreSQL 16: pgvector/pgvector (bookworm) + pg_search bookworm .deb extracted at build time.
# No PostGIS. ParadeDB optional: POSTGRES_IMAGE=paradedb/paradedb:0.24.0-pg16

ARG PG_MAJOR=16
ARG DEBIAN_CODENAME=bookworm
ARG PG_SEARCH_VERSION=0.24.0

FROM alpine:3.20 AS pg_search_extract

ARG PG_MAJOR
ARG DEBIAN_CODENAME
ARG PG_SEARCH_VERSION
ARG TARGETARCH

RUN apk add --no-cache curl binutils xz \
    && ARCH="${TARGETARCH:-amd64}" \
    && curl -fsSL \
        "https://github.com/paradedb/paradedb/releases/download/v${PG_SEARCH_VERSION}/postgresql-${PG_MAJOR}-pg-search_${PG_SEARCH_VERSION}-1PARADEDB-${DEBIAN_CODENAME}_${ARCH}.deb" \
        -o /tmp/pg_search.deb \
    && cd /tmp \
    && ar x pg_search.deb \
    && mkdir /out \
    && tar -xJf data.tar.xz -C /out

FROM pgvector/pgvector:pg${PG_MAJOR}

ARG PG_MAJOR

USER root

COPY --from=pg_search_extract /out/usr/lib/postgresql/${PG_MAJOR}/lib/pg_search.so /usr/lib/postgresql/${PG_MAJOR}/lib/
COPY --from=pg_search_extract /out/usr/share/postgresql/${PG_MAJOR}/extension/pg_search* /usr/share/postgresql/${PG_MAJOR}/extension/

USER postgres

CMD ["postgres", "-c", "shared_preload_libraries=pg_search"]
