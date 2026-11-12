<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('openwa:webhook', function () {
    $secret=(string)config('services.openwa.webhook_secret');$url=config('services.openwa.webhook_url');
    if(strlen($secret)<32 || !filter_var($url,FILTER_VALIDATE_URL) || (app()->environment('production') && parse_url($url,PHP_URL_SCHEME)!=='https')) {$this->error('Configura OPENWA_WEBHOOK_SECRET (32 caracteres mínimo) y OPENWA_WEBHOOK_URL (HTTPS en producción).');return 1;}
    try {
        $wa=app(\App\Services\OpenWaService::class);$sessions=$wa->sesiones();
        if(!$sessions){$this->error('No hay sesiones registradas para este sistema.');return 1;}
        foreach($sessions as $s){
            \Illuminate\Support\Facades\DB::table('openwa_webhook_sessions')->updateOrInsert(['session_id'=>$s['id']],['registered_at'=>now()]);
            $wa->registrarWebhook($s['id']);
            $prueba=$wa->probarWebhook($s['id']);
            if(!($prueba['success']??false)){$this->error('Webhook registrado pero el receptor no respondió correctamente. Estado HTTP: '.($prueba['statusCode']??'sin respuesta'));return 1;}
        }
        $this->info('Webhook registrado para las sesiones de este sistema.');return 0;
    }catch(\App\Exceptions\OpenWaException $e){$this->error($e->getMessage());return 1;}
})->purpose('Registrar el receptor firmado de conversaciones en OpenWA');
