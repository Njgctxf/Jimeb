<?php

namespace App\Livewire\Concerns;

use App\Support\ImageUpload;

/**
 * Champ unique « photos et vidéos » d'un plat : chaque sélection de fichiers s'ajoute à la liste
 * déjà choisie (galleryTemp) au lieu de la remplacer.
 */
trait HandlesMediaUploads
{
    /** Zone de dépôt : se vide aussitôt, ses fichiers passent dans galleryTemp. */
    public array $newMedia = [];

    public function updatedNewMedia(): void
    {
        $this->validate(['newMedia.*' => ImageUpload::MEDIA_MIMES_MAX_50MB], [
            'newMedia.*.mimes' => 'Formats acceptés : JPG, PNG, WebP, GIF, MP4, WebM, MOV.',
            'newMedia.*.max' => '50 Mo maximum par fichier.',
            'newMedia.*.file' => 'Fichier invalide.',
        ]);

        $this->galleryTemp = array_values(array_merge($this->galleryTemp, array_values($this->newMedia)));
        $this->newMedia = [];
    }
}
