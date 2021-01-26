<?php
/**
 * @author Aaron Francis <aarondfrancis@gmail.com|https://twitter.com/aarondfrancis>
 */

namespace Hammerstone\Airdrop\Tests\Drivers;

use Hammerstone\Airdrop\Tests\BaseTest;
use Hammerstone\Airdrop\Triggers\InputFilesTrigger;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class FileSystemDriverTest extends BaseTest
{
    public function getEnvironmentSetUp($app)
    {
        Storage::fake('s3');

        config()->set('airdrop.triggers', [
            InputFilesTrigger::class => [
                'trim' => base_path(),
                'include' => [
                    base_path('tests/Support/primary-webpack.mix.example'),
                ]
            ]
        ]);

        config()->set('airdrop.outputs.include', [
            base_path('tests/Support/public')
        ]);
    }

    /** @test */
    public function it_creates_and_uploads_a_zip()
    {
        Storage::fake('s3');

        $this->artisan('airdrop:upload');

        Storage::disk('s3')->assertExists('airdrop/airdrop-bf3e492980dd286b875ea06ce67de948.zip');
    }

    /** @test */
    public function it_downloads_and_restores()
    {
        $this->artisan('airdrop:upload');

        // Back up the public directory.
        File::moveDirectory(base_path('tests/Support/public'), base_path('tests/Support/public_backup'));

        $this->artisan('airdrop:download');

        $this->assertEquals('/* app.css */', File::get(base_path('tests/Support/public/css/app.css')));

        $this->assertEquals('// app.js', File::get(base_path('tests/Support/public/js/app.js')));

        File::deleteDirectory(base_path('tests/Support/public_backup'));

        $this->assertFileExists(base_path('.airdrop_skip'));

        File::delete(base_path('.airdrop_skip'));
    }
}