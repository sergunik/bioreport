# Self-Hosted Deployment

This document describes how the backend and frontend are delivered as a single runtime unit and how to deploy the system.

## How the frontend is included

- The backend repository does **not** contain frontend source code. Only **prebuilt static assets** are used at runtime.
- Prebuilt assets (SPA bundle: `index.html`, JavaScript, CSS) must be placed in **`frontend/public/`** at the root of the backend repository.
- The frontend is built in its own repository ([bioreport-canvas](https://github.com/sergunik/bioreport-canvas)); the build output is exported as an artifact and copied into `frontend/public/`.
- At runtime, no Node.js or frontend tooling is required. Nginx serves files from `frontend/public/` for all non-API requests.
- If `frontend/public/` is empty or missing, a placeholder `index.html` can be used until the first frontend build is deployed.

## How to deploy

1. Ensure Docker and Docker Compose are installed.
2. Run:

```bash
docker-compose up -d && docker-compose exec app composer setup
```

Or:

```bash
make up && make setup
```

3. Load frontend from latest bioreport-canvas repository:


Or:

```bash
make front
```

4. Open the application in a browser at `http://localhost` (or your server hostname).

No reverse proxy is required for basic use. For production, you may put Traefik or another reverse proxy in front and keep a single domain so that `/` and `/api` remain on the same origin.
