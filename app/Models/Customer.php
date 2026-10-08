<?php

namespace App\Models;

use App\Traits\HasRestaurant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends BaseModel
{
    use HasFactory;
    use Notifiable;
    use HasRestaurant;
    use Notifiable;

    protected $guarded = ['id'];

    /**
     * Get loyalty points attribute
     * This accessor ensures the property is accessible in views
     * Works whether loyalty module is enabled or not
     */
    public function getLoyaltyPointsAttribute()
    {
        // Get attributes using getAttributes() method (more reliable than direct access)
        $attrs = $this->getAttributes();

        // Check if loyalty_points exists in attributes
        if (array_key_exists('loyalty_points', $attrs)) {
            $value = $attrs['loyalty_points'];
            // Handle null, empty string, or falsy values
            if ($value === null || $value === '' || $value === false) {
                return 0;
            }
            return (int)$value;
        }

        // Check if it was set as a regular property
        if (property_exists($this, 'loyalty_points') && $this->loyalty_points !== null && $this->loyalty_points !== '') {
            return (int)$this->loyalty_points;
        }

        // Default to 0 if not set (module might not be enabled or customer has no account)
        return 0;
    }

    /**
     * Find or create a customer for POS/order submit without violating
     * customers_restaurant_email_unique (restaurant_id + email).
     *
     * Match order: explicit id, then email (unique per restaurant), then phone.
     * Never match on empty/null phone. Never copy an email onto a different customer.
     */
    public static function resolveForOrder(int $restaurantId, array $customerData, ?int $customerId = null): ?self
    {
        $name = trim((string) ($customerData['name'] ?? ''));
        $phone = trim((string) ($customerData['phone'] ?? ''));
        $email = trim((string) ($customerData['email'] ?? ''));
        $phoneCode = trim((string) ($customerData['phone_code'] ?? ''));

        $customerId = ($customerId && $customerId > 0) ? $customerId : null;

        if (!$customerId && $name === '' && $phone === '' && $email === '') {
            return null;
        }

        $scoped = function () use ($restaurantId) {
            return static::withoutGlobalScopes()->where('restaurant_id', $restaurantId);
        };

        $customer = null;

        if ($customerId) {
            $customer = $scoped()->where('id', $customerId)->first();
        }

        if (!$customer && $email !== '') {
            $customer = $scoped()->where('email', $email)->first();
        }

        if (!$customer && $phone !== '') {
            $customer = $scoped()->where('phone', $phone)->first();
        }

        if (!$customer) {
            return static::withoutGlobalScopes()->create([
                'restaurant_id' => $restaurantId,
                'name' => $name !== '' ? $name : null,
                'email' => $email !== '' ? $email : null,
                'phone' => $phone !== '' ? $phone : null,
                'phone_code' => $phoneCode !== '' ? $phoneCode : null,
            ]);
        }

        if ($name !== '') {
            $customer->name = $name;
        }

        if ($phone !== '') {
            $customer->phone = $phone;
        }

        if ($phoneCode !== '') {
            $customer->phone_code = $phoneCode;
        }

        $currentEmail = trim((string) ($customer->email ?? ''));
        if ($email !== '' && strcasecmp($currentEmail, $email) !== 0) {
            $emailTaken = static::withoutGlobalScopes()
                ->where('restaurant_id', $restaurantId)
                ->where('email', $email)
                ->where('id', '!=', $customer->id)
                ->exists();

            if (!$emailTaken) {
                $customer->email = $email;
            }
        }

        $customer->save();

        return $customer;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderBy('id', 'desc');
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class)->orderBy('id', 'desc');
    }

    public function latestDeliveryAddress(): HasOne
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')->orderByDesc('id');
    }

    public function routeNotificationForVonage($notification)
    {
        if (!is_null($this->phone) && !is_null($this->phone_code)) {
            return '+' . $this->phone_code . $this->phone;
        }

        return null;
    }

    public function routeNotificationForTwilio()
    {
        if (!is_null($this->phone) && !is_null($this->phone_code)) {
            return '+' . $this->phone_code . $this->phone;
        }

        return null;
    }

    public function routeNotificationForMsg91($notification)
    {
        if (!is_null($this->phone) && !is_null($this->phone_code)) {
            return $this->phone_code . $this->phone;
        }

        return null;
    }

    public function routeNotificationForAndroidSmsGateway($notification)
    {
        return $this->routeNotificationForVonage($notification);
    }

}
