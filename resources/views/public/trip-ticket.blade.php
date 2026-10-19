@php
    $longDate = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('F j, Y') : '';

    // An imported trip may name a driver who has no account yet
    $driverName = $requestRecord->confirmedDriver?->name ?? $requestRecord->driver_name ?? '';

    $vehicleLabel = trim(($requestRecord->vehicle?->brand ?? '') . ' ' . ($requestRecord->vehicle?->model ?? ''));
    $plateLabel = $vehicleLabel !== ''
        ? $vehicleLabel . ', ' . $requestRecord->vehicle?->plate_number
        : ($requestRecord->vehicle?->plate_number ?? '');

    $passengerNames = collect($requestRecord->requester_is_passenger ? [$requestRecord->requester_name] : [])
        ->merge($requestRecord->passengers ?? [])
        ->filter()
        ->values();
    $passengersText = $passengerNames->implode(', ');

    $start = $longDate($requestRecord->travel_date);
    $end = $longDate($requestRecord->travel_date_end);
    $duration = ($end && $end !== $start) ? "{$start} to {$end}" : $start;

    // $signatories is resolved in the controller (max 2, the ones ticked on the Approving Officers page)
    $signatories = $signatories ?? collect();

    // Part B as the driver logged it (blank lines until the completion is recorded)
    $partB = $requestRecord->completion;
    $num = fn ($v) => ($partB === null || $v === null) ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    // A speedometer reading of 0 means none was recorded (e.g. imported trips with only one reading)
    $reading = fn ($v) => (float) $v > 0 ? $num($v) : '';
    // Times are typed freely ("7:30 AM"); keep the printed a.m./p.m. hint only while the line is empty
    $time = fn (?string $v) => [$v ?? '', filled($v) && preg_match('/[ap]\.?\s*m\.?/i', $v) ? '' : 'a.m./p.m.'];@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Trip_Ticket_{{ $requestRecord->trip_ticket_number }}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #e9ebee; font-family: Calibri, 'Segoe UI', Arial, Helvetica, sans-serif; color: #1a1a1a; }
        .toolbar { display: flex; align-items: center; justify-content: flex-end; padding: 12px 20px; background: #fff; border-bottom: 1px solid #d9dde1; position: sticky; top: 0; z-index: 10; }
        .toolbar a { font-size: 13px; color: #5b6570; text-decoration: none; }
        .toolbar button { padding: 9px 16px; border-radius: 8px; border: none; background: #008751; color: #fff; font-weight: 700; font-size: 13.5px; cursor: pointer; }
        .sheet { max-width: 210mm; min-height: 297mm; margin: 24px auto; background: #fff; font-size: 12px; line-height: 1.22; box-shadow: 0 4px 24px rgba(0,0,0,.12); overflow: hidden; }
        .sheet img.hdr { width: 100%; display: block; }
        .body { padding: 2mm 12mm 3mm; }
        .row { display: flex; font-size: 12px; margin-bottom: 2px; }
        .row .n { width: 20px; flex-shrink: 0; }
        .row .lbl { width: 220px; flex-shrink: 0; }
        .row .val { flex: 1; border-bottom: 1px solid #333; padding-bottom: 1px; min-height: 14px; white-space: pre-wrap; }
        .drow { display: flex; font-size: 12px; margin-bottom: 2px; align-items: flex-end; }
        .drow .n { width: 20px; flex-shrink: 0; }
        .drow .lbl { width: 260px; flex-shrink: 0; }
        .drow .line { flex: 1; border-bottom: 1px solid #333; min-height: 10px; padding-left: 4px; }        .drow .sfx { flex-shrink: 0; margin-left: 6px; font-size: 10px; }
        .drow.sub { padding-left: 20px; }
        .drow.sub .lbl { }
        .u { display: inline-block; border-bottom: 1px solid #333; }
        .sign { width: 260px; margin-left: auto; }
        .sign .space { min-height: 20px; }
        .sign .name { border-top: 1px solid #333; text-align: center; font-weight: 700; padding-top: 1px; }
        .sign .ttl { font-size: 10px; text-align: center; }
        /* On narrow screens keep the official A4 layout intact and let it pan horizontally instead of clipping */
        .sheet-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media screen and (max-width: 840px) { .sheet { width: 210mm; max-width: none; margin: 12px; } }
        @page { size: A4; margin: 0; }
        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .sheet { margin: 0 !important; box-shadow: none !important; max-width: none; width: 210mm; height: 297mm; page-break-after: avoid; }
            .body { padding-bottom: 8mm; }
            * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .sheet-scroll { overflow: visible !important; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
    </div>

    <div class="sheet-scroll">
    <div class="sheet">
        <img class="hdr" src="{{ asset('trip-ticket-header.png') }}" alt="Department of Agriculture MIMAROPA Region">

        <div class="body">
            <div style="text-align:right;font-size:11px">Appendix A</div>

            <h1 style="text-align:center;font-size:17px;font-weight:700;letter-spacing:.75px;margin:3px 0">DRIVER'S TRIP TICKET</h1>

            <div style="text-align:right;font-size:12px;margin-bottom:4px">
                <div>No. <span class="u" style="min-width:140px">{{ $requestRecord->trip_ticket_number ?: '—' }}</span></div>
                <div style="margin-top:2px"><span class="u" style="min-width:160px">{{ ($requestRecord->approved_at ?? now())->format('F j, Y') }}</span></div>            </div>

            <p style="font-size:12px;font-weight:700;margin:0 0 3px">A. To be filled by the Administrative Official Authorizing Official Travel:</p>

            <div style="line-height:1.1">
                <div class="row"><div class="n">1</div><div class="lbl">Name of Driver of the vehicle</div><div class="val">{{ $driverName }}</div></div>
                <div class="row"><div class="n">2</div><div class="lbl">Government car to be used, Plate No.</div><div class="val">{{ $plateLabel }}</div></div>
                <div class="row"><div class="n">3</div><div class="lbl">Name of authorized passenger</div><div class="val">{{ $passengersText }}</div></div>
                <div class="row"><div class="n">4</div><div class="lbl">Duration or Date of travel</div><div class="val">{{ $duration }}</div></div>
                @php
                    $rawDest = $requestRecord->destination ?? '';
                    $destFormatted = str_contains($rawDest, ';;')
                        ? implode(', ', array_filter(array_map('trim', explode(';;', $rawDest))))
                        : $rawDest;
                @endphp
                <div class="row"><div class="n">5</div><div class="lbl">Place or places to be visited</div><div class="val">{{ $destFormatted }}</div></div>
                <div class="row"><div class="n">6</div><div class="lbl">Purpose :</div><div class="val">{{ $requestRecord->purpose }}</div></div>
                <div class="row"><div class="n">7</div><div class="lbl">Previous date of Travel Trip Ticket</div><div class="val">{{ $longDate($requestRecord->previous_trip_ticket_date) }}</div></div>
            </div>

            <div style="margin-top:5px;font-size:12px">
                <div style="text-align:center">Approved by:</div>
                @forelse($signatories as $i => $signee)
                    <div class="sign" style="margin-top:{{ $i === 0 ? '3px' : '6px' }}">
                        <div class="space"></div>
                        <div class="name">{{ $signee->name }}</div>
                        <div class="ttl">{{ $signee->title }}</div>
                    </div>
                @empty
                    <div class="sign" style="margin-top:3px">
                        <div class="space"></div>
                        <div class="name">{{ $requestRecord->approvedBy?->name ?: 'Authorized Approver' }}</div>
                        <div class="ttl">Approving Officer</div>
                    </div>
                @endforelse
            </div>

            <p style="font-size:12px;font-weight:700;margin:6px 0 3px">B. To be filled by the Driver:</p>

            <div style="line-height:1.1">
                @foreach([
                    [1, 'Time of departure for Office/Garage', ...$time($partB?->departure_garage_time)],
                    [2, 'Time of arrival at (per No. 4 above)', ...$time($partB?->arrival_destination_time)],
                    [3, 'Time of departure from (per No. 4)', ...$time($partB?->departure_destination_time)],
                    [4, 'Time of arrival back to Office/Garage', ...$time($partB?->arrival_garage_time)],
                    [5, 'Approximate distance travelled (to and from)', $num($partB?->distance_travelled), 'kms.'],
                ] as [$n, $label, $value, $sfx])
                    <div class="drow"><div class="n">{{ $n }}</div><div class="lbl">{{ $label }}</div><div class="line">{{ $value }}</div><div class="sfx">{{ $sfx }}</div></div>
                @endforeach

                <div class="drow"><div class="n">6</div><div style="flex:1">Gasoline issued, purchase and consumed:</div></div>
                @foreach([
                    ['a. Balance in tank', $partB?->fuel_balance_in_tank, false],
                    ['b. Issued by office from stock', $partB?->fuel_issued_office, false],
                    ['c. Add-purchased during trip', $partB?->fuel_purchased_trip, false],
                    ['Total', $partB?->fuel_total_liters, true],
                    ['d. Deduct: Used during the trip (to and from)', $partB?->fuel_used_liters, false],
                    ['e. Balance in tank at the end of trip', $partB?->fuel_ending_balance, false],
                ] as [$label, $value, $bold])
                    <div class="drow sub"><div class="lbl" @if($bold) style="font-weight:700" @endif>{{ $label }}</div><div class="line" @if($bold) style="font-weight:700" @endif>{{ $num($value) }}</div><div class="sfx">liters</div></div>
                @endforeach

                @foreach([[7, 'Gear oil issued', $partB?->gear_oil_issued], [8, 'Lub oil issued', $partB?->lub_oil_issued], [9, 'Grease issued', $partB?->grease_issued]] as [$n, $label, $value])
                    <div class="drow"><div class="n">{{ $n }}</div><div class="lbl">{{ $label }}</div><div class="line">{{ $num($value) }}</div><div class="sfx">liters</div></div>
                @endforeach

                <div class="drow"><div class="n">10</div><div style="flex:1">Speedometer readings, if any:</div></div>
                @foreach([
                    ['At beginning of trip', $partB?->odo_beginning],
                    ['At end of trip', $partB?->odo_ending],
                    ['Distance travelled (per 5 above)', $partB?->distance_travelled],
                ] as [$label, $value])
                    <div class="drow sub"><div class="lbl">{{ $label }}</div><div class="line">{{ $reading($value) }}</div><div class="sfx">miles/kms.</div></div>
                @endforeach

                <div class="drow"><div class="n">11</div><div class="lbl">Remarks</div><div class="line">{{ $partB?->remarks }}</div></div>
            </div>

            <p style="font-size:9.5px;margin:4px 0 0">I hereby certify to the correctness of the above statement of record of travel.</p>

            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-top:6px">
                <div style="font-size:9.5px;max-width:260px">I hereby certify that I used this car on official business as stated above.</div>
                <div style="text-align:center;width:220px">
                    <div style="font-weight:700;font-size:12px;min-height:15px">{{ $driverName }}</div>
                    <div style="border-top:1px solid #333;margin-top:2px"></div>
                    <div style="font-size:9.5px">Driver</div>
                </div>
            </div>

            <div style="margin-top:5px;text-align:center">
                <div style="min-height:15px">{{ $passengersText }}</div>
                <div style="border-top:1px solid #333;margin-top:2px"></div>
                <div style="font-size:9.5px;margin-top:1px">Name of Passengers</div>
            </div>

            <div style="font-size:10px;color:#333;margin-top:3px;line-height:1.2">
                <div>Doc. No.: DAMIMAROPA-F083-2023</div>
                <div>Rev. No.: 0</div>
                <div>Issued Date: 10/23/23</div>
            </div>
        </div>
    </div>
    </div>
</body>
</html>
