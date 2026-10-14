<?php

namespace Tests\Feature;

use App\Models\BillCycle;
use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use App\Models\MilkRate;
use App\Models\User;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function cycle(array $attributes = []): BillCycle
    {
        return BillCycle::create($attributes + ['name' => 'October', 'start_date' => '2026-10-01', 'end_date' => '2026-10-15', 'cycle_type' => '15_days', 'status' => 'active']);
    }

    private function farmer(): Farmer
    {
        return Farmer::create(['name' => '<img src=x>', 'phone' => '9876543210', 'milk_type' => 'cow', 'status' => 'inactive']);
    }

    private function collection(Farmer $farmer): MilkCollection
    {
        return MilkCollection::create(['farmer_id' => $farmer->id, 'collection_date' => '2026-10-05', 'session' => 'morning', 'milk_type' => 'cow', 'quantity' => 10, 'rate_per_litre' => 40, 'amount' => 400]);
    }

    public function test_generation_includes_inactive_farmers_and_preserves_paid_totals(): void
    {
        $cycle = $this->cycle();
        $farmer = $this->farmer();
        $collection = $this->collection($farmer);
        $billing = app(InvoiceService::class);
        $this->assertSame(1, $billing->generate($cycle));
        $invoice = Invoice::firstOrFail();
        $this->assertSame('400.00', $invoice->net_amount);
        $invoice->update(['status' => 'paid']);
        $collection->update(['amount' => 800]);
        $this->assertSame(0, $billing->generate($cycle));
        $this->assertSame('400.00', $invoice->fresh()->net_amount);
    }

    public function test_operator_cannot_change_rates_or_payment_status(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => false]));
        $this->putJson('/api/milk-rates', [])->assertForbidden();
        $this->postJson('/api/bill-cycles', [])->assertForbidden();
    }

    public function test_overlapping_cycles_are_rejected_without_closing_current_cycle(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $cycle = $this->cycle();
        $this->postJson('/api/bill-cycles', ['cycle_type' => 'custom', 'start_date' => '2026-10-15', 'end_date' => '2026-10-20'])->assertUnprocessable();
        $this->assertSame('active', $cycle->fresh()->status);
    }

    public function test_retries_are_idempotent_and_multiple_deliveries_are_allowed(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $farmer = $this->farmer();
        MilkRate::create(['milk_type' => 'cow', 'rate_per_litre' => 40, 'fat_min' => 3, 'fat_max' => 5, 'snf_min' => 8, 'snf_max' => 9]);
        $entry = ['request_id' => (string) Str::uuid(), 'farmer_id' => $farmer->id, 'collection_date' => '2026-10-05', 'session' => 'morning', 'quantity' => 10, 'fat_percent' => 4, 'snf_percent' => 8.5];
        $this->postJson('/api/milk-collection', $entry)->assertCreated();
        $this->postJson('/api/milk-collection', $entry)->assertOk();
        $this->assertDatabaseCount('milk_collections', 1);
        $entry['request_id'] = (string) Str::uuid();
        $this->postJson('/api/milk-collection', $entry)->assertCreated();
        $this->assertDatabaseCount('milk_collections', 2);
    }

    public function test_explicit_empty_cycle_filter_returns_all_cycles(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $farmer = $this->farmer();
        $first = $this->cycle();
        $second = $this->cycle(['name' => 'Next', 'start_date' => '2026-10-16', 'end_date' => '2026-10-31', 'status' => 'completed']);
        foreach ([$first, $second] as $cycle) {
            Invoice::create(['farmer_id' => $farmer->id, 'bill_cycle_id' => $cycle->id, 'period_start' => $cycle->start_date, 'period_end' => $cycle->end_date]);
        }
        $this->getJson('/api/invoices?bill_cycle_id=')->assertOk()->assertJsonPath('data.invoices.total', 2);
    }

    public function test_finalized_invoice_rejects_deductions_and_reopening(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $cycle = $this->cycle();
        $farmer = $this->farmer();
        $this->collection($farmer);
        app(InvoiceService::class)->generate($cycle);
        $invoice = Invoice::firstOrFail();
        $this->patchJson("/api/invoices/{$invoice->id}/status", ['status' => 'paid'])->assertOk();
        $this->postJson('/api/deductions', ['farmer_id' => $farmer->id, 'bill_cycle_id' => $cycle->id, 'deduction_type' => 'Feed', 'amount' => 10, 'deduction_date' => '2026-10-05'])->assertUnprocessable();
        $this->patchJson("/api/invoices/{$invoice->id}/status", ['status' => 'pending'])->assertUnprocessable();
        $this->getJson("/api/invoices/{$invoice->id}")->assertOk()->assertJsonPath('data.invoice.net_amount', '400.00');
        $this->assertDatabaseCount('deductions', 0);
    }

    public function test_overlapping_rate_slabs_leave_existing_rates_unchanged(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_admin' => true]));
        $rate = ['milk_type' => 'cow', 'rate_per_litre' => 40, 'fat_min' => 3, 'fat_max' => 5, 'snf_min' => 8, 'snf_max' => 9];
        $existing = MilkRate::create($rate);
        $this->putJson('/api/milk-rates', ['rates' => [$rate, array_merge($rate, ['rate_per_litre' => 50])]])->assertUnprocessable();
        $this->assertDatabaseCount('milk_rates', 1);
        $this->assertSame('40.00', $existing->fresh()->rate_per_litre);
    }

    public function test_due_invoice_command_is_repeatable(): void
    {
        $this->travelTo(Carbon::parse('2026-10-16'));
        $this->cycle(['auto_generate_invoice' => true, 'sms_on_invoice' => false]);
        $this->collection($this->farmer());
        $this->artisan('billing:generate-due')->assertSuccessful();
        $this->artisan('billing:generate-due')->assertSuccessful();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertSame('400.00', Invoice::firstOrFail()->net_amount);
        $this->travelBack();
    }
}
