<?php

namespace App\Livewire\Forms;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CheckoutForm extends Form
{
    public Cart $cart;

    public Collection $items;

    public User $user;

    #[Locked]
    public float $totalPrice = 0;

    public float $totalWeight = 0;

    public float $discountAmount = 0;

    public string $email = '';

    #[Validate]
    public string $name = '';

    public string $phone = '';

    public ?string $province = null;

    public ?string $city = null;

    public string $address = '';

    public string $postalCode = '';

    public ?string $shippingCourier = null;

    public ?string $shippingCourierService = null;

    public ?float $shippingCourierServiceTax = 0;

    public ?string $paymentMethod = null;

    public string $note = '';

    public bool $acceptTermsAndCondition = false;

    public function rules()
    {
        return [
            'name' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'email' => [
                'email',
            ],
            'phone' => [
                'required',
                'phone:ID',
            ],
            'province' => [
                'required',
                'numeric',
                'exists:provinces,id',
            ],
            'city' => [
                'required',
                'numeric',
                'exists:cities,id',
            ],
            'address' => [
                'required',
                'string',
                'min:10',
                'max:1000',
            ],
            'postalCode' => [
                'required',
                'numeric',
                'digits:5',
            ],
            'shippingCourier' => [
                'required',
                'string',
                'in:jne,pos,tiki',
            ],
            'shippingCourierService' => [
                'required',
                'string',
            ],
            'shippingCourierServiceTax' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'paymentMethod' => [
                'required',
                'string',
                'in:qris,gopay,shopeepay,dana,other_qris,bca_va,bni_va,bri_va,echannel,permata_va,cimb_va,other_va',
            ],
            'note' => [
                'nullable',
                'string',
                'min:3',
                'max:100',
            ],
            'acceptTermsAndCondition' => [
                'accepted',
            ],
        ];
    }

    public function validationAttributes()
    {
        return [
            'name' => 'Nama lengkap',
            'email' => 'Alamat email',
            'phone' => 'Nomor telefon',
            'province' => 'Provinsi',
            'city' => 'Kabupaten/Kota',
            'address' => 'Alamat lengkap',
            'postalCode' => 'Kode pos',
            'shippingCourier' => 'Kurir ekspedisi',
            'shippingCourierService' => 'Layanan ekspedisi',
            'shippingCourierServiceTax' => 'Biaya pengiriman',
            'paymentMethod' => 'Metode pembayaran',
            'note' => 'Catatan pesanan',
            'acceptTermsAndCondition' => 'Syarat dan Ketentuan toko',
        ];
    }

    public function setCheckoutData(Cart $cart)
    {
        $this->cart = $cart;
        $this->items = $cart->items;
        $this->totalPrice = $this->cart->calculateTotalPrice();
        $this->totalWeight = $this->cart->calculateTotalWeight();
        $this->discountAmount = $this->cart->discount ? $this->cart->discount->calculateDiscount($this->totalPrice) : 0;
        $this->setUser($this->cart);
    }

    private function setUser($cart)
    {
        $this->user = $cart->user;
        $this->name = $this->user->name;
        $this->email = $this->user->email;
        $this->phone = $this->safeDecrypt($this->user->phone_number);
        $this->province = $this->user->city_id ? $this->user->city->province_id : null;
        $this->city = $this->user->city_id ? $this->user->city_id : null;
        $this->address = $this->safeDecrypt($this->user->address);
        $this->postalCode = $this->safeDecrypt($this->user->postal_code);
    }

    private function safeDecrypt($encryptedValue)
    {
        try {
            return $encryptedValue ? \Illuminate\Support\Facades\Crypt::decryptString($encryptedValue) : '';
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return '';
        }
    }
}
