## Security Headers Fixer (WordPress plugin)

This plugin addresses the scan findings that can be fixed at the WordPress/PHP layer:

- **Missing HSTS header** (`Strict-Transport-Security`)
- **Missing Referrer-Policy header**
- **Missing Content-Security-Policy header** (supports Report-Only first, then Enforce)
- **Missing X-Frame-Options header** (skipped automatically if CSP contains `frame-ancestors`)
- **Unsafe cross-origin links** (adds `rel="noopener noreferrer"` to `target="_blank"` links in rendered content)

### Install (local / manual)

1. Copy the `security-headers-fixer/` folder into your WordPress `wp-content/plugins/` directory.
2. Activate **Security Headers Fixer** in WP Admin → Plugins.
3. Configure in WP Admin → Settings → **Security Headers**.

OR YOU CAN UPLOAD DIRECTLY IN UPLOAD PLUGIN

### Recommended rollout order

1. **Enable Referrer-Policy + nosniff + X-Frame-Options** first (low risk).
2. For CSP:
   - Enable CSP in **Report-Only** mode first.
   - Open pages and check your browser devtools console for CSP violations.
   - Update directives as needed.
   - Only then switch to **Enforce**.
3. Enable **HSTS** only when you are sure your site is fully HTTPS and all subdomains (if enabled) are also HTTPS.

### How to validate headers

- In Chrome DevTools → Network → select a document → **Response Headers**
- Or run:

```bash
curl -I https://your-site.example/
```

You should see headers like:

- `Strict-Transport-Security: ...` (only if enabled, and only on HTTPS)
- `Referrer-Policy: ...`
- `X-Content-Type-Options: nosniff`
- `Content-Security-Policy-Report-Only: ...` or `Content-Security-Policy: ...`
- `X-Frame-Options: ...` (only if CSP doesn’t include `frame-ancestors`)

### Notes / limitations (cause vs symptom)

- **Some headers are better at the web server / CDN edge** (nginx/Apache/Cloudflare) because they apply even to non-WordPress responses (static files, 404s served before WP, etc.). This plugin is still useful when you can’t change server config, but server-level is the most complete fix.
- **CSP is site-specific**. The provided directives are intentionally permissive to avoid breaking Elementor; tighten after observing Report-Only violations.
- **Mixed content** is best fixed by serving all assets over HTTPS. The optional CSP `upgrade-insecure-requests` can mitigate, but it’s not a substitute for fixing the source URLs.

