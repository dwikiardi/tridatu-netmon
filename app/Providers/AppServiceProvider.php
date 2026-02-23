<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Customer;
use App\Observers\CustomerObserver;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Customer::observe(CustomerObserver::class);
    \App\Models\Ticket::observe(\App\Observers\TicketObserver::class);

    if($this->app->environment('production') || strpos(request()->getHost(), 'ngrok') !== false) {
        \Illuminate\Support\Facades\URL::forceScheme('https');
    }
  }
}
