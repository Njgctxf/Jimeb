<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Enums\PackageType;
use App\Models\Contact;
use App\Models\CustomMenu;
use App\Models\FrontDetail;
use App\Models\FrontFaq;
use App\Models\FrontFeature;
use App\Models\FrontReviewSetting;
use App\Models\LanguageSetting;
use App\Models\Restaurant;
use Froiden\Envato\Traits\AppBoot;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use App\Models\Module;
use Nwidart\Modules\Facades\Module as  ModuleFacade;

class HomeController extends Controller
{

    use AppBoot;

    protected $language;

    public function __construct()
    {
        parent::__construct();

        $locale = session('customer_locale') ?? (global_setting()->locale ?? 'en');
        $languageSetting = LanguageSetting::where('language_code', $locale)->first();

        if (!$languageSetting) {
            $locale = 'en';
            $languageSetting = LanguageSetting::where('language_code', 'en')->first();
        }

        session([
            'customer_locale' => $locale,
            'customer_is_rtl' => (bool) ($languageSetting?->is_rtl == 1),
        ]);

        app()->setLocale($locale);
        $this->language = $locale;
    }

    public function changeLocale($locale)
    {
        // Validate if the locale exists in language settings
        $languageSetting = LanguageSetting::where('language_code', $locale)->first();

        // Set the locale in session for both customer and admin/system
        session([
            'customer_locale' => $locale,
            'locale' => $locale,
            'customer_is_rtl' => (bool) ($languageSetting?->is_rtl == 1),
        ]);
        app()->setLocale($locale);
        $this->language = $locale;
        return redirect()->back()->with('success', 'Language changed successfully');
    }

    public function landing()
    {

        $this->showInstall();

        $global = global_setting();

        if ($global->disable_landing_site && !request()->ajax()) {
            return redirect(route('login'));
        }

        if ($global->landing_site_type == 'custom') {
            return response(file_get_contents($global->landing_site_url));
        }

        return $this->alytics();
    }

    /**
     * Ancienne page d'accueil (thème d'origine), conservée en secours.
     */
    public function legacyLanding()
    {
        $global = global_setting();

        $this->modules = Module::where('is_superadmin', 0)->pluck('name')->toArray();
        $this->PackageFeatures = Package::ADDITIONAL_FEATURES;

        $AllModulesWithFeature = array_merge(
            $this->modules,
            $this->PackageFeatures
        );

        $packages = Package::with('modules')
            ->where('package_type', '!=', PackageType::DEFAULT)
            ->where('package_type', '!=', PackageType::TRIAL)
            ->where('is_private', false)
            ->orderBy('sort_order', 'asc')
            ->get();

        $trialPackage = Package::where('package_type', PackageType::TRIAL)->first();
        $customMenu = CustomMenu::all();

        $monthlyPackages = Package::where('package_type', PackageType::STANDARD)->where('monthly_status', true)->where('is_private', false)->get();
        $annualPackages = Package::where('package_type', PackageType::STANDARD)->where('annual_status', true)->where('is_private', false)->get();
        $lifetimePackages = Package::where('package_type', PackageType::LIFETIME)->where('is_private', false)->get();
        $language = $this->language;

        $languageSetting = LanguageSetting::where('language_code', $language)->first();
        $languageId = $languageSetting ? $languageSetting->id : null;
        $frontDetails = FrontDetail::where('language_setting_id', $languageId)->first();
        $frontFeatures = FrontFeature::where('language_setting_id', $languageId)->get();
        $frontReviews = FrontReviewSetting::where('language_setting_id', $languageId)->get();
        $frontFaqs = FrontFaq::where('language_setting_id', $languageId)->get();
        $frontContact = Contact::where('language_setting_id', $languageId)->first();

        if ($global->landing_type == 'static') {
            return view('landing.index', compact('packages', 'AllModulesWithFeature', 'trialPackage', 'monthlyPackages', 'annualPackages', 'lifetimePackages'));
        }

        return view('landing.dynamic-index', compact('packages', 'AllModulesWithFeature', 'trialPackage', 'monthlyPackages', 'annualPackages', 'lifetimePackages', 'customMenu', 'frontDetails', 'frontFeatures', 'frontReviews', 'frontFaqs', 'frontContact'));
    }

