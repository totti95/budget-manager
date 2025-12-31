<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Budget {{ $budget->month->format('F Y') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
            margin: 20mm 15mm 25mm 15mm; /* top right bottom left */
        }

        .header {
            background-color: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-bottom: 3px solid #3b82f6;
        }

        .header h1 {
            color: #1f2937;
            font-size: 22pt;
            margin-bottom: 5px;
        }

        .header .meta {
            color: #6b7280;
            font-size: 9pt;
        }

        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .summary-card {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            text-align: center;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
        }

        .summary-card h3 {
            font-size: 9pt;
            color: #6b7280;
            margin-bottom: 5px;
            font-weight: normal;
            text-transform: uppercase;
        }

        .summary-card .amount {
            font-size: 18pt;
            font-weight: bold;
        }

        .summary-card.planned .amount {
            color: #1f2937;
        }

        .summary-card.actual .amount {
            color: #3b82f6;
        }

        .summary-card.variance .amount.positive {
            color: #10b981;
        }

        .summary-card.variance .amount.negative {
            color: #ef4444;
        }

        .summary-card .percent {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 3px;
        }

        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
            margin: 20px 0 10px 0;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th {
            background-color: #f3f4f6;
            padding: 8px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }

        table td {
            padding: 6px 8px;
            border: 1px solid #e5e7eb;
            font-size: 9pt;
        }

        table tr.category-row {
            background-color: #f9fafb;
            font-weight: bold;
        }

        table tr.subcategory-row td:first-child {
            padding-left: 20px;
        }

        table tr.total-row {
            background-color: #e5e7eb;
            font-weight: bold;
            font-size: 10pt;
        }

        .amount-cell {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .variance-positive {
            color: #10b981;
        }

        .variance-negative {
            color: #ef4444;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }

        .page-number:after {
            content: counter(page);
        }

        .no-data {
            text-align: center;
            padding: 30px;
            color: #6b7280;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $budget->name }}</h1>
        <div class="meta">
            <strong>Utilisateur :</strong> {{ $user->name }} ({{ $user->email }})<br>
            <strong>Généré le :</strong> {{ now()->isoFormat('DD MMMM YYYY à HH:mm') }}
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card planned">
            <h3>Total Prévu</h3>
            <div class="amount">{{ number_format($stats['total_planned_cents'] / 100, 2, ',', ' ') }} €</div>
        </div>
        <div class="summary-card actual">
            <h3>Total Dépensé</h3>
            <div class="amount">{{ number_format($stats['total_actual_cents'] / 100, 2, ',', ' ') }} €</div>
        </div>
        <div class="summary-card variance">
            <h3>Écart</h3>
            <div class="amount {{ $stats['variance_cents'] >= 0 ? 'positive' : 'negative' }}">
                {{ number_format($stats['variance_cents'] / 100, 2, ',', ' ') }} €
            </div>
            <div class="percent">
                ({{ $stats['variance_percent'] > 0 ? '+' : '' }}{{ number_format($stats['variance_percent'], 2, ',', ' ') }} %)
            </div>
        </div>
    </div>

    <!-- Categories Table -->
    <h2 class="section-title">Détail par Catégorie</h2>

    @if(count($stats['by_category']) > 0)
    <table>
        <thead>
            <tr>
                <th style="width: 40%;">Catégorie / Sous-catégorie</th>
                <th style="width: 15%;">Prévu</th>
                <th style="width: 15%;">Dépensé</th>
                <th style="width: 15%;">Écart</th>
                <th style="width: 15%;">Écart %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['by_category'] as $category)
                <!-- Category Row -->
                <tr class="category-row">
                    <td>{{ $category['name'] }}</td>
                    <td class="amount-cell">{{ number_format($category['planned_cents'] / 100, 2, ',', ' ') }} €</td>
                    <td class="amount-cell">{{ number_format($category['actual_cents'] / 100, 2, ',', ' ') }} €</td>
                    <td class="amount-cell {{ $category['variance_cents'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ number_format($category['variance_cents'] / 100, 2, ',', ' ') }} €
                    </td>
                    <td class="amount-cell {{ $category['variance_percent'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ $category['variance_percent'] > 0 ? '+' : '' }}{{ number_format($category['variance_percent'], 1, ',', ' ') }} %
                    </td>
                </tr>

                <!-- Subcategory Rows -->
                @foreach($category['subcategories'] as $subcategory)
                <tr class="subcategory-row">
                    <td>{{ $subcategory['name'] }}</td>
                    <td class="amount-cell">{{ number_format($subcategory['planned_cents'] / 100, 2, ',', ' ') }} €</td>
                    <td class="amount-cell">{{ number_format($subcategory['actual_cents'] / 100, 2, ',', ' ') }} €</td>
                    <td class="amount-cell {{ $subcategory['variance_cents'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ number_format($subcategory['variance_cents'] / 100, 2, ',', ' ') }} €
                    </td>
                    <td class="amount-cell {{ $subcategory['variance_percent'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ $subcategory['variance_percent'] > 0 ? '+' : '' }}{{ number_format($subcategory['variance_percent'], 1, ',', ' ') }} %
                    </td>
                </tr>
                @endforeach
            @endforeach

            <!-- Total Row -->
            <tr class="total-row">
                <td>TOTAL</td>
                <td class="amount-cell">{{ number_format($stats['total_planned_cents'] / 100, 2, ',', ' ') }} €</td>
                <td class="amount-cell">{{ number_format($stats['total_actual_cents'] / 100, 2, ',', ' ') }} €</td>
                <td class="amount-cell {{ $stats['variance_cents'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                    {{ number_format($stats['variance_cents'] / 100, 2, ',', ' ') }} €
                </td>
                <td class="amount-cell {{ $stats['variance_percent'] >= 0 ? 'variance-positive' : 'variance-negative' }}">
                    {{ $stats['variance_percent'] > 0 ? '+' : '' }}{{ number_format($stats['variance_percent'], 2, ',', ' ') }} %
                </td>
            </tr>
        </tbody>
    </table>
    @else
    <div class="no-data">Aucune catégorie définie pour ce budget</div>
    @endif

    <!-- Top 10 Expenses -->
    @if(count($stats['top_expenses']) > 0)
    <h2 class="section-title">Top {{ count($stats['top_expenses']) }} des Dépenses</h2>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 30%;">Libellé</th>
                <th style="width: 20%;">Catégorie</th>
                <th style="width: 20%;">Sous-catégorie</th>
                <th style="width: 18%;">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stats['top_expenses'] as $expense)
            <tr>
                <td>{{ \Carbon\Carbon::parse($expense['date'])->isoFormat('DD/MM/YYYY') }}</td>
                <td>{{ $expense['label'] }}</td>
                <td>{{ $expense['category'] }}</td>
                <td>{{ $expense['subcategory'] }}</td>
                <td class="amount-cell"><strong>{{ number_format($expense['amount_cents'] / 100, 2, ',', ' ') }} €</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Footer -->
    <div class="footer">
        Généré avec Budget Manager le {{ now()->isoFormat('DD/MM/YYYY à HH:mm') }} - Page <span class="page-number"></span>
    </div>
</body>
</html>
