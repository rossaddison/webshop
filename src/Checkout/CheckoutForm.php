<?php

declare(strict_types=1);

namespace App\Checkout;

use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Rule\Email;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\RulesProviderInterface;

/**
 * Matches invoice's `OrderService::createOrder()` `$customer` array shape
 * exactly (`src/Api/OrderService.php` on the invoice side) — name,
 * surname, email required, the rest optional.
 */
final class CheckoutForm extends FormModel implements RulesProviderInterface
{
    private string $name = '';
    private string $surname = '';
    private string $email = '';
    private string $address1 = '';
    private string $address2 = '';
    private string $city = '';
    private string $zip = '';
    private string $country = '';
    private string $phone = '';

    #[\Override]
    public function getFormName(): string
    {
        return 'Checkout';
    }

    #[\Override]
    public function getRules(): array
    {
        return [
            'name' => [new Required(), new Length(max: 100)],
            'surname' => [new Required(), new Length(max: 100)],
            'email' => [new Required(), new Email()],
            'address1' => [new Length(max: 100)],
            'address2' => [new Length(max: 100)],
            'city' => [new Length(max: 100)],
            'zip' => [new Length(max: 20)],
            'country' => [new Length(max: 100)],
            'phone' => [new Length(max: 30)],
        ];
    }

    /**
     * @return array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string}
     */
    public function toCustomerArray(): array
    {
        return [
            'name' => $this->name,
            'surname' => $this->surname,
            'email' => $this->email,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'zip' => $this->zip,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }
}
