<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->publishImages();

        // Contas de demonstração (senha de todas: aurora123)
        $accounts = [
            ['Administração Aurora', 'admin@aurora.test', UserRole::Admin],
            ['Recepção Aurora', 'recepcao@aurora.test', UserRole::Staff],
            ['Mariana Costa', 'cliente@aurora.test', UserRole::Guest],
        ];

        foreach ($accounts as [$name, $email, $role]) {
            User::create([
                'name' => $name, 'email' => $email, 'password' => DatabaseSeeder::demoPassword(),
                'role' => $role, 'phone' => '(31) 99876-5432', 'email_verified_at' => now(),
            ]);
        }

        // Hóspedes fictícios para popular o histórico
        User::factory(45)->create(['role' => UserRole::Guest]);

        $this->call([
            CatalogSeeder::class,
            ReservationSeeder::class,
        ]);
    }

    /** Hash bcrypt calculado uma vez só (cada hash custa ~0,3 s de CPU). */
    public static function demoPassword(): string
    {
        static $hash;

        return $hash ??= Hash::make('aurora123');
    }

    /** Copia as fotos versionadas (database/seeders/images) para o disco público. */
    private function publishImages(): void
    {
        $disk = Storage::disk('public');

        foreach (File::files(database_path('seeders/images')) as $file) {
            if (! $disk->exists('img/'.$file->getFilename())) {
                $disk->put('img/'.$file->getFilename(), File::get($file->getPathname()));
            }
        }
    }
}
