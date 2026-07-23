# African Museum Artifacts API

**Aggregate • Normalize • Preserve • Expose African Cultural Heritage Data**

A Laravel-powered platform that aggregates artifact metadata from multiple museum collections, normalizes inconsistent schemas into a unified data model, and exposes a reusable foundation for cultural heritage applications.

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel)![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php)![SQLite](https://img.shields.io/badge/SQLite-Database-003B57?style=for-the-badge&logo=sqlite)![License](https://img.shields.io/badge/License-MIT-success?style=for-the-badge)![Status](https://img.shields.io/badge/Status-Active_Development-blue?style=for-the-badge)---

### Building modern software for museums, galleries, archives, and cultural institutions.

---

# Project Overview

African cultural heritage is dispersed across museums and cultural institutions worldwide. Every institution exposes its collections differently, making discovery, integration, and preservation difficult.

The **African Museum Artifacts API** provides a unified platform that imports data from multiple public museum APIs, normalizes inconsistent metadata into a consistent schema, stores it locally, and prepares it for search, analysis, and future cultural heritage applications.

This repository is the **first project** in a Laravel portfolio focused on building production-ready software for the cultural heritage sector.

---

# Project Statistics

| Metric | Value |
| --- | --- |
| Museum APIs Integrated | **1 / 9** |
| Artifact Records Imported | **0+** |
| Normalized Fields | **20+** |
| Duplicate Prevention | ✅ |
| Import Progress Tracking | ✅ |
| Background Imports | ✅ |
| UUID Support | ✅ |
| Laravel Version | **13** |

---

# Features

## Domain Model

- UUID primary keys
- Eloquent Models
- Soft Deletes
- Mass Assignment
- Attribute Casting
- Accessors & Mutators
- Local Query Scopes
- Model Observers
- Factories & Seeders

---

## Import Engine

- Metropolitan Museum of Art integration
- Metadata normalization
- African heritage filtering
- Duplicate detection
- Batch imports
- Offset-based importing
- Import progress monitoring
- Background import execution
- Source metadata preservation

---

## Infrastructure

- PostgreSQL
- Docker-ready
- Feature-based Git workflow
- Automatic migrations
- Production-ready architecture

---

# Technology Stack

| Technology | Version |
| --- | --- |
| Laravel | 13 |
| PHP | 8.2+ |
| PostgreSQL | 15 |
| Docker | Supported |
| GitHub | Version Control |

---

# System Architecture

```text
Museum APIs
      │
      ▼
Import Engine
      │
      ▼
Metadata Normalization
      │
      ▼
PostgreSQL Database
      │
      ▼
Artifact Catalog
      │
      ▼
REST API
      │
      ▼
Future Cultural Heritage Applications
```

---

# Screenshots

## Dashboard

<img src="docs/screenshots/dashboard.png" alt="Dashboard" width="94" />\---

## Artifact Listing

![Artifacts](docs/screenshots/artifacts.png)\---

## Artifact Details

![Artifact Details](docs/screenshots/artifact-details.png)\---

## Import Progress

![Import Progress](docs/screenshots/import-progress.png)\---

# Demo

- Triggering an import
- Import progress
- Browsing artifacts
- Searching the collection

```
docs/demo/demo.gif
```

---

# Local Installation

Clone the repository.

```bash
git clone https://github.com/ogbuaguwizard/african-museum-artifacts-api.git

cd african-museum-artifacts-api

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve
```

---

# Importing Museum Data

Import artifacts in batches.

First batch

```bash
php artisan import:met --limit=1000 --offset=0
```

Second batch

```bash
php artisan import:met --limit=1000 --offset=1000
```

Third batch

```bash
php artisan import:met --limit=1000 --offset=2000
```

The importer automatically:

- retrieves artifact records
- filters African heritage objects
- normalizes metadata
- preserves original source metadata
- prevents duplicate imports
- tracks import progress

---

# Development Workflow

```text
Feature Branch
      │
      ▼
Development
      │
      ▼
Commit
      │
      ▼
Push
      │
      ▼
Pull Request
      │
      ▼
Merge to Main
```

---

# Roadmap

## Phase 1

- Project foundation
- Artifact domain model
- Import engine
- Metadata normalization

---

## Phase 2

- Search
- Filtering
- Sorting
- REST API

---

## Phase 3

- Dashboard
- Artifact pages
- Collection browser

---

## Phase 4

- Authentication
- Queue workers
- Redis
- Performance optimization
- Automated testing

---

# Documentation

📖 **Deployment Guide**

See: [DEPLOYMENT.md](DEPLOYMENT.md)

---

📝 **Project Changelog**

See: [CHANGELOG.md](CHANGELOG.md)

---

# Future Projects

This project forms the foundation for a larger ecosystem of cultural heritage software.

- Curator Workspace
- Digital Exhibition Builder
- Cultural Collections Analytics

Together these projects demonstrate progressively deeper Laravel knowledge while solving real-world problems for museums, galleries, archives, and cultural institutions.

---

# License

Released under the MIT License.