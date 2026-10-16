@php
    $peso = fn ($v) => '₱' . number_format((float) $v, 2);
    $vehicleLabel = function ($r) {
        $v = $r->vehicle;
        if (! $v) return '—';
        $name = trim(($v->brand ?? '') . ' ' . ($v->model ?? ''));
        return $name !== '' ? "{$name}, {$v->plate_number}" : $v->plate_number;
    };
    $locSlug = $location ? \Illuminate\Support\Str::studly(\Illuminate\Support\Str::slug($location, ' ')) : 'AllOffices';
    $fileTitle = 'Trip_Report_' . $locSlug . '_' . $rangeStart->format('Y-m-d') . '_to_' . $rangeEnd->format('Y-m-d');
    $maxDest = max(1, collect($topDestinations['data'])->max() ?? 1);
    $maxVeh = max(1, collect($tripsPerVehicle['data'])->max() ?? 1);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fileTitle }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ebee; font-family: Calibri, 'Segoe UI', Arial, Helvetica, sans-serif; color: #1a1a1a; font-size: 11px; }
        .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 12px 20px; background: #fff; border-bottom: 1px solid #d9dde1; position: sticky; top: 0; z-index: 10; }
        .toolbar a { font-size: 13px; color: #5b6570; text-decoration: none; }
        .toolbar button { padding: 9px 16px; border-radius: 8px; border: none; background: #008751; color: #fff; font-weight: 700; font-size: 13.5px; cursor: pointer; }
        .sheet { width: 210mm; min-height: 297mm; margin: 24px auto; background: #fff; box-shadow: 0 4px 24px rgba(0,0,0,.12); position: relative; padding-bottom: 14mm; }
        .hdr { width: 100%; display: block; }
        .body { padding: 3mm 12mm 0; }
        h1 { text-align: center; font-size: 17px; letter-spacing: .75px; margin: 4px 0 2px; }
        .meta { text-align: center; font-size: 11px; color: #444; line-height: 1.4; }
        h2 { font-size: 12px; color: #005a36; margin: 14px 0 5px; border-bottom: 2px solid #005a36; padding-bottom: 2px; text-transform: uppercase; letter-spacing: .4px; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 10px; }
        .kpi { border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 9px; }
        .kpi b { display: block; font-size: 16px; color: #005a36; }
        .kpi span { font-size: 9.5px; color: #64748b; text-transform: uppercase; letter-spacing: .3px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #005a36; color: #fff; text-align: left; padding: 4px 6px; font-size: 10px; }
        td { padding: 3.5px 6px; border-bottom: 1px solid #e2e8f0; vertical-align: top; }
        tr { page-break-inside: avoid; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .r { text-align: right; }
        .bars { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .bar-row { display: flex; align-items: center; gap: 6px; margin-bottom: 3px; font-size: 10.5px; }
        .bar-row .lbl { width: 42%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .bar-row .track { flex: 1; height: 8px; background: #eef2f6; border-radius: 4px; overflow: hidden; }
        .bar-row .fill { height: 100%; background: #10b981; }
        .bar-row .num { width: 22px; text-align: right; font-weight: 700; }
        .empty { color: #94a3b8; font-style: italic; padding: 6px 0; }
        .foot { position: absolute; left: 12mm; right: 12mm; bottom: 6mm; font-size: 9px; color: #555; display: flex; justify-content: space-between; border-top: 1px solid #cbd5e1; padding-top: 3px; }
        /* On narrow screens the fixed-width A4 sheet pans inside this wrapper instead of scrolling the whole page */
        .sheet-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media screen and (max-width: 840px) { .sheet { margin: 12px; } }
        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .sheet { margin: 0 !important; box-shadow: none !important; width: 210mm; min-height: 0; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead { display: table-header-group; }
            .sheet-scroll { overflow: visible !important; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <a href="{{ route('reports.index', array_filter(['location' => $location, 'range' => $range, 'from' => request('from'), 'to' => request('to')])) }}">&larr; Back to Reports</a>
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet-scroll">
    <div class="sheet">
        <img class="hdr" src="{{ asset('trip-ticket-header.png') }}" alt="Department of Agriculture MIMAROPA Region">

        <div class="body">
            <h1>FLEET MANAGEMENT REPORT</h1>
            <div class="meta">
                <strong>Office:</strong> {{ $scopeLabel }} &nbsp;|&nbsp;
                <strong>Period:</strong> {{ $rangeStart->format('F j, Y') }} – {{ $rangeEnd->format('F j, Y') }}<br>
                Generated by {{ $generatedBy }} on {{ $generatedAt->format('F j, Y g:i A') }}
            </div>

            <div class="kpis">
                <div class="kpi"><b>{{ $summary['total'] }}</b><span>Total requests</span></div>
                <div class="kpi"><b>{{ $summary['approved'] }}</b><span>Approved trips{{ $summary['approval_rate'] !== null ? ' (' . $summary['approval_rate'] . '%)' : '' }}</span></div>
                <div class="kpi"><b>{{ $summary['pending'] }}</b><span>Pending</span></div>
                <div class="kpi"><b>{{ $summary['declined'] }}</b><span>Declined</span></div>
                <div class="kpi"><b>{{ $summary['fleet'] }}</b><span>Fleet vehicles</span></div>
                <div class="kpi"><b>{{ $peso($summary['maintenance_cost']) }}</b><span>Maintenance cost ({{ $summary['maintenance_done'] }} done)</span></div>
            </div>

            {{-- Super Admin, all offices only --}}
            @if(! empty($locationBreakdown))
                <h2>Office Comparison</h2>
                <table>
                    <thead><tr><th>Office</th><th class="r">Vehicles</th><th class="r">Requests</th><th class="r">Pending</th><th class="r">Approved</th><th class="r">Declined</th><th class="r">Approval</th><th class="r">Active Maint.</th><th class="r">Maint. Cost</th></tr></thead>
                    <tbody>
                        @foreach($locationBreakdown as $row)
                            <tr>
                                <td><strong>{{ $row['location'] }}</strong></td>
                                <td class="r">{{ $row['vehicles'] }}</td><td class="r">{{ $row['requests'] }}</td><td class="r">{{ $row['pending'] }}</td>
                                <td class="r">{{ $row['trips'] }}</td><td class="r">{{ $row['declined'] }}</td>
                                <td class="r">{{ $row['approval_rate'] === null ? '—' : $row['approval_rate'] . '%' }}</td>
                                <td class="r">{{ $row['maintenance_active'] }}</td><td class="r">{{ $peso($row['maintenance_cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            <div class="bars">
                <div>
                    <h2>Top Destinations</h2>
                    @forelse($topDestinations['labels'] as $i => $label)
                        <div class="bar-row"><div class="lbl" title="{{ $label }}">{{ $label }}</div><div class="track"><div class="fill" style="width:{{ round($topDestinations['data'][$i] / $maxDest * 100) }}%"></div></div><div class="num">{{ $topDestinations['data'][$i] }}</div></div>
                    @empty
                        <div class="empty">No destinations in this period.</div>
                    @endforelse
                </div>
                <div>
                    <h2>Trips per Vehicle</h2>
                    @forelse($tripsPerVehicle['labels'] as $i => $label)
                        <div class="bar-row"><div class="lbl">{{ $label }}</div><div class="track"><div class="fill" style="width:{{ round($tripsPerVehicle['data'][$i] / $maxVeh * 100) }}%;background:#8b5cf6"></div></div><div class="num">{{ $tripsPerVehicle['data'][$i] }}</div></div>
                    @empty
                        <div class="empty">No approved trips in this period.</div>
                    @endforelse
                </div>
            </div>

            <h2>Trip Log ({{ $tripLog->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Ticket No.</th><th>Travel Date</th><th>Requester</th>
                        @if($isSuperAdmin)<th>Office</th>@endif
                        <th>Vehicle</th><th>Destination</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tripLog as $r)
                        <tr>
                            <td>{{ $r->trip_ticket_number ?: '—' }}</td>
                            <td>{{ $r->travel_date?->format('M j, Y') }}{{ $r->travel_date_end && $r->travel_date_end->ne($r->travel_date) ? ' – ' . $r->travel_date_end->format('M j, Y') : '' }}</td>
                            <td>{{ $r->requester_name }}</td>
                            @if($isSuperAdmin)<td>{{ $r->location }}</td>@endif
                            <td>{{ $vehicleLabel($r) }}</td>
                            <td>{{ $r->destination }}</td>
                            <td style="text-transform:capitalize">{{ $r->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isSuperAdmin ? 7 : 6 }}" class="empty">No requests found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <h2>Completed Maintenance ({{ $maintenanceLog->count() }})</h2>
            <table>
                <thead><tr><th>Completed</th><th>Vehicle</th><th>Type</th><th>Title</th>@if($isSuperAdmin)<th>Office</th>@endif<th class="r">Cost</th></tr></thead>
                <tbody>
                    @forelse($maintenanceLog as $m)
                        <tr>
                            <td>{{ $m->completed_date?->format('M j, Y') }}</td>
                            <td>{{ trim(($m->vehicle_label ?: '') . ' ' . $m->plate_number) }}</td>
                            <td>{{ $m->type }}</td>
                            <td>{{ $m->title }}</td>
                            @if($isSuperAdmin)<td>{{ $m->location }}</td>@endif
                            <td class="r">{{ $peso($m->cost) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isSuperAdmin ? 6 : 5 }}" class="empty">No completed maintenance in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="foot">
            <span>Doc. No.: DAMIMAROPA-F083-2023 &middot; Rev. No.: 0 &middot; Issued Date: 10/23/23</span>
            <span>{{ $scopeLabel }}</span>
        </div>
    </div>
    </div>
</body>
</html>
