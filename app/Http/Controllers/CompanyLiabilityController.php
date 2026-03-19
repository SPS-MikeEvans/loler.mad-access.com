<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyLiabilityRequest;
use App\Models\CompanyLiability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyLiabilityController extends Controller
{
    public function edit(): View
    {
        $liability = CompanyLiability::current();

        return view('liabilities.edit', compact('liability'));
    }

    public function update(UpdateCompanyLiabilityRequest $request): RedirectResponse
    {
        $liability = CompanyLiability::current();
        $validated = $request->validated();
        $existingCertificatePaths = collect($liability->insurances ?? [])
            ->pluck('certificate_path')
            ->filter()
            ->values();
        $insurances = $this->prepareInsurances($validated['insurances'] ?? []);
        $newCertificatePaths = collect($insurances)
            ->pluck('certificate_path')
            ->filter()
            ->values();

        $existingCertificatePaths
            ->diff($newCertificatePaths)
            ->each(fn (string $path) => Storage::disk('public')->delete($path));

        $liability->update([
            'terms_and_conditions' => $validated['terms_and_conditions'] ?? null,
            'insurances' => $insurances ?: null,
        ]);

        return redirect()->route('liabilities.edit')
            ->with('success', 'Liabilities updated successfully.');
    }

    public function showPublic(): View
    {
        $liability = CompanyLiability::current();

        return view('liabilities.public', compact('liability'));
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array{name: string, insurer: string, policy_number: string, expiry: ?string, limit: ?string, certificate_path: ?string}>
     */
    private function prepareInsurances(array $rows): array
    {
        return collect($rows)
            ->map(function (array $row): ?array {
                $name = trim((string) ($row['name'] ?? ''));
                $insurer = trim((string) ($row['insurer'] ?? ''));
                $policyNumber = trim((string) ($row['policy_number'] ?? ''));
                $expiry = ! empty($row['expiry']) ? (string) $row['expiry'] : null;
                $limit = $this->normalizeLimit($row['limit'] ?? null);
                $existingCertificatePath = ! empty($row['existing_certificate_path'])
                    ? (string) $row['existing_certificate_path']
                    : null;
                $removeCertificate = filter_var($row['remove_certificate'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $uploadedCertificate = $row['certificate'] ?? null;

                $hasContent = $name !== ''
                    || $insurer !== ''
                    || $policyNumber !== ''
                    || $expiry !== null
                    || $limit !== null
                    || $uploadedCertificate !== null
                    || ($existingCertificatePath !== null && ! $removeCertificate);

                if (! $hasContent) {
                    if ($existingCertificatePath) {
                        Storage::disk('public')->delete($existingCertificatePath);
                    }

                    return null;
                }

                $certificatePath = $existingCertificatePath;

                if ($uploadedCertificate) {
                    if ($existingCertificatePath) {
                        Storage::disk('public')->delete($existingCertificatePath);
                    }

                    $certificatePath = $uploadedCertificate->store('insurance-certificates', 'public');
                } elseif ($removeCertificate) {
                    if ($existingCertificatePath) {
                        Storage::disk('public')->delete($existingCertificatePath);
                    }

                    $certificatePath = null;
                }

                return [
                    'name' => $name,
                    'insurer' => $insurer,
                    'policy_number' => $policyNumber,
                    'expiry' => $expiry,
                    'limit' => $limit,
                    'certificate_path' => $certificatePath,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeLimit(mixed $limit): ?string
    {
        if ($limit === null || $limit === '') {
            return null;
        }

        return number_format((float) $limit, 2, '.', '');
    }
}
