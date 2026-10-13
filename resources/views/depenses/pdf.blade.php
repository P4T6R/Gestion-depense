<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport des Dépenses</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            color: #1e40af;
            text-align: center;
            margin-bottom: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #1e40af;
        }
        .stats {
            margin-bottom: 30px;
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .stats-label {
            font-weight: bold;
            color: #374151;
        }
        .stats-value {
            color: #1e40af;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #1e40af;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }
        tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .status-en_attente { background: #fef3c7; color: #92400e; }
        .status-acceptee { background: #dbeafe; color: #1e40af; }
        .status-validee { background: #d1fae5; color: #065f46; }
        .status-rejetee { background: #fee2e2; color: #991b1b; }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Rapport des Dépenses</h1>
        <p><strong>StratAction Consulting</strong></p>
        <p>Généré le {{ date('d/m/Y à H:i') }}</p>
    </div>

    <div class="stats">
        <div class="stats-row">
            <span class="stats-label">Nombre total de dépenses :</span>
            <span class="stats-value">{{ $stats['total'] }}</span>
        </div>
        <div class="stats-row">
            <span class="stats-label">Montant total :</span>
            <span class="stats-value">{{ number_format($stats['montant_total'], 0, ',', ' ') }} F CFA</span>
        </div>
        <div class="stats-row">
            <span class="stats-label">Montant validé :</span>
            <span class="stats-value">{{ number_format($stats['montant_valide'], 0, ',', ' ') }} F CFA</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Chantier</th>
                <th>Produit</th>
                <th>Qté</th>
                <th>Prix Unit.</th>
                <th>Total</th>
                <th>Catégorie</th>
                <th>Statut</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($depenses as $depense)
            <tr>
                <td>#{{ $depense->id }}</td>
                <td>{{ $depense->chantier->nom ?? 'N/A' }}</td>
                <td>{{ $depense->libelle }}</td>
                <td>{{ $depense->quantite }} {{ $depense->unite->nom ?? '' }}</td>
                <td>{{ number_format($depense->prix_unitaire ?? 0, 0, ',', ' ') }} F</td>
                <td><strong>{{ number_format($depense->prix_total, 0, ',', ' ') }} F</strong></td>
                <td>{{ $depense->categorie->nom ?? 'N/A' }}</td>
                <td>
                    <span class="status status-{{ $depense->statut_validation }}">
                        {{ ucfirst(str_replace('_', ' ', $depense->statut_validation)) }}
                    </span>
                </td>
                <td>{{ $depense->created_at->format('d/m/Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p><strong>StratAction Consulting</strong> - Système de Suivi des Dépenses</p>
        <p>Document confidentiel - {{ date('Y') }}</p>
    </div>
</body>
</html>
