<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Mail;

echo "Mail config:\n";
echo "  MAILER: " . config('mail.default') . "\n";
echo "  HOST: " . config('mail.mailers.smtp.host') . "\n";
echo "  PORT: " . config('mail.mailers.smtp.port') . "\n";
echo "  SCHEME: " . config('mail.mailers.smtp.scheme') . "\n";
echo "  USERNAME: " . config('mail.mailers.smtp.username') . "\n";
echo "  FROM: " . config('mail.from.address') . "\n\n";

try {
    echo "Sending test email...\n";
    Mail::to('contact@asloobulhayat.com')->send(new OtpMail('123456'));
    echo "SUCCESS!\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
