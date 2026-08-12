<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta de Separação</title>
    <style>
        @page {
            margin: 12mm;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #000;
            background: #fff;
            font-size: 12px;
            line-height: 1.35;
        }

        .label-stack-item {
            margin-bottom: 24px;
            page-break-inside: avoid;
        }

        .label-stack-item:last-child {
            margin-bottom: 0;
        }

        .label-container {
            width: 100%;
            max-width: 14cm;
            border: 2px solid #000;
            padding: 16px 18px;
            background: #fff;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 12px;
            line-height: 1.2;
        }

        .section {
            margin-top: 10px;
        }

        .section-label {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 2px;
        }

        .section-value {
            font-size: 13px;
            word-wrap: break-word;
        }

        .observation-value {
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .strips-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
            font-size: 12px;
        }

        .strips-table th,
        .strips-table td {
            border: 1px solid #cfcfcf;
            padding: 6px 8px;
            text-align: right;
        }

        .strips-table thead th {
            background: #ececec;
            font-weight: bold;
        }

        .strips-table tbody tr:nth-child(even) td {
            background: #f7f7f7;
        }

        .status {
            margin-top: 14px;
            font-size: 15px;
            text-align: center;
            text-transform: uppercase;
        }

        .status-bold {
            font-weight: bold;
        }

        .layout-quantity {
            margin-top: 8px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    @foreach(($labels ?? []) as $label)
        <div class="label-stack-item">
            @include('pdf.expedition.partials.separation-label-item', [
                'label' => $label,
                'compact' => false,
            ])
        </div>
    @endforeach
</body>
</html>
