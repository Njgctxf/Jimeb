<?php

namespace Tests\Unit;

use App\Helper\QrCodeGenerator;
use Tests\TestCase;

class QrCodeGeneratorTest extends TestCase
{
    public function test_arabic_label_is_drawn_with_unicode_font_instead_of_missing_glyphs(): void
    {
        $directory = storage_path('framework/testing');
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $arabicPath = $directory . '/qr-arabic-label.png';
        $englishPath = $directory . '/qr-english-label.png';

        QrCodeGenerator::savePng('https://example.test/table/arabic', $arabicPath, 'طاولة 12');
        QrCodeGenerator::savePng('https://example.test/table/english', $englishPath, 'Table 12');

        $this->assertFileExists($arabicPath);
        $this->assertFileExists($englishPath);

        $arabic = imagecreatefrompng($arabicPath);
        $english = imagecreatefrompng($englishPath);

        $this->assertNotFalse($arabic);
        $this->assertNotFalse($english);
        $this->assertGreaterThan(300, imagesy($arabic));
        $this->assertGreaterThan(300, imagesy($english));

        imagedestroy($arabic);
        imagedestroy($english);
        @unlink($arabicPath);
        @unlink($englishPath);
    }
}
