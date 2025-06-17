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

            section.company-details {
                margin-bottom: 40px;
            }

            section.company-details h1 {
                font-size: 20px;
                font-weight: bolder;
                margin-top: 10px;
                margin-bottom: 8px;
            }

            section.company-details .company-address p {
                width: 100%;
                max-width: 300px;
                margin: 10px 0;
            }

            section.company-details .company-address p a {
                font-weight: bold;
                color: #0c0c0c;
                text-underline-offset: 2px;
            }

            section.invoice-details {
                position: absolute;
                top: 40px;
                right: 40px;
                text-align: right;
            }

            section.invoice-details h2 {
                font-size: 20px;
                font-weight: bolder;
                margin-bottom: 8px;
            }

            section.invoice-details p {
                margin: 4px 0;
            }

            section.invoice-details p span {
                margin-right: 2px;
            }

            section.bill-to {
                margin-bottom: 30px;
            }

            section.bill-to h3 {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 8px;
            }

            section.bill-to p {
                width: 100%;
                max-width: 300px;
                margin: 4px 0;
            }

            main table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 40px;
            }

            main table thead tr th {
                background-color: #fd5722;
                border: 1px solid #000000;
                color: white;
                text-align: left;
                padding: 5px;
                font-weight: bold;
            }

            main table tbody tr,
            main table tbody td {
                border: 1px solid #000000;
            }

            main table tbody tr td {
                padding: 8px 4px;
            }

            main table tbody tr td.product-column {
                text-align: left;
                width: 50%;
            }

            main table tbody tr td.price-column {
                text-align: left;
                width: 20%;
            }

            main table tbody tr td.qty-column {
                text-align: center;
                width: 10%;
            }

            main table tbody tr td.amount-column {
                padding: 8px 4px;
                text-align: right;
                width: 20%;
            }

            main table tbody tr.summary-row td {
                border-bottom: none;
                padding-top: 10px;
                text-align: right;
            }

            main table tbody tr.summary-row td.total-label {
                font-size: 16px;
                font-weight: bold;
                color: #fd5722;
            }

            section.payment-info {
                margin: 40px 0;
            }

            section.payment-info h3 {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 8px;
            }

            section.payment-info p {
                margin: 4px 0;
            }

            section.terms {
                margin-bottom: 40px;
            }

            section.terms h3 {
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 8px;
            }

            section.terms ul {
                margin: 0 16px;
            }

            section.terms ul li {
                margin: 4px 0;
            }

            section.terms ul li a {
                color: #0c0c0c;
                text-underline-offset: 2px;
            }
        </style>
    </head>
    <body>
        <header>
            <section class="company-details">
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
                <h1>{{ config('app.name') }}</h1>
                <div class="company-address">
                    <p>
                        Alamat:
                        <a href="{{ config('business.map_link') }}">{{ config('business.address') }}</a>
                    </p>
                    <p>
                        Nomor WhatsApp:
                        <a href="{{ config('business.whatsapp') }}">{{ config('business.phone') }}</a>
                    </p>
                    <p>
                        Email:
                        <a href="mailto:{{ config('business.email') }}">{{ config('business.email') }}</a>
                    </p>
                </div>
            </section>
            <section class="invoice-details">
                <h2>INVOICE</h2>
                <p>
                    <span>Nomor Pesanan:</span>
                    <strong>{{ $order->order_number }}</strong>
                </p>
                <p>
                    <span>Tanggal Pesanan Dibuat:</span>
                    <strong>{{ formatTimestamp($order->created_at) }}</strong>
                </p>
            </section>
        </header>
        <main>
            <section class="bill-to">
                <h3>PEMBELI:</h3>
                <p>Nama: <strong>{{ $order->user->name }}</strong></p>
                <p>Alamat: <strong>{{ \Illuminate\Support\Facades\Crypt::decryptString($order->shipping_address) }}</strong></p>
                <p>
                    Nomor Telefon:
                    <strong>+62 {{ \Illuminate\Support\Facades\Crypt::decryptString($order->user->phone_number) }}</strong>
                </p>
            </section>
            <table cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>PRODUK</th>
                        <th>HARGA</th>
                        <th style="text-align: center">JUMLAH</th>
                        <th style="text-align: right">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->details as $item)
                        <tr>
                            @php
                                $variant = $item->productVariant->combinations->first()->variationVariant ?? null;
                            @endphp

                            <td class="product-column">
                                {{ $item->productVariant->product->name }}
                                @if ($variant)
                                        ({{ ucwords($variant->variation->name) . ' : ' . ucwords($variant->name) }})
                                @endif
                            </td>
                            <td class="price-column">Rp {{ formatPrice($item->price) }}</td>
                            <td class="qty-column">{{ $item->quantity }}</td>
                            <td class="amount-column">Rp {{ formatPrice($item->price * $item->quantity) }}</td>
                        </tr>
                    @endforeach

                    <tr class="summary-row">
                        <td colspan="3" style="text-align: right" class="amount-column"><span>Subtotal:</span></td>
                        <td>Rp {{ formatPrice($order->subtotal_amount) }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td colspan="3" style="text-align: right" class="amount-column"><span>Diskon:</span></td>
                        <td>- Rp {{ formatPrice(str_replace('-', '', $order->discount_amount)) }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td colspan="3" style="text-align: right" class="amount-column"><span>Ongkos Kirim:</span></td>
                        <td>+ Rp {{ formatPrice($order->shipping_cost_amount) }}</td>
                    </tr>
                    <tr class="summary-row">
                        <td colspan="3" style="text-align: right" class="amount-column total-label">
                            <span>TOTAL:</span>
                        </td>
                        <td class="total-label">Rp {{ formatPrice($order->total_amount) }}</td>
                    </tr>
                </tbody>
            </table>
            <section class="payment-info">
                <h3>INFORMASI PEMBAYARAN:</h3>
                @if ($order->payment->exists() && $order->payment->method)
                    @if (str_contains($order->payment->method, 'bank_transfer_'))
                        <p>
                            <span>Metode Pembayaran:</span>
                            <strong>{{ strtoupper(str_replace('bank_transfer_', '', $order->payment->method)) }} VA</strong>
                        </p>
                        <p>
                            <span>Nomor Referensi Pembayaran:</span>
                            <strong>{{ $order->payment->reference_number }}</strong>
                        </p>
                    @elseif (str_contains($order->payment->method, 'ewallet_'))
                        <p>
                            <span>Metode Pembayaran:</span>
                            <strong>{{ strtoupper(str_replace('ewallet_', '', $order->payment->method)) }}</strong>
                        </p>
                    @endif
                    <p>
                        <span>Status Pembayaran:</span>
                        <strong>Berhasil</strong>
                    </p>
                @else
                    <p>Anda belum membayar pesanan ini.</p>
                @endif
            </section>
            <section class="terms">
                <h3>SYARAT DAN KETENTUAN:</h3>
                <ul>
                    <li>Pembayaran harus diselesaikan maksimal 1 hari setelah pesanan dibuat.</li>
                    <li>Pesanan yang belum dibayar akan dibatalkan secara otomatis.</li>
                    <li>Anda dapat menghubungi kami untuk mengajukan pengembalian barang.</li>
                    <li>Waktu pengiriman tergantung pada lokasi tujuan dan jasa pengiriman yang dipilih.</li>
                    <li>
                        Untuk lebih lengkapnya, anda dapat mengakses halaman
                        <a href="{{ route('terms-and-conditions') }}">syarat dan ketentuan</a>
                        kami.
                    </li>
                </ul>
            </section>
        </main>
    </body>
</html>
