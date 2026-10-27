{{-- Photos et vidéos du plat : un seul champ, autant de fichiers que l'on veut.
     La 1re photo devient l'image principale. $mediaMode = 'create' | 'update'. --}}
@php
    $isUpdate = ($mediaMode ?? 'create') === 'update';
    $removeBtn = 'absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors';
    $closeIcon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
@endphp
<div x-data="{ drag: false }">
    <x-label for="galleryTemp" value="Photos et vidéos du plat" />

    <label for="galleryTemp"
        class="mt-1 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed px-4 py-6 text-center text-sm transition"
        :class="drag ? 'border-skin-base bg-skin-base/5' : 'border-gray-300 bg-gray-50 dark:border-gray-600 dark:bg-gray-700'"
        @dragover.prevent="drag = true" @dragleave.prevent="drag = false" @drop="drag = false">
        <svg class="h-8 w-8 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v13.5A1.5 1.5 0 0 0 3.75 21Z"/></svg>
        <span class="font-medium text-gray-700 dark:text-gray-200">Ajouter des photos et des vidéos</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">Autant que vous voulez. La première photo sera l'image principale du plat.</span>
        <input id="galleryTemp" class="sr-only" type="file" multiple wire:model="newMedia"
            accept="image/*,video/mp4,video/webm,video/quicktime">
    </label>

    <div wire:loading wire:target="newMedia" class="mt-2 text-xs text-gray-500">Envoi en cours…</div>
    <x-input-error for="newMedia.*" class="mt-2" />
    <x-input-error for="galleryTemp.*" class="mt-2" />
    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Photos : JPG, PNG, WebP. Vidéos : MP4, WebM, MOV. 50 Mo maximum par fichier.</p>

    <div class="mt-3 flex flex-wrap gap-3">
        {{-- Déjà enregistrés (modification) --}}
        @if ($isUpdate && $menuItem->image && !$mainImageRemoved)
            <div class="relative" wire:key="media-main">
                <img src="{{ $menuItem->item_photo_url }}" alt="" class="h-20 w-20 rounded-lg border border-gray-300 object-cover">
                <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold text-white">Principale</span>
                <button type="button" wire:click="removeMainImage" class="{{ $removeBtn }}" aria-label="Retirer">{!! $closeIcon !!}</button>
            </div>
        @endif

        @if ($isUpdate)
            @foreach ($extraImages as $ei => $file)
                <div class="relative" wire:key="media-old-{{ $ei }}">
                    @if (\App\Models\MenuItem::isVideoFile($file))
                        <video src="{{ asset_url_local_s3('item/' . $file) }}" class="h-20 w-20 rounded-lg border border-gray-300 object-cover" muted preload="metadata"></video>
                        <span class="absolute bottom-1 left-1 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-semibold text-white">Vidéo</span>
                    @else
                        <img src="{{ asset_url_local_s3('item/' . $file) }}" alt="" class="h-20 w-20 rounded-lg border border-gray-300 object-cover">
                    @endif
                    <button type="button" wire:click="removeExtraImage({{ $ei }})" class="{{ $removeBtn }}" aria-label="Retirer">{!! $closeIcon !!}</button>
                </div>
            @endforeach
        @endif

        {{-- Nouveaux fichiers --}}
        @foreach ($galleryTemp as $gi => $media)
            <div class="relative" wire:key="media-new-{{ $gi }}">
                @if (\App\Models\MenuItem::isVideoFile($media->getClientOriginalName()))
                    <div class="flex h-20 w-20 flex-col items-center justify-center gap-1 rounded-lg border border-gray-300 bg-gray-100 px-1 text-center dark:bg-gray-700">
                        <svg class="h-6 w-6 text-gray-500" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                        <span class="w-full truncate text-[10px] text-gray-600 dark:text-gray-300">{{ $media->getClientOriginalName() }}</span>
                    </div>
                @else
                    <img src="{{ $media->temporaryUrl() }}" alt="" class="h-20 w-20 rounded-lg border border-gray-300 object-cover">
                @endif
                <button type="button" wire:click="removeGalleryPhoto({{ $gi }})" class="{{ $removeBtn }}" aria-label="Retirer">{!! $closeIcon !!}</button>
            </div>
        @endforeach
    </div>
</div>