    /**
     * Page vitrine : export statique du template Framer « Alytics » (public/landing/alytics),
     * en cours d'adaptation étape par étape. L'ancienne vitrine reste sur /vitrine.
     */
    public function alytics(string $page = 'index')
    {
        $file = public_path('landing/alytics/' . $page . '.html');

        abort_unless(is_file($file), 404);

        $html = file_get_contents($file);

        // Les chemins de l'export sont relatifs : on les ancre sur son dossier.
        $html = preg_replace('/<head>/', '<head><base href="' . asset('landing/alytics') . '/">', $html, 1);

        // Ajustements Jimbe (retrait des éléments du créateur du template).
        $v = filemtime(public_path('landing/alytics/jimbe-overrides.css'));
        $currentLocale = session('customer_locale') ?? session('locale') ?? global_setting()->locale ?? 'fr';
        $head = '<link rel="stylesheet" href="jimbe-overrides.css?v=' . $v . '">'
            . '<script src="jimbe-overrides.js?v=' . $v . '" data-signup="' . e(route('restaurant_signup', [], false)) . '" data-login="' . e(route('login', [], false)) . '" data-locale="' . e($currentLocale) . '"></script>';
        $html = str_replace('</head>', $head . '</head>', $html);

        // Bouton de bascule de langue FR / EN
        $langSwitchHtml = '<div class="jb-alytics-lang-switch" role="group" aria-label="Langue">'
            . '<a href="' . route('change.locale', 'fr') . '" class="' . ($currentLocale === 'fr' ? 'active' : '') . '">🇫🇷 FR</a>'
            . '<span class="sep">/</span>'
            . '<a href="' . route('change.locale', 'en') . '" class="' . ($currentLocale === 'en' ? 'active' : '') . '">🇬🇧 EN</a>'
            . '</div>';
        $html = preg_replace('/<body[^>]*>/', '$0' . $langSwitchHtml, $html, 1);

        // Traceur d'analytique Framer du template : retiré.
        $html = preg_replace('/<script[^>]*\ssrc="script"[^>]*><\/script>/', '', $html);

        // Correction des liens vers l'application réelle
        $html = str_replace(
            ['https://connexion.jimbe.invalid/', 'https://inscription.jimbe.invalid/', 'index.html#', 'href="index.html"', 'newsletter.html'],
            [route('login'), route('restaurant_signup'), '#', 'href="' . url('/') . '"', route('alytics.newsletter')],
            $html
        );

        return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * Nouvelle page d'accueil (copie du template Framer adaptée à Jimbe).
     * Les forfaits viennent de la base ; les autres textes sont rédigés dans la vue.
     */
    public function vitrine()
    {
        $modules = Module::where('is_superadmin', 0)->pluck('name')->toArray();
        $allModulesWithFeature = array_merge($modules, Package::ADDITIONAL_FEATURES);

        $packages = Package::with('modules')
            ->where('package_type', '!=', PackageType::DEFAULT)
            ->where('package_type', '!=', PackageType::TRIAL)
            ->where('is_private', false)
            ->orderBy('sort_order', 'asc')
            ->get();

        $trialPackage = Package::where('package_type', PackageType::TRIAL)->first();
        $customMenu = CustomMenu::orderBy('sort_order')->get();
        $frontContact = Contact::first();

        return view('framer.home', [
            'packages' => $packages,
            'allModulesWithFeature' => $allModulesWithFeature,
            'trialPackage' => $trialPackage,
            'customMenu' => $customMenu,
            'frontContact' => $frontContact,
        ]);
    }

    public function signup()
    {
        // Même mise en page que la connexion (colonne verte Mobilo + carte de formulaire).
        if (config('jimeb.simple_mode') || global_setting()->disable_landing_site) {
            return view('auth.restaurant_register');
        }

        return view('auth.restaurant_signup');
    }

    public function customerLogout()
    {
        session()->flush();
        return redirect(module_enabled('Subdomain') ? url('/') : route('shop_restaurant', [request()->restaurant]));
    }

    public function manifest()
    {
        $hash = request()->query('hash', '');

        if (!empty($hash)) {
            $slug = 'restaurant/' . $hash . '/';
        } else {
            $slug = 'super-admin/';
        }

        $relativeUrl = urldecode(request()->query('url', ''));

        $superadminUrl1 = File::exists(public_path('user-uploads/favicons/super-admin/android-chrome-192x192.png')) ? asset('user-uploads/favicons/super-admin/android-chrome-192x192.png') : asset('img/192x192.png');
        $superadminUrl2 = File::exists(public_path('user-uploads/favicons/super-admin/android-chrome-512x512.png')) ? asset('user-uploads/favicons/super-admin/android-chrome-512x512.png') : asset('img/512x512.png');


        $firstimagePath = public_path('user-uploads/favicons/' . $slug . 'android-chrome-192x192.png');
        $secondimagePath = public_path('user-uploads/favicons/' . $slug . 'android-chrome-512x512.png');
        $firsticonUrl = File::exists($firstimagePath) ? asset('user-uploads/favicons/' . $slug . 'android-chrome-192x192.png') : $superadminUrl1;
        $secondiconUrl = File::exists($secondimagePath) ? asset('user-uploads/favicons/' . $slug . 'android-chrome-512x512.png') : $superadminUrl2;
        $globalSetting = global_setting();

        $restaurant = Restaurant::where('hash', $hash)->first();

        return response()->json([
            'name' => $restaurant ? $restaurant->name : $globalSetting->name,
            'short_name' => $restaurant ? $restaurant->name : $globalSetting->name,
            'description' => $restaurant ? $restaurant->name : $globalSetting->name,
            'start_url' => url($relativeUrl),
            'display' => 'standalone',
            'background_color' => '#ffffff',
            'theme_color' => '#000000',
            'icons' => [
                [
                    'src' => $firsticonUrl,
                    'sizes' => '192x192',
                    'type' => 'image/png'
                ],
                [
                    'src' => $secondiconUrl,
                    'sizes' => '512x512',
                    'type' => 'image/png'
                ]
            ]
        ]);
    }



    public function validatePartnerDomain(Request $request)
    {
        $restApiInstalled = ModuleFacade::has('RestApi');

        if (!$restApiInstalled) {
            return response()->json([
                'status' => false,
                'code' => 'REST_API_MODULE_NOT_INSTALLED',
                'message' => __('messages.restApiModuleNotInstalledForDelivery'),
            ], 422);
        }

        if (!module_enabled('RestApi')) {
            return response()->json([
                'status' => false,
                'code' => 'REST_API_MODULE_NOT_ENABLED',
                'message' => __('messages.restApiModuleNotEnabledForDelivery'),
            ], 422);
        }

        $globalSetting = global_setting();
        $mapProvider = $globalSetting->map_provider ?? 'google';

        if ($mapProvider === 'google') {
            $googleMapApiKey = $globalSetting->google_map_api_key;
            if (blank($googleMapApiKey)) {
                return response()->json([
                    'status' => false,
                    'code' => 'GOOGLE_MAP_API_KEY_NOT_CONFIGURED',
                    'message' => __('messages.googleMapApiKeyMissingForDelivery'),
                ], 422);
            }
        }

        $plugins = ModuleFacade::all(); /* @phpstan-ignore-line */
        $updateArray = [];
        $updateArrayEnabled = [];

        foreach ($plugins as $key => $plugin) {
            $modulePath = $plugin->getPath();
            $version = trim(File::get($modulePath . '/version.txt'));

            if ($plugin->isEnabled()) {
                $updateArrayEnabled[$key] = $version;
            }

            $updateArray[$key] = $version;
        }


        return response()->json([
            'status' => true,
            'code' => 'VALIDATION_SUCCESSFUL',
            'message' => __('messages.partnerValidationSuccessful'),
            'map_provider' => $mapProvider,
            'module_enabled' => $updateArray,
            'restaurant' => [
                'name' => global_setting()->name,
                'theme_hex' => global_setting()->theme_hex,
                'logo' => global_setting()->logoUrl,
                'locale' => global_setting()->locale,
            ],
        ], 200);
    }
}
