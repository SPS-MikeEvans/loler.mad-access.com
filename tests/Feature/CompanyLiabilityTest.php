<?php

use App\Models\Client;
use App\Models\CompanyLiability;
use App\Models\Inspection;
use App\Models\InspectionCheck;
use App\Models\Invoice;
use App\Models\KitItem;
use App\Models\KitType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('allows an admin to open the liabilities editor', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('liabilities.edit'));

    $response->assertOk();
    $response->assertSee('Company Liabilities &amp; Insurance', false);
});

it('forbids non-admin users from the liabilities editor', function () {
    $inspector = User::factory()->create(['role' => 'inspector']);

    $response = $this->actingAs($inspector)->get(route('liabilities.edit'));

    $response->assertForbidden();
});

it('shows the public liabilities page without authentication', function () {
    CompanyLiability::current()->update([
        'terms_and_conditions' => "Line one\nLine two",
        'insurances' => [[
            'name' => 'Public Liability',
            'insurer' => 'Aviva',
            'policy_number' => 'PL-123',
            'expiry' => '2027-03-31',
            'limit' => '10000000.00',
            'certificate_path' => null,
        ]],
    ]);

    $response = $this->get(route('liabilities.public'));

    $response->assertOk();
    $response->assertSee('Liabilities &amp; Insurance', false);
    $response->assertSee('Public Liability');
    $response->assertSee('Line one', false);
});

it('saves terms and structured insurance rows', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->put(route('liabilities.update'), [
        'terms_and_conditions' => "Terms line 1\nTerms line 2",
        'insurances' => [
            [
                'name' => 'Public Liability',
                'insurer' => 'Aviva',
                'policy_number' => 'PL-123456',
                'expiry' => '2027-03-31',
                'limit' => '10000000',
            ],
            [
                'name' => 'Employers Liability',
                'insurer' => 'Zurich',
                'policy_number' => 'EL-654321',
                'expiry' => '2027-04-30',
                'limit' => '5000000',
            ],
        ],
    ]);

    $response->assertRedirect(route('liabilities.edit'));

    $liability = CompanyLiability::current();

    expect($liability->terms_and_conditions)->toBe("Terms line 1\nTerms line 2");
    expect($liability->insurances)->toHaveCount(2);
    expect($liability->insurances[0])->toMatchArray([
        'name' => 'Public Liability',
        'insurer' => 'Aviva',
        'policy_number' => 'PL-123456',
        'expiry' => '2027-03-31',
        'limit' => '10000000.00',
        'certificate_path' => null,
    ]);
});

it('uploads certificate PDFs and stores their paths', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    $file = UploadedFile::fake()->create('public-liability.pdf', 120, 'application/pdf');

    $this->actingAs($admin)->put(route('liabilities.update'), [
        'terms_and_conditions' => 'Uploaded',
        'insurances' => [
            [
                'name' => 'Public Liability',
                'insurer' => 'Aviva',
                'policy_number' => 'PL-123456',
                'expiry' => '2027-03-31',
                'limit' => '10000000',
                'certificate' => $file,
            ],
        ],
    ])->assertRedirect(route('liabilities.edit'));

    $liability = CompanyLiability::current();
    $path = $liability->insurances[0]['certificate_path'];

    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);
});

it('replaces and removes certificate PDFs while cleaning up old files', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    Storage::disk('public')->put('insurance-certificates/original.pdf', 'original');

    CompanyLiability::current()->update([
        'terms_and_conditions' => 'Existing',
        'insurances' => [[
            'name' => 'Public Liability',
            'insurer' => 'Aviva',
            'policy_number' => 'PL-123456',
            'expiry' => '2027-03-31',
            'limit' => '10000000.00',
            'certificate_path' => 'insurance-certificates/original.pdf',
        ]],
    ]);

    $replacement = UploadedFile::fake()->create('replacement.pdf', 100, 'application/pdf');

    $this->actingAs($admin)->put(route('liabilities.update'), [
        'terms_and_conditions' => 'Existing',
        'insurances' => [
            [
                'name' => 'Public Liability',
                'insurer' => 'Aviva',
                'policy_number' => 'PL-123456',
                'expiry' => '2027-03-31',
                'limit' => '10000000',
                'existing_certificate_path' => 'insurance-certificates/original.pdf',
                'certificate' => $replacement,
            ],
        ],
    ])->assertRedirect(route('liabilities.edit'));

    $replacedPath = CompanyLiability::current()->fresh()->insurances[0]['certificate_path'];

    expect($replacedPath)->not->toBe('insurance-certificates/original.pdf');
    Storage::disk('public')->assertMissing('insurance-certificates/original.pdf');
    Storage::disk('public')->assertExists($replacedPath);

    $this->actingAs($admin)->put(route('liabilities.update'), [
        'terms_and_conditions' => 'Existing',
        'insurances' => [
            [
                'name' => 'Public Liability',
                'insurer' => 'Aviva',
                'policy_number' => 'PL-123456',
                'expiry' => '2027-03-31',
                'limit' => '10000000',
                'existing_certificate_path' => $replacedPath,
                'remove_certificate' => '1',
            ],
        ],
    ])->assertRedirect(route('liabilities.edit'));

    $insurance = CompanyLiability::current()->fresh()->insurances[0];

    expect($insurance['certificate_path'])->toBeNull();
    Storage::disk('public')->assertMissing($replacedPath);
});

