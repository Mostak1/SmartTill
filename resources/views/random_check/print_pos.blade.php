<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Check Report (POS)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 88mm; /* Adjust width for POS printer */
        }

        .container {
            padding: 2mm;
        }

        h1, h2, h3, h4, h5, h6 {
            margin: 0;
            padding: 0;
        }

        h5 {
            font-size: 1em;
            text-align: center;
            margin-bottom: 3px;
        }

        h6 {
            font-size: 0.7em;
            line-height: 1.2;
            text-align: left;
        }

        p {
            margin: 0;
            padding: 0;
            font-size: 0.6em;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        table, th, td {
            border: 0.5px solid #000;
        }

        th, td {
            padding: 2px;
            font-size: 0.7em;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .category-header {
            font-size: 0.8em;
            font-weight: bold;
            margin-top: 4mm;
            margin-bottom: 2mm;
        }

        .details {
            margin-top: 5mm;
        }

        .details ul {
            padding: 0;
            list-style: none;
            font-size: 0.6em;
        }

        .details li {
            margin-bottom: 0.3em;
        }

        .break-after-6 {
            word-break: break-all;
            width: 6ch; /* 6 characters width */
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="handlePrint()">
    <div class="container">
        <div class="heading">
            <h5>Check Report: {{$randomCheck->check_no}}</h5>
            <h6><b>Location:</b> {{$location->name}}</h6>
            <p style="text-align: right; font-size: 0.6em; margin-top: 5px; margin-bottom: -20px;">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
        </div>
        @php
            $currentCategory = null;
        @endphp

        @foreach ($randomCheck->randomCheckDetails as $detail)
            @if ($currentCategory != $detail->product->category->name)
                @if ($currentCategory)
                    </tbody>
                    </table>
                @endif

                <div class="category-header">
                    {{ $detail->product->category->name }}
                </div>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50%;">Product Name</th>
                            <th style="width: 10%;">SKU</th>
                            <th style="width: 20%;">Brand Name</th>
                            <th style="width: 10%;">Soft. Count</th>
                            <th style="width: 10%;">Phy. Count Diff.</th>
                        </tr>
                    </thead>
                    <tbody>
            @endif

            <tr>
                <td>{{ $detail->product->name }}</td>
                <td class="break-after-6">{{ $detail->product->sku }}</td>
                <td>{{ $detail->brand_name ?? 'No Brand' }}</td>
                <td>{{ $detail->current_stock }}</td>
                <td></td>
            </tr>

            @php
                $currentCategory = $detail->product->category->name;
            @endphp
        @endforeach

        </tbody>
        </table>

        <div class="details">
            <ul>
                <li><strong>Checked by:</strong> {{ $randomCheck->checkedBy->first_name }} {{ $randomCheck->checkedBy->last_name }}</li>
                <li><strong>Checked at:</strong> {{ $randomCheck->created_at->format('d F Y, g:i A') }}</li>
            </ul>
        </div>

        <div class="no-print">
            <p>If the print dialog doesn't appear automatically, <a href="#" onclick="window.print()">click here to print</a>.</p>
        </div>
    </div>
    <script>
        function handlePrint() {
            window.print();
            window.onafterprint = function() {
                window.history.back();
            };
        }
    </script>
</body>
</html>