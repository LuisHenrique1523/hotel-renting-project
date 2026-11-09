# Aurora Workspaces: sistema de agendamento e reservas

[![CI](https://github.com/LuisHenrique1523/hotel-renting-project/actions/workflows/ci.yml/badge.svg)](https://github.com/LuisHenrique1523/hotel-renting-project/actions/workflows/ci.yml)
[![Deploy to Render](https://render.com/images/deploy-to-render-button.svg)](https://render.com/deploy?repo=https://github.com/LuisHenrique1523/hotel-renting-project)

Trabalho Avaliativo 3 de **Programação de Sistemas Web** (Afya, prof. Gabriel Cardoso), **Equipe 5**.
Tema: *Sistema de Agendamento e Reservas (Salas ou Profissionais)*.

Uma **rede de espaços de trabalho flexíveis**, no modelo de Regus e Hub Central: a administração **cadastra** as unidades, as categorias de espaço e os espaços de cada unidade, e o cliente reserva pelo site. A categoria define como o espaço é cobrado:

| Categoria (exemplo) | Cobrança | Como se reserva |
|---|---|---|
| Sala de reunião, Auditório | por hora | blocos de hora cheia dentro do horário da unidade |
| Coworking, Sala de treinamento | por dia | um ou mais dias inteiros |
| Escritório privativo | por mês | contrato de N meses a partir de uma data |

Categorias marcadas como **uso compartilhado** (ex.: coworking) vendem **vagas**: várias pessoas reservam o mesmo salão ao mesmo tempo até lotar a capacidade, e o preço é por pessoa.

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

# 3. Banco + dados de demonstração (4 unidades, 20 espaços, ~1.800 reservas, fotos, usuários)
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
| Cliente | cliente@aurora.test | Site, reservas, pagamento, "Minhas reservas" |
| Recepção | recepcao@aurora.test | Painel completo (operação e cadastros), exceto gestão de usuários |
| Administrador | admin@aurora.test | Tudo |

Na tela de login há atalhos que preenchem essas contas (só no ambiente local).

### Antes da apresentação
`php artisan migrate:fresh --seed` recria os dados **relativos à data de hoje**. Assim, o dashboard, o calendário e o mapa de ocupação sempre aparecem cheios e com espaços livres para a demo.

---

## Cadastros no painel (CRUD)

Menu **Cadastros** do `/admin`:

| Tela | O que se cadastra |
|---|---|
| **Unidades** | Nome, endereço, cidade/UF, horário de funcionamento, comodidades, foto. Dentro de cada unidade há a aba **Espaços desta unidade**, para cadastrar as salas dela sem sair da tela. |
| **Categorias** | Nome, ícone, foto, **forma de cobrança** (hora/dia/mês), duração mínima e máxima por reserva, **uso compartilhado** e ordem de exibição (arrastar para reordenar). |
| **Espaços** | Unidade, categoria, capacidade, área, andar, preço (por hora, dia ou mês, conforme a categoria), recursos, foto, destaque na home e se aceita reservas. |

Tudo o que é cadastrado aparece no site na hora: a home lista as categorias, a busca filtra por cidade, categoria e número de pessoas, e cada unidade tem a sua página.
Para não perder histórico, o sistema **não deixa excluir** unidade com espaços, categoria com espaços nem espaço com reservas. Nesses casos, o caminho é desativar.

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
- **Contas de demo:** como é uma demonstração pública, as contas aparecem na tela de login (variável `AURORA_DEMO`). Qualquer alteração feita por visitantes some na próxima reinicialização.

---

## Arquitetura

```
Navegador ──► Rotas (web.php) ──► Middleware (auth, throttle, CSRF)
                   │
        ┌──────────┴───────────┐
  Controllers + Blade      Painel Filament (/admin)
  (site do cliente)        (cadastros, recepção, admin)
        │                       │
        └──► Form Requests + Policies (validação e permissão)
                   │
                   ▼
        Services (regra de negócio)
        ├─ AvailabilityService  → o que está livre? (horas, dias, vagas)
        ├─ PricingService       → quanto custa?
        └─ ReservationService   → reservar, pagar, check-in, cancelar
                   │
        Models Eloquent ──► MySQL
                   │
        Events ──► Listeners (e-mail)      Scheduler ──► reservations:housekeeping
```

### Modelo de dados
```
units (unidades) 1 ──< spaces (espaços) >── 1 space_categories (categorias: cobrança e regras)
                           │
                           1
                           │
                           ^
                  reservations ──1── payments
                  (início, fim, quantidade, preço unitário, pessoas, status)
```

A reserva guarda um **retrato da cobrança** (`billing_unit`, `quantity`, `unit_price`). Se o admin mudar o preço ou a categoria depois, o histórico não muda.

### Uma regra de conflito para tudo
Qualquer reserva vira um intervalo `[início, fim)`:
- **por hora:** 9h + 3 horas → 09:00–12:00;
- **por dia:** 3 dias a partir de 05/11 → 05/11 na abertura até 07/11 no fechamento;
- **por mês:** 2 meses a partir de 10/11 → 10/11 até 09/01.

Toda verificação usa a mesma interseção de intervalos:

```
existente.início < nova.fim  E  existente.fim > nova.início  (ignorando canceladas)
```

Espaço exclusivo: nenhuma reserva pode sobrepor. Espaço compartilhado: a soma de pessoas das reservas sobrepostas, mais as novas, precisa caber na capacidade. Assim um contrato mensal bloqueia também as reservas por dia daquele escritório.

A criação roda dentro de uma transação com `SELECT ... FOR UPDATE` no espaço. Isso impede que duas pessoas reservem o mesmo período ao mesmo tempo.

### Máquina de estados
```
pending ──► confirmed ──► checked_in ──► completed
   │            │
   └────────────┴──► cancelled / no_show
```

Transições inválidas (por exemplo, check-in de reserva não paga) são bloqueadas no `ReservationService`.

### Regras de negócio
- Duração mínima e máxima por reserva vêm da **categoria** (ex.: auditório, mínimo de 2 horas).
- Reservas por hora respeitam o **horário de funcionamento da unidade**.
- O cliente cancela online até 24h antes. Uma reserva pendente pode ser abandonada a qualquer momento.
- Reserva não paga expira em 30 min (rotina automática).
- Reserva confirmada sem check-in após 6h vira "não compareceu".
- Parâmetros gerais em `config/aurora.php`.

---

## Testes

```bash
php artisan test
```

São 58 testes cobrindo:
- Janela e preço de cada forma de cobrança (hora, dia, mês).
- Conflito de horários, horas encadeadas e contrato mensal bloqueando o mesmo espaço.
- Coworking compartilhado: aceita reservas até lotar e cobra por pessoa.
- Duração mínima da categoria, horário da unidade, capacidade, unidade desativada.
- Máquina de estados e prazo de cancelamento.
- Fluxo HTTP completo: buscar, reservar, pagar e ver o voucher.
- Endpoints de disponibilidade (horários, dias, orçamento).
- CRUD pelo painel: categoria, unidade e espaço criados aparecem no site; unidade com espaços não pode ser excluída.
- Reserva no balcão passa pelo mesmo motor (e é recusada em conflito).
- Autorização: cliente A não vê a reserva de B. Permissões do painel por papel.
- Expiração automática.

## Principais pastas

| Caminho | Conteúdo |
|---|---|
| `app/Services` | Regra de negócio (motor de reservas) |
| `app/Models` | `Unit`, `SpaceCategory`, `Space`, `Reservation`, `Payment`, `User` |
| `app/Enums` | Forma de cobrança, status, papéis |
| `app/Http/Controllers` | Fluxo do site e endpoints de disponibilidade |
| `app/Filament` | Painel: cadastros, reservas, calendário, mapa de ocupação, widgets |
| `app/Policies` | Quem pode ver e operar cada reserva |
| `resources/views/site` | Páginas do site (Blade + Tailwind + Alpine.js) |
| `resources/js/booking.js` | Agenda em tempo real (grade de horas ou calendário de dias) |
| `tests/Feature` | Testes automatizados |

Fotos: Unsplash (licença livre). Projeto acadêmico, sem fins comerciais.
