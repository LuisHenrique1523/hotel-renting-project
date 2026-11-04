<?php

namespace Database\Seeders;

use App\Enums\RoomStatus;
use App\Enums\ServiceCategory;
use App\Enums\SpaceType;
use App\Enums\UserRole;
use App\Models\Professional;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Catálogo do hotel: quartos, salas, serviços do spa e profissionais. */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->roomTypes();
            $this->spaces();
            $this->servicesAndProfessionals();
        });
    }

    private function roomTypes(): void
    {
        $types = [
            [
                'name' => 'Standard Jardim', 'tagline' => 'Aconchego com vista para os jardins tropicais',
                'capacity' => 2, 'size_m2' => 24, 'bed' => 'Cama queen', 'base_price' => 389, 'image' => 'room-1',
                'amenities' => ['Wi-Fi', 'Ar-condicionado', 'Smart TV', 'Frigobar', 'Café da manhã'],
                'description' => 'Quarto acolhedor voltado para os jardins internos, ideal para casais que buscam conforto e tranquilidade a poucos passos da piscina.',
                'rooms' => ['101', '102', '103', '104', '105', '106', '107', '108'], 'floor' => 1,
            ],
            [
                'name' => 'Superior Vista Mar', 'tagline' => 'Acorde com o som das ondas',
                'capacity' => 2, 'size_m2' => 30, 'bed' => 'Cama king', 'base_price' => 529, 'image' => 'room-2', 'featured' => true,
                'amenities' => ['Wi-Fi', 'Vista para o mar', 'Varanda', 'Smart TV', 'Cafeteira Nespresso', 'Café da manhã'],
                'description' => 'Varanda privativa com vista panorâmica para o oceano, cama king e acabamento em madeira natural. O favorito dos nossos hóspedes.',
                'rooms' => ['201', '202', '203', '204', '205', '206', '207'], 'floor' => 2,
            ],
            [
                'name' => 'Deluxe Família', 'tagline' => 'Espaço de sobra para toda a família',
                'capacity' => 4, 'size_m2' => 42, 'bed' => '2 camas queen', 'base_price' => 689, 'image' => 'room-8',
                'amenities' => ['Wi-Fi', 'Duas camas queen', 'Smart TV', 'Kit infantil', 'Frigobar', 'Café da manhã'],
                'description' => 'Amplo e iluminado, com duas camas queen e área de estar. Perfeito para famílias que querem curtir o resort juntas.',
                'rooms' => ['301', '302', '303', '304', '305'], 'floor' => 3,
            ],
            [
                'name' => 'Suíte Bangalô', 'tagline' => 'Privacidade entre palmeiras',
                'capacity' => 2, 'size_m2' => 48, 'bed' => 'Cama king', 'base_price' => 890, 'image' => 'room-5', 'featured' => true,
                'amenities' => ['Wi-Fi', 'Deck privativo', 'Banheira', 'Roupão e chinelos', 'Serviço de quarto 24h', 'Café da manhã'],
                'description' => 'Bangalô independente cercado de vegetação, com deck privativo, banheira e decoração artesanal. Uma experiência de refúgio.',
                'rooms' => ['B01', 'B02', 'B03', 'B04'], 'floor' => 0,
            ],
            [
                'name' => 'Suíte Master', 'tagline' => 'Sofisticação em cada detalhe',
                'capacity' => 3, 'size_m2' => 65, 'bed' => 'Cama king + sofá-cama', 'base_price' => 1290, 'image' => 'room-3', 'featured' => true,
                'amenities' => ['Wi-Fi', 'Sala de estar', 'Vista para o mar', 'Banheira de hidromassagem', 'Acesso ao lounge VIP', 'Café da manhã'],
                'description' => 'Suíte com sala de estar separada, hidromassagem e acesso ao lounge VIP. Pensada para quem quer o melhor do Aurora.',
                'rooms' => ['401', '402', '403'], 'floor' => 4,
            ],
            [
                'name' => 'Suíte Presidencial Aurora', 'tagline' => 'O topo do Aurora, literalmente',
                'capacity' => 4, 'size_m2' => 120, 'bed' => 'Cama king + quarto extra', 'base_price' => 2490, 'image' => 'room-7',
                'amenities' => ['Wi-Fi', 'Cobertura panorâmica', 'Mordomo', 'Jacuzzi', 'Adega', 'Transfer incluso', 'Café da manhã'],
                'description' => 'Cobertura de 120 m² com vista 360°, jacuzzi, adega climatizada e mordomo exclusivo. Para ocasiões inesquecíveis.',
                'rooms' => ['501'], 'floor' => 5,
            ],
        ];

        foreach ($types as $data) {
            $type = RoomType::create([
                'name' => $data['name'],
                'tagline' => $data['tagline'],
                'description' => $data['description'],
                'capacity' => $data['capacity'],
                'size_m2' => $data['size_m2'],
                'bed' => $data['bed'],
                'base_price' => $data['base_price'],
                'amenities' => $data['amenities'],
                'cover_image' => "img/{$data['image']}.jpg",
                'is_featured' => $data['featured'] ?? false,
            ]);

            foreach ($data['rooms'] as $number) {
                $type->rooms()->create([
                    'number' => $number,
                    'floor' => $data['floor'],
                    'status' => $number === '106' ? RoomStatus::Maintenance : RoomStatus::Available,
                ]);
            }
        }
    }

    private function spaces(): void
    {
        $spaces = [
            ['Sala Executiva Atlântico', SpaceType::Meeting, 12, 180, 'space-meeting', 8, 20,
                'Sala de reuniões com mesa para 12 pessoas, vista para o mar e tela de 75". Ideal para diretorias e negociações.',
                ['Tela 75"', 'Videoconferência', 'Wi-Fi dedicado', 'Coffee break opcional', 'Quadro branco']],
            ['Lounge Coworking Brisa', SpaceType::Coworking, 20, 60, 'space-cowork', 7, 22,
                'Espaço de trabalho compartilhado com estações individuais, café à vontade e muita luz natural.',
                ['Estações individuais', 'Café e água', 'Wi-Fi 1 Gbps', 'Cabines de chamada']],
            ['Auditório Horizonte', SpaceType::Event, 120, 650, 'space-conf', 8, 23,
                'Auditório climatizado com projeção em alta definição, sonorização e palco. Perfeito para palestras e lançamentos.',
                ['Projetor 4K', 'Som profissional', '2 microfones sem fio', 'Palco', 'Técnico de AV']],
            ['Salão Grand Aurora', SpaceType::Event, 250, 1200, 'space-event', 10, 23,
                'Nosso salão de festas com lustres de cristal, pista de dança e cozinha de apoio para casamentos e celebrações.',
                ['Lustres de cristal', 'Pista de dança', 'Cozinha de apoio', 'Iluminação cênica', 'Camarim']],
        ];

        foreach ($spaces as [$name, $type, $capacity, $price, $image, $opens, $closes, $description, $amenities]) {
            Space::create([
                'name' => $name, 'type' => $type, 'capacity' => $capacity, 'hourly_price' => $price,
                'image' => "img/{$image}.jpg", 'opens_hour' => $opens, 'closes_hour' => $closes,
                'description' => $description, 'amenities' => $amenities,
            ]);
        }
    }

    private function servicesAndProfessionals(): void
    {
        $services = collect([
            ['Massagem Relaxante', ServiceCategory::Spa, 60, 280, 'spa-massage', 'Movimentos suaves e óleos aquecidos para aliviar tensões e restaurar o equilíbrio do corpo.'],
            ['Pedras Quentes', ServiceCategory::Spa, 90, 390, 'spa-stones', 'Pedras vulcânicas aquecidas relaxam a musculatura profunda e melhoram a circulação.'],
            ['Aromaterapia', ServiceCategory::Spa, 50, 240, 'spa-pool', 'Óleos essenciais selecionados para acalmar a mente e renovar as energias.'],
            ['Ritual Day Spa Aurora', ServiceCategory::Spa, 120, 520, 'spa-room', 'Esfoliação, massagem completa e chá de ervas: duas horas só para você.'],
            ['Massagem Desportiva', ServiceCategory::Spa, 60, 300, 'spa-stones', 'Técnicas de alongamento e pressão para recuperação muscular após atividades físicas.'],
            ['Limpeza de Pele', ServiceCategory::Wellness, 60, 220, 'spa-facial', 'Higienização profunda, extração e máscara hidratante com cosméticos naturais.'],
            ['Drenagem Linfática', ServiceCategory::Wellness, 60, 260, 'spa-room', 'Massagem com movimentos leves e ritmados que reduzem inchaço e desintoxicam.'],
            ['Yoga ao Pôr do Sol', ServiceCategory::Fitness, 60, 120, 'fit-yoga', 'Aula particular de yoga no deck à beira-mar, para todos os níveis.'],
            ['Personal Training', ServiceCategory::Fitness, 60, 180, 'fit-gym', 'Treino personalizado na academia do hotel com avaliação rápida inicial.'],
        ])->mapWithKeys(fn ($s) => [$s[0] => Service::create([
            'name' => $s[0], 'category' => $s[1], 'duration_min' => $s[2], 'price' => $s[3],
            'image' => "img/{$s[4]}.jpg", 'description' => $s[5],
        ])]);

        // [nome, e-mail, especialidade, foto, serviços, turnos por dia da semana]
        $split = [['09:00', '13:00'], ['14:00', '19:00']];
        $pros = [
            ['Camila Duarte', 'spa@aurora.test', 'Massoterapeuta', 'pro-1',
                ['Massagem Relaxante', 'Pedras Quentes', 'Aromaterapia', 'Ritual Day Spa Aurora'],
                [2 => $split, 3 => $split, 4 => $split, 5 => $split, 6 => $split, 0 => [['10:00', '16:00']]],
                'Especialista em terapias relaxantes, com 10 anos de experiência em spas de resorts.'],
            ['Rafael Mendes', 'rafael@aurora.test', 'Massoterapeuta desportivo', 'pro-2',
                ['Massagem Relaxante', 'Massagem Desportiva', 'Pedras Quentes'],
                [1 => [['10:00', '18:00']], 2 => [['10:00', '18:00']], 3 => [['10:00', '18:00']], 4 => [['10:00', '18:00']], 5 => [['10:00', '18:00']], 6 => [['09:00', '14:00']]],
                'Fisioterapeuta de formação, focado em recuperação muscular e bem-estar ativo.'],
            ['Helena Prado', 'helena@aurora.test', 'Esteticista', 'pro-3',
                ['Limpeza de Pele', 'Drenagem Linfática', 'Aromaterapia'],
                [1 => [['09:00', '17:00']], 2 => [['09:00', '17:00']], 3 => [['09:00', '17:00']], 4 => [['09:00', '17:00']], 5 => [['09:00', '17:00']], 6 => [['09:00', '13:00']]],
                'Cuida da sua pele com cosméticos naturais e protocolos personalizados.'],
            ['Bruno Tavares', 'bruno@aurora.test', 'Personal trainer', 'pro-4',
                ['Personal Training'],
                [1 => [['06:00', '12:00']], 2 => [['06:00', '12:00']], 3 => [['06:00', '12:00']], 4 => [['06:00', '12:00']], 5 => [['06:00', '12:00']], 6 => [['07:00', '11:00']]],
                'Educador físico especializado em treinos funcionais e de alta performance.'],
            ['Lívia Araújo', 'livia@aurora.test', 'Instrutora de yoga', 'pro-5',
                ['Yoga ao Pôr do Sol'],
                collect(range(0, 6))->mapWithKeys(fn ($d) => [$d => [['06:00', '09:00'], ['16:00', '19:00']]])->all(),
                'Professora de Hatha e Vinyasa Yoga, certificada pela Yoga Alliance.'],
            ['Jorge Almeida', 'jorge@aurora.test', 'Terapeuta corporal', 'pro-6',
                ['Massagem Desportiva', 'Drenagem Linfática', 'Massagem Relaxante'],
                [3 => [['08:00', '16:00']], 4 => [['08:00', '16:00']], 5 => [['08:00', '16:00']], 6 => [['08:00', '16:00']], 0 => [['08:00', '14:00']]],
                'Mais de 20 anos dedicados a terapias corporais e reabilitação.'],
        ];

        foreach ($pros as [$name, $email, $specialty, $photo, $serviceNames, $schedule, $bio]) {
            $user = User::create([
                'name' => $name, 'email' => $email, 'password' => DatabaseSeeder::demoPassword(),
                'role' => UserRole::Professional, 'email_verified_at' => now(),
            ]);

            $pro = Professional::create([
                'user_id' => $user->id, 'specialty' => $specialty, 'bio' => $bio, 'photo' => "img/{$photo}.jpg",
            ]);

            $pro->services()->attach($services->only($serviceNames)->pluck('id'));

            foreach ($schedule as $weekday => $shifts) {
                foreach ($shifts as [$start, $end]) {
                    $pro->workingHours()->create(['weekday' => $weekday, 'start_time' => $start, 'end_time' => $end]);
                }
            }
        }
    }
}
