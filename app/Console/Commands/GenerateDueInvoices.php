<?php

namespace App\Console\Commands;

use App\Models\BillCycle;
use App\Services\InvoiceService;
use App\Services\SmsService;
use Illuminate\Console\Command;

class GenerateDueInvoices extends Command
{
    protected $signature = 'billing:generate-due';

    protected $description = 'Generate invoices for ended cycles and send configured invoice notifications';

    public function handle(InvoiceService $billing, SmsService $sms): int
    {
        foreach (BillCycle::where('auto_generate_invoice', true)->whereDate('end_date', '<', today())->get() as $cycle) {
            $billing->generate($cycle);
            if (! $cycle->sms_on_invoice || ! config('sms.invoice_template_id') || ! config('sms.api_key')) {
                continue;
            }
            foreach ($cycle->invoices()->where('status', 'pending')->with('farmer')->get() as $invoice) {
                if ($invoice->farmer && $sms->sendInvoiceSMS($invoice)) {
                    $invoice->update(['status' => 'sent', 'sent_date' => today()]);
                }
            }
        }

        return self::SUCCESS;
    }
}
