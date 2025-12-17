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

            h1 {
                font-size: 20px;
                font-weight: bolder;
                margin-top: 10px;
                margin-bottom: 16px;
            }

            h2 {
                font-size: 20px;
                font-weight: bolder;
                margin-bottom: 8px;
            }

            .container {
                width: 100%;
                margin-bottom: 16px;
            }

            .left {
                float: left;
                width: 60%;
            }

            .right {
                float: right;
                width: 35%;
                text-align: right;
            }

            .label {
                font-weight: bold;
            }

            .value {
                margin-bottom: 10px;
            }

            .clear {
                clear: both;
            }

            table {
                width: 100%;
                border-collapse: collapse;
            }

            table thead tr th {
                background-color: #fd5722;
                border: 1px solid #000000;
                color: white;
                text-align: left;
                padding: 5px;
                font-weight: bold;
            }

            table tbody tr,
            table tbody td {
                border: 1px solid #000000;
            }

            table tbody tr td {
                padding: 8px 4px;
            }

            table tbody tr td ul {
                list-style-type: disc;
            }

            table tbody tr td ul li {
                margin: 4px 16px;
            }
        </style>
    </head>
    <body>
        @php
            $svg = '
                                                    <svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 316 316" fill="#fd5722">
                                                        <polygon
                                                            points="215.24 70.31 316.24 105.47 316.24 52.84 165.24 0.3 165.24 18.92 165.24 53.02 165.24 105.37 165.24 158.09 165.24 210.44 165.24 221.13 165.24 263.16 316.24 315.69 316.24 262.86 215.24 227.7 215.24 175.42 316.24 210.4 316.24 157.94 215.24 122.78 215.24 70.31"
                                                        />
                                                        <polygon
                                                            points="0 52.64 0 105.27 101.33 70 101.33 70 101.33 227.9 51.02 245.45 51.02 210.73 0 227.9 0 263.18 0 316 151.29 263.36 151.29 221.25 151.29 210.54 151.29 52.82 151.29 18.65 151.29 0 0 52.64"
                                                        />
                                                    </svg>
                                                ';

            $image = '<img src="data:image/svg+xml;base64,' . base64_encode($svg) . '"  width="50" height="50" />';
        @endphp

        {!! $image !!}

        <h1>
            LAPORAN PENJUALAN TOKO JOGJA ELECTRIK

            @if ($month)
                {{ $month }}
            @endif

            {{ $year }}
        </h1>
        <h2>RINGKASAN PENJUALAN</h2>
        <div class="container">
            <div class="left">
                <p>
                    Periode laporan penjualan:
                    <strong>
                        @if ($month)
                            {{ 'Bulan ' . $month . ', ' }}
                        @endif

                        {{ 'Tahun ' . $year }}
                    </strong>
                </p>
                <p>
                    Laporan ini dibuat pada:
                    <strong>{{ formatTimestamp(now()) }}</strong>
                </p>
            </div>
            <div class="right">
                <div class="value total-line">
                    <span class="label">GRAND TOTAL PENJUALAN</span>
                    <br />
                    <strong>Rp {{ formatPrice($grandTotalSales) }}</strong>
                </div>
                <div class="value total-line">
                    <span class="label">GRAND TOTAL PROFIT</span>
                    <br />
                    <strong>Rp {{ formatPrice($grandTotalProfit) }}</strong>
                </div>
            </div>

            <div class="clear"></div>
        </div>
        <table>
            <thead>
                <tr>
                    <th align="center">NO.</th>
                    <th>NOMOR PESANAN</th>
                    <th align="center">SUMBER PENJUALAN</th>
                    <th>PRODUK YANG DIBELI</th>
                    <th align="center">METODE PEMBAYARAN</th>
                    <th align="center">TOTAL PENJUALAN</th>
                    <th align="center">TOTAL PROFIT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sales as $sale)
                    <tr>
                        <td align="center">{{ $loop->iteration }}.</td>
                        <td>
                            {{ $sale['order_number'] }}
                        </td>
                        <td align="center">
                            {{ $sale['source'] === 'ecommerce' ? 'Website Online' : 'Penjualan Offline' }}
                        </td>
                        <td>
                            <ul>
                                @foreach ($sale['order_details'] as $item)
                                    <li>{{ $item['order_detail_quantity'] . 'x ' . $item['product_name'] }}</li>
                                @endforeach
                            </ul>
                        </td>

                        @if ($sale['payment_method'] === 'offline')
                            <td align="center">Penjualan {{ ucwords($sale['payment_method']) }}</td>
                        @else
                            <td align="center">
                                @php
                                    $paymentMethod = null;

                                    if (str_contains($sale['payment_method'], 'bank_transfer_')) {
                                        $paymentMethod = str_replace('bank_transfer_', '', $sale['payment_method']) . ' VA';
                                    } elseif (str_contains($sale['payment_method'], 'ewallet_')) {
                                        $paymentMethod = str_replace('ewallet_', '', $sale['payment_method']);
                                    }
                                @endphp

                                {{ strtoupper($paymentMethod) }}
                            </td>
                        @endif
                        <td align="center">
                            @php
                                $totalCost = 0;

                                foreach ($sale['order_details'] as $detail) {
                                    $orderDetailPrice = (float) $detail['order_detail_price'];
                                    $orderDetailQuantity = (int) $detail['order_detail_quantity'];

                                    $totalCost += $orderDetailPrice * $orderDetailQuantity;
                                }

                                echo 'Rp ' . formatPrice($totalCost);
                            @endphp
                        </td>
                        <td align="center">
                            @php
                                $totalProfit = 0;

                                foreach ($sale['order_details'] as $detail) {
                                    $revenue = (float) $detail['order_detail_price'] * (int) $detail['order_detail_quantity'];
                                    $cost = (float) $detail['product_cost_price'] * (int) $detail['order_detail_quantity'];

                                    $totalProfit += $revenue - $cost;
                                }

                                echo 'Rp ' . formatPrice($totalProfit);
                            @endphp
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </body>
</html>
