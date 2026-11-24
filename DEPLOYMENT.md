# cPanel deployment and readiness review

Reviewed October 31, 2026 for a public website on shared cPanel hosting.

**Verdict: ready for staging validation; not yet approved for public production.** Local functional checks pass, but live hosting, business configuration, and operational controls still need verification. No deployment or demo-data deletion was performed.

## Checks and fixes completed

- Reviewed public and authenticated routes, role restrictions, guest/group bookings, multi-service visits, course follow-ups, discounts, receipts, reports, stock movements, claims, signatures, and uploads.
- Updated `symfony/mime` from 7.4.19 to 7.4.22 for its published header-injection advisory. Composer and npm security audits report no known advisories after the patch. An audit is not a guarantee that no vulnerabilities exist.
- Removed automatic guest matching to accounts by email, name, or contact number. Every unauthenticated participant gets an isolated guest profile; contact email is stored on the appointment. Registered-account ownership now comes from sign-in or staff-reviewed claims.
- Restricted claims to unclaimed guest records and the verified treatment course. Claim approval cannot transfer a registered client's account history and uses transactional locks.
- Added request throttles for public bookings, registration, password-reset requests, and claims; bounded booking/payment arrays and required list-shaped payloads.
- Existing sessions are logged out when their account is deactivated. Newly created models now have explicit defaults matching the database.
- Made stock adjustments transactional, locked, precision-checked, and bounded so stock and its movement record stay together.
- Approval rejects expired requests and locks the appointment; decline updates only pending records.
- New public/staff scheduling interprets input in Philippine time and stores application-timezone timestamps (UTC). Calendar month boundaries, receipts, and visible appointment times use Philippine time.
- Added booking-email migration and applied it to the local database. Existing records were preserved.
- Added `app:deployment-check`, a read-only production preflight, and `app:create-owner`, an interactive administrator setup command.
- Demo seeding is blocked in production. It does not remove demo records already present.
- Added standard response headers for MIME sniffing, framing, and referrer handling. CSRF protection remains enabled.
- Required consent must be signed before staff can complete an appointment or record its payment.
- Corrected the legacy profile tests to verify the existing disabled self-service deletion behavior. Account deletion was not enabled; appointment and financial history must not be silently cascade-deleted.
- Fixed Reports chart overflow found in a real headless browser at phone width.
- Verified production asset build and configuration, route, and Blade caching. Configuration and route caches were cleared again on the local development machine.

Local browser coverage: landing page; owner dashboard, Reports, Sessions, and Claims; staff Sessions; member dashboard and booking details. Phone width 390px and desktop width 1440px where applicable: 14 page/viewport combinations, no runtime JavaScript exceptions, failed local responses, or page-level horizontal overflow. These were smoke checks, not a full accessibility, load, or penetration test. Local screenshots and results are in `storage/app/deployment-checks/` and are excluded from Git.

Final verification: **66 automated tests passed (558 assertions)**; PHP syntax passed for **106 files**; Composer manifest validation and local production platform checks passed; the asset build passed; all local migrations are applied. Local MySQL-backed pages also passed the browser smoke checks. Dependency audits returned zero known advisories. The deployment preflight correctly refuses the current local/demo environment rather than treating it as production-ready.

## Outstanding before launch

| Priority | Item | Required action |
| --- | --- | --- |
| Blocker | Development configuration | Current local settings use `APP_ENV=local`, debug output, localhost, insecure-cookie defaults, and log-only mail. Keep local development separate; configure production on the host. |
| Blocker | Demo identities and sample records | Use a new production database. Do not import this demo database or run demo/default test seeders. Create a real owner with a unique password. If real data is mixed with demos, back up first and plan selective cleanup; do not reset that database. |
| Blocker | Public document root and HTTPS | The public domain must serve only Laravel's `public/` directory. Verify TLS, redirects, and denied access to private files. cPanel primary-domain restrictions may require help from the host. |
| Blocker | SMTP and password recovery | Configure a real sender and test actual delivery of password-reset messages to an external inbox. Local mail assertions do not prove delivery. |
| Blocker | Business identity and consent wording | Replace the landing page's concept-studio text, missing phone/address/location, sample service descriptions, and demonstration consent wording. Confirm prices and per-session charging. |
| High | Scheduling capacity and operating rules | Server-side opening hours, blocked dates, staff availability, treatment duration, and collision/capacity limits are not implemented. Requests require manual staff approval, but approval itself does not prevent overlapping bookings. Define capacity for simultaneous clients/services and verify it before confirming real visits. |
| High | Historic timestamps | Records created before the time-zone fix may contain Philippine wall-clock times stored as UTC. Review existing real appointment times before migration; do not shift all timestamps, because payment/consent timestamps and newer records may already be correct. The preserved old demo examples may also display shifted times. |
| High | Backups and rollback | Back up MySQL, uploaded gallery files, and the production encryption key; test restoring them into a separate staging database. Establish retention and access control for signed client records. |
| High | Server validation | Run the test and preflight commands on staging using the host's PHP and MySQL/MariaDB versions. Automated feature tests use isolated SQLite; concurrency behavior and production database semantics still need staging validation. |
| Medium | Email verification | The User model does not enforce `MustVerifyEmail`; the `verified` middleware therefore does not currently require a verified address. Decide whether verification is required before launch. Guest isolation no longer relies on the submitted email. |
| Medium | Operational workflows | No self-service cancellation/rescheduling, automatic reminders, automatic capacity management, refunds, expenses, or detailed staff audit log. The owner report shows recorded collections, not profit. |

