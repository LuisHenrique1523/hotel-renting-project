<?php

namespace Database\Seeders;

use App\Enums\BillingUnit;
use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogo inicial da rede: categorias, unidades e espaços.
 * Tudo isso também pode ser cadastrado, editado e removido pelo painel (/admin).
 */
class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $categories = $this->categories();
            $this->unitsAndSpaces($categories);
        });
    }

    /** @return array<string, SpaceCategory> */
    private function categories(): array
    {
        $rows = [
            ['coworking', 'Coworking', 'Day pass em mesa compartilhada', BillingUnit::Day, 1, 30, 'heroicon-o-computer-desktop', 'cowork-1',
                'Estações em ambiente compartilhado, com café, internet rápida e áreas de convivência. Chegue, escolha a mesa e trabalhe.'],
            ['sala-de-reuniao', 'Sala de reunião', 'Para reuniões, entrevistas e videochamadas', BillingUnit::Hour, 1, 10, 'heroicon-o-user-group', 'meeting-4',
                'Salas equipadas com TV, videoconferência e quadro branco, reservadas por hora cheia.'],
            ['escritorio-privativo', 'Escritório privativo', 'Sala fechada e mobiliada para a sua equipe', BillingUnit::Month, 1, 12, 'heroicon-o-building-office', 'office-1',
                'Escritório com chave, mobiliado e pronto para usar. Contrato mensal sem fiador, com recepção e limpeza inclusas.'],
            ['sala-de-treinamento', 'Sala de treinamento', 'Workshops, cursos e dinâmicas de grupo', BillingUnit::Day, 1, 5, 'heroicon-o-academic-cap', 'training-1',
                'Salas amplas com layout flexível (escolar, U ou ilhas), projetor e coffee break opcional. Reservadas por dia.'],
            ['auditorio', 'Auditório', 'Palestras, lançamentos e eventos', BillingUnit::Hour, 2, 12, 'heroicon-o-megaphone', 'auditorium-2',
                'Auditórios com palco, som, projeção e suporte técnico. Mínimo de 2 horas por reserva.'],
        ];

        $categories = [];
        foreach ($rows as $i => [$slug, $name, $tagline, $billing, $min, $max, $icon, $image, $description]) {
            $categories[$slug] = SpaceCategory::create([
                'name' => $name, 'slug' => $slug, 'tagline' => $tagline, 'description' => $description,
                'billing_unit' => $billing, 'min_quantity' => $min, 'max_quantity' => $max,
                'icon' => $icon, 'image' => "img/{$image}.jpg", 'sort_order' => $i + 1,
                'is_shared' => $slug === 'coworking',
            ]);
        }

        return $categories;
    }

    /** @param array<string, SpaceCategory> $c */
    private function unitsAndSpaces(array $c): void
    {
        $units = [
            [
                'unit' => [
                    'name' => 'Aurora Paulista', 'city' => 'São Paulo', 'state' => 'SP', 'neighborhood' => 'Bela Vista',
                    'address' => 'Av. Paulista, 1500 · 12º andar', 'zip_code' => '01310-200', 'phone' => '(11) 4000-2101',
                    'opens_hour' => 7, 'closes_hour' => 22, 'image' => 'unit-paulista',
                    'description' => 'Nossa unidade-sede, no coração da Paulista e a dois minutos do metrô Trianon-Masp. Vista para a avenida, auditório próprio e recepção bilíngue.',
                    'amenities' => ['Recepção bilíngue', 'Café e água à vontade', 'Wi-Fi 1 Gbps', 'Metrô a 2 min', 'Bicicletário', 'Estacionamento conveniado'],
                ],
                'spaces' => [
                    ['coworking', 'Coworking Paulista', 60, 49, 140, '12º', 'cowork-1', true, 'Salão aberto com 60 estações, cabines de chamada e café especial.', ['Mesa compartilhada', 'Cabines de chamada', 'Armário diário', 'Café especial']],
                    ['sala-de-reuniao', 'Sala Trianon', 6, 75, 18, '12º', 'meeting-4', true, 'Sala intimista para até 6 pessoas, ideal para entrevistas e reuniões de alinhamento.', ['TV 55"', 'Videoconferência', 'Quadro branco']],
                    ['sala-de-reuniao', 'Sala Consolação', 12, 140, 32, '12º', 'meeting-1', false, 'Mesa para 12 com vista para a avenida e sistema de videoconferência com câmera 4K.', ['TV 75"', 'Câmera 4K', 'Videoconferência', 'Quadro branco']],
                    ['escritorio-privativo', 'Escritório 1204', 4, 3900, 16, '12º', 'office-2', false, 'Escritório para 4 pessoas com divisórias de vidro e luz natural.', ['Mobiliado', 'Chave própria', 'Limpeza diária', 'Endereço comercial']],
                    ['escritorio-privativo', 'Escritório 1210', 8, 6900, 30, '12º', 'office-1', true, 'Escritório para equipes de até 8 pessoas, com sala de reunião interna.', ['Mobiliado', 'Sala de reunião interna', 'Chave própria', 'Endereço comercial']],
                    ['auditorio', 'Auditório Paulista', 120, 650, 180, 'Térreo', 'auditorium-1', true, 'Auditório climatizado com palco, projeção em alta definição e técnico de som.', ['Palco', 'Projetor 4K', 'Som profissional', 'Técnico de AV', 'Microfones sem fio']],
                ],
            ],
            [
                'unit' => [
                    'name' => 'Aurora Faria Lima', 'city' => 'São Paulo', 'state' => 'SP', 'neighborhood' => 'Itaim Bibi',
                    'address' => 'Av. Brigadeiro Faria Lima, 3477 · 8º andar', 'zip_code' => '04538-133', 'phone' => '(11) 4000-2102',
                    'opens_hour' => 8, 'closes_hour' => 21, 'image' => 'unit-faria-lima',
                    'description' => 'Endereço premium no principal polo financeiro do país. Pensada para escritórios de equipes e reuniões com clientes.',
                    'amenities' => ['Recepção', 'Café e água à vontade', 'Wi-Fi 1 Gbps', 'Phone booths', 'Estacionamento conveniado'],
                ],
                'spaces' => [
                    ['coworking', 'Coworking Faria Lima', 40, 69, 110, '8º', 'cowork-3', false, 'Estações com monitor externo e cadeiras ergonômicas, em ambiente silencioso.', ['Monitor externo', 'Cadeira ergonômica', 'Armário diário']],
                    ['sala-de-reuniao', 'Sala Itaim', 8, 120, 22, '8º', 'meeting-2', false, 'Sala envidraçada para 8 pessoas com TV e videoconferência.', ['TV 65"', 'Videoconferência', 'Quadro de vidro']],
                    ['escritorio-privativo', 'Escritório 804', 2, 2900, 10, '8º', 'office-4', false, 'Escritório compacto para duplas, com acesso a todas as áreas comuns.', ['Mobiliado', 'Chave própria', 'Endereço comercial']],
                    ['escritorio-privativo', 'Escritório 812', 12, 9800, 45, '8º', 'office-3', false, 'Andar corporativo para equipes de até 12 pessoas.', ['Mobiliado', 'Copa privativa', 'Chave própria', 'Endereço comercial']],
                    ['sala-de-treinamento', 'Sala Pinheiros', 30, 1200, 70, '8º', 'training-2', false, 'Sala de treinamento para 30 pessoas, com layout flexível e projetor.', ['Projetor', 'Layout flexível', 'Coffee break opcional', 'Flip chart']],
                ],
            ],
            [
                'unit' => [
                    'name' => 'Aurora Savassi', 'city' => 'Belo Horizonte', 'state' => 'MG', 'neighborhood' => 'Savassi',
                    'address' => 'Rua Pernambuco, 1000 · 5º andar', 'zip_code' => '30130-151', 'phone' => '(31) 4000-2103',
                    'opens_hour' => 8, 'closes_hour' => 20, 'image' => 'unit-savassi',
                    'description' => 'Na região mais charmosa de BH, cercada de cafés e restaurantes. Ótima para startups e equipes remotas.',
                    'amenities' => ['Recepção', 'Café e água à vontade', 'Wi-Fi 500 Mb', 'Terraço', 'Bicicletário'],
                ],
                'spaces' => [
                    ['coworking', 'Coworking Savassi', 35, 39, 95, '5º', 'cowork-2', true, 'Coworking com terraço e clima de comunidade, com eventos de networking toda semana.', ['Mesa compartilhada', 'Terraço', 'Eventos de networking']],
                    ['sala-de-reuniao', 'Sala Liberdade', 10, 90, 24, '5º', 'meeting-3', false, 'Sala com vista da cidade, mesa para 10 e videoconferência.', ['TV 65"', 'Videoconferência', 'Quadro branco']],
                    ['escritorio-privativo', 'Escritório 502', 6, 3400, 22, '5º', 'office-3', false, 'Escritório para 6 pessoas com janela para a Savassi.', ['Mobiliado', 'Chave própria', 'Endereço comercial']],
                    ['sala-de-treinamento', 'Sala Mangabeiras', 24, 850, 60, '5º', 'training-1', true, 'Espaço para cursos e workshops com projetor e quadro branco.', ['Projetor', 'Quadro branco', 'Coffee break opcional']],
                    ['auditorio', 'Auditório Savassi', 80, 420, 120, 'Térreo', 'space-conf', false, 'Auditório para 80 pessoas com som e projeção.', ['Palco', 'Projetor', 'Som profissional']],
                ],
            ],
            [
                'unit' => [
                    'name' => 'Aurora Botafogo', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'neighborhood' => 'Botafogo',
                    'address' => 'Praia de Botafogo, 300 · 10º andar', 'zip_code' => '22250-040', 'phone' => '(21) 4000-2104',
                    'opens_hour' => 8, 'closes_hour' => 20, 'image' => 'unit-botafogo',
                    'description' => 'Vista para a Baía de Guanabara e o Pão de Açúcar, a poucos passos do metrô Botafogo.',
                    'amenities' => ['Recepção', 'Café e água à vontade', 'Wi-Fi 1 Gbps', 'Vista para a baía', 'Metrô a 3 min'],
                ],
                'spaces' => [
                    ['coworking', 'Coworking Botafogo', 30, 45, 80, '10º', 'cowork-5', false, 'Estações com vista para a baía e muita luz natural.', ['Mesa compartilhada', 'Vista para a baía', 'Armário diário']],
                    ['sala-de-reuniao', 'Sala Urca', 8, 95, 20, '10º', 'space-meeting', true, 'Sala de reunião com vista para o Pão de Açúcar.', ['TV 55"', 'Videoconferência', 'Vista para a baía']],
                    ['escritorio-privativo', 'Escritório 1003', 4, 3200, 18, '10º', 'space-office', false, 'Escritório para 4 pessoas, mobiliado e com janela.', ['Mobiliado', 'Chave própria', 'Endereço comercial']],
                    ['sala-de-treinamento', 'Sala Guanabara', 20, 780, 50, '10º', 'training-3', false, 'Sala para treinamentos de até 20 pessoas.', ['Projetor', 'Layout flexível', 'Flip chart']],
                ],
            ],
        ];

        foreach ($units as $row) {
            $data = $row['unit'];
            $unit = Unit::create(['image' => "img/{$data['image']}.jpg"] + $data);

            foreach ($row['spaces'] as [$cat, $name, $capacity, $price, $size, $floor, $image, $featured, $description, $amenities]) {
                Space::create([
                    'unit_id' => $unit->id, 'space_category_id' => $c[$cat]->id,
                    'name' => $name, 'description' => $description, 'capacity' => $capacity, 'price' => $price,
                    'size_m2' => $size, 'floor' => $floor, 'image' => "img/{$image}.jpg",
                    'is_featured' => $featured, 'amenities' => $amenities,
                ]);
            }
        }
    }
}
