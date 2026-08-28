<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Run this script from the command line.\n");
}

require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Services\NotificationService;
use App\Services\SubscriptionService;

$expiring = SubscriptionService::expiringSoon(7);
foreach ($expiring as $subscription) {
    NotificationService::create(
        'business',
        'subscription_expiring',
        'Subscription expiring soon',
        'Your subscription expires on ' . $subscription['expires_at'] . '. Please contact the platform admin to renew.',
        (int) $subscription['business_id'],
        null,
        'subscription',
        (int) $subscription['id']
    );
}

echo 'Created ' . count($expiring) . " renewal reminder notification(s).\n";
