<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Helper\Files;
use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable
{

    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use HasBranch;

    protected function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'restaurant_id',
        'locale',
        'phone_number',
        'phone_code',
        'terms_and_privacy_accepted',
        'marketing_emails_accepted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'restaurant_id' => 'integer',
            'branch_id' => 'integer',
            'terms_and_privacy_accepted' => 'boolean',
            'marketing_emails_accepted' => 'boolean',
        ];
    }

    protected function defaultProfilePhotoUrl()
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&color=#27272a&background=f4f4f5';
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function isRestaurantActive(): bool
    {
        return $this->restaurant?->is_active ?? true;
    }

    public function isRestaurantApproved(): bool
    {
        return $this->restaurant?->approval_status === 'Approved';
    }

    public function updateProfilePhoto($photo)
    {
        $path = 'profile-photos';
        $oldFilename = $this->profile_photo_path ? basename($this->profile_photo_path) : null;
        $filename = Files::uploadLocalOrS3($photo, $path, width: 150, height: 150, oldFilename: $oldFilename);
        // Update the user's profile photo path in the database
        $this->forceFill([
            'profile_photo_path' => $path . '/' . $filename,
        ])->save();
    }

    public static function validateLoginActiveDisabled($user)
    {

        self::restrictUserLoginFromOtherSubdomain($user);

        // Check if restaurant is active
        if (!$user->isRestaurantActive()) {
            throw ValidationException::withMessages([
                'email' => __('Restaurant is inactive. Contact admin.')
            ]);
        }
    }

    private static function restrictUserLoginFromOtherSubdomain($user)
    {
        if (!module_enabled('Subdomain')) {
            return true;
        }

        $restaurant = getRestaurantBySubDomain();

        // Check if superadmin is trying to login. Make sure the database do not have main domain as subdomain
        if (!$restaurant) {
            $userCount = User::whereNull('restaurant_id')->count();
            return $userCount > 0;
        };


        // Check if user is trying to login from other restaurant
        if ($user->restaurant_id !== $restaurant->id) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed')
            ]);
        }

        return true;
    }

    public function getTimezoneFromIp(): string
    {
        $ip = $this->getClientIpFromRequest();

        try {
            $response = Http::get('http://ip-api.com/json/' . $ip);

            if ($response->failed()) {
                return 'UTC';
            }

            if ($response->json()['status'] == 'success') {
                return $response->json()['timezone'] ?? 'UTC';
            }

            return 'UTC';
        } catch (\Throwable $th) {
            return 'UTC';
        }
    }

    public function getCountryFromIp(): string
    {
        // If you're behind Cloudflare, this is the most reliable signal.
        $cfCountry = request()->header('CF-IPCountry');
        if (is_string($cfCountry) && preg_match('/^[A-Z]{2}$/', $cfCountry) && $cfCountry !== 'XX') {
            return $cfCountry;
        }

        $ip = $this->getClientIpFromRequest();

        // Le pays d'une adresse IP ne change pas d'une page à l'autre : on le garde 24 h au lieu d'interroger
        // des sites externes (ipify, ip-api) à CHAQUE affichage de page, ce qui ralentissait tout le tableau de bord.
        $key = 'ip-country:' . md5($ip);

        $cached = \Illuminate\Support\Facades\Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $country = $this->lookupCountryFromIp($ip);

        // Réponse obtenue : 24 h. Échec (hors ligne, limite atteinte) : on réessaie dans 10 minutes seulement.
        \Illuminate\Support\Facades\Cache::put($key, $country['code'], $country['ok'] ? now()->addDay() : now()->addMinutes(10));

        return $country['code'];
    }

    /** @return array{code:string, ok:bool} */
    private function lookupCountryFromIp(string $ip): array
    {
        // En local, 127.0.0.1 n'a pas de pays : on cherche l'adresse publique de la machine.
        if ($ip === '127.0.0.1' || $ip === '::1') {
            $context = stream_context_create(['http' => ['timeout' => 2]]);
            $public = @file_get_contents('https://api.ipify.org', false, $context);

            if (! is_string($public) || ! filter_var($public, FILTER_VALIDATE_IP)) {
                return ['code' => 'US', 'ok' => false];
            }

            $ip = $public;
        }

        try {
            $response = Http::timeout(2)->get('http://ip-api.com/json/' . $ip);

            if ($response->successful() && ($response->json()['status'] ?? null) === 'success') {
                return ['code' => (string) $response->json()['countryCode'], 'ok' => true];
            }
        } catch (\Throwable $th) {
            // on retombe sur la valeur par défaut
        }

        return ['code' => 'US', 'ok' => false];
    }

    private function getClientIpFromRequest(): string
    {
        // Prefer common proxy/CDN headers when TrustProxies isn't configured.
        $candidates = [
            request()->header('CF-Connecting-IP'),
            request()->header('X-Real-IP'),
        ];

        $xff = request()->header('X-Forwarded-For');
        if (is_string($xff) && $xff !== '') {
            // XFF can be a list: client, proxy1, proxy2...
            $first = trim(explode(',', $xff)[0] ?? '');
            if ($first !== '') {
                $candidates[] = $first;
            }
        }

        $candidates[] = request()->ip();

        foreach ($candidates as $ip) {
            if (!is_string($ip) || $ip === '') {
                continue;
            }
            $ip = trim($ip);

            // Skip obvious non-routable placeholders.
            if (in_array($ip, ['127.0.0.1', '::1'], true)) {
                continue;
            }

            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return request()->ip();
    }

    public function getPhoneCodeFromIp(): ?string
    {
        $ipCountry = $this->getCountryFromIp();


        try {
            $country = \App\Models\Country::where('countries_code', $ipCountry)->first();
            return $country ? $country->phonecode : null;
        } catch (\Throwable $th) {
            return null;
        }
    }

    public function routeNotificationForVonage($notification)
    {
        if (!is_null($this->phone_number) && !is_null($this->phone_code)) {
            return '+' . $this->phone_code . $this->phone_number;
        }

        return null;
    }

    public function routeNotificationForTwilio()
    {
        if (!is_null($this->phone_number) && !is_null($this->phone_code)) {
            return '+' . $this->phone_code . $this->phone_number;
        }

        return null;
    }


    public function routeNotificationForMsg91($notification)
    {
        if (!is_null($this->phone_number) && !is_null($this->phone_code)) {
            return $this->phone_code . $this->phone_number;
        }

        return null;

    }

    public function routeNotificationForAndroidSmsGateway($notification)
    {
        return $this->routeNotificationForVonage($notification);
    }
    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
