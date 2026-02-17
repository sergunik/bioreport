<?php

declare(strict_types=1);

namespace App\Providers;

use App\UploadedDocuments\Contracts\DocumentStorageInterface;
use App\UploadedDocuments\Storage\LocalDocumentStorage;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(DocumentStorageInterface::class, function ($app): DocumentStorageInterface {
            return new LocalDocumentStorage(
                $app['filesystem']->disk('uploaded_documents')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });
    }
}
