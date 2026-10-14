<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\Invoice;
use App\Models\MilkCollection;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendInvoiceSMS(Invoice $invoice): bool
    {
        $farmer = $invoice->farmer;
        $log = SmsLog::create([
            'farmer_id' => $farmer->id,
            'phone' => $farmer->phone,
            'message' => "Invoice {$invoice->invoice_no}: Rs.{$invoice->net_amount}",
            'status' => 'pending',
        ]);
        try {
            $response = Http::timeout(20)->withHeaders(['authkey' => config('sms.api_key')])->post(config('sms.gateway_url'), [
                'template_id' => config('sms.invoice_template_id'),
                'mobiles' => $this->formatIndianMobile($farmer->phone),
                'var1' => $farmer->name,
                'var2' => $invoice->invoice_no,
                'var3' => (string) $invoice->net_amount,
            ]);
            $success = $response->successful() && $response->json('type') === 'success';
            $log->update(['status' => $success ? 'sent' : 'failed', 'gateway_response' => substr($response->body(), 0, 250)]);

            return $success;
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'gateway_response' => substr($e->getMessage(), 0, 250)]);

            return false;
        }
    }

    public function sendCollectionSMS(MilkCollection $collection, Farmer $farmer): bool
    {
        return $this->sendMilkSms($collection, $farmer);
    }

    public function sendMilkSms(MilkCollection $collection, Farmer $farmer): bool
    {
        $template = config('sms.collection_template', '');
        $message = str_replace(
            ['{name}', '{qty}', '{type}', '{date}', '{amount}'],
            [
                $farmer->name,
                $collection->quantity,
                $collection->milk_type,
                $collection->collection_date->format('d M Y'),
                $collection->amount,
            ],
            $template
        );

        $log = SmsLog::create([
            'farmer_id' => $farmer->id,
            'milk_collection_id' => $collection->id,
            'phone' => $farmer->phone,
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            $response = Http::withHeaders([
                'authkey' => config('sms.api_key'),
                'Content-Type' => 'application/json',
            ])->post(config('sms.gateway_url'), [
                'template_id' => config('sms.collection_template_id'),
                'mobiles' => $this->formatIndianMobile($farmer->phone),
                'var1' => $farmer->name,
                'var2' => optional($collection->collection_date)->format('d/m/Y') ?: now()->format('d/m/Y'),
                'var3' => optional($collection->created_at)->format('h:i A') ?: now()->format('h:i A'),
                'var4' => config('sms.dairy_name'),
                'var5' => (string) $collection->quantity,
                'var6' => (string) $collection->fat_percent,
                'var7' => (string) $collection->snf_percent,
                'var8' => (string) $collection->rate_per_litre,
                'var9' => (string) $collection->amount,
                'var10' => config('sms.dairy_name'),
            ]);

            $success = $response->successful();
            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'gateway_response' => $response->body(),
            ]);

            return $success;
        } catch (\Throwable $e) {
            Log::error('MSG91 SMS sending failed: '.$e->getMessage());
            $log->update([
                'status' => 'failed',
                'gateway_response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function send(string $phone, string $message, ?int $collectionId = null, ?int $farmerId = null): bool
    {
        $log = SmsLog::create([
            'farmer_id' => $farmerId,
            'milk_collection_id' => $collectionId,
            'phone' => $phone,
            'message' => $message,
            'status' => 'pending',
        ]);

        try {
            $response = Http::asForm()->post(config('sms.gateway_url'), [
                'apikey' => config('sms.api_key'),
                'sender' => config('sms.sender_id'),
                'number' => $phone,
                'message' => $message,
                'dlt_entity_id' => config('sms.dlt_entity_id'),
                'dlt_template_id' => config('sms.dlt_template_id'),
            ]);

            $success = $response->successful();
            $log->update([
                'status' => $success ? 'sent' : 'failed',
                'gateway_response' => $response->body(),
            ]);

            return $success;
        } catch (\Throwable $e) {
            Log::error('SMS sending failed: '.$e->getMessage());
            $log->update([
                'status' => 'failed',
                'gateway_response' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function formatIndianMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10) {
            return '91'.$digits;
        }

        return $digits;
    }
}
