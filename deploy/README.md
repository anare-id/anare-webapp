# Rumahweb development deployment

Target: `https://dev.anare.co.id`. Main domain remains WordPress until a separate approved cutover.

## Automatic updates

Push approved changes to `main` in `anare-id/anare-webapp`. GitHub Actions `.github/workflows/deploy-dev.yml` runs tests, installs production dependencies, builds a release archive, and uploads through certificate-verified FTPS. The `development` GitHub environment holds `FTP_HOST`, `FTP_USER`, and `FTP_PASSWORD`. No hosting password, `.env`, database dump, or application password is committed.

The FTP account is restricted to `/home/anaj8418/.deploy-anare`. A private cron job runs `activate.sh` once per minute. It verifies SHA-256, prepares a new release, links persistent environment/storage, runs forward migrations, caches Laravel, and atomically changes the release pointer. GitHub waits until `deployment.json` reports the expected commit and `/up` is healthy. Failed activation must be inspected in the private deployment log; a green upload is not treated as successful deployment.

Paths:

- Releases: `/home/anaj8418/anare-dev/releases/<commit>`.
- Current code: `/home/anaj8418/anare-dev/current` symlink.
- Private environment: `/home/anaj8418/anare-dev/shared/.env`.
- Persistent uploads/session/cache/logs: `/home/anaj8418/anare-dev/shared/storage`.
- Domain webroot: `/home/anaj8418/public_html/anare-dev/current/public`, symlink to current public directory. Hosting restricts document roots to `public_html`; application source remains outside it. Parent staging `.htaccess` denies access via the main-domain path.
- Activation log: `/home/anaj8418/anare-dev/deploy.log`.

Hosting specifics: cron runs Bash despite interactive SSH being disabled. PHP `exec`, `proc_open`, and `symlink` are disabled, so the shell activator creates storage/release links. Bash process substitution is unavailable in the jailed environment; the script avoids `/dev/fd`. Archive extraction obeys a restrictive umask, so the activator explicitly restores public directory/file traversal/read modes. `deploy/staging-parent.htaccess` overrides an inherited WordPress PHP 7.4 handler with PHP 8.4 for staging only; the main WordPress `.htaccess` is unchanged.

Only reviewed trusted code may be pushed to the deploy branch. An action that can upload PHP into the release inbox can execute application code as the hosting user. Do not run deployment secrets on pull-request workflows. FTPS inbox credentials do not have general hosting-directory access.

## Rollback

Keep previous release directories. Restore the `current` symlink to a known-good release from an authorized cron or hosting support, then check `/up`, login, and storage. Health-check failure in the activation script restores the prior code pointer when one exists. **Database migrations are not automatically reversed**: use backward-compatible expand/contract migrations and take database backups before destructive changes. Do not use `migrate:fresh` on hosting.

`deploy/rollback.sh <commit>` implements a locked code-only rollback, verifies origin `/up`, and restores the original pointer if the rollback target fails. Install/run it through an authorized private cron (no public admin execution endpoint). It deliberately does not run migration rollback or copy/reset shared storage.

The original full account backup is `backup-10.26.2026_15-27-37_anaj8418.tar.gz` in the hosting home directory. An independently downloaded copy is `D:/PC_SYIHAB/anare-pre-laravel-20261026.tar.gz`, SHA-256 `b379b0ce3f909208a4dbc45d447dcaff2a3fe8464e355a73b17a762c1864cd06`. Archive listing verified original WordPress files and `mysql/anaj8418_wp633.sql`. The backup contains private account data: keep it outside the repository and public webroot.

## DNS and TLS

Cloudflare Free zone `anare.co.id` uses `karsyn.ns.cloudflare.com` and `melnicoff.ns.cloudflare.com`. Existing website, MX, SPF, DKIM, SRV, and IPv6 records were copied before changing nameservers. Records initially remain DNS-only to avoid disrupting mail and control-panel services. Never proxy mail/FTP service records. cPanel AutoSSL handles origin certificates. Keep `.env` production debug disabled and secure cookies enabled; do not use local demo/shared credentials on the Internet.

## Verified setup

GitHub run `40319784888` successfully completed tests, production build, FTPS upload, hosting activation, and HTTPS health verification for commit `94cd196530cefe3b026df7ce729f11b4f1bd3f9e`: https://github.com/anare-id/anare-webapp/actions/runs/40319784888 . Earlier runs failed while identifying jailed-shell/Apache constraints; these failures were not treated as completed deployments.

The staging frontend was visually inspected in a browser. Real database Super Admin login succeeded, authenticated state returned 200, requests without CSRF returned 419, and a real uploaded PNG was saved in shared storage and served with HTTP 200. A temporary draft was saved and deleted afterward. Initial staging credentials were subsequently replaced by the separate accounts described below. Original WordPress files and database were not replaced; only the `dev` subdomain serves Laravel.

Account update: the owner requested separate permanent accounts. Local and development databases now have `superadmin` with only `super-admin`, and `admin` with only `admin`. Both real password logins were verified; Admin account/role management is denied with HTTP 403. Passwords were reset during this requested provisioning and are intentionally not stored in repository documentation. Replace development credentials before a production cutover.

Code rollback was exercised on hosting: commit `94cd196530cefe3b026df7ce729f11b4f1bd3f9e` was switched to `9fe6f4288121d6515bb4898fb3edbb21bf2588e9`, origin health passed, then restored to `94cd196530cefe3b026df7ce729f11b4f1bd3f9e`. The same authenticated session and uploaded image still returned HTTP 200 after both switches. Temporary verification cron jobs were removed; only the once-per-minute activator remains. Cloudflare zone status is active and public resolver 1.1.1.1 reports its assigned nameservers.
