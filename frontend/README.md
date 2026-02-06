# Frontend assets (prebuilt)

This directory holds **prebuilt** static assets of the BioReport SPA. It is not frontend source code.

- **`public/`** — Place the build output of the [frontend repository](https://github.com/sergunik/bioreport-canvas) here (`index.html`, JS and CSS bundles). The backend serves these files at `/` with SPA fallback routing.
- At runtime, no Node.js or frontend build step is required.

See [../docs/self-host.md](../docs/self-host.md) for deployment and integration details.
