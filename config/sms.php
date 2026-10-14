<?php

return [
    'invoice_template_id' => env('MSG91_INVOICE_TEMPLATE_ID', ''),
    'gateway_url' => env('MSG91_FLOW_URL', env('SMS_GATEWAY_URL', 'https://control.msg91.com/api/v5/flow/')),
    'api_key' => env('MSG91_AUTHKEY', env('SMS_API_KEY', '')),
    'sender_id' => env('SMS_SENDER_ID', 'DAIRY'),
    'dlt_entity_id' => env('DLT_ENTITY_ID', ''),
    'dlt_template_id' => env('DLT_TEMPLATE_ID', ''),
    'collection_template_id' => env('MSG91_COLLECTION_TEMPLATE_ID', '6a43ce4b1f159b9584063193'),
    'dairy_name' => env('SMS_DAIRY_NAME', env('APP_NAME', 'Jay Malhar Dairy')),
    'collection_template' => 'Dear {name}, your milk collection of {qty}L ({type}) recorded on {date}. Amount: Rs.{amount}. - Jay Malhar Dairy',
];
