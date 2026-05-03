<?php

namespace App\Models;

use App\Events\JobStatusChanged;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'inspection_jobs';

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    protected $fillable = [
        'client_id',
        'created_by_user_id',
        'job_number',
        'status',
        'bag_qr_code',
        'notes',
        'drop_off_at',
        'drop_off_signature_path',
        'drop_off_signed_by',
        'drop_off_ip',
        'return_at',
        'return_signature_path',
        'return_signed_by',
        'return_ip',
    ];

    protected function casts(): array
    {
        return [
            'drop_off_at' => 'datetime',
            'return_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Job $job): void {
            $year = now()->year;

            DB::table('inspection_job_sequences')->upsert(
                ['year' => $year, 'last_number' => 1],
                ['year'],
                ['last_number' => DB::raw('last_number + 1')]
            );

            $seq = DB::table('inspection_job_sequences')->where('year', $year)->value('last_number');
            $job->job_number = sprintf('JOB-%d-%04d', $year, $seq);
            $job->bag_qr_code = (string) Str::uuid();
        });

        static::created(function (Job $job): void {
            JobStatusChanged::dispatch($job, null, $job->status);
        });

        static::updated(function (Job $job): void {
            if ($job->wasChanged('status')) {
                JobStatusChanged::dispatch($job, $job->getOriginal('status'), $job->status);
            }
        });
    }

    /** Valid status transitions. */
    private const TRANSITIONS = [
        'draft' => ['open'],
        'open' => ['in_progress'],
        'in_progress' => ['complete'],
        'complete' => ['returned'],
        'returned' => [],
    ];

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    /** @return array{done: int, total: int, flagged: int} */
    public function inspectionProgress(): array
    {
        $kitItemIds = $this->kitItems()->pluck('kit_items.id');

        $total = $kitItemIds->count();
        $done = Inspection::whereIn('kit_item_id', $kitItemIds)
            ->where('inspection_job_id', $this->id)
            ->complete()
            ->count();
        $flagged = KitItem::whereIn('id', $kitItemIds)
            ->where('status', 'quarantined')
            ->count();

        return compact('done', 'total', 'flagged');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isComplete(): bool
    {
        return $this->status === 'complete';
    }

    public function isReturned(): bool
    {
        return $this->status === 'returned';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /** @return BelongsTo<Client, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return BelongsToMany<KitItem, $this> */
    public function kitItems(): BelongsToMany
    {
        return $this->belongsToMany(KitItem::class, 'inspection_job_kit_items', 'inspection_job_id', 'kit_item_id')
            ->withPivot('condition_notes')
            ->withTimestamps();
    }

    /** @return HasMany<Inspection, $this> */
    public function inspections(): HasMany
    {
        return $this->hasMany(Inspection::class, 'inspection_job_id');
    }

    /** @return HasMany<JobPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(JobPhoto::class, 'inspection_job_id');
    }
}
