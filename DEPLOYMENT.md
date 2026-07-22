# Deployment

This project is deployed to **Render** using **Docker** and **PostgreSQL**. Every deployment is automatically built from the `main` branch and configured to run database migrations before the application starts.

---

# Environment Variables

Configure the following environment variables in your Render Web Service.

| Variable | Description |
|----------|-------------|
| APP_ENV | production |
| APP_DEBUG | false |
| APP_KEY | Laravel application key |
| DB_CONNECTION | pgsql |
| DB_HOST | PostgreSQL host |
| DB_PORT | PostgreSQL port |
| DB_DATABASE | PostgreSQL database |
| DB_USERNAME | PostgreSQL username |
| DB_PASSWORD | PostgreSQL password |
| IMPORT_TOKEN | Secret token used to authorize import requests |

---

# Automatic Deployment

Every push to the **main** branch automatically triggers a new deployment on Render.

During deployment the application:

- Builds the Docker image
- Installs Composer dependencies
- Starts PHP-FPM and Nginx
- Runs database migrations automatically using `start.sh`
- Launches the application

No manual migration step is required.

---

# How the Import Process Works

The importer is designed to safely process large museum collections without overwhelming the application or importing duplicate records.

The import process consists of two stages.

## Stage 1 — Discover Artifacts

The application queries the Metropolitan Museum of Art Collection API using multiple search strategies (such as department, culture, and geographic origin) to discover artifacts related to African cultural heritage.

Instead of importing records immediately, it first collects all matching object IDs and stores them as an import session. This creates a stable list of artifacts that can be processed incrementally.

## Stage 2 — Import Artifacts

The importer processes the collected object IDs in batches.

For each object ID it:

- Retrieves the complete artifact metadata
- Normalizes the metadata into the application's unified schema
- Preserves source metadata
- Retrieves available image URLs
- Checks for duplicate records
- Stores the artifact in PostgreSQL

Processing artifacts in batches keeps memory usage low and allows very large collections to be imported safely.

---

# Triggering Museum Imports

Imports are started through a secure HTTP endpoint.

Example:

```text
https://african-artifact-collections.onrender.com/import/trigger?token=YOUR_IMPORT_TOKEN&limit=1000&offset=0
```

The import runs in the background, allowing the application to remain responsive while artifacts are processed.

---

# Batch Imports

The importer supports incremental imports using three parameters.

| Parameter | Description |
|----------|-------------|
| `batch` | Number of artifacts processed at a time during a single import job. |
| `limit` | Maximum number of artifact IDs to process during the current import. |
| `offset` | Number of artifact IDs to skip before processing begins. |

Example workflow:

### First 1,000 artifacts

```text
https://african-artifact-collections.onrender.com/import/trigger?token=YOUR_IMPORT_TOKEN&limit=1000&offset=0
```

### Next 1,000 artifacts

```text
https://african-artifact-collections.onrender.com/import/trigger?token=YOUR_IMPORT_TOKEN&limit=1000&offset=1000
```

### Next 1,000 artifacts

```text
https://african-artifact-collections.onrender.com/import/trigger?token=YOUR_IMPORT_TOKEN&limit=1000&offset=2000
```

Continue increasing the `offset` value until the desired portion of the collection has been imported.

Because every artifact is checked before insertion, repeated imports are safe and duplicate records are automatically ignored.

---

# Monitoring Import Progress

Import progress can be monitored through the status endpoint.

Example:

```text
https://african-artifact-collections.onrender.com/import/status?token=YOUR_IMPORT_TOKEN
```

The endpoint returns:

- Current import status
- Total artifact IDs discovered
- Processed artifacts
- Successfully imported artifacts
- Skipped artifacts
- Failed artifacts
- Overall completion percentage

This makes it possible to monitor long-running imports without accessing the server.

---

# Security

The import endpoints are protected using the `IMPORT_TOKEN` environment variable.

Requests made without a valid token return:

```text
HTTP 403 Forbidden
```

This prevents unauthorized users from triggering imports or viewing import progress.

---

# Deployment Workflow

```text
Feature Branch
      │
      ▼
GitHub Pull Request
      │
      ▼
Merge into main
      │
      ▼
Render Auto Deployment
      │
      ▼
Docker Build
      │
      ▼
Run start.sh
      │
      ├── Install Dependencies
      ├── Clear Caches
      ├── Run Database Migrations
      └── Start Nginx & PHP-FPM
      │
      ▼
Application Live
```