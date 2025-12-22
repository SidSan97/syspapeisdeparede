<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Etiqueta de Separação</title>
    <style>
        @page {
            margin: 0;
            size: A4;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #fff;
        }

        .label-container {
            width: 10cm;
            height: 6cm;
            border: 2px solid #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background: #fff;
            margin: 20px 30px 0 20px;
        }

        .title {
            font-size: 28px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
            color: #000;
            line-height: 1.2;
        }

        .status {
            font-size: 16px;
            text-align: center;
            text-transform: uppercase;
            color: #000;
            font-weight: normal;
        }

        .status-bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="label-container">
        <br><br><br>
        <div class="title">{{ $title }}</div>
        <div class="status">
            @php
                $statusParts = explode(' ', $status);
            @endphp
            @if(count($statusParts) > 1)
                {{ strtoupper($statusParts[0]) }} <span class="status-bold">{{ strtoupper($statusParts[1]) }}</span>
            @else
                {{ strtoupper($status) }}
            @endif
        </div>
    </div>
</body>
</html>

