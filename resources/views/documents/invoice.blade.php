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

            section.company-details .company-logo {
                width: 50px;
                height: 50px;
                border-radius: 100%;
                background-color: #000;
                margin-bottom: 10px;
            }

            section.company-details h1 {
                font-size: 20px;
                font-weight: bolder;
                margin-bottom: 8px;
            }

            section.company-details .company-address p {
                width: 100%;
                max-width: 300px;
                margin: 4px 0;
            }

            section.company-details .company-address p a {
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
                border: 1px solid #ccc;
                color: white;
                text-align: left;
                padding: 5px;
                font-weight: normal;
            }

            main table tbody tr,
            main table tbody td {
                border: 1px solid #ccc;
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

            footer {
                width: 100%;
                padding: 8px 4px;
                background-color: #ffe6d4;
                text-align: center;
            }

            footer p {
                margin: 4px 0;
            }

            footer p a {
                color: #0c0c0c;
                text-underline-offset: 2px;
            }
        </style>
    </head>
    <body>
        <header>
            <section class="company-details">
                <div class="company-logo"></div>
                <h1>JOGJA ELECTRIK</h1>
                <div class="company-address">
                    <p><a href="https://google.com">123 Anywhere St., Any City</a></p>
                    <p><a href="https://google.com">123-456-7890</a></p>
                    <p><a href="mailto:someone@example.com">someone@example.com</a></p>
                </div>
            </section>
            <section class="invoice-details">
                <h2>INVOICE</h2>
                <p>
                    <span>Nomor Pesanan:</span>
                    {{ $order->order_number }}
                </p>
                <p>
                    <span>Tanggal Dibuat:</span>
                    {{ formatTimestamp($order->created_at) }}
                </p>
            </section>
        </header>
        <main>
            <section class="bill-to">
                <h3>PEMBELI:</h3>
                <p>{{ $order->user->name }}</p>
                <p>{{ \Illuminate\Support\Facades\Crypt::decryptString($order->shipping_address) }}</p>
                <p>+62 {{ \Illuminate\Support\Facades\Crypt::decryptString($order->user->phone_number) }}</p>
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
                            <td class="product-column">{{ $item->productVariant->product->name }}</td>
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
                        <td>- Rp {{ formatPrice($order->discount_amount) }}</td>
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
                            {{ strtoupper(str_replace('bank_transfer_', '', $order->payment->method)) }} VA
                        </p>
                        <p>
                            <span>Nomor Virtual Account:</span>
                            {{ $order->payment->reference_number }}
                        </p>
                    @elseif (str_contains($order->payment->method, 'ewallet_'))
                        <p>
                            <span>Metode Pembayaran:</span>
                            {{ strtoupper(str_replace('ewallet_', '', $order->payment->method)) }}
                        </p>
                    @endif
                    <p>
                        <span>Status Pembayaran:</span>
                        Berhasil
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
                        Untuk lebih lengkapnya, anda dapat mengakses
                        <a href="{{ route('terms-and-condition') }}">halaman ini</a>
                        .
                    </li>
                </ul>
            </section>
        </main>
        <footer>
            <p>
                Terimakasih telah berbelanja di
                <a href="{{ url('') }}">Jogja Electrik</a>
            </p>
            <p>&copy; 2024</p>
        </footer>
    </body>
</html>
