# Apresentação: Equipe 5 · Aurora Workspaces

**Data:** 13/11/2026 · **Duração:** 10 minutos · **Slides:** link do deck (exportar em PDF ou PPTX para o e-mail)

O texto falado de cada slide está nas **notas do apresentador** do deck.

## Divisão de tempo

| Tempo | Quem | Slides | Assunto |
|---|---|---|---|
| 0:00–1:15 | Pedro Henrique Chaves | 1–4 | Capa, equipe, problema, proposta |
| 1:15–3:00 | Gabriel Rocha | 5–7 | Stack, arquitetura em camadas, caminho de uma reserva |
| 3:00–4:40 | Vitor Alexandre | 8–10 | Modelo de dados, regra de conflito, máquina de estados |
| 4:40–6:40 | Luis Henrique | 11 | **Demo ao vivo:** cliente |
| 6:40–8:40 | Thiago Lima | 12 | **Demo ao vivo:** cadastros e recepção |
| 8:40–10:00 | Lucas Marques | 13–14 | Segurança, testes, aprendizados, encerramento |

## Roteiro da demo do cliente (Luis)

1. Home → buscar **São Paulo + Sala de reunião** → abrir a **Sala Trianon**.
2. Agenda por hora: escolher amanhã, clicar 9h e depois 11h → total de 3 horas aparece ao vivo → **Reservar agora**.
3. Pagamento: mostrar o contador de 30 min → **Pix** → **Já paguei** → confete e voucher com QR code.
4. **Coworking Paulista**: calendário mostra as **vagas livres por dia** → 2 dias, 3 pessoas → preço por pessoa → reservar.
5. ⭐ **Momento-chave:** em uma 2ª aba **já aberta** na mesma sala (antes do passo 2), clicar no **mesmo horário** → o sistema recusa: "Esse horário não está mais disponível".
6. **Minhas reservas** → abas Próximas, Anteriores e Canceladas.

## Roteiro da demo dos cadastros e da recepção (Thiago)

1. `/admin` (recepcao@aurora.test) → Dashboard: ocupação, receita, contratos mensais, agenda de hoje.
2. **Cadastros → Categorias** → criar "Estúdio de podcast", **por hora**, mínimo 1 h.
3. **Cadastros → Unidades** → criar "Aurora Centro" (Brasília/DF) → na aba **Espaços desta unidade**, criar o "Estúdio Eixo" → **Ver no site**: a unidade e o espaço já aparecem.
4. Tentar excluir a **Aurora Paulista** → o painel explica que ela tem espaços (é preciso desativar).
5. **Mapa de ocupação** → filtrar uma unidade → coworking com barra de lotação, escritório com contrato.
6. **Calendário** → aba de uma unidade → reservas por hora e contratos na faixa "Dia todo".
7. **Reservas → Nova reserva no balcão** → mesmo horário da reserva do Luis → o painel bloqueia (mesmo motor).

## Checklist do dia (30 min antes)

- [ ] XAMPP: **MySQL ligado**.
- [ ] `php artisan migrate:fresh --seed`: dados frescos relativos ao dia da apresentação.
- [ ] `npm run build` e `php artisan serve` rodando.
- [ ] Navegador 1 (aba normal): **cliente@aurora.test** no site, com duas abas na Sala Trianon.
- [ ] Navegador 2 (janela anônima): **recepcao@aurora.test** em `/admin`.
- [ ] Zoom do navegador em 110–125%, notificações do sistema desligadas.
- [ ] **Vídeo de backup** da demo gravado (caso a máquina ou o projetor falhe).
- [ ] Ensaio cronometrado: cada pessoa dentro do seu tempo.

Senha de todas as contas: `aurora123`. A tela de login tem botões de atalho.

---

## E-mail de entrega

**Para:** gabriel.cardoso@afya.com.br

**Assunto:**
```
[Equipe - 5][6 Periodo 2026][Programação WEB][Trabalho Avaliativo 3]
```

**Anexo:** a apresentação exportada em PDF ou PPTX.

**Corpo:**
```
Prezado professor Gabriel,

Segue em anexo a apresentação do Trabalho Avaliativo 3.

Equipe 5:
- Pedro Henrique Chaves
- Gabriel Rocha
- Vitor Alexandre
- Thiago Lima
- Luis Henrique
- Lucas Marques

Disciplina: Programação de Sistemas WEB
Semestre: Segundo semestre de 2026
Tema: Sistema de Agendamento e Reservas (Salas ou Profissionais)

Projeto: Aurora Workspaces, plataforma em Laravel 12 + MySQL para uma
rede de espaços de trabalho flexíveis. A administração cadastra unidades,
categorias e espaços pelo painel, e os clientes reservam salas por hora,
coworking por dia e escritórios privativos por mês, com disponibilidade
em tempo real.

Atenciosamente,
Equipe 5
```
