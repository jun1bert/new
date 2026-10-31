# Appointment scheduling

Open the staff **Dashboard** for the day or week calendar. Dates and hours use Philippine time. Filter by treatment provider or use **Add / manage** to schedule and approve bookings. The former `/studio/calendar` URL redirects to the dashboard calendar; there is one calendar workspace.

The dashboard calendar is an overview. Provider assignment and other scheduling actions belong in **Sessions**. Administrators can configure studio business hours and weekly provider shifts under **Working hours** in Sessions. Unchecked days are unavailable. A provider without a saved shift uses studio hours. Studio hours initially remain unset so installing the feature does not invent business hours. Once saved, the full appointment must fit within both schedules. Changes do not move or cancel existing bookings.

New scheduled appointments have a separate treatment provider; the original `staff_id` still records the booking operator. Choose the provider when scheduling, approving, or creating a follow-up. The default is the current operator for first sessions and approvals, and the previous provider for follow-ups. Providers must be active staff. Administrators manage bookings but must select a staff member as provider. If a provider becomes an administrator or is deactivated, pending and scheduled assignments are cleared; completed history is retained. Use **Assign treatment provider** or **Change treatment provider** on a scheduled session in Sessions.

Reservations use the sum of service durations, with a 60-minute fallback per service lacking a duration. Duration is saved at reservation time so later service edits do not alter a reserved slot. Adjacent appointments are permitted; overlapping provider or client appointments are rejected. Pending and declined requests do not reserve availability. Public requests check studio hours; staff approval checks provider availability.

Older appointments retain their original records and appear as unassigned. Assign their treatment provider in Sessions to include them in provider conflict checks. Until assigned, they still prevent overlapping appointments for the same client, but cannot block a particular provider. Older durations are estimated from their services until assigned.

The scheduling-settings row serializes reservation transactions and working-hour changes on databases supporting row locks. Automated tests use SQLite and cover workflow behavior; they do not prove concurrent MySQL request behavior. Weekly schedules support one continuous shift each day; split shifts, dated leave, and room/equipment capacity are outside this release.

Setup: run `php artisan migrate` and `npm.cmd run build` on Windows (`npm run build` elsewhere). No new dependencies are required.

## Rescheduling, cancellations, and missed visits

In **Sessions**, use **Reschedule** on a scheduled, cancelled, or missed session. Choose a future Philippine date/time, an active staff provider, and a reason. Availability is rechecked using the original reserved duration. The same appointment and treatment-course session are retained. Rescheduling a cancelled/no-show session makes it scheduled again.

**Cancel session** accepts pending or scheduled appointments and requires a reason. **Mark no-show** is available for scheduled appointments once their start time has passed, and also requires a reason. Cancelled and missed sessions do not reserve provider/client capacity and do not create payments or stock deductions. Completed sessions cannot be changed through these controls. Pending requests must be approved before rescheduling; they can be cancelled directly.

**Session history** shows who changed the booking, when, the reason, and previous/new times, providers, or statuses. Tracking starts when this migration is installed; earlier changes are not reconstructed. Creation, approval, decline, completion, reassignment, rescheduling, cancellation, and no-show updates are tracked. Members can see updated appointment dates/statuses but only the studio team can use these management actions. The dashboard and reports include cancelled and no-show statuses.