it('discards empty insurance rows and deletes certificates from removed rows', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => 'admin']);
    Storage::disk('public')->put('insurance-certificates/to-remove.pdf', 'old');

    CompanyLiability::current()->update([
        'terms_and_conditions' => 'Existing',
        'insurances' => [[
            'name' => 'Old Policy',
            'insurer' => 'Old Insurer',
            'policy_number' => 'OLD-1',
            'expiry' => '2027-03-31',
            'limit' => '100.00',
            'certificate_path' => 'insurance-certificates/to-remove.pdf',
        ]],
    ]);

    $this->actingAs($admin)->put(route('liabilities.update'), [
        'terms_and_conditions' => 'Updated',
        'insurances' => [
            [
                'name' => '',
                'insurer' => '',
                'policy_number' => '',
                'expiry' => '',
                'limit' => '',
            ],
        ],
    ])->assertRedirect(route('liabilities.edit'));

    $liability = CompanyLiability::current()->fresh();

    expect($liability->insurances)->toBeNull();
    Storage::disk('public')->assertMissing('insurance-certificates/to-remove.pdf');
});

it('renders the liabilities footer content in the inspection pdf view', function () {
    $inspection = createInspectionGraph();

    $html = view('pdf.inspection-report', [
        'inspection' => $inspection->load(['kitItem.kitType', 'kitItem.client', 'inspector', 'checks.photos']),
        'company_name' => config('company.name'),
        'report_date' => now()->format('d F Y'),
        'report_no' => str_pad((string) $inspection->id, 6, '0', STR_PAD_LEFT),
        'verdict' => 'SAFE FOR CONTINUED USE',
    ])->render();

    expect($html)->toContain(route('liabilities.public'));
    expect($html)->toContain('current insurance arrangements');
});

it('renders the liabilities footer content in the invoice pdf view', function () {
    $invoice = createInvoiceGraph();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->load(['client', 'inspections.kitItem.kitType', 'inspections.inspector']),
        'company_name' => config('company.name'),
        'company' => config('company'),
    ])->render();

    expect($html)->toContain(route('liabilities.public'));
    expect($html)->toContain('current insurance arrangements');
});

function createInspectionGraph(): Inspection
{
    $client = Client::create([
        'name' => 'Acme Access Ltd',
        'address' => '1 High Street',
        'contact_email' => 'client@example.com',
        'phone' => '01234567890',
    ]);

    $inspector = User::factory()->create([
        'role' => 'admin',
        'competent_person_flag' => true,
    ]);

    $kitType = KitType::create([
        'name' => 'Harness',
        'interval_months' => 6,
    ]);

    $kitItem = KitItem::create([
        'client_id' => $client->id,
        'kit_type_id' => $kitType->id,
        'asset_tag' => 'KIT-100',
        'manufacturer' => 'Petzl',
        'model' => 'Volt',
        'serial_no' => 'SN-100',
        'status' => 'in_service',
    ]);

    $inspection = Inspection::create([
        'kit_item_id' => $kitItem->id,
        'inspector_user_id' => $inspector->id,
        'status' => 'complete',
        'inspection_date' => '2026-03-19',
        'next_due_date' => '2026-09-19',
        'overall_status' => 'pass',
    ]);

    InspectionCheck::create([
        'inspection_id' => $inspection->id,
        'check_category' => 'Visual',
        'check_text' => 'Webbing and stitching in good condition',
        'status' => 'pass',
        'notes' => 'All good',
    ]);

    return $inspection;
}

function createInvoiceGraph(): Invoice
{
    $inspection = createInspectionGraph();

    $invoice = Invoice::create([
        'client_id' => $inspection->kitItem->client->id,
        'invoice_number' => 'INV-2026-001',
        'issued_date' => '2026-03-19',
        'period_from' => '2026-03-01',
        'period_to' => '2026-03-31',
        'notes' => 'Payment due within 30 days.',
        'total_amount' => '125.00',
    ]);

    $inspection->update([
        'invoice_id' => $invoice->id,
        'cost' => '125.00',
    ]);

    return $invoice;
}
