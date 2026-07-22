# Changelog

All notable changes to this project are documented here.

This project follows a feature-based development workflow. Every completed feature is committed independently and merged into the `main` branch after review.

---

## Unreleased

### Planned

- Smithsonian importer
- Harvard Art Museums importer
- Cleveland Museum importer
- Europeana importer
- Rijksmuseum importer
- Public REST API
- Search API
- Dashboard
- Authentication
- Queue-based imports
- Redis caching
- Automated testing

---

# v0.1.0 — Project Foundation

## Added

- Laravel 13 project initialization
- PostgreSQL configuration
- Docker deployment support
- Render deployment configuration
- GitHub repository
- Project documentation
- Dockerfile
- `.dockerignore`
- `start.sh` deployment script

---

# v0.2.0 — Artifact Domain Model

## Added

- Artifact model
- UUID primary keys
- Database migration
- Soft deletes
- Mass assignment configuration
- Attribute casting
- Accessors
- Mutators
- Query scopes
- Model observers
- Model factories
- Database seeders
- Eloquent best practices

---

# v0.3.0 — Metropolitan Museum Import Engine

## Added

- Metropolitan Museum Collection API integration
- Import Artisan command
- African heritage filtering
- Metadata normalization
- Duplicate prevention
- Artifact image importing
- Source metadata storage
- Import progress reporting

---

# v0.4.0 — Search & Discovery

## Planned

### Search

- Keyword search
- Full-text search

### Filters

- Country
- Culture
- Dynasty
- Period
- Classification
- Museum
- Source

### Sorting

- Title
- Artist
- Date
- Source

---

# v0.5.0 — Public API

## Planned

- REST API
- JSON Resources
- Pagination
- Filtering
- Sorting
- Rate limiting
- API versioning

---

# v0.6.0 — User Interface

## Planned

- Homepage
- Artifact listing
- Artifact detail page
- Search interface
- Collection statistics
- Responsive design

---

# Future Releases

This project is part of a long-term Laravel learning journey focused on cultural heritage technologies.

Future milestones will continue expanding the platform with:

- Additional museum integrations
- Improved metadata normalization
- Performance optimization
- Authentication
- Background processing
- API documentation
- Testing
- Production monitoring