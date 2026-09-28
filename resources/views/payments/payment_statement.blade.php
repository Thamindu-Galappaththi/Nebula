<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Statement</title>
    <style nonce="{{ $cspNonce }}">
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 18px 18px 40px 18px;
            color: #000;
            background: #fff;
        }

        .institute-header {
            text-align: center;
            border-bottom: 1px solid #000;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .institute-name {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin: 0;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding: 8px 0 12px 0;
            margin-bottom: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-table,
        .summary-table,
        .data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .info-table {
            margin-bottom: 16px;
            border: 1px solid #000;
            background: #f8f8f8;
        }

        .info-table td {
            width: 50%;
            padding: 6px 10px;
            vertical-align: top;
            border: none;
        }

        .student-name {
            font-size: 13px;
            font-weight: bold;
            padding: 8px 10px 4px 10px !important;
            border-bottom: 1px solid #ccc !important;
        }

        .info-label {
            font-weight: bold;
        }

        .section-title {
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 2px solid #000;
            padding: 6px 0 4px 0;
            margin: 18px 0 8px 0;
        }

        .data-table {
            margin-bottom: 16px;
            border: 1px solid #000;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 6px 6px;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table th {
            background: #000;
            color: #fff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .col-center {
            text-align: center;
        }

        .col-left {
            text-align: left;
        }

        .col-amount {
            text-align: right;
        }

        .no-records {
            text-align: center;
            font-style: italic;
            color: #666;
            padding: 14px;
        }

        .summary-table {
            width: 55%;
            margin: 0 0 16px auto;
            border: 1px solid #000;
            background: #f8f8f8;
        }

        .summary-table td {
            border: 1px solid #000;
            padding: 6px 8px;
        }

        .summary-table .col-amount {
            width: 40%;
            font-weight: bold;
        }

        .total-row td {
            background: #e8e8e8;
            font-weight: bold;
        }

        .outstanding {
            color: #666;
            font-style: italic;
        }

        .footer {
            position: fixed;
            bottom: 12px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="institute-header">
        <h1 class="institute-name">Nebula Institute</h1>
    </div>

    <div class="header">
        <h1>Statement of Account</h1>
    </div>

    <table class="info-table">
        <tr>
            <td class="student-name" colspan="2">{{ $student['id'] }} - {{ $student['name'] }}</td>
        </tr>
        <tr>
            <td><span class="info-label">NIC:</span> {{ $student['nic'] }}</td>
            <td><span class="info-label">Date Issued:</span> {{ $generated_date }}</td>
        </tr>
        <tr>
            <td><span class="info-label">Course:</span> {{ $course['name'] }}</td>
            <td><span class="info-label">Intake:</span> {{ $course['intake'] }}</td>
        </tr>
        <tr>
            <td colspan="2"><span class="info-label">Registration:</span> {{ $course['registration_date'] }}</td>
        </tr>
    </table>

    <h2 class="section-title">Payment Details</h2>
    <table class="data-table">
        <colgroup>
            <col style="width:38%">
            <col style="width:16%">
            <col style="width:16%">
            <col style="width:14%">
            <col style="width:16%">
        </colgroup>
        <thead>
            <tr>
                <th class="col-left">Item Description</th>
                <th class="col-center">Payment Mode</th>
                <th class="col-center">Receipt No</th>
                <th class="col-center">Date</th>
                <th class="col-amount">Amount Paid</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $p)
                <tr>
                    <td class="col-left">
                        {{ $p['description'] }}
                        @if(($p['amount'] ?? 0) == 0)
                            <span class="outstanding">(Outstanding)</span>
                        @endif
                    </td>
                    <td class="col-center">{{ $p['method'] ?? '-' }}</td>
                    <td class="col-center">{{ $p['receipt_no'] ?? '-' }}</td>
                    <td class="col-center">{{ $p['date'] ?? '-' }}</td>
                    <td class="col-amount">{{ number_format((float) ($p['amount'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="no-records">No payment records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>Total Amount:</td>
            <td class="col-amount">Rs. {{ number_format((float) $totals['total_amount'], 2) }}</td>
        </tr>
        <tr>
            <td>Total Paid:</td>
            <td class="col-amount">Rs. {{ number_format((float) $totals['total_paid'], 2) }}</td>
        </tr>
        <tr>
            <td>Total Outstanding:</td>
            <td class="col-amount">Rs. {{ number_format((float) $totals['total_remaining'], 2) }}</td>
        </tr>
    </table>

    @if($paymentPlan && $paymentPlan->installments->count())
    <h2 class="section-title">Student Payment Plan (LKR)</h2>
    <table class="data-table">
        <colgroup>
            <col style="width:8%">
            <col style="width:16%">
            <col style="width:19%">
            <col style="width:19%">
            <col style="width:19%">
            <col style="width:19%">
        </colgroup>
        <thead>
            <tr>
                <th class="col-center">#</th>
                <th class="col-center">Due Date</th>
                <th class="col-amount">Base Amount</th>
                <th class="col-amount">Discount</th>
                <th class="col-amount">SLT Loan</th>
                <th class="col-amount">Final Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumBase = 0;
                $sumDisc = 0;
                $sumLoan = 0;
                $sumFinal = 0;
            @endphp
            @foreach($paymentPlan->installments as $inst)
                @php
                    $sumBase += $inst->base_amount ?? $inst->amount ?? 0;
                    $sumDisc += $inst->discount_amount ?? 0;
                    $sumLoan += $inst->slt_loan_amount ?? 0;
                    $sumFinal += $inst->final_amount ?? ($inst->base_amount ?? $inst->amount ?? 0);
                @endphp
                <tr>
                    <td class="col-center">{{ $inst->installment_number }}</td>
                    <td class="col-center">{{ $inst->formatted_due_date }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst->base_amount ?? $inst->amount ?? 0), 2) }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst->discount_amount ?? 0), 2) }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst->slt_loan_amount ?? 0), 2) }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst->final_amount ?? ($inst->base_amount ?? $inst->amount ?? 0)), 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="col-amount" colspan="2">TOTAL</td>
                <td class="col-amount">{{ number_format((float) $sumBase, 2) }}</td>
                <td class="col-amount">{{ number_format((float) $sumDisc, 2) }}</td>
                <td class="col-amount">{{ number_format((float) $sumLoan, 2) }}</td>
                <td class="col-amount">{{ number_format((float) $sumFinal, 2) }}</td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(!empty($courseInstallments))
    <h2 class="section-title">Course Installment Plan (Master)</h2>
    <table class="data-table">
        <colgroup>
            <col style="width:10%">
            <col style="width:18%">
            <col style="width:26%">
            <col style="width:26%">
            <col style="width:20%">
        </colgroup>
        <thead>
            <tr>
                <th class="col-center">#</th>
                <th class="col-center">Due Date</th>
                <th class="col-amount">Local Amount (LKR)</th>
                <th class="col-amount">Foreign Amount</th>
                <th class="col-center">Currency</th>
            </tr>
        </thead>
        <tbody>
            @php
                $sumLocal = 0;
                $sumForeign = 0;
            @endphp
            @foreach($courseInstallments as $inst)
                @php
                    $sumLocal += $inst['local_amount'] ?? 0;
                    $sumForeign += $inst['international_amount'] ?? 0;
                @endphp
                <tr>
                    <td class="col-center">{{ $inst['installment_number'] }}</td>
                    <td class="col-center">{{ \Carbon\Carbon::parse($inst['due_date'])->timezone(config('app.timezone', 'Asia/Colombo'))->format('d/m/Y') }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst['local_amount'] ?? 0), 2) }}</td>
                    <td class="col-amount">{{ number_format((float) ($inst['international_amount'] ?? 0), 2) }}</td>
                    <td class="col-center">{{ $coursePlan->international_currency ?? '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td class="col-amount" colspan="2">TOTAL</td>
                <td class="col-amount">{{ number_format((float) $sumLocal, 2) }}</td>
                <td class="col-amount">{{ number_format((float) $sumForeign, 2) }}</td>
                <td class="col-center"></td>
            </tr>
        </tbody>
    </table>
    @endif

    <div class="footer">Page 1</div>
</body>
</html>
