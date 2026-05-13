<?php

use App\Models\BusinessSetting;
use App\Models\Client;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\User;

it('shows the business settings page to admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('accounting.settings.edit'))
        ->assertOk()
        ->assertSee('Banking details for invoices');
});

it('forbids inspectors from viewing business settings', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $this->actingAs($inspector)
        ->get(route('accounting.settings.edit'))
        ->assertForbidden();
});

it('persists business settings on update and strips sort-code dashes', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('accounting.settings.update'), [
            'bank_name' => 'Tide',
            'account_holder' => 'Acme Ltd',
            'sort_code' => '12-34-56',
            'account_number' => '12345678',
            'iban' => null,
            'reference_instructions' => 'Use invoice number.',
            'payment_terms_days' => 30,
        ])
        ->assertRedirect(route('accounting.settings.edit'));

    $row = BusinessSetting::current();
    expect($row->bank_name)->toBe('Tide');
    expect($row->sort_code)->toBe('123456');
    expect($row->account_number)->toBe('12345678');
    expect($row->payment_terms_days)->toBe(30);
});

it('rejects invalid sort code formats', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('accounting.settings.update'), [
            'sort_code' => '12345',
            'account_number' => '12345678',
            'payment_terms_days' => 14,
        ])
        ->assertSessionHasErrors('sort_code');
});

it('rejects an account number that is not 8 digits', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put(route('accounting.settings.update'), [
            'sort_code' => '123456',
            'account_number' => '1234567',
            'payment_terms_days' => 14,
        ])
        ->assertSessionHasErrors('account_number');
});

it('includes a banking block on the invoice PDF when settings are populated', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    $settings = BusinessSetting::current();
    $settings->fill([
        'bank_name' => 'Tide',
        'account_holder' => 'Acme Ltd',
        'sort_code' => '123456',
        'account_number' => '12345678',
        'reference_instructions' => 'Use invoice number.',
        'payment_terms_days' => 14,
    ])->save();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->fresh(),
        'company_name' => 'MaD-ACCESS',
        'company' => config('company'),
        'businessSettings' => $settings,
    ])->render();

    expect($html)->toContain('Payment Instructions');
    expect($html)->toContain('Tide');
    expect($html)->toContain('12-34-56');
    expect($html)->toContain('12345678');
    expect($html)->toContain($invoice->invoice_number);
});

it('omits the banking block when settings are empty', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->create(['client_id' => $client->id]);

    $html = view('pdf.invoice', [
        'invoice' => $invoice->fresh(),
        'company_name' => 'MaD-ACCESS',
        'company' => config('company'),
        'businessSettings' => BusinessSetting::current(),
    ])->render();

    expect($html)->not->toContain('Payment Instructions');
});

it('hides the banking block and shows the PAID watermark when invoice is paid', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->paid()->create(['client_id' => $client->id]);

    $settings = BusinessSetting::current();
    $settings->fill([
        'bank_name' => 'Tide',
        'account_holder' => 'Acme Ltd',
        'sort_code' => '123456',
        'account_number' => '12345678',
        'payment_terms_days' => 14,
    ])->save();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->fresh(),
        'company_name' => 'MaD-ACCESS',
        'company' => config('company'),
        'businessSettings' => $settings,
    ])->render();

    expect($html)->not->toContain('Payment Instructions');
    expect($html)->toContain('paid-watermark');
    expect($html)->toContain('PAID');
});

it('hides the banking block when invoice is cancelled', function () {
    $client = Client::factory()->create();
    $invoice = Invoice::factory()->cancelled()->create(['client_id' => $client->id]);

    $settings = BusinessSetting::current();
    $settings->fill([
        'bank_name' => 'Tide',
        'account_holder' => 'Acme Ltd',
        'sort_code' => '123456',
        'account_number' => '12345678',
        'payment_terms_days' => 14,
    ])->save();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->fresh(),
        'company_name' => 'MaD-ACCESS',
        'company' => config('company'),
        'businessSettings' => $settings,
    ])->render();

    expect($html)->not->toContain('Payment Instructions');
});

it('runs the accounting:setup command idempotently', function () {
    $this->artisan('accounting:setup')->assertSuccessful();
    $this->artisan('accounting:setup')->assertSuccessful();

    expect(BusinessSetting::count())->toBe(1);
    expect(ExpenseCategory::count())->toBeGreaterThanOrEqual(6);
});
