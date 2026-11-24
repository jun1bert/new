Run `php artisan db:seed --class=DemoScenarioSeeder` to add fictional portal scenarios without resetting the database.

New demo accounts use password `DemoPortal!2026`:

| Role | Email |
| --- | --- |
| Administrator | admin@demo.shafers.test |
| Staff | staff@demo.shafers.test |
| Client | mia@demo.shafers.test |
| Client with a pending past-booking claim | sofia@demo.shafers.test |

Look in Sessions for names starting with **Demo**:

The complete seeder creates 17 demo services and 43 appointments for 14 demo clients (including the two guest profiles), plus administrator and staff accounts. Ten additional clients use `client1@demo.shafers.test` through `client10@demo.shafers.test` with the same demo password.

- Mia's three-session laser course: the first visit includes a manicure; the next two include only laser. Two visits are completed and one is scheduled.
- Mia's facial and massage: one scheduled appointment with a combined consent form ready to sign.
- Lara and Sofia's guest group: matching time and contact details, two appointments with two services each, pending approval.
- Mia's completed facial and manicure: a retail serum, PHP 250 discount, GCash payment, receipt, and supply usage.
- Sofia's previous guest visit: a completed massage and pedicure, signed sample consent, and a pending claim from her registered account.
- Additional pending and declined requests, plus low-stock inventory.
- A fuller catalog including hydrating facials, LED therapy, acne care, body contouring, body scrubs, foot spas, lash treatments, brow shaping, waxing, and nail art.
- Thirty additional completed, scheduled, pending, and declined multi-service visits spread across clients and dates.
- A six-session body contouring course with two completed visits and a third visit scheduled. Only the first visit includes a body scrub.

Completed examples use dates in the month of the first seed run. Upcoming examples use today and the next three days in Philippine time.

Repeat runs skip existing demo appointments and preserve changes made while exploring. They do not reset passwords, stock, payments, or statuses. Existing appointments are not merged or deleted. Demo services and products also appear in the landing page catalog.

All consent text and sample signature drawings are fictional demonstration data.
