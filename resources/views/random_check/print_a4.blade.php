<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Random Check Report (A4)</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            line-height: 1;
            width: 210mm; /* A4 width */
            height: 297mm; /* A4 height */
            box-sizing: border-box;
        }

        .container {
            padding: 10mm;
        }

        h1 {
            font-size: 1.5em;
            margin-bottom: 1em;
            margin-top: -5px;
            text-align: center;
        }

        h4, p.location-info {
            margin: 0;
            padding: 0;
            font-size: 1em;
            text-align: left;
            line-height: 1.2;
        }

        p {
            margin: 0;
            padding: 0;
            font-size: 0.9em;
            text-align: right;
            line-height: 1.2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        table, th, td {
            border: 1px solid #000;
        }

        th, td {
            padding: 5px;
            text-align: left;
            font-size: 0.9em;
        }

        th {
            background-color: #f2f2f2;
        }

        .details {
            margin-bottom: 1em;
        }

        .details ul {
            padding: 0;
            list-style: none;
            font-size: 0.9em;
        }

        .details li {
            margin-bottom: 0.5em;
        }

        .break-after-6 {
            word-break: break-all;
            width: 6ch; /* 6 characters width */
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1em;
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
        <h1>Check Report: {{$randomCheck->check_no}}</h1>
        <div class="header-container">
            <div>
                <h4 class="location-info"><strong>Location:</strong> {{$location->name}}</h4>
            </div>
            <div>
                <p class="generated-at">Generated at: {{ now()->format('d-m-Y, h:i A') }}</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Brand Name</th>
                    <th>Soft. Count</th>
                    <th>Phy. Count Diff.</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($randomCheck->randomCheckDetails as $detail)
                    <tr>
                        <td>{{ $detail->product->category->name }}</td>
                        <td>{{ $detail->product->name }}</td>
                        <td class="break-after-6">{{ $detail->product->sku }}</td>
                        <td>{{ $detail->brand_name ?? 'No Brand' }}</td>
                        <td>{{ $detail->current_stock }}</td>
                        <td></td>
                    </tr>
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
