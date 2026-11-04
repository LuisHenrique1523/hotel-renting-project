# Aurora Hotel & Spa: sistema de agendamento e reservas

[![CI](https://github.com/LuisHenrique1523/hotel-renting-project/actions/workflows/ci.yml/badge.svg)](https://github.com/LuisHenrique1523/hotel-renting-project/actions/workflows/ci.yml)
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/LuisHenrique1523/hotel-renting-project)

Trabalho Avaliativo 3 de **Programação de Sistemas Web** (Afya, prof. Gabriel Cardoso), **Equipe 5**.
Tema: *Sistema de Agendamento e Reservas (Salas ou Profissionais)*.

O sistema cobre três tipos de reserva com **um único motor de reservas**:

| O que se reserva | Unidade de tempo | Exemplo |
|---|---|---|
| Quartos | diária (check-in 14h, check-out 12h) | Suíte Bangalô, 3 diárias |
| Salas | blocos de 1 hora | Sala Executiva, 9h–12h |
| Profissionais | duração do serviço | Massagem de 60 min com a Camila |

Equipe: Pedro Henrique Chaves, Gabriel Rocha, Vitor Alexandre, Thiago Lima, Luis Henrique e Lucas Marques.

---

## Como rodar (Windows + XAMPP)

**Pré-requisitos:**
- XAMPP com PHP 8.2+ e MySQL/MariaDB.
- Composer.
- Node 18+.
- No `php.ini`, as extensões `intl`, `gd` e `zip` habilitadas.

```bash
# 1. Dependências
composer install
npm install

# 2. Configuração
cp .env.example .env
php artisan key:generate
#    Crie o banco "aurora" no phpMyAdmin (utf8mb4_unicode_ci) e confira DB_* no .env

# 3. Banco + dados de demonstração (~1.300 reservas, fotos, usuários)
php artisan storage:link
php artisan migrate:fresh --seed

# 4. Front-end e servidor
npm run build
php artisan serve
```

Acesse:
- **Site:** http://127.0.0.1:8000
- **Painel:** http://127.0.0.1:8000/admin

Rotina automática (opcional, em outro terminal): `php artisan schedule:work`

### Contas de demonstração (senha: `aurora123`)

| Papel | E-mail | O que vê |
|---|---|---|
| Hóspede | cliente@aurora.test | Site, reservas, pagamento, "Minhas reservas" |
| Recepção | recepcao@aurora.test | Painel completo, exceto gestão de usuários |
| Profissional | spa@aurora.test | Painel apenas com a própria agenda |
| Administrador | admin@aurora.test | Tudo |

Na tela de login há atalhos que preenchem essas contas (só no ambiente local).

### Antes da apresentação
`php artisan migrate:fresh --seed` recria os dados **relativos à data de hoje**. Assim, o dashboard, o calendário e o mapa de quartos sempre aparecem cheios e com vagas livres para a demo.

---

## Site na internet (Render)

O GitHub guarda o código e o [Render](https://render.com) publica o site. A cada `git push` na `main`, o GitHub Actions roda os testes e o build Docker, e o Render reconstrói e publica sozinho.

**Configuração (uma vez só):**
1. Entre em render.com com a conta do GitHub.
2. Clique no botão **Deploy to Render** acima. Ou, no Render, use **New → Blueprint** e escolha este repositório.
3. Confirme. O `render.yaml` já traz todas as configurações.
4. Em cerca de 5 minutos o site fica no ar em `https://aurora-hotel-spa.onrender.com` (ou um nome parecido, se este estiver ocupado).

**Como funciona no plano gratuito:**
- **Imagem e banco:** a imagem (`Dockerfile`) usa PHP 8.2 + Apache, e o banco é **SQLite**, recriado com os dados de demonstração a cada inicialização. O site sempre começa limpo e com datas relativas ao dia.
- **Hibernação:** depois de 15 minutos sem acesso, o serviço hiberna. O primeiro acesso seguinte leva cerca de 1 minuto e recria os dados. **Abra o site uns 2 minutos antes de apresentar.**
- **Contas de demo:** como é uma demonstração pública, as contas aparecem na tela de login (variável `HOTEL_DEMO`). Qualquer alteração feita por visitantes some na próxima reinicialização.

---

## Arquitetura

```
Navegador ──► Rotas (web.php) ──► Middleware (auth, throttle, CSRF)
                   │
        ┌──────────┴───────────┐
  Controllers + Blade      Painel Filament (/admin)
  (site do hóspede)        (recepção, profissionais, admin)
        │                       │
        └──► Form Requests + Policies (validação e permissão)
                   │
                   ▼
        Services (regra de negócio)
        ├─ AvailabilityService  → o que está livre?
        ├─ PricingService       → quanto custa?
        └─ ReservationService   → reservar, pagar, check-in, cancelar
                   │
        Models Eloquent ──► MySQL
                   │
        Events ──► Listeners (e-mail)      Scheduler ──► reservations:housekeeping
```

### Motor de reservas polimórfico
A tabela `reservations` aponta para **qualquer recurso reservável** através de `reservable_type` e `reservable_id`, que podem ser `room`, `space` ou `professional`. Toda verificação de conflito usa a mesma regra de interseção de intervalos:

```
existente.início < nova.fim  E  existente.fim > nova.início  (ignorando canceladas)
```

A criação roda dentro de uma transação com `SELECT ... FOR UPDATE` no recurso. Isso impede que duas pessoas reservem o mesmo horário ao mesmo tempo.

### Máquina de estados
```
pending ──► confirmed ──► checked_in ──► completed
   │            │
   └────────────┴──► cancelled / no_show
```

Transições inválidas (por exemplo, check-in de reserva não paga) são bloqueadas no `ReservationService`.

### Regras de negócio (config/hotel.php)
- Diárias de sexta e sábado custam +20%.
- O hóspede cancela online até 24h antes. Uma reserva pendente pode ser abandonada a qualquer momento.
- Reserva não paga expira em 30 min (rotina automática).
- Reserva confirmada sem check-in após 6h vira "não compareceu".
- Profissionais só atendem dentro do expediente cadastrado.

---

## Testes

```bash
php artisan test
```

São 47 testes cobrindo:
- Conflito de horários: quarto, sala e profissional.
- Diárias encadeadas (check-out 12h e check-in 14h no mesmo dia).
- Alocação automática de quarto.
- Preço com acréscimo de fim de semana.
- Expediente dos profissionais.
- Máquina de estados.
- Prazo de cancelamento.
- Fluxo HTTP completo: reservar, pagar e ver o voucher.
- Autorização: hóspede A não vê a reserva de B.
- Permissões do painel por papel.
- Expiração automática.

## Principais pastas

| Caminho | Conteúdo |
|---|---|
| `app/Services` | Regra de negócio (motor de reservas) |
| `app/Models` | Entidades + `Concerns/HasReservations` (comportamento de "reservável") |
| `app/Enums` | Status, papéis e categorias |
| `app/Http/Controllers` | Fluxo do site |
| `app/Filament` | Painel administrativo (recursos, widgets, calendário, mapa de quartos) |
| `app/Policies` | Quem pode ver e operar cada reserva |
| `resources/views/site` | Páginas do site (Blade + Tailwind + Alpine.js) |
| `resources/js/booking.js` | Grade de horários em tempo real |
| `tests/Feature` | Testes automatizados |

Fotos: Unsplash (licença livre). Projeto acadêmico, sem fins comerciais.