## Hosting prerequisites

Confirm PHP **8.2 or newer**, Composer 2, MySQL/MariaDB, HTTPS, and Apache/LiteSpeed rewrite support with the host. Enable Laravel's PHP extensions and `pdo_mysql`; run `composer check-platform-reqs --no-dev` using the same PHP version as the web application. Confirm writable `storage/`, `bootstrap/cache/`, and `public/uploads/gallery/` for the PHP user. Gallery uploads allow up to ten images of 10 MB each, so host upload/request limits may need adjustment or the UI's allowed batch size should be reduced.

Suggested layout:

```text
/home/CPANEL_USER/shafers/         application, .env, vendor, storage
/home/CPANEL_USER/shafers/public/  domain document root
```

Do not expose the repository root or move `index.php` there. If cPanel cannot point the domain at `public/`, ask the host to configure an appropriate domain/document root; do not upload the full application into a publicly browsable directory as a workaround.

## Initial staging/production installation

Use the cPanel PHP executable that matches the domain's PHP version. Run these from the application root via Terminal/SSH. If Terminal is unavailable, have the host run them; do not add web-accessible migration or setup scripts.

1. Upload the application and lock files, including new/untracked source files. Exclude `.git`, local `.env`, `node_modules`, `public/hot`, local cache files, test artifacts, local logs, and demo database exports. Runtime-generated gallery uploads need a separate backup/copy strategy on updates.
2. Prepare a new, least-privilege cPanel database/user. Copy `.env.production.example` to the server's `.env`, replacing the sample domain, database and SMTP values. Never commit actual credentials. Keep app storage timezone UTC; input/display conversion to Philippine time is explicit in the application.
3. Install dependencies from the lock file:

   ```sh
   composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
   composer check-platform-reqs --no-dev
   ```

4. Build assets locally or in a build environment, and upload `public/build/`:

   ```sh
   npm ci
   npm run build
   ```

   Node is not needed on cPanel when compiled assets are uploaded. Never upload `public/hot`.

5. On the **first install only**, generate the key. On updates, preserve the existing production `APP_KEY`:

   ```sh
   php artisan key:generate
   php artisan migrate --force
   php artisan app:create-owner
   ```

   The owner command prompts for the password without printing it and refuses to overwrite an existing account. Add real services and inventory through the owner interface. Do not run `migrate:fresh`, `db:wipe`, `DemoScenarioSeeder`, or a generic test seeder on production.

6. Enable TLS and force HTTPS redirects in cPanel. The production template enables secure, HTTP-only, encrypted database sessions. It uses synchronous jobs so password-reset delivery does not rely on a persistent shared-host queue worker. If the host requires SMTP STARTTLS on port 587 instead of implicit TLS on 465, use the provider's corresponding mail settings.
7. Optimize and check:

   ```sh
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   php artisan app:deployment-check
   ```

   All preflight checks should pass. The command checks configuration, database access/migrations, assets, and demo records; it cannot certify DNS, mail delivery, backups, public isolation, or operating rules.

8. Confirm all of the following on the actual HTTPS domain:
   - Landing page, catalog, login, logout, and password-reset delivery.
   - Guest group request: two clients, multiple services each, separate appointments with matching arrival/contact details.
   - Member data isolation, denied staff/member access to owner reports, and immediate logout after deactivation.
   - Approval, multi-session follow-up, payment including retail and discount, receipt print/PDF, and stock usage.
   - Combined consent signing, original-wording preservation, and tablet lock/unlock.
   - Reports for today/month/custom dates, including the first and last hours of a Philippine day.
   - Phone navigation, form errors, payment dialogs, long service names, and gallery upload/delete.
   - `/up` returns success. This health route proves application boot, not full database/mail readiness.
   - Requests for `/.env`, `/composer.json`, `/storage/logs/laravel.log`, and `/.git/config` never return their private contents.

## Updating an existing production installation

Take a database/files/key backup and identify the rollback artifact first. Enter maintenance mode, upload tested code and compiled assets without replacing `.env` or uploads, install the locked dependencies, run `php artisan migrate --force`, rebuild config/route/view caches, run the preflight, and leave maintenance mode. Never generate a new key on an existing installation. Do not use destructive database rollback to undo data transformations without a tested restore plan.

If queued jobs are introduced later, use a host-supported worker or cron setup and monitor failed jobs. There are currently no scheduled application tasks in `routes/console.php`; reminder scheduling is not implemented.

## References

- [Laravel 12 deployment requirements, public directory, caching, permissions and debug settings](https://laravel.com/docs/12.x/deployment)
- [cPanel domain document roots and HTTPS redirects](https://docs.cpanel.net/cpanel/domains/domains/)
- [Symfony MIME advisory and patched versions](https://symfony.com/cve-2026-45070)

## Staff attendance QR

Set `APP_URL` to the public HTTPS address of the deployed application and clear/rebuild the Laravel configuration cache after deployment. The owner opens **Staff attendance → Open shop QR display** on a screen kept at the shop. Staff scan the current code, sign in to their own account, and confirm time in or out. Codes change every 30 seconds; after a valid scan, that browser has five minutes to finish signing in and confirming. A successful confirmation consumes the scan. A changing code discourages reuse of old screenshots but does not prove physical presence; a live image can still be shared. Owners can correct attendance records with a reason.
