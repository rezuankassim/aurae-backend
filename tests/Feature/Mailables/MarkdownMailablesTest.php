<?php

use App\Mail\Auth\WelcomeMail;
use App\Mail\Orders\OrderInvoiceMail;
use App\Mail\Orders\OrderReceivedAdminMail;
use App\Models\User;
use Lunar\Models\Order;

test('welcome mail renders successfully with markdown components', function () {
    $user = User::factory()->make([
        'name' => 'Welcome Tester',
        'email' => 'welcome@example.com',
    ]);

    $html = (new WelcomeMail($user))->render();

    expect($html)->toContain('Welcome to');
    expect($html)->toContain('Get Started');
});

test('affected mailables are configured to use markdown templates', function () {
    $user = User::factory()->make();
    $order = new Order;

    $welcomeContent = (new WelcomeMail($user))->content();
    expect($welcomeContent->markdown)->toBe('mail.auth.welcome');
    expect($welcomeContent->view)->toBeNull();

    $invoiceContent = (new OrderInvoiceMail($order))->content();
    expect($invoiceContent->markdown)->toBe('mail.orders.invoice');
    expect($invoiceContent->view)->toBeNull();

    $adminOrderContent = (new OrderReceivedAdminMail($order))->content();
    expect($adminOrderContent->markdown)->toBe('mail.orders.admin-order-received');
    expect($adminOrderContent->view)->toBeNull();
});
