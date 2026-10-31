# Lunelle next steps

The presentation refresh covers the public site, authentication, and shared member, studio, and administrator screens. Appointment requests still require studio approval.

The current deployment audit, completed fixes, remaining launch requirements, and cPanel instructions are in [DEPLOYMENT.md](DEPLOYMENT.md).

## First: booking reliability

- Enforce studio hours, blocked dates, staff availability, and treatment duration on the server. Public booking currently checks that the time is in the future but does not check overlaps or capacity. Apply the same rules to staff scheduling and request acceptance.
- Group/multi-service requests are transactional and guest profiles are isolated. Requests, course sessions, payments, supplies, claims, signatures, roles, and deployment controls now have regression tests.
- Decide how to allocate consecutive treatments and simultaneous clients before adding automatic availability enforcement.

## Then: a complete studio experience

- Add administrator-managed business details: address, contact number, operating hours, cancellation policy, and genuine studio images. The public page still identifies the business as a concept studio.
- Send request acknowledgments, approval/decline messages, and reminders. Configure a delivery provider and queue before enabling messages in production.
- Add rescheduling/cancellation workflows, clear deadlines, and a staff activity history.
- Show upcoming appointments separately from completed history and let users browse their full appointment history. The member dashboard currently shows five recent appointments.
- Make the calendar navigable across months and add day/week views with staff filters.
- Self-service account deletion remains unavailable, and tests now verify that contract. Any future deletion workflow needs an explicit retention strategy for payment and consent records.

## Before release

- Phone and desktop browser smoke checks now cover owner/staff/member pages. Continue with full tablet, keyboard, screen-reader, and real-device checks, plus all form error states.
- Standardize dialogs with focus containment, accessible labels, scroll locking, and consistent cancel actions. The project has a shared modal component; several management pages still use custom inline dialogs.
- New appointment input/display uses Philippine time with UTC storage. Review ambiguous historic timestamps rather than applying an automatic bulk shift.
- Set up backups with a restore check, HTTPS, production mail/queue workers, and error monitoring.

## Later

- The business overview, low-stock highlights, and package progress are implemented. Export files, expenses, refunds, and automated alerts remain future work.
- Loyalty rewards after booking, payment, and client identity flows are reliable.
