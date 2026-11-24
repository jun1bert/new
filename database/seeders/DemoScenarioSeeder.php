<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\ClientClaimRequest;
use App\Models\ConsentForm;
use App\Models\InventoryMovement;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoScenarioSeeder extends Seeder
{
    public const PASSWORD = 'DemoPortal!2026';

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('Demo scenarios are disabled in production. Use a separate local or staging database.');
        }
        DB::transaction(function () {
            $this->user('administrator', 'Demo Administrator', 'admin@demo.shafers.test');
            $staff = $this->user('staff', 'Demo Studio Staff', 'staff@demo.shafers.test');
            $mia = $this->user('member', 'Demo Mia Santos', 'mia@demo.shafers.test');
            $sofia = $this->user('member', 'Demo Sofia Reyes', 'sofia@demo.shafers.test');
            $guestA = $this->user('member', 'Demo Lara Cruz', 'guest-demo-lara@booking.local');
            $guestB = $this->user('member', 'Demo Sofia Reyes', 'guest-demo-sofia@booking.local');

            $facial = $this->service('Signature Facial', 'Skin Care', 1500, 75, 1, true);
            $manicure = $this->service('Gel Manicure', 'Nail Care', 850, 60);
            $pedicure = $this->service('Luxury Pedicure', 'Nail Care', 950, 60);
            $massage = $this->service('Relaxation Massage', 'Body Care', 1400, 60, 1, true);
            $laser = $this->service('Laser Treatment Course', 'Skin Care', 1800, 45, 3, true);
            $serum = $this->product($staff, 'DEMO-RET-001', 'Demo Hydrating Serum', 'retail', 300, 12, 3);
            $supply = $this->product($staff, 'DEMO-SUP-001', 'Demo Treatment Serum (bottle)', 'supply', 0, 10, 2);
            $this->product($staff, 'DEMO-RET-002', 'Demo Face Mask - Low Stock', 'retail', 160, 2, 5);

            $month = now('Asia/Manila')->startOfMonth();
            $today = now('Asia/Manila')->startOfDay();

            // One course: the one-session manicure finishes on the first visit.
            $this->appointment('course-mia', 1, $mia, $staff, [$laser, $manicure], $month->copy()->addDays(4)->setTime(10, 0), 'completed', $supply);
            $this->appointment('course-mia', 2, $mia, $staff, [$laser], $month->copy()->addDays(11)->setTime(10, 0), 'completed', $supply);
            $this->appointment('course-mia', 3, $mia, $staff, [$laser], $today->copy()->addDay()->setTime(10, 0), 'scheduled');

            // Two treatments requiring consent, combined into one appointment.
            $this->appointment('scheduled-mia', 1, $mia, $staff, [$facial, $massage], $today->copy()->setTime(14, 0), 'scheduled');

            // A landing-page group request: two clients at the same time, one appointment each.
            foreach ([[$guestA, [$facial, $manicure], 'guest-lara'], [$guestB, [$massage, $pedicure], 'guest-sofia']] as [$client, $services, $key]) {
                $this->appointment($key, 1, $client, $staff, $services, $today->copy()->addDay()->setTime(11, 0), 'pending',
                    note: 'Demo guest group booking: Lara and Sofia. Shared contact and arrival time; separate services and payments.', contact: '09170000002');
            }

            $this->appointment('discount-mia', 1, $mia, $staff, [$facial, $manicure], $month->copy()->addDays(19)->setTime(13, 0), 'completed', $supply, $serum, 250);
            $pastGuest = $this->appointment('past-sofia', 1, $guestB, $staff, [$massage, $pedicure], $month->copy()->addDays(21)->setTime(15, 0), 'completed', $supply, contact: '09170000002');
            $this->appointment('declined-sofia', 1, $sofia, $staff, [$pedicure], $today->copy()->addDays(2)->setTime(9, 0), 'declined', note: 'Demo declined request: requested time unavailable.');
            $this->appointment('pending-sofia', 1, $sofia, $staff, [$facial, $pedicure], $today->copy()->addDays(3)->setTime(10, 0), 'pending', note: 'Demo member request awaiting staff approval.');

            ClientClaimRequest::whereDate('appointment_date', $pastGuest->scheduled_at->toDateString())->firstOrCreate([
                'claimant_id' => $sofia->id, 'name_used' => $guestB->name,
                'service_name' => $massage->name,
            ], ['appointment_date' => $pastGuest->scheduled_at->toDateString(), 'booking_contact_number' => '09170000002', 'status' => 'pending']);

            $this->moreBookings($staff, $facial, $manicure, $pedicure, $massage, $supply, $serum);
        });

        $this->command?->info('Demo scenarios are ready. Existing records and previously seeded demo scenarios were preserved.');
        $this->command?->table(['Role', 'Email', 'Password for newly created demo accounts'], [
            ['Administrator', 'admin@demo.shafers.test', self::PASSWORD],
            ['Staff', 'staff@demo.shafers.test', self::PASSWORD],
            ['Client', 'mia@demo.shafers.test', self::PASSWORD],
            ['Client / past booking claim', 'sofia@demo.shafers.test', self::PASSWORD],
        ]);
    }

    private function moreBookings(User $staff, Service $facial, Service $manicure, Service $pedicure, Service $massage, Product $supply, Product $retail): void
    {
        $catalog = [];
        foreach ([
            ['classic', 'Classic Manicure', 'Nail Care', 450, 45, 1, false],
            ['nail-art', 'Nail Art', 'Nail Care', 350, 30, 1, false],
            ['hydra', 'Hydrating Facial', 'Skin Care', 2200, 75, 1, true],
            ['acne', 'Acne Care Course', 'Skin Care', 1600, 60, 4, true],
            ['led', 'LED Light Therapy', 'Skin Care', 700, 30, 1, true],
            ['scrub', 'Full Body Scrub', 'Body Care', 1200, 60, 1, true],
            ['contour', 'Body Contouring Course', 'Body Care', 2500, 60, 6, true],
            ['foot-spa', 'Foot Spa', 'Nail Care', 650, 45, 1, false],
            ['lash-lift', 'Lash Lift', 'Lash & Brow', 1000, 60, 1, true],
            ['lashes', 'Classic Lash Extensions', 'Lash & Brow', 1800, 90, 1, true],
            ['brow', 'Brow Shaping', 'Lash & Brow', 350, 30, 1, false],
            ['wax', 'Underarm Wax', 'Waxing', 400, 20, 1, true],
        ] as [$key, $name, $category, $price, $duration, $sessions, $consent]) {
            $catalog[$key] = $this->service($name, $category, $price, $duration, $sessions, $consent);
        }

        $combinations = [
            [$catalog['hydra'], $catalog['classic']], [$catalog['led'], $facial],
            [$catalog['scrub'], $catalog['foot-spa']], [$catalog['lash-lift'], $catalog['brow']],
            [$catalog['wax'], $pedicure], [$catalog['hydra'], $manicure, $catalog['nail-art']],
            [$massage, $catalog['foot-spa']], [$catalog['lashes'], $catalog['brow']],
            [$facial, $catalog['led'], $catalog['classic']], [$catalog['wax'], $massage],
        ];
        $today = now('Asia/Manila')->startOfDay();
        $month = $today->copy()->startOfMonth();
        $names = ['Isabella Garcia', 'Chloe Mendoza', 'Bea Lim', 'Camille Tan', 'Dani Flores',
            'Elise Navarro', 'Faith Ramos', 'Grace Dizon', 'Hannah Yu', 'Ivy Bautista'];

        foreach ($names as $index => $name) {
            $key = 'expanded-client-'.($index + 1);
            $client = $this->user('member', 'Demo '.$name, 'client'.($index + 1).'@demo.shafers.test');
            $contact = '091700001'.str_pad((string) $index, 2, '0', STR_PAD_LEFT);
            $services = $combinations[$index];
            // Spread history through the current month, without dating completed visits in the future.
            $historyDay = min(1 + $index * 2, max(0, $today->day - 1));
            $this->appointment($key.'-history', 1, $client, $staff, $services,
                $month->copy()->addDays($historyDay)->setTime(9 + $index % 6, 0), 'completed', $supply,
                $index % 3 === 0 ? $retail : null, $index % 2 === 0 ? 150 : 0, contact: $contact);

            $this->appointment($key.'-scheduled', 1, $client, $staff, $combinations[($index + 3) % 10],
                $today->copy()->addDays($index % 5)->setTime(9 + $index % 7, 30), 'scheduled',
                note: 'Demo upcoming appointment with multiple services. Client requested a relaxed visit.', contact: $contact);
            $this->appointment($key.'-request', 1, $client, $staff, [$services[0], $pedicure],
                $today->copy()->addDays(1 + $index % 7)->setTime(10 + $index % 6, 0), $index >= 8 ? 'declined' : 'pending',
                note: $index >= 8 ? 'Demo declined request: chosen time unavailable.' : 'Demo new request: please confirm the selected services.', contact: $contact);

            if ($index === 0) {
                // A second package example with six sessions and two different services on visit one.
                $this->appointment('expanded-contouring-course', 1, $client, $staff, [$catalog['contour'], $catalog['scrub']],
                    $month->copy()->setTime(10, 0), 'completed', $supply, contact: $contact);
                $this->appointment('expanded-contouring-course', 2, $client, $staff, [$catalog['contour']],
                    $month->copy()->addDays(min(7, max(0, $today->day - 1)))->setTime(10, 0), 'completed', $supply, contact: $contact);
                $this->appointment('expanded-contouring-course', 3, $client, $staff, [$catalog['contour']],
                    $today->copy()->addDays(2)->setTime(16, 0), 'scheduled', contact: $contact);
            }
        }
    }

    private function user(string $role, string $name, string $email): User
    {
        return User::firstOrCreate(['email' => $email], [
            'name' => $name, 'user_type' => $role, 'is_active' => true, 'password' => self::PASSWORD,
        ]);
    }

    private function service(string $name, string $category, int $price, int $minutes, int $sessions = 1, bool $consent = false): Service
    {
        return Service::firstOrCreate(['name' => 'Demo '.$name], [
            'category' => $category, 'price' => $price, 'duration' => $minutes, 'session_count' => $sessions,
            'description' => 'Fictional service for exploring the portal. Price is per session.', 'is_active' => true,
            'requires_consent' => $consent,
            'consent_content' => $consent ? "DEMO ONLY - Sample treatment consent for {$name}.\n\nThis fictional form demonstrates the signing screen. I have discussed the treatment, possible discomfort, aftercare, and my questions with the studio.\n\nReplace this sample wording before using a service with real clients." : null,
        ]);
    }

    private function product(User $staff, string $sku, string $name, string $type, int $price, int $stock, int $reorder): Product
    {
        $product = Product::firstOrCreate(['sku' => $sku], [
            'name' => $name, 'type' => $type, 'selling_price' => $price, 'cost_price' => 80,
            'stock_quantity' => $stock, 'reorder_level' => $reorder, 'is_active' => true,
        ]);
        if ($product->wasRecentlyCreated) {
            InventoryMovement::create(['product_id' => $product->id, 'recorded_by' => $staff->id,
                'quantity' => $stock, 'type' => 'restock', 'notes' => 'Demo opening stock']);
        }
        return $product;
    }

    private function appointment(string $key, int $session, User $client, User $staff, array $services, Carbon $date, string $status,
        ?Product $supply = null, ?Product $retail = null, int $discount = 0, ?string $note = null, string $contact = '09170000001'): Appointment
    {
        // Stable UUIDs keep repeat runs from creating duplicate visits or charges.
        $courseId = 'de000000-0000-4000-8000-'.substr(hash('sha256', $key), 0, 12);
        $appointment = Appointment::firstOrCreate(['treatment_course_id' => $courseId, 'session_number' => $session], [
            'user_id' => $client->id, 'service_id' => $services[0]->id,
            'staff_id' => $status === 'pending' ? null : $staff->id, 'scheduled_at' => $date->copy()->timezone(config('app.timezone')),
            'status' => $status, 'completed_at' => $status === 'completed' ? $date->copy()->addMinutes(90)->timezone(config('app.timezone')) : null,
            'contact_number' => $contact, 'notes' => $note ?? 'Demo scenario: '.$key.'. Fictional client and appointment.',
        ]);
        if (!$appointment->wasRecentlyCreated) { return $appointment; }
        $appointment->services()->sync(collect($services)->mapWithKeys(fn ($service) => [$service->id => ['session_count' => $service->session_count]])->all());
        if ($status !== 'completed') { return $appointment; }

        $subtotal = collect($services)->sum(fn ($service) => (int) round((float) $service->price * 100));
        $subtotal += $retail ? (int) round((float) $retail->selling_price * 100) : 0;
        $payment = Payment::create([
            'appointment_id' => $appointment->id, 'recorded_by' => $staff->id, 'amount' => ($subtotal - $discount * 100) / 100,
            'discount_amount' => $discount, 'discount_reason' => $discount ? 'Demo loyalty reward' : null,
            'method' => $discount ? 'gcash' : 'cash', 'reference' => $discount ? 'DEMO-GCASH-001' : null,
            'paid_at' => $appointment->completed_at,
        ]);
        $payment->update(['receipt_number' => 'DEMO-'.$date->format('Ymd').'-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT)]);
        foreach ($services as $service) {
            $payment->items()->create(['description' => $service->name, 'quantity' => 1, 'unit_price' => $service->price]);
        }
        foreach ([[$supply, 0.25, 'treatment_usage'], [$retail, 1, 'sale']] as [$product, $quantity, $type]) {
            if (!$product) { continue; }
            $product->decrement('stock_quantity', $quantity);
            InventoryMovement::create(['product_id' => $product->id, 'appointment_id' => $appointment->id,
                'recorded_by' => $staff->id, 'quantity' => -$quantity, 'type' => $type, 'notes' => 'Demo usage / '.$payment->receipt_number]);
            if ($type === 'sale') {
                $payment->items()->create(['product_id' => $product->id, 'description' => $product->name, 'quantity' => $quantity, 'unit_price' => $product->selling_price]);
            }
        }
        if ($consent = $appointment->consent_service) {
            ConsentForm::create([
                'appointment_id' => $appointment->id, 'user_id' => $client->id, 'service_id' => $consent->id,
                'recorded_by' => $staff->id, 'signed_name' => $client->name, 'service_name' => $consent->name,
                'signed_at' => $date->copy()->subMinutes(10)->timezone(config('app.timezone')), 'content_snapshot' => $consent->consent_content,
                'content_hash' => hash('sha256', $consent->name."\n".$consent->consent_content),
                'signature_strokes' => [[[20, 60], [80, 100], [130, 40], [200, 90]]],
                'notes' => 'DEMO ONLY - Fictional consent and sample drawing; not a client signature.',
            ]);
        }
        return $appointment;
    }
}
