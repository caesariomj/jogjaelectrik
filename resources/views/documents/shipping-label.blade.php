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
                position: relative;
                font-family: Arial, sans-serif;
                color: #0c0c0c;
                margin: 0;
                padding: 0;
                font-size: 0.8125rem;
                letter-spacing: -0.025em;
                border: 1px solid #0c0c0c;
                width: 396px;
                min-height: 559px;
            }

            .wrapper {
                width: 100%;
                height: 100%;
            }

            header {
                padding: 0.5rem;
                text-align: center;
                border-bottom: 1px solid #0c0c0c;
            }

            header .logo {
                display: inline-block;
                vertical-align: middle;
                width: 25px;
                height: 25px;
                border-radius: 100%;
                background-color: black;
                margin-right: 0.5rem;
            }

            header h1 {
                display: inline-block;
                vertical-align: middle;
                font-size: 1.125rem;
            }

            main table.order-detail {
                width: 100%;
                border-collapse: collapse;
                text-align: start;
                vertical-align: top;
            }

            main table.order-detail tbody tr td {
                vertical-align: top;
            }

            main table.order-detail tbody tr td.order-number {
                width: 100%;
                padding: 0.5rem;
                text-align: center;
                border-bottom: 1px solid #0c0c0c;
            }

            main table.order-detail tbody tr td.receiver {
                width: 50%;
                padding: 0.5rem;
                border-right: 1px solid #0c0c0c;
            }

            main table.order-detail tbody tr td.receiver h2 {
                font-size: 1rem;
                margin-bottom: 8px;
            }

            main table.order-detail tbody tr td.receiver strong.receiver-name {
                display: block;
                margin-bottom: 2px;
            }
            main table.order-detail tbody tr td.receiver p.receiver-address {
                margin-bottom: 4px;
            }

            main table.order-detail tbody tr td.sender {
                width: 50%;
                padding: 0.5rem;
                border-left: 1px solid #0c0c0c;
            }

            main table.order-detail tbody tr td.sender h2 {
                font-size: 1rem;
                margin-bottom: 8px;
            }

            main table.order-detail tbody tr td.weight {
                width: 100%;
                padding: 0.5rem;
                text-align: center;
                border-top: 1px solid #0c0c0c;
            }

            main table.product-list {
                width: 100%;
                border-collapse: collapse;
                text-align: start;
                vertical-align: top;
                border-top: 1px dashed #0c0c0c;
            }

            main table.product-list tbody tr td {
                vertical-align: top;
            }

            main table.product-list {
                width: 100%;
                border-collapse: collapse;
            }

            main table.product-list th,
            main table.product-list td {
                padding: 0.25rem 0.5rem;
            }

            main table.product-list .product-column {
                width: 50%;
                text-align: left;
            }

            main table.product-list .sku-column {
                width: 40%;
                text-align: center;
            }

            main table.product-list .quantity-column {
                width: 10%;
                text-align: right;
            }
        </style>
    </head>
    <body>
        <div class="wrapper">
            <header>
                <div class="logo"></div>
                <h1>Toko Jogja Electrik</h1>
            </header>
            <main>
                <table class="order-detail">
                    <tbody>
                        <tr>
                            <td class="order-number" colspan="2">
                                Nomor Pesanan:
                                <strong>{{ $order->order_number }}</strong>
                            </td>
                        </tr>
                        <tr>
                            <td class="receiver">
                                <h2>Penerima:</h2>
                                <strong class="receiver-name">{{ $order->user->name }}</strong>
                                <p class="receiver-address">
                                    {{ \Illuminate\Support\Facades\Crypt::decryptString($order->shipping_address) }}
                                </p>
                                <p class="receiver-phone-number">
                                    +62
                                    {{ \Illuminate\Support\Facades\Crypt::decryptString($order->user->phone_number) }}
                                </p>
                            </td>
                            <td class="sender">
                                <h2>Pengirim:</h2>
                                <strong class="receiver-name">Toko Jogja Electrik</strong>
                                <p class="receiver-address">Alamat</p>
                                <p class="receiver-phone-number">Nomor Telefon</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="weight" colspan="2">
                                Berat Paket:
                                <strong>3 kg</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="product-list">
                    <thead>
                        <tr>
                            <th class="product-column" align="left">Produk</th>
                            <th class="sku-column" align="center">SKU</th>
                            <th class="quantity-column" align="right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->details as $item)
                            <tr>
                                <td class="product-column" align="left">
                                    {{ $item->productVariant->product->name }}
                                </td>
                                <td class="sku-column" align="center">
                                    @if ($item->productVariant->variant_sku)
                                        {{ $item->productVariant->product->main_sku . '-' . $item->productVariant->variant_sku }}
                                    @else
                                        {{ $item->productVariant->product->main_sku }}
                                    @endif
                                </td>
                                <td class="quantity-column" align="right">{{ $item->quantity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </main>
        </div>
    </body>
</html>
