@php
    $tile = 'flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-gray-200 bg-white px-3 py-2.5 text-center text-xs font-medium text-gray-600 transition hover:border-gray-300 peer-checked:border-skin-base peer-checked:bg-skin-base/5 peer-checked:text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:peer-checked:text-white';
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-600 dark:bg-gray-800';
    $h = 'text-sm font-semibold text-gray-900 dark:text-white';
    $help = 'mt-1 text-xs text-gray-500 dark:text-gray-400';
    $colorInput = 'h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1';
    $fileInput = 'block w-full cursor-pointer rounded-lg border border-gray-300 bg-gray-50 text-sm text-slate-500 dark:border-gray-600 dark:bg-gray-700';
@endphp

<div class="pb-10">
    <div class="p-4 bg-white dark:bg-gray-800">
        <a href="{{ route('qrcodes.index') }}" class="text-sm text-skin-base hover:underline">← Retour aux QR codes</a>
        <h1 class="mt-1 text-base font-semibold text-gray-900 dark:text-white">Personnaliser vos QR codes</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Vous dessinez ici un seul QR code : le QR général. Une fois enregistré, son style est appliqué automatiquement à tous vos QR codes (tables et QR général). Les liens ne changent pas : les QR déjà imprimés restent valables.</p>
    </div>

    <div class="mt-4 grid gap-5 px-4 lg:grid-cols-[minmax(0,1fr)_340px]">

        {{-- ====== Aperçu en direct ====== --}}
        <aside class="order-first lg:order-last">
            <div class="{{ $card }} lg:sticky lg:top-4">
                <div class="flex items-center justify-between">
                    <p class="{{ $h }}">Aperçu en direct</p>
                    <span wire:loading class="text-xs text-gray-500">Mise à jour…</span>
                </div>

                <div class="relative mt-3 flex min-h-[260px] items-center justify-center rounded-xl border border-gray-200 bg-[repeating-conic-gradient(#f3f4f6_0%_25%,#fff_0%_50%)] bg-[length:20px_20px] p-3 dark:border-gray-600">
                    @if ($preview)
                        <img src="{{ $preview }}" alt="Aperçu du QR code" class="mx-auto w-full max-w-[300px] transition-opacity" wire:loading.class="opacity-40">
                    @else
                        <p class="px-4 text-center text-sm text-red-600">Corrigez les couleurs pour voir l'aperçu.</p>
                    @endif
                </div>

                <x-input-error for="color" class="mt-2" />

                @foreach ($warnings as $w)
                    <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">{{ $w }}</p>
                @endforeach

                <div class="mt-4 flex flex-col gap-2">
                    <x-button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save" class="justify-center">
                        <span wire:loading.remove wire:target="save">Enregistrer et appliquer à tous les QR</span>
                        <span wire:loading wire:target="save">Mise à jour des QR codes…</span>
                    </x-button>
                    <x-secondary-button type="button" wire:click="resetToClassic" class="justify-center">Revenir au style classique</x-secondary-button>
                </div>
                <p class="{{ $help }}">Ce style sera appliqué à {{ $qrCount }} QR code(s). Scannez toujours un exemplaire avec votre téléphone avant d'imprimer en grand nombre.</p>
            </div>
        </aside>

        {{-- ====== Réglages ====== --}}
        <div class="space-y-5">

            {{-- 1. Forme du code --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">1. Forme du code</p>
                <p class="{{ $help }}">Le code prend la forme choisie. Les points en dehors de la forme restent là, très pâles : c'est ce qui garde le code lisible.</p>

                <div class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-6">
                    @php
                        $icons = [
                            'none' => '<rect x="5" y="5" width="22" height="22" rx="1"/>',
                            'circle' => '<circle cx="16" cy="16" r="11"/>',
                            'squircle' => '<rect x="4" y="4" width="24" height="24" rx="9"/>',
                            'diamond' => '<path d="M16 3 29 16 16 29 3 16Z"/>',
                            'heart' => '<path d="M16 28S4 20 4 12.5A6.5 6.5 0 0 1 16 9a6.5 6.5 0 0 1 12 3.5C28 20 16 28 16 28Z"/>',
                            'image' => '<rect x="4" y="6" width="24" height="20" rx="2"/><path d="m4 22 7-7 6 6 4-4 7 7"/>',
                        ];
                    @endphp
                    @foreach (\App\Support\QrStyle::SILHOUETTES as $key => $label)
                        <label wire:key="sil-{{ $key }}">
                            <input type="radio" class="peer sr-only" name="silhouette" value="{{ $key }}" wire:model.live="silhouette">
                            <span class="{{ $tile }}">
                                <svg class="h-8 w-8" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round">{!! $icons[$key] !!}</svg>
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($silhouette === 'image')
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Votre forme</p>
                        <input type="file" wire:model="silhouetteTemp" accept="image/png,image/jpeg,image/webp,image/gif" class="mt-1 {{ $fileInput }}">
                        <div wire:loading wire:target="silhouetteTemp" class="mt-1 text-xs text-gray-500">Envoi…</div>
                        <x-input-error for="silhouetteTemp" class="mt-1" />
                        <p class="{{ $help }}">Une forme pleine (logo, bouteille, silhouette…) sur fond blanc ou transparent. Les zones sombres ou colorées deviennent la forme du code.</p>
                        @if ($silhouetteImage)
                            <button type="button" wire:click="removeImage('silhouette')" class="mt-1 text-sm text-red-600 hover:underline">Retirer cette forme</button>
                        @endif
                    </div>
                @endif

                @if ($silhouette !== 'none')
                    <div class="mt-4">
                        <label class="flex items-center justify-between text-sm font-medium text-gray-800 dark:text-gray-100">
                            <span>Points en dehors de la forme</span>
                            <span class="text-xs font-normal text-gray-500">{{ $ghost }} %</span>
                        </label>
                        <input type="range" min="26" max="45" step="1" wire:model.live.debounce.250ms="ghost" class="mt-1 w-full accent-[rgb(var(--color-base))]">
                        <div class="flex justify-between text-[11px] text-gray-500"><span>Plus pâles</span><span>Plus visibles (lecture plus sûre)</span></div>
                    </div>
                @endif

                <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Support imprimé</p>
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach (\App\Support\QrStyle::BADGES as $key => $label)
                            <label wire:key="badge-{{ $key }}">
                                <input type="radio" class="peer sr-only" name="badge" value="{{ $key }}" wire:model.live="badge">
                                <span class="{{ $tile }}">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($badge !== 'none')
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="color" wire:model.live.debounce.300ms="badgeColor" class="{{ $colorInput }}">
                            Couleur du contour
                        </label>
                    @endif
                </div>
            </section>

            {{-- 2. Points --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">2. Points du code</p>
                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach (\App\Support\QrStyle::SHAPES as $key => $label)
                        <label wire:key="shape-{{ $key }}">
                            <input type="radio" class="peer sr-only" name="shape" value="{{ $key }}" wire:model.live="shape">
                            <span class="{{ $tile }}">
                                <svg class="h-7 w-7" viewBox="0 0 32 32" fill="currentColor">
                                    @if ($key === 'square')<rect x="6" y="6" width="20" height="20"/>
                                    @elseif ($key === 'rounded')<rect x="6" y="6" width="20" height="20" rx="7"/>
                                    @elseif ($key === 'dots')<circle cx="16" cy="16" r="10"/>
                                    @else<path d="M16 3 29 16 16 29 3 16Z"/>@endif
                                </svg>
                                {{ $label }}
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label class="flex items-center justify-between text-sm font-medium text-gray-800 dark:text-gray-100">
                        <span>Taille des points</span>
                        <span class="text-xs font-normal text-gray-500">{{ $dotScale }} %</span>
                    </label>
                    <input type="range" min="60" max="100" step="2" wire:model.live.debounce.250ms="dotScale" class="mt-1 w-full accent-[rgb(var(--color-base))]">
                </div>
            </section>

            {{-- 3. Coins --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">3. Les trois coins</p>
                <div class="mt-3 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-800 dark:text-gray-100">Cadre</p>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach (\App\Support\QrStyle::EYE_FRAMES as $key => $label)
                                <label wire:key="frame-{{ $key }}">
                                    <input type="radio" class="peer sr-only" name="eyeFrame" value="{{ $key }}" wire:model.live="eyeFrame">
                                    <span class="{{ $tile }}">
                                        <svg class="h-7 w-7" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="4"><rect x="5" y="5" width="22" height="22" rx="{{ $key === 'rounded' ? 6 : 0 }}"/></svg>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <p class="mb-2 text-sm font-medium text-gray-800 dark:text-gray-100">Centre</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (\App\Support\QrStyle::EYE_PUPILS as $key => $label)
                                <label wire:key="pupil-{{ $key }}">
                                    <input type="radio" class="peer sr-only" name="eyePupil" value="{{ $key }}" wire:model.live="eyePupil">
                                    <span class="{{ $tile }}">
                                        <svg class="h-7 w-7" viewBox="0 0 32 32" fill="currentColor"><rect x="8" y="8" width="16" height="16" rx="{{ $key === 'square' ? 0 : ($key === 'rounded' ? 5 : 8) }}"/></svg>
                                        {{ $label }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <x-checkbox wire:model.live="customEyes" />
                    Donner aux coins leurs propres couleurs
                </label>
                @if ($customEyes)
                    <div class="mt-3 flex flex-wrap items-center gap-5">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="color" wire:model.live.debounce.300ms="eyeColor" class="{{ $colorInput }}"> Cadre
                        </label>
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="color" wire:model.live.debounce.300ms="pupilColor" class="{{ $colorInput }}"> Centre
                        </label>
                    </div>
                @endif
            </section>

            {{-- 4. Couleurs --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">4. Couleurs</p>
                <div class="mt-3 flex flex-wrap items-center gap-5">
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="color" wire:model.live.debounce.300ms="color" class="{{ $colorInput }}"> Points
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                        <input type="color" wire:model.live.debounce.300ms="background" class="{{ $colorInput }}"> Fond
                    </label>
                </div>

                <label class="mt-4 flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                    <x-checkbox wire:model.live="useGradient" />
                    Dégradé de deux couleurs
                </label>
                @if ($useGradient)
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                            <input type="color" wire:model.live.debounce.300ms="color2" class="{{ $colorInput }}"> Deuxième couleur
                        </label>
                        <x-select wire:model.live="gradient" class="w-56">
                            @foreach (['h' => 'Horizontal', 'v' => 'Vertical', 'd' => 'Diagonale'] as $k => $l)
                                <option value="{{ $k }}">{{ $l }}</option>
                            @endforeach
                        </x-select>
                    </div>
                @endif
                <p class="{{ $help }}">Gardez des couleurs franchement plus foncées que le fond : c'est ce que les téléphones lisent le mieux.</p>
            </section>

            {{-- 5. Logo et image --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">5. Logo et image</p>

                <div class="mt-3">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Logo au centre</p>
                    <input type="file" wire:model="logoTemp" accept="image/png,image/jpeg,image/webp,image/gif" class="mt-1 {{ $fileInput }}">
                    <div wire:loading wire:target="logoTemp" class="mt-1 text-xs text-gray-500">Envoi…</div>
                    <x-input-error for="logoTemp" class="mt-1" />
                    @if ($logo)
                        <div class="mt-2 flex items-center justify-between gap-3">
                            <button type="button" wire:click="removeImage('logo')" class="text-sm text-red-600 hover:underline">Retirer le logo</button>
                        </div>
                        <label class="mt-2 flex items-center justify-between text-sm font-medium text-gray-800 dark:text-gray-100">
                            <span>Taille du logo</span><span class="text-xs font-normal text-gray-500">{{ $logoSize }} %</span>
                        </label>
                        <input type="range" min="14" max="26" step="1" wire:model.live.debounce.250ms="logoSize" class="w-full accent-[rgb(var(--color-base))]">
                    @endif
                    <p class="{{ $help }}">PNG, JPG, WebP ou GIF, 6 Mo maximum. Un logo simple, sur fond transparent, donne le meilleur résultat.</p>
                </div>

                <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">Image fusionnée avec le code (avancé)</p>
                    <p class="{{ $help }}">Votre image apparaît derrière les points, comme dans les QR « artistiques ». Elle est éclaircie pour garder le code lisible, et remplace le logo du centre.</p>
                    <input type="file" wire:model="artTemp" accept="image/png,image/jpeg,image/webp,image/gif" class="mt-2 {{ $fileInput }}">
                    <div wire:loading wire:target="artTemp" class="mt-1 text-xs text-gray-500">Envoi…</div>
                    <x-input-error for="artTemp" class="mt-1" />
                    @if ($art)
                        <button type="button" wire:click="removeImage('art')" class="mt-2 text-sm text-red-600 hover:underline">Retirer l'image</button>
                        <label class="mt-2 flex items-center justify-between text-sm font-medium text-gray-800 dark:text-gray-100">
                            <span>Éclaircir l'image</span><span class="text-xs font-normal text-gray-500">{{ $artStrength }} %</span>
                        </label>
                        <input type="range" min="40" max="85" step="1" wire:model.live.debounce.250ms="artStrength" class="w-full accent-[rgb(var(--color-base))]">
                        <div class="flex justify-between text-[11px] text-gray-500"><span>Image plus visible</span><span>Lecture plus sûre</span></div>
                    @endif
                </div>
            </section>

            {{-- 6. Texte --}}
            <section class="{{ $card }}">
                <p class="{{ $h }}">6. Texte sous le code</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-3">
                    @foreach (\App\Support\QrStyle::TEXTS as $key => $label)
                        <label wire:key="text-{{ $key }}">
                            <input type="radio" class="peer sr-only" name="text" value="{{ $key }}" wire:model.live="text">
                            <span class="{{ $tile }}">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                @if ($text === 'custom')
                    <x-input type="text" maxlength="40" class="mt-3 block w-full" placeholder="ex. Scannez pour commander" wire:model.live.debounce.400ms="textCustom" />
                @endif
            </section>
        </div>
    </div>
</div>
