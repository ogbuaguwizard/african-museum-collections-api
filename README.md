# African Museum Artifacts API

A Laravel-powered API that aggregates, normalizes, stores, and exposes African museum artifact data from multiple open-access museum collections through a unified schema.

> **Project Status:** 🚧 Active Development

---

## Overview

African cultural heritage is distributed across museums, archives, libraries, and cultural institutions around the world. Each institution exposes its collections using different metadata standards and API structures, making it difficult for developers, researchers, and digital heritage projects to work with the data consistently.

The African Museum Artifacts API solves this problem by importing artifact records from multiple public museum APIs, transforming them into a unified schema, storing them locally, and exposing them through a single platform.

This project is the first in a series of portfolio applications focused on applying modern Laravel to cultural heritage technology.

---

## Objectives

- Aggregate artifact metadata from multiple museum APIs.
- Normalize inconsistent metadata into a unified schema.
- Store normalized records in PostgreSQL.
- Build a searchable artifact catalog.
- Expose a clean REST API.
- Serve as a reusable backend for cultural heritage applications.

---

## Supported Data Sources

Current integration:

- ✅ Metropolitan Museum of Art Collection API

Planned integrations:

- Smithsonian Open Access API
- Harvard Art Museums API
- Cleveland Museum of Art API
- Europeana API
- Rijksmuseum API
- Art Institute of Chicago API
- New York Public Library Digital Collections API
- Internet Archive Metadata API
- Open Library API

Priority is given to records related to African cultural heritage.

---

## Current Features

### Domain Model

- Artifact model
- UUID primary keys
- Soft deletes
- Mass assignment
- Attribute casting
- Accessors
- Mutators
- Local query scopes
- Model observers
- Model factories
- Database seeders

### Import Engine

- Metropolitan Museum of Art Collection API integration
- Secure background import endpoint
- Batch importing with offset support
- Import progress monitoring
- Metadata normalization
- African heritage filtering
- Duplicate detection
- Source metadata preservation
- Image importing

### Infrastructure

- PostgreSQL
- Docker deployment
- Render deployment
- Automatic database migrations
- Feature-based Git workflow

---

## Technology Stack

| Technology | Version |
|------------|----------|
| Laravel | 13 |
| PHP | 8.2+ |
| PostgreSQL | 15 |
| Docker | Production Deployment |
| Render | Hosting |

---

## Local Installation

Clone the repository.

```bash
git clone https://github.com/ogbuaguwizard/african-museum-artifacts-api.git
```

Navigate into the project.

```bash
cd african-museum-artifacts-api
```

Install dependencies.

```bash
composer install
```

Create the environment file.

```bash
cp .env.example .env
```

Generate the application key.

```bash
php artisan key:generate
```

Configure your PostgreSQL database inside `.env`.

Run the migrations.

```bash
php artisan migrate
```

Start the development server.

```bash
php artisan serve
```

---

## Importing Museum Data

Import artifacts from the Metropolitan Museum of Art Collection API.

Import the first batch:

```bash
php artisan import:met --limit=1000 --offset=0
```

Import the second batch:

```bash
php artisan import:met --limit=1000 --offset=1000
```

Import the third batch:

```bash
php artisan import:met --limit=1000 --offset=2000
```

Continue increasing the `offset` value until the desired portion of the collection has been imported.

The importer automatically:

- Retrieves artifact records
- Filters African heritage objects
- Normalizes metadata
- Preserves source metadata
- Prevents duplicate records

Production imports are performed through secure endpoints protected by an application token.

For deployment instructions and production imports, see the **Deployment Guide** below.

---

## Deployment

The application is deployed to **Render** using Docker and PostgreSQL.

Deployment features include:

- Automatic GitHub deployments
- Docker image builds
- Automatic database migrations
- Secure background imports
- Batch importing
- Import progress monitoring

See the complete deployment guide:

- 📖 [Deployment Guide](DEPLOYMENT.md)

---

## Development Workflow

This project follows a feature-based Git workflow.

```text
Feature Branch
      ↓
Development
      ↓
Commit
      ↓
Push
      ↓
Merge into main
      ↓
Automatic Render Deployment
```

Each feature is developed independently before being merged into the `main` branch.

---

## Roadmap

### Phase 1 — Foundation

- Project setup
- Artifact domain model
- Metadata normalization
- Museum import engine

### Phase 2 — Discovery

- Search
- Filtering
- Sorting
- Public REST API

### Phase 3 — User Experience

- Dashboard
- Artifact detail pages
- Collection browser

### Phase 4 — Production

- Authentication
- Queue workers
- Redis caching
- Performance optimization
- Automated testing

---

## Documentation

- 📖 [Deployment Guide](DEPLOYMENT.md)
- 📝 [Project Changelog](CHANGELOG.md)

---

## Project Vision

The African Museum Artifacts API is the foundation of a larger ecosystem of cultural heritage software built with Laravel.

Future projects include:

- Curator Workspace
- Digital Exhibition Builder
- Cultural Collections Analytics

Together, these projects demonstrate progressively deeper Laravel knowledge while solving real-world problems for museums, galleries, archives, and cultural institutions.

---

## License

Licensed under the MIT License.