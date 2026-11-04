<?php

namespace App\Providers;

use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Space;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Motor de reservas polimórfico: nomes curtos e estáveis no banco
        Relation::enforceMorphMap([
            Reservation::KIND_ROOM => Room::class,
            Reservation::KIND_SPACE => Space::class,
            Reservation::KIND_PROFESSIONAL => Professional::class,
            'user' => \App\Models\User::class,
        ]);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Carbon::setLocale('pt_BR');
        setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');

        Blade::directive('money', fn ($expr) => "<?php echo 'R$ '.number_format((float) ($expr), 2, ',', '.'); ?>");
    }
}
