<?php

namespace Database\Seeders;

use App\Enums\MilkType;
use App\Models\BillCycle;
use App\Models\Deduction;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use App\Models\MilkRate;
use App\Models\RateHistory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin User ──
        User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@dairyapp.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
        ]);

        User::create([
            'name'     => 'Operator',
            'email'    => 'operator@dairyapp.com',
            'password' => Hash::make('password'),
            'is_admin' => false,
        ]);

        // ── Milk Rates ──
        MilkRate::create([
            'milk_type' => 'cow', 'rate_per_litre' => 42.00,
            'fat_min' => 3.5, 'fat_max' => 5.0, 'snf_min' => 8.0, 'snf_max' => 8.8,
            'updated_by' => 1,
        ]);
        MilkRate::create([
            'milk_type' => 'buffalo', 'rate_per_litre' => 38.00,
            'fat_min' => 6.0, 'fat_max' => 8.5, 'snf_min' => 8.5, 'snf_max' => 9.5,
            'updated_by' => 1,
        ]);
        MilkRate::create([
            'milk_type' => 'a2', 'rate_per_litre' => 55.00,
            'fat_min' => 4.0, 'fat_max' => 5.5, 'snf_min' => 8.2, 'snf_max' => 9.0,
            'updated_by' => 1,
        ]);

        // ── Sample Farmers ──
        $farmerData = [
            ['name' => 'Ramesh Kumar',    'phone' => '9876543210', 'village' => 'Rampur',     'milk_type' => 'cow'],
            ['name' => 'Suresh Yadav',    'phone' => '9876543211', 'village' => 'Najibabad',  'milk_type' => 'buffalo'],
            ['name' => 'Mahesh Pal',      'phone' => '9876543212', 'village' => 'Bijnor',     'milk_type' => 'a2'],
            ['name' => 'Om Prakash',      'phone' => '9876543213', 'village' => 'Moradabad',  'milk_type' => 'cow'],
            ['name' => 'Ajay Singh',      'phone' => '9876543214', 'village' => 'Rampur',     'milk_type' => 'buffalo'],
            ['name' => 'Vikram Singh',    'phone' => '9876543215', 'village' => 'Bijnor',     'milk_type' => 'cow'],
            ['name' => 'Rajendra Pal',    'phone' => '9876543216', 'village' => 'Najibabad',  'milk_type' => 'buffalo'],
            ['name' => 'Kamal Yadav',     'phone' => '9876543217', 'village' => 'Rampur',     'milk_type' => 'a2'],
            ['name' => 'Deepak Sharma',   'phone' => '9876543218', 'village' => 'Moradabad',  'milk_type' => 'cow'],
            ['name' => 'Pankaj Kumar',    'phone' => '9876543219', 'village' => 'Bijnor',     'milk_type' => 'buffalo'],
            ['name' => 'Mohan Lal',       'phone' => '9876543220', 'village' => 'Rampur',     'milk_type' => 'cow'],
            ['name' => 'Ravi Verma',      'phone' => '9876543221', 'village' => 'Najibabad',  'milk_type' => 'a2'],
            ['name' => 'Sunil Pal',       'phone' => '9876543222', 'village' => 'Bijnor',     'milk_type' => 'buffalo'],
            ['name' => 'Anil Kumar',      'phone' => '9876543223', 'village' => 'Moradabad',  'milk_type' => 'cow'],
            ['name' => 'Pradeep Yadav',   'phone' => '9876543224', 'village' => 'Rampur',     'milk_type' => 'buffalo'],
        ];

        foreach ($farmerData as $fd) {
            $fd['status'] = 'active';
            Farmer::create($fd);
        }

        // One inactive farmer
        Farmer::create([
            'name' => 'Chandra Pal', 'phone' => '9876543999',
            'village' => 'Moradabad', 'milk_type' => 'cow', 'status' => 'inactive',
        ]);

        // ── Bill Cycle ──
        $cycle = BillCycle::create([
            'name'     => 'Jul 2026 — 1st Half',
            'start_date' => '2026-07-01',
            'end_date'   => '2026-07-15',
            'cycle_type' => '15_days',
            'auto_generate_invoice' => true,
            'sms_on_invoice' => true,
            'status' => 'active',
        ]);

        // ── Milk Collections (last 15 days) ──
        $farmers = Farmer::where('status', 'active')->get();
        $sessions = ['morning', 'evening'];
        $typeRates = ['cow' => 42, 'buffalo' => 38, 'a2' => 55];

        for ($day = 14; $day >= 0; $day--) {
            $date = now()->subDays($day)->toDateString();
            foreach ($farmers as $farmer) {
                foreach ($sessions as $session) {
                    $rate = $typeRates[$farmer->milk_type->value];
                    $qty = round(rand(5, 25) + (rand(0, 9) / 10), 1);
                    $amount = round($qty * $rate, 2);

                    MilkCollection::create([
                        'farmer_id'      => $farmer->id,
                        'collection_date' => $date,
                        'session'        => $session,
                        'milk_type'      => $farmer->milk_type->value,
                        'fat_percent'    => round(rand(30, 80) / 10, 1),
                        'snf_percent'    => round(rand(80, 95) / 10, 1),
                        'quantity'       => $qty,
                        'rate_per_litre' => $rate,
                        'amount'         => $amount,
                        'sms_sent'       => rand(0, 1),
                        'recorded_by'    => 1,
                    ]);
                }
            }
        }

        // ── Sample Deductions ──
        $deductionTypes = ['Feed Cost', 'Advance Payment', 'Loan EMI', 'Penalty'];
        $deductionData = [
            ['farmer_id' => 1, 'type' => 'Feed Cost',       'amount' => 800],
            ['farmer_id' => 2, 'type' => 'Advance Payment', 'amount' => 2000],
            ['farmer_id' => 3, 'type' => 'Loan EMI',        'amount' => 1500],
            ['farmer_id' => 4, 'type' => 'Penalty',         'amount' => 300],
            ['farmer_id' => 5, 'type' => 'Feed Cost',       'amount' => 1200],
        ];

        foreach ($deductionData as $dd) {
            Deduction::create([
                'farmer_id'      => $dd['farmer_id'],
                'bill_cycle_id'  => $cycle->id,
                'deduction_type' => $dd['type'],
                'amount'         => $dd['amount'],
                'deduction_date' => now()->subDays(rand(0, 14))->toDateString(),
                'status'         => $dd['farmer_id'] === 4 ? 'pending' : 'applied',
                'created_by'     => 1,
            ]);
        }

        // ── Sample Invoices ──
        foreach (range(1, 5) as $i) {
            $farmer = Farmer::find($i);
            $collections = MilkCollection::where('farmer_id', $i)
                ->whereBetween('collection_date', [$cycle->start_date, $cycle->end_date])
                ->get();
            $totalLitres = $collections->sum('quantity');
            $gross = $collections->sum('amount');
            $deductions = Deduction::where('farmer_id', $i)
                ->where('bill_cycle_id', $cycle->id)
                ->where('status', 'applied')
                ->sum('amount');

            $statuses = ['sent', 'sent', 'pending', 'draft', 'sent'];
            Invoice::create([
                'farmer_id'        => $i,
                'bill_cycle_id'    => $cycle->id,
                'period_start'     => $cycle->start_date,
                'period_end'       => $cycle->end_date,
                'total_litres'     => $totalLitres,
                'gross_amount'     => $gross,
                'total_deductions' => $deductions,
                'net_amount'       => $gross - $deductions,
                'status'           => $statuses[$i - 1],
                'created_by'       => 1,
            ]);
        }

        // ── Rate History ──
        RateHistory::create([
            'milk_type' => 'cow', 'old_rate' => 40.00, 'new_rate' => 42.00, 'changed_by' => 1,
        ]);
        RateHistory::create([
            'milk_type' => 'buffalo', 'old_rate' => 36.00, 'new_rate' => 38.00, 'changed_by' => 1,
        ]);

        $this->command->info('Database seeded successfully!');
        $this->command->info('Login: admin@dairyapp.com / password');
    }
}
