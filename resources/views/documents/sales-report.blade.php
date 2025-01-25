<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta
            name="viewport"
            content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"
        />
        <meta http-equiv="X-UA-Compatible" content="ie=edge" />
        <meta name="format-detection" content="telephone=no" />
        <meta name="print-color-adjust" content="exact" />
        <meta name="color-adjust" content="exact" />
        <style type="text/css">
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: Arial, sans-serif;
                color: #0c0c0c;
                margin: 40px;
                padding: 0;
                font-size: 14px;
                letter-spacing: -0.025em;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            th,
            td {
                padding: 8px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }

            th {
                background-color: #f2f2f2;
            }
        </style>
    </head>
    <body>
        <h1>LAPORAN PENJUALAN (Waktu)</h1>
        <h2>RINGKASAN PENJUALAN</h2>
        <p>TANGGAL: TANGGAL BERAPA - SAMPAI TANGGAL BERAPA</p>
        <p>SUBTOTAL PENJUALAN: Subtotalnya Berapa Ya</p>
        <p>TOTAL DISKON: Total diskon berapa ya</p>
        <p>TOTAL BIAYA PENGIRIMAN: Total biaya pengiriman berapa ya</p>
        <p>TOTAL PENJUALAN: Total penjualan berapa ya</p>
        <h2>TABEL PENJUALAN</h2>
        <table>
            <thead>
                <tr>
                    <th>NO.</th>
                    <th>NOMOR PESANAN</th>
                    <th>TANGGAL</th>
                    <th>NAMA PEMBELI</th>
                    <th>STATUS PESANAN</th>
                    <th>TOTAL</th>
                    <th>METODE PEMBAYARAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sales as $sale)
                    <tr>
                        <td>{{ $loop->index + 1 . '.' }}</td>
                        <td>{{ $sale->order_number }}</td>
                        <td>{{ $sale->created_at->format('d-m-Y') }}</td>
                        <td>{{ $sale->user->name }}</td>
                        <td>Sukses</td>
                        <td>{{ formatPrice($sale->total_amount) }}</td>
                        <td>
                            @if (str_contains($sale->payment->method, 'bank_transfer'))
                                {{ strtoupper(str_replace('bank_transfer_', '', $sale->payment->method)) }}
                            @else
                                {{ strtoupper(str_replace('ewallet_', '', $sale->payment->method)) }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
