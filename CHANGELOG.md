# V5.13 - 12/08/2026

- Major DCTLAB Enterprise Admin UI redesign for the WHMCS WhatsApp Notifications module.
- Redesigned Dashboard with messaging metrics, provider status, message activity, WhatsApp usage and notification performance.
- Added date-range analytics with delivery, read and failure rates.
- Redesigned Notification Reports with improved filters, tables, status badges, actions and empty states.
- Redesigned Notifications management with search, status/provider filters and improved template management UI.
- Redesigned Meta and standard WhatsApp template editors with improved forms and Meta message preview.
- Redesigned WhatsApp Conversations with improved conversation list, message bubbles and mobile navigation.
- Redesigned WhatsApp 2FA Client/Admin Logs with improved filters, badges, tables and output escaping.
- Redesigned provider Settings using the existing data-driven settings architecture.
- Added secure credential masking: stored password-type settings are no longer rendered into HTML.
- Blank credential submissions now preserve existing credentials.
- Added credential masking to module logging.
- Added `idx_created_at_status (created_at, status)` for notification report performance.
- Added redesigned Bulk Messaging interfaces while preserving existing recipient matching and cron-based processing.
- Fixed bulk campaign Max Concurrency display when editing an existing campaign.
- Improved notification performance reporting to respect selected date ranges.
- Added accessible chart text alternatives using `role="img"` and `aria-label`.
- Fixed raw Evolution API error responses being displayed in the admin UI.
- Fixed unescaped Chatwoot client data in JavaScript interpolation.
- Fixed unescaped Botms webhook response output.
- Improved 2FA log output escaping.
- Preserved Bootstrap 3.4 compatibility and existing WHMCS admin integration.
- No WHMCS core modifications.
- No Bootstrap 5 migration.
- Final security and regression QA completed.
- Production readiness assessment: PASS.



# 1.0.0

