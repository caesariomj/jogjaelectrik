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
                'title' => '1. Apa itu Toko Jogja Electrik',
                'content' => 'Toko Jogja Electrik adalah toko yang menyediakan berbagai produk elektronik rumahan berkualitas. Kami menawarkan peralatan dapur, perangkat rumah tangga, serta alat elektronik lainnya. Kami berkomitmen untuk menyediakan barang berkualitas dengan harga bersaing dan pelayanan terbaik.',
            ],
            [
                'id' => 2,
                'title' => '2. Apakah Toko Jogja Electrik memiliki toko fisik?',
                'content' => 'Ya, lokasi kami berada di Yogyakarta, di Jalan-jalan di akhir pekan.',
            ],
            [
                'id' => 3,
                'title' => '3. Apa saja produk yang dijual di Toko Jogja Electrik?',
                'content' => 'Kami menjual berbagai jenis produk elektronik rumahan, termasuk peralatan dapur seperti blender, mixer, dan lain-lain, serta perangkat rumah tangga seperti setrika, kipas angin, hingga alat elektronik lainnya. Untuk lebih lengkapnya anda dapat melihatnya di menu produk.',
            ],
            [
                'id' => 4,
                'title' => '4. Apakah produk yang dijual asli dan bergaransi?',
                'content' => 'Ya, semua produk yang kami jual adalah 100% asli dan berasal dari produsen terpercaya. Setiap produk juga dilengkapi dengan garansi resmi dari pabrik sesuai dengan ketentuan yang berlaku, yang memberikan Anda perlindungan tambahan jika terjadi kerusakan atau masalah pada produk.',
            ],
            [
                'id' => 5,
                'title' => '5. Jam operasional Toko Jogja Electrik?',
                'content' => 'Toko Jogja Electrik beroperasi secara online 24 jam. Namun, untuk layanan pelanggan kami tersedia setiap hari pukul 08:00 - 21:00 WIB. Anda dapat menghubungi kami selama jam operasional tersebut untuk bantuan lebih lanjut.',
            ],
            [
                'id' => 6,
                'title' => '6. Bagaimana cara memesan produk?',
                'content' => 'Untuk melakukan pemesanan, Anda perlu membuat akun terlebih dahulu. Selanjutnya anda cukup memilih produk yang Anda inginkan di website kami, atur jumlah yang Anda inginkan lalu klik "Tambah ke Keranjang", dan lanjutkan ke halaman checkout. Anda akan diminta untuk memasukkan informasi pengiriman dan memilih metode pembayaran yang diinginkan. Setelah pembayaran dikonfirmasi, pesanan Anda akan diproses dan segera dikirim.',
            ],
            [
                'id' => 7,
                'title' => '7. Apakah saya bisa membatalkan pesanan?',
                'content' => 'Pesanan dapat dibatalkan sebelum pesanan dikirim. Jika Anda ingin membatalkan pesanan, silakan akses halaman "Pesanan Saya" lalu klik tombol "Batalkan Pesanan" dan pilih alasan pembatalan pesanan Anda.',
            ],
            [
                'id' => 8,
                'title' => '8. Apa saja metode pembayaran yang tersedia?',
                'content' => 'Kami menerima berbagai metode pembayaran, termasuk virtual akun bank, serta beberapa metode pembayaran digital seperti e-wallet. Semua transaksi dijamin aman karena melalui payment gateway Xendit dan dengan enkripsi SSL untuk melindungi data pribadi Anda.',
            ],
            [
                'id' => 9,
                'title' => '9. Apa saja layanan ekspedisi pengiriman yang tersedia?',
                'content' => 'Kami menggunakan beberapa ekspedisi pengiriman terpercaya untuk pengiriman produk Anda, termasuk JNE, TIKI, dan POS INDONESIA. Anda dapat memilih layanan pengiriman yang sesuai dengan preferensi Anda saat checkout.',
            ],
            [
                'id' => 10,
                'title' => '10. Berapa lama waktu pengiriman?',
                'content' => 'Waktu pengiriman dapat bervariasi tergantung pada lokasi Anda dan pilihan ekspedisi yang digunakan. Anda dapat melihat estimasi waktu pengiriman ketika memilih ekspedisi beserta layanan-nya pada saat Anda melakukan checkout.',
            ],
            [
                'id' => 11,
                'title' => '11. Apakah saya bisa melacak pesanan saya?',
                'content' => 'Ya, setelah pesanan Anda dikirim, kami akan memberikan nomor resi yang dapat Anda gunakan untuk melacak status pengiriman. Anda bisa melacak pesanan melalui situs web ekspedisi yang anda pilih untuk mengirim pesanan Anda.',
            ],
            [
                'id' => 12,
                'title' => '12. Bagaimana kebijakan pengembalian barang?',
                'content' => 'Kami menerima pengembalian barang dalam waktu 7 hari setelah barang diterima, dengan syarat produk dalam kondisi asli, belum digunakan, dan masih dalam kemasan aslinya. Produk yang rusak atau cacat saat diterima dapat dikembalikan untuk penggantian sesuai dengan ketentuan garansi. Anda dapat menghubungi layanan pelanggan kami untuk melakukan pengembalian barang.',
            ],
            [
                'id' => 13,
                'title' => '13. Bagaimana cara mengajukan refund?',
                'content' => 'Jika anda ingin membatalkan pesanan yang telah dibayar, atau produk yang Anda terima tidak sesuai dengan pesanan atau mengalami kerusakan, Anda dapat mengajukan refund dengan menghubungi layanan pelanggan kami. Proses pengembalian dana akan dilakukan dalam waktu 3-5 hari kerja setelah permintaan Anda disetujui.',
            ],
            [
                'id' => 14,
                'title' => '14. Bagaimana cara menghubungi layanan pelanggan?',
                'content' => 'Anda dapat menghubungi nomor telepon kami di [nomor telepon]. Layanan pelanggan kami siap membantu Anda dari Senin hingga Sabtu, pukul 08:00 - 21:00 WIB.',
            ],
            [
                'id' => 15,
                'title' => '15. Bagaimana cara mendapatkan promo atau diskon?',
                'content' => 'Kami juga menyediakan promo atau diskon yang dapat digunakan pada pembelian tertentu, yang akan diinformasikan melalui email setelah anda membuat akun.',
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
                'content' => 'Untuk melakukan pembelian di toko kami, Anda perlu membuat akun terlebih dahulu. Dengan membuat akun, Anda dapat menyimpan alamat pengiriman, melihat riwayat pesanan, dan mendapatkan promo eksklusif. Anda juga dapat menghapus akun Anda pada halaman "Pengaturan Akun". Jika Anda mengalami masalah saat mendaftar, masuk, atau menghapus akun, silakan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 2,
                'title' => 'Cara Pemesanan',
                'content' => 'Untuk memesan produk, cari barang yang diinginkan lalu klik "Tambah ke Keranjang". Setelah itu, masuk ke halaman keranjang, periksa pesanan Anda, dan klik "Checkout". Isi detail pengiriman, pilih metode pembayaran, lalu selesaikan transaksi. Anda akan menerima email dan pesan WhatsApp konfirmasi setelah pesanan berhasil dibuat dan setelah pembayaran Anda telah diselesaikan.',
            ],
            [
                'id' => 3,
                'title' => 'Pembayaran dan Transaksi',
                'content' => 'Kami menerima berbagai metode pembayaran, termasuk transfer bank dan e-wallet melalui payment gateway yang aman. Semua transaksi diproses dengan enkripsi SSL untuk melindungi data Anda. Jika pembayaran belum terverifikasi dalam 1x24 jam, silakan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 4,
                'title' => 'Kebijakan Pengiriman',
                'content' => 'Pesanan Anda akan diproses dalam waktu 1-2 hari kerja setelah pembayaran dikonfirmasi. Waktu pengiriman tergantung pada lokasi tujuan dan jasa ekspedisi yang dipilih. Anda dapat melacak pesanan melalui halaman "Pesanan Saya". Jika ada kendala dalam pengiriman, silakan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 5,
                'title' => 'Kebijakan Pengembalian Barang',
                'content' => 'Jika Anda menerima produk yang rusak atau tidak sesuai, Anda dapat mengajukan pengembalian dalam waktu 7 hari setelah barang diterima. Produk harus dalam kondisi asli dan dikembalikan dengan kemasan lengkap. Silakan hubungi layanan pelanggan untuk proses pengembalian dan syarat lebih lanjut.',
            ],
            [
                'id' => 6,
                'title' => 'Kebijakan Privasi dan Keamanan',
                'content' => 'Kami menghargai privasi pelanggan kami. Data pribadi yang Anda berikan hanya digunakan untuk keperluan pemrosesan pesanan dan layanan pelanggan. Kami tidak membagikan informasi pelanggan kepada pihak ketiga tanpa izin. Seluruh transaksi dilindungi dengan enkripsi tingkat tinggi.',
            ],
            [
                'id' => 7,
                'title' => 'Kontak Layanan Pelanggan',
                'content' => 'Jika Anda membutuhkan bantuan, Anda dapat menghubungi kami melalui email, WhatsApp, atau media sosial resmi kami. Layanan pelanggan tersedia setiap hari dari pukul 08:00 - 21:00 WIB. Kami berkomitmen untuk memberikan solusi terbaik bagi setiap permasalahan pelanggan.',
            ],
            [
                'id' => 8,
                'title' => 'Promo dan Diskon',
                'content' => 'Kami secara rutin menawarkan promo dan diskon menarik untuk pelanggan setia. Kami akan mengirimkan email kepada Anda untuk memberikan informasi promo dan diskon terbaru. Beberapa promo dan diskon memiliki syarat dan ketentuan tertentu, jadi pastikan untuk membaca detailnya sebelum melakukan transaksi.',
            ],
            [
                'id' => 9,
                'title' => 'Garansi Produk',
                'content' => 'Semua produk yang kami jual memiliki garansi resmi dari pabrik atau distributor. Masa garansi bervariasi tergantung produk yang dapat anda lihat di halaman detail produk terkait. Untuk klaim garansi, simpan bukti pembelian dan hubungi layanan pelanggan kami.',
            ],
            [
                'id' => 10,
                'title' => 'Cara Melacak Pesanan',
                'content' => 'Setelah pesanan dikirim, Anda akan menerima nomor resi pengiriman. Anda bisa melihat nomor resi ini pada halaman "Pesanan Saya" setelah login. Untuk melacak status pengiriman, kunjungi situs ekspedisi yang digunakan (JNE, POS INDONESIA, atau TIKI) dan masukkan nomor resi tersebut pada kolom pelacakan.',
            ],
        ];

        return view('pages.help', compact('helpItems'));
    }
}
