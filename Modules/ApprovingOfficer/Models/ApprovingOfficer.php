<?php

declare(strict_types=1);

namespace Modules\ApprovingOfficer\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ApprovingOfficer extends Model
{
    public const MAX_SIGNATORIES = 10;
    public const MAX_ON_TICKET = 2;

    protected $fillable = ['location', 'signee1_name', 'signee1_title', 'signee2_name', 'signee2_title', 'updated_by'];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }

    public function signatories(): HasMany
    {
        return $this->hasMany(ApprovingOfficerSignatory::class)->orderBy('sort_order')->orderBy('id');
    }

    /** Signatories chosen to appear on the approved Trip Ticket (max 2, in order). */
    public function ticketSignatories(): Collection
    {
        return $this->signatories
            ->where('show_on_ticket', true)
            ->take(self::MAX_ON_TICKET)
            ->values();
    }

    /**
     * Replace the full signatory list. $rows = [['name'=>, 'title'=>, 'show_on_ticket'=>bool], ...]
     * Also mirrors the selected ones into the legacy signee1/signee2 columns so older code keeps working.
     */
    public function syncSignatories(array $rows, ?int $updatedBy = null): void
    {
        DB::transaction(function () use ($rows, $updatedBy): void {
            $this->signatories()->delete();

            foreach (array_values($rows) as $i => $row) {
                $this->signatories()->create([
                    'name' => $row['name'],
                    'title' => $row['title'] ?? null,
                    'show_on_ticket' => (bool) ($row['show_on_ticket'] ?? false),
                    'sort_order' => $i,
                ]);
            }

            $this->refreshLegacyColumns($updatedBy);
        });
    }

    /** Set which signatories (by id) appear on the Trip Ticket. */
    public function setTicketSelection(array $ids, ?int $updatedBy = null): void
    {
        DB::transaction(function () use ($ids, $updatedBy): void {
            $this->signatories()->update(['show_on_ticket' => false]);
            $this->signatories()->whereIn('id', $ids)->update(['show_on_ticket' => true]);
            $this->refreshLegacyColumns($updatedBy);
        });
    }

    private function refreshLegacyColumns(?int $updatedBy = null): void
    {
        $selected = $this->load('signatories')->ticketSignatories();
        $this->forceFill([
            'signee1_name' => $selected[0]->name ?? null,
            'signee1_title' => $selected[0]->title ?? null,
            'signee2_name' => $selected[1]->name ?? null,
            'signee2_title' => $selected[1]->title ?? null,
            'updated_by' => $updatedBy ?? $this->updated_by,
        ])->save();
    }

    /**
     * Helper for the Admin forms (quick 2-signatory entry).
     * Only touches the list when the submitted pair differs from what is currently on the ticket,
     * and keeps any other (unselected) signatories already saved for the office.
     */
    public function syncFromPair(?string $n1, ?string $t1, ?string $n2, ?string $t2, ?int $updatedBy = null): void
    {
        $current = $this->signatories()->get();
        $selected = $current->where('show_on_ticket', true)->take(self::MAX_ON_TICKET)->values();

        $new = [];
        foreach ([[$n1, $t1], [$n2, $t2]] as [$name, $title]) {
            if (filled($name)) {
                $new[] = ['name' => trim($name), 'title' => filled($title) ? trim($title) : null];
            }
        }

        $old = $selected->map(fn ($s) => ['name' => $s->name, 'title' => $s->title])->all();
        if ($new === $old && $current->isNotEmpty()) {
            return;
        }

        $rows = collect($new)->map(fn ($r) => $r + ['show_on_ticket' => true]);
        $others = $current->where('show_on_ticket', false)
            ->take(self::MAX_SIGNATORIES - count($new))
            ->map(fn ($s) => ['name' => $s->name, 'title' => $s->title, 'show_on_ticket' => false]);

        $this->syncSignatories($rows->concat($others)->all(), $updatedBy);
    }
}
