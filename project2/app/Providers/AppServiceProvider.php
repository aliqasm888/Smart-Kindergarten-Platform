<?php

namespace App\Providers;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use App\Services\AttachmentService;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Messaging;
use Kreait\Firebase\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Binding الخاص بـ AttachmentService
        $this->app->bind(AttachmentService::class, function ($app) {
            return new AttachmentService();
        });

        // Binding الخاص بـ Guzzle ClientInterface
        $this->app->bind(ClientInterface::class, Client::class);

        // Binding الخاص بـ Firebase Messaging
        $this->app->singleton(\Kreait\Firebase\Contract\Messaging::class, function ($app) {
            $factory = (new \Kreait\Firebase\Factory())
                ->withServiceAccount(storage_path('firebase_credentials.json'))
                ->withProjectId(env('FIREBASE_PROJECT_ID')); // 🔑 هي مهمة

            return $factory->createMessaging();
        });

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

}
