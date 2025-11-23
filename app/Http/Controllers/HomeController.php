<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Discount;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Displays the main page.
     */
    public function index(): View
    {
        $primaryCategories = Category::queryPrimary()->get();

        $bannerSlides = $primaryCategories->map(function ($category, $key) {
            return (object) [
                'imgSrc' => $key % 2 === 0 ? 'https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-1.webp' : 'https://penguinui.s3.amazonaws.com/component-assets/carousel/default-slide-2.webp',
                'imgAlt' => 'Banner kategori '.$category->name.'.',
                'title' => ucwords($category->name),
                'description' => $key % 2 === 0 ? 'Jelajahi koleksi '.$category->name.' dengan pilihan terbaik untuk memenuhi kebutuhan Anda.' : 'Temukan berbagai pilihan '.$category->name.' yang siap melengkapi kebutuhan Anda dengan kualitas terbaik.',
                'ctaUrl' => route('products.category', ['category' => $category->slug]),
                'ctaText' => 'Jelajahi Produk '.ucwords($category->name),
            ];
        });

        $bestSellingProducts = Product::queryAllWithRelations(columns: [
            'products.id',
            'products.name',
            'products.slug',
            'products.base_price',
            'products.base_price_discount',
        ], relations: [
            'thumbnail',
            'category',
            'rating',
        ])
            ->where('products.is_active', true)
            ->limit(8)
            ->orderByDesc(
                DB::table('order_details')
                    ->join('product_variants', 'product_variants.id', '=', 'order_details.product_variant_id')
                    ->selectRaw('COALESCE(SUM(order_details.quantity), 0)')
                    ->whereColumn('product_variants.product_id', 'products.id')
            )
            ->get()
            ->map(function ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'link' => $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]),
                    'price' => $product->base_price,
                    'price_discount' => $product->base_price_discount,
                    'thumbnail' => asset('storage/uploads/product-images/'.$product->thumbnail),
                    'rating' => number_format($product->average_rating, 1),
                ];
            });

        $activeDiscount = Discount::queryAllUsable(
            userId: auth()->check() ? auth()->user()->id : null,
            columns: [
                'id',
                'name',
                'description',
                'code',
                'type',
                'value',
                'max_discount_amount',
                'end_date',
            ])
            ->when(auth()->check() && auth()->user()->cart()->exists(), function ($query) {
                $query->where('id', '!=', auth()->user()->cart->discount_id);
            })
            ->first();

        $activeDiscount = $activeDiscount ? (new Discount)->newFromBuilder($activeDiscount) : null;

        $latestProducts = Product::queryAllWithRelations(columns: [
            'products.id',
            'products.name',
            'products.slug',
            'products.base_price',
            'products.base_price_discount',
        ], relations: [
            'thumbnail',
            'category',
            'rating',
        ])
            ->where('products.is_active', true)
            ->limit(8)
            ->orderByDesc('products.created_at')
            ->get()
            ->map(function ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'link' => $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]),
                    'price' => $product->base_price,
                    'price_discount' => $product->base_price_discount,
                    'thumbnail' => asset('storage/uploads/product-images/'.$product->thumbnail),
                    'rating' => number_format($product->average_rating, 1),
                ];
            });

        return view('pages.home', compact('bannerSlides', 'bestSellingProducts', 'activeDiscount', 'latestProducts'));
    }

    /**
     * Displays the products page.
     */
    public function products(Request $request, string $category = '', string $subcategory = ''): View|RedirectResponse
    {
        $validatedSearch = $request->validate([
            'q' => ['sometimes', 'string', 'max:255'],
        ]);

        $search = isset($validatedSearch['q']) ? $validatedSearch['q'] : '';

        $categoryAndSubcategoryValidator = validator([
            'category' => $category,
            'subcategory' => $subcategory,
        ], [
            'category' => ['sometimes', 'string', 'lowercase', 'max:255', 'exists:categories,slug'],
            'subcategory' => ['sometimes', 'string', 'lowercase', 'max:255', 'exists:subcategories,slug'],
        ]);

        if ($categoryAndSubcategoryValidator->fails()) {
            session()->flash('error', $subcategory ? 'Produk dengan subkategori '.str_replace('-', ' ', $subcategory).' tidak ditemukan' : ($category ? 'Produk dengan kategori '.str_replace('-', ' ', $category).' tidak ditemukan' : 'Produk tidak ditemukan'));

            return redirect()->route('home');
        }

        $validatedCategoryAndSubcategory = $categoryAndSubcategoryValidator->validated();

        $category = $validatedCategoryAndSubcategory['category'] ?? '';

        $subcategory = $validatedCategoryAndSubcategory['subcategory'] ?? '';

        return view('pages.products', compact('category', 'subcategory', 'search'));
    }

    /**
     * Displays the product detail page.
     */
    public function productDetail(?string $category, ?string $subcategory, string $slug): View|RedirectResponse
    {
        $product = Product::queryBySlug(slug: $slug, columns: [
            'products.id',
            'products.subcategory_id',
            'products.name',
            'products.slug',
            'products.description',
            'products.main_sku',
            'products.base_price',
            'products.base_price_discount',
            'products.is_active',
            'products.warranty',
            'products.material',
            'products.dimension',
            'products.package',
            'products.weight',
            'products.power',
            'products.voltage',
        ], relations: [
            'category',
            'images',
            'variation',
            'reviews',
            'aggregates',
        ]);

        if (! $product) {
            session()->flash('error', 'Produk dengan nama '.str_replace('-', ' ', $slug).' tidak ditemukan.');

            return redirect()->route('products');
        }

        $product->images = collect($product->images);

        $productRecommendations = Product::queryAllWithRelations(columns: [
            'products.id',
            'products.name',
            'products.slug',
            'products.base_price',
            'products.base_price_discount',
        ], relations: [
            'thumbnail',
            'category',
            'rating',
        ])
            ->where('products.is_active', true)
            ->when($product->category, function ($query) use ($product) {
                return $query->where('categories.id', $product->category->id);
            })
            ->whereNot('products.id', $product->id)
            ->limit(8)
            ->orderByDesc('products.created_at')
            ->get()
            ->map(function ($product) {
                return (object) [
                    'id' => $product->id,
                    'name' => $product->name,
                    'link' => $product->category_slug && $product->subcategory_slug ? route('products.detail', ['category' => $product->category_slug, 'subcategory' => $product->subcategory_slug, 'slug' => $product->slug]) : route('products.detail.without.category.subcategory', ['slug' => $product->slug]),
                    'price' => $product->base_price,
                    'price_discount' => $product->base_price_discount,
                    'thumbnail' => asset('storage/uploads/product-images/'.$product->thumbnail),
                    'rating' => number_format($product->average_rating, 1),
                ];
            });

        $product = (new Product)->newFromBuilder($product);

        return view('pages.product-detail', compact('product', 'productRecommendations'));
    }

    /**
     * Displays the about page.
     */
    public function about(): View
    {
        return view('pages.about');
    }

    /**
     * Displays the contact page.
     */
    public function contact(): View
    {
        return view('pages.contact');
    }

    /**
     * Displays the terms and condition page.
     */
    public function termsAndConditions(): View
    {
        return view('pages.terms-and-conditions');
    }

    /**
     * Displays the privacy policy page.
     */
    public function privacyPolicy(): View
    {
        return view('pages.privacy-policy');
    }

    /**
     * Displays the faq page.
     */
    public function faq(): View
    {
        $faqItems = [
            [
                'id' => 1,
                'title' => 'Apa itu Toko Jogja Electrik',
                'content' => 'Toko Jogja Electrik adalah toko yang menyediakan berbagai <strong>produk elektronik rumahan berkualitas</strong>. Kami menawarkan <strong>peralatan dapur, perangkat rumah tangga</strong>, serta alat elektronik lainnya. Kami berkomitmen untuk menyediakan <strong>barang berkualitas dengan harga bersaing</strong> dan pelayanan terbaik.',
            ],
            [
                'id' => 2,
                'title' => 'Apakah Toko Jogja Electrik memiliki toko fisik?',
                'content' => 'Ya, lokasi kami berada di <strong>Yogyakarta</strong>, '.config('business.address').'.',
            ],
            [
                'id' => 3,
                'title' => 'Apa saja produk yang dijual di Toko Jogja Electrik?',
                'content' => 'Kami menjual berbagai jenis produk elektronik rumahan, termasuk <strong>peralatan dapur</strong> seperti blender, mixer, dan lain-lain, serta <strong>perangkat rumah tangga</strong> seperti setrika, kipas angin, hingga alat elektronik lainnya. Untuk lebih lengkapnya anda dapat melihatnya di halaman <strong>"Produk"</strong>.',
            ],
            [
                'id' => 4,
                'title' => 'Apakah produk yang dijual asli dan bergaransi?',
                'content' => 'Ya, semua produk yang kami jual adalah <strong>100% asli</strong> dan berasal dari <strong>produsen terpercaya</strong>. Setiap produk juga dilengkapi dengan <strong>garansi resmi pabrik</strong> sesuai dengan ketentuan yang berlaku, yang memberikan Anda perlindungan tambahan jika terjadi kerusakan atau masalah pada produk.',
            ],
            [
                'id' => 5,
                'title' => 'Jam operasional Toko Jogja Electrik?',
                'content' => 'Toko Jogja Electrik beroperasi secara online <strong>24 jam</strong>. Namun, untuk <strong>layanan pelanggan</strong> kami tersedia setiap hari pukul <strong>08:00 - 21:00 WIB</strong>. Anda dapat menghubungi kami selama jam operasional tersebut untuk bantuan lebih lanjut.',
            ],
            [
                'id' => 6,
                'title' => 'Bagaimana cara memesan produk?',
                'content' => 'Untuk melakukan pemesanan, Anda perlu <strong>membuat akun terlebih dahulu</strong>. Selanjutnya anda cukup memilih produk yang Anda inginkan, atur jumlah lalu klik <strong>"Tambah ke Keranjang"</strong>, dan lanjutkan ke halaman checkout. Anda akan diminta untuk memasukkan <strong>informasi pengiriman</strong> dan memilih <strong>metode pembayaran</strong>. Setelah pembayaran dikonfirmasi, pesanan Anda akan <strong>diproses dan segera dikirim</strong>.',
            ],
            [
                'id' => 7,
                'title' => 'Apakah saya bisa membatalkan pesanan?',
                'content' => 'Pesanan dapat dibatalkan <strong>sebelum pesanan dikirim</strong>. Jika Anda ingin membatalkan pesanan, silakan akses halaman <strong>"Pesanan Saya"</strong> lalu klik tombol <strong>"Batalkan Pesanan"</strong> dan <strong>pilih alasan pembatalan</strong>.',
            ],
            [
                'id' => 8,
                'title' => 'Bagaimana cara mendapatkan promo atau diskon?',
                'content' => 'Kami menyediakan <strong>promo dan diskon</strong> yang akan diinformasikan melalui email setelah Anda <strong>membuat akun</strong>.',
            ],
            [
                'id' => 9,
                'title' => 'Apa saja metode pembayaran yang tersedia?',
                'content' => 'Kami menerima berbagai metode pembayaran, termasuk <strong>virtual account bank</strong>, serta metode digital seperti <strong>e-wallet</strong>. Semua transaksi dijamin <strong>aman melalui payment gateway Xendit</strong> dan dilindungi dengan <strong>enkripsi SSL</strong>.',
            ],
            [
                'id' => 10,
                'title' => 'Apakah Bisa COD?',
                'content' => 'Saat ini kami <strong>belum menyediakan metode pembayaran COD</strong>. Pembayaran dilakukan melalui Virtual Account atau e-Wallet melalui Xendit.',
            ],
            [
                'id' => 11,
                'title' => 'Bagaimana cara mengajukan refund?',
                'content' => 'Proses pengembalian dana untuk metode pembayaran <strong>e-wallet</strong> akan diproses dalam <strong>1-2 hari kerja</strong> setelah permintaan refund disetujui. Sementara Untuk pembayaran via <strong>Virtual Account</strong>, silakan <strong>hubungi layanan pelanggan</strong> melalui WhatsApp dan sertakan: <strong>nomor pesanan, nama pemesan sesuai dengan sistem, rekening tujuan</strong>.',
            ],
            [
                'id' => 12,
                'title' => 'Apa saja layanan ekspedisi pengiriman yang tersedia?',
                'content' => 'Kami menggunakan beberapa ekspedisi terpercaya, termasuk <strong>'.strtoupper(str_replace(':', ', ', config('services.rajaongkir.courier_codes'))).'</strong>. Anda dapat memilih layanan pengiriman yang sesuai dengan preferensi Anda saat checkout.',
            ],
            [
                'id' => 13,
                'title' => 'Bagaimana biaya pengiriman dihitung?',
                'content' => '<strong>Biaya pengiriman ditentukan berdasarkan berat total produk, lokasi tujuan, dan ekspedisi yang dipilih.</strong> Estimasi ongkir akan ditampilkan otomatis di halaman checkout sebelum Anda melakukan pembayaran.',
            ],
            [
                'id' => 14,
                'title' => 'Berapa lama waktu pengiriman?',
                'content' => 'Waktu pengiriman <strong>bervariasi tergantung lokasi pengiriman</strong> dan <strong>jasa ekspedisi</strong> yang digunakan. Estimasi waktu pengiriman dapat dilihat pada saat <strong>checkout</strong> dan di halaman <strong>"Pesanan Saya"</strong>.',
            ],
            [
                'id' => 15,
                'title' => 'Apakah saya bisa melacak pesanan saya?',
                'content' => 'Ya, setelah pesanan Anda dikirim, kami akan memberikan <strong>nomor resi pengiriman</strong> yang dapat digunakan untuk melacak status pengiriman melalui situs ekspedisi terkait.',
            ],
            [
                'id' => 16,
                'title' => 'Bagaimana kebijakan pengembalian barang?',
                'content' => 'Kami menerima <strong>pengembalian barang dalam waktu 7 hari</strong> setelah barang diterima, dengan syarat produk dalam <strong>kondisi asli</strong>, belum digunakan, dan masih dalam <strong>kemasan lengkap</strong>. Produk <strong>rusak atau cacat</strong> dapat dikembalikan untuk penggantian sesuai garansi. Silakan hubungi layanan pelanggan untuk proses pengembalian.',
            ],
            [
                'id' => 17,
                'title' => 'Bagaimana Jika Produk Rusak Saat Pengiriman?',
                'content' => 'Jika barang diterima dalam kondisi rusak atau cacat akibat proses pengiriman, silakan hubungi kami dalam <strong>maksimal 1x24 jam</strong> melalui WhatsApp dengan menyertakan:<br>• <strong>Foto kondisi barang</strong><br>• <strong>Foto packing luar dan dalam</strong><br>• <strong>Video unboxing</strong>',
            ],
            [
                'id' => 18,
                'title' => 'Bagaimana cara menghubungi layanan pelanggan?',
                'content' => 'Anda dapat menghubungi kami melalui kontak yang tertera di halaman <strong>"Kontak Kami"</strong>. Layanan pelanggan tersedia <strong>Senin - Sabtu pukul 08:00 - 21:00 WIB</strong>.',
            ],
        ];

        return view('pages.faq', compact('faqItems'));
    }

    /**
     * Displays the help page.
     */
    public function help(): View
    {
        $helpItems = [
            [
                'id' => 1,
                'title' => 'Akun Pengguna',
                'content' => 'Untuk melakukan pembelian di toko kami, Anda perlu <strong>membuat akun terlebih dahulu</strong>. Dengan membuat akun, Anda dapat menyimpan alamat pengiriman, melihat riwayat pesanan, dan mendapatkan promo eksklusif. Anda juga dapat <strong>menghapus akun Anda</strong> pada halaman <strong>"Pengaturan Akun"</strong>. Jika Anda mengalami masalah saat mendaftar, masuk, atau menghapus akun, silakan hubungi <strong>layanan pelanggan</strong> kami.',
            ],
            [
                'id' => 2,
                'title' => 'Cara Pemesanan',
                'content' => 'Untuk memesan produk, cari barang yang diinginkan, atur jumlah kuantitas, lalu klik <strong>"Tambah ke Keranjang"</strong>. Setelah itu, masuk ke halaman <strong>"Keranjang Belanja"</strong>, periksa pesanan Anda, dan klik tombol <strong>"Checkout"</strong>. Isi detail pengiriman, pilih metode pembayaran, lalu selesaikan transaksi. Anda akan menerima <strong>email dan pesan WhatsApp konfirmasi</strong> setelah pesanan berhasil dibuat dan setelah pembayaran diselesaikan.',
            ],
            [
                'id' => 3,
                'title' => 'Pembayaran dan Transaksi',
                'content' => 'Kami menerima berbagai metode pembayaran, termasuk <strong>transfer bank</strong> melalui <strong>virtual account</strong> serta <strong>e-wallet</strong>. Semua pemrosesan pembayaran dilakukan melalui <strong>payment gateway resmi dan aman</strong> dengan enkripsi SSL untuk melindungi data Anda. Jika pembayaran <strong>belum terverifikasi dalam 1x24 jam</strong>, silakan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 4,
                'title' => 'Kebijakan Pengiriman',
                'content' => 'Pesanan Anda akan diproses dalam <strong>1-2 hari kerja</strong> setelah pembayaran dikonfirmasi. Waktu pengiriman tergantung pada <strong>lokasi tujuan</strong> dan <strong>jasa ekspedisi</strong> yang dipilih. Anda dapat melihat pesanan Anda melalui halaman <strong>"Pesanan Saya"</strong>. Jika ada kendala dalam pengiriman, silakan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 5,
                'title' => 'Cara Melacak Pesanan',
                'content' => 'Setelah pesanan dikirim, Anda akan menerima <strong>nomor resi pengiriman</strong>. Anda dapat melihat nomor resi ini pada halaman <strong>"Pesanan Saya"</strong>. Untuk melacak status pengiriman, kunjungi situs ekspedisi yang digunakan dan masukkan <strong>nomor resi</strong> pesanan Anda.',
            ],
            [
                'id' => 6,
                'title' => 'Tentang Refund Pesanan',
                'content' => 'Refund pesanan dapat Anda ajukan ketika pesanan <strong>sudah dibayar</strong> namun belum dikirim.<br><br>Refund hanya dapat dilakukan untuk pesanan dengan status:<br><strong>• Menunggu Diproses</strong> (pesanan sudah diterima dan menunggu pengemasan)<br><strong>• Menunggu Pengiriman</strong> (pesanan sudah disiapkan dan menunggu diserahkan ke ekspedisi)<br><br>Jika pesanan sudah berada pada tahap pengiriman atau telah selesai, refund tidak dapat diajukan.',
            ],
            [
                'id' => 7,
                'title' => 'Cara Mengajukan Refund',
                'content' => 'Anda dapat mengikuti langkah berikut untuk melakukan refund:<br>1. Masuk ke halaman <strong>"Pesanan Saya"</strong><br>2. Pilih pesanan yang ingin dibatalkan<br>3. Pastikan status pesanan adalah <strong>"Menunggu diproses"</strong> atau <strong>"Menunggu pengiriman"</strong><br>4. Klik tombol <strong>"Batalkan Pesanan"</strong><br>5. Pilih salah satu alasan pembatalan yang tersedia. Jika tidak sesuai, pilih opsi <strong>"Alasan Lainnya"</strong> di bagian paling bawah dan tuliskan alasan Anda secara manual.<br>6. Klik tombol <strong>"Batalkan Pesanan"</strong><br><br>Setelah dikirim, permintaan refund akan masuk ke sistem untuk diperiksa oleh kami.',
            ],
            [
                'id' => 8,
                'title' => 'Verifikasi Permintaan Refund',
                'content' => 'Setiap pengajuan refund <strong>tidak langsung disetujui</strong>, melainkan harus diverifikasi oleh kami terlebih dahulu. Kami berhak untuk:<br>• Menyetujui pengajuan refund<br>• Menolak permintaan refund<br><br>Jika ditolak, kami akan memberikan penjelasan mengenai alasan penolakan agar pelanggan dapat memahami penyebabnya.',
            ],
            [
                'id' => 9,
                'title' => 'Berapa Lama Proses Refund',
                'content' => 'Setelah pengajuan refund disetujui oleh admin, proses pengembalian dana akan mengikuti metode pembayaran yang Anda gunakan saat checkout.<br><br><strong>1. E-Wallet</strong><br>Jika Anda membayar menggunakan e-wallet (misalnya: OVO, Dana, ShopeePay, GoPay, dll):<br>• Kami akan memproses refund melalui sistem pembayaran Xendit<br>• Dana biasanya kembali dalam ±1 hari kerja setelah permintaan diproses<br>• Waktu dapat sedikit berbeda tergantung kebijakan penyedia e-wallet<br><br>Anda dapat memantau status refund melalui halaman <strong>"Riwayat Transaksi"</strong> → <strong>"Detail Transaksi"</strong> yang di refund.<br><br><strong>2. Virtual Account Bank</strong><br>Jika Anda membayar melalui Virtual Account (VA):<br>• Refund tidak dapat diproses otomatis melalui Xendit<br>• Setelah pengajuan refund disetujui, Anda wajib menghubungi tim kami agar proses pengembalian dana dapat dilakukan secara manual<br><br>Silakan hubungi kami melalui kontak kami dengan menyertakan:<br><strong>• Nomor pesanan</strong><br><strong>• Nama pemesan sesuai dengan sistem</strong><br><strong>• Nomor rekening tujuan refund</strong><br><br>Proses pengembalian dana akan dilakukan langsung oleh tim kami sesuai data yang Anda berikan.',
            ],
            [
                'id' => 10,
                'title' => 'Kapan Refund Tidak Bisa Diajukan',
                'content' => 'Pengajuan refund tidak bisa dilakukan jika:<br>1. Pesanan belum dibayar<br>2. Pesanan sudah masuk ke proses pengiriman oleh kurir<br>3. Pesanan telah selesai<br>4. Alasan pembatalan tidak sesuai dengan kebijakan toko<br><br>Selama pesanan masih dalam tahap <strong>Menunggu Diproses</strong> atau <strong>Menunggu Pengiriman</strong>, Anda tetap dapat mengajukan pembatalan.',
            ],
            [
                'id' => 11,
                'title' => 'Garansi Produk',
                'content' => 'Semua produk yang kami jual memiliki <strong>garansi resmi</strong> dari pabrik atau distributor. Masa garansi <strong>bervariasi tergantung produk</strong> dan dapat Anda lihat di halaman detail produk terkait. Untuk <strong>klaim garansi</strong>, simpan bukti pembelian dan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 12,
                'title' => 'Kebijakan Pengembalian Barang',
                'content' => 'Jika Anda menerima produk yang <strong>rusak atau tidak sesuai</strong>, Anda dapat mengajukan pengembalian dalam waktu maksimal <strong>7 hari</strong> setelah barang diterima. Produk harus dalam <strong>kondisi asli</strong> dan dikembalikan dengan <strong>kemasan lengkap</strong>. Silakan hubungi layanan pelanggan untuk proses pengembalian dan informasi lebih lanjut.',
            ],
            [
                'id' => 13,
                'title' => 'Promo dan Diskon',
                'content' => 'Kami juga menawarkan <strong>promo dan diskon menarik</strong> untuk Anda. Kami akan mengirimkan email kepada Anda untuk memberikan informasi promo dan diskon terbaru. Beberapa promo dan diskon memiliki <strong>syarat dan ketentuan</strong> tertentu, jadi pastikan untuk membaca detailnya sebelum melakukan transaksi.',
            ],
            [
                'id' => 14,
                'title' => 'Kebijakan Privasi dan Keamanan',
                'content' => 'Kami menghargai <strong>privasi pelanggan</strong> kami. Data pribadi yang Anda berikan hanya digunakan untuk keperluan <strong>pemrosesan pesanan dan layanan pelanggan</strong>. Kami hanya membagikan data yang diperlukan kepada <strong>mitra resmi yang terlibat dalam transaksi</strong>, seperti payment gateway, dan <strong>tidak membagikan informasi pelanggan kepada pihak ketiga lainnya</strong> tanpa izin. Seluruh transaksi dilindungi dengan <strong>enkripsi</strong>.',
            ],
            [
                'id' => 15,
                'title' => 'Kontak Layanan Pelanggan',
                'content' => 'Jika Anda membutuhkan bantuan, Anda dapat menghubungi kami melalui <strong>email, WhatsApp, atau media sosial resmi</strong> kami. Layanan pelanggan tersedia setiap hari dari <strong>08:00 - 21:00 WIB</strong>. Kami berkomitmen memberikan <strong>solusi terbaik</strong> bagi setiap permasalahan pelanggan.',
            ],
        ];

        return view('pages.help', compact('helpItems'));
    }
}
