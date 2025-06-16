<?php

namespace App\Livewire\Forms;

use App\Models\Cart;
use App\Models\Discount;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CheckoutForm extends Form
{
    public ?Cart $cart = null;

    public ?User $user = null;

    public ?Discount $discount = null;

    #[Locked]
    public Collection $items;

    #[Locked]
    public float $totalPrice = 0;

    #[Locked]
    public float $totalWeight = 0;

    #[Locked]
    public float $discountAmount = 0;

    #[Locked]
    public string $email = '';

    #[Validate]
    public string $name = '';

    public ?string $phone = null;

    public ?string $province = null;

    public ?string $city = null;

    public ?string $address = null;

    public ?string $postalCode = null;

    public ?string $shippingCourier = null;

    public ?string $shippingCourierService = null;

    public float $shippingCourierServiceTax = 0;

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
            'note' => 'Catatan pesanan',
            'acceptTermsAndCondition' => 'Syarat dan Ketentuan toko',
        ];
    }

    public function setCheckoutData(Cart $cart)
    {
        $this->cart = $cart;
        $this->items = $cart->items;
        $this->totalPrice = $cart->total_price;
        $this->totalWeight = $cart->total_weight;

        $this->setUser($this->cart->user);

        if ($cart->discount) {
            $this->discount = $cart->discount;
            $this->calculateDiscount($cart->discount);
        }
    }

    private function setUser(User $user)
    {
        $this->user = $user;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone_number;
        $this->province = $user->province_id;
        $this->city = $user->city_id;
        $this->address = $user->address;
        $this->postalCode = $user->postal_code;
    }

    private function calculateDiscount(Discount $discount)
    {
        if ($discount->type === 'fixed') {
            $this->discountAmount = (float) min($discount->value, $this->totalPrice);
        } elseif ($discount->type === 'percentage') {
            $discountAmount = $this->totalPrice * ($discount->value / 100);

            if ($discount->max_discount_amount && $discountAmount > $discount->max_discount_amount) {
                $this->discountAmount = (float) $discount->max_discount_amount;
            } else {
                $this->discountAmount = (float) $discountAmount;
            }
        } else {
            $this->discountAmount = (float) 0.0;
        }
    }
}
