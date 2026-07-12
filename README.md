# African Museum Artifacts API

A Laravel-powered API that aggregates, normalizes, stores, and exposes African museum artifact data from multiple open-access museum collections through a unified schema.

---

## Overview

African cultural heritage is distributed across museums, archives, and cultural institutions around the world. Each institution exposes its collections differently, making it difficult for developers, researchers, and digital heritage projects to work with the data consistently.

The African Museum Artifacts API provides a unified data model by importing artifact records from multiple public museum APIs, normalizing their metadata, and exposing them through a single platform.

This project serves as both a portfolio application demonstrating modern Laravel development and the foundation for future cultural heritage software.

---

## Goals

- Aggregate artifact data from multiple museum APIs.
- Normalize inconsistent metadata into a single schema.
- Store artifacts locally using PostgreSQL.
- Provide a searchable catalog.
- Expose a clean REST API.
- Serve as a reusable data platform for cultural heritage applications.

---

## Planned Data Sources

- Metropolitan Museum of Art Collection API
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

- Artifact data model
- UUID primary keys
- Eloquent accessors and mutators
- Attribute casting
- Query scopes
- Model events and observers
- Soft deletes
- Factories and seeders
- Metadata normalization
- Searchable artifact catalog (in progress)

---

## Technology Stack

| Technology | Version |
|------------|----------|
| Laravel | 13 |
| PHP | 8.2+ |
| PostgreSQL | 15 |
| Docker | Deployment only |
| Render | Production deployment |

---

## Local Installation

Clone the repository.

```bash
git clone https://github.com/yourusername/african-museum-artifacts-api.git
```

Enter the project.

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

## Deployment

This project is designed for deployment on **Render** using Docker.

Deployment includes:

- Dockerfile
- PostgreSQL
- Automatic GitHub deployments
- Environment variable configuration
- Automatic database migrations

---

## Roadmap

### Milestone 1
- Project foundation
- Artifact model
- Database schema
- UUID support
- Eloquent fundamentals

### Milestone 2
- Museum API import engine
- Metadata normalization
- Initial data import

### Milestone 3
- Search and filtering
- Public API endpoints
- Resource responses

### Milestone 4
- Dashboard
- Artifact detail pages
- Source browser

### Milestone 5
- Authentication
- Queues
- Performance optimization
- Testing
- Production improvements

---

## Project Status

🚧 Active Development

This project is being developed incrementally as part of a deep dive into Laravel. Each milestone introduces new Laravel concepts while evolving the application into a production-ready platform.

---

## License

MIT License