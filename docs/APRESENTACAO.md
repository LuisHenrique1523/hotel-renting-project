# Apresentação: Equipe 5 · Aurora Hotel & Spa

**Data:** 13/11/2026 · **Duração:** 10 minutos · **Slides:** link do deck (exportar em PDF ou PPTX para o e-mail)

O texto falado de cada slide está nas **notas do apresentador** do deck.

## Divisão de tempo

| Tempo | Quem | Slides | Assunto |
|---|---|---|---|
| 0:00–1:15 | Pedro Henrique Chaves | 1–4 | Capa, equipe, problema, proposta |
| 1:15–3:00 | Gabriel Rocha | 5–7 | Stack, arquitetura em camadas, caminho de uma reserva |
| 3:00–4:40 | Vitor Alexandre | 8–10 | Modelo de dados, regra de conflito, máquina de estados |
| 4:40–6:40 | Luis Henrique | 11 | **Demo ao vivo:** hóspede |
| 6:40–8:40 | Thiago Lima | 12 | **Demo ao vivo:** recepção e profissional |
| 8:40–10:00 | Lucas Marques | 13–14 | Segurança, testes, aprendizados, encerramento |

## Roteiro da demo do hóspede (Luis)

1. Home → buscar de **amanhã a 2 noites**, 2 adultos → mostrar o selo "Só 2 disponíveis".
2. **Superior Vista Mar** → preço diária a diária (marcação de fim de semana) → **Reservar agora**.
3. Pagamento: mostrar o contador de 30 min → **Pix** → **Já paguei** → confete e voucher.
4. **Spa → Massagem Relaxante** → Camila → um horário livre → **Confirmar** → pagar.
5. ⭐ **Momento-chave:** em uma 2ª aba **já aberta** na mesma tela do spa (antes do passo 4), clicar no **mesmo horário** → mensagem "acabou de ser reservado por outra pessoa".
6. **Minhas reservas** → abas Próximas, Anteriores e Canceladas.

## Roteiro da demo da recepção (Thiago)

1. `/admin` (recepcao@aurora.test) → Dashboard: ocupação, receita, gráfico, agenda de hoje.
2. **Reservas** → a reserva do Luis no topo → abrir → **Fazer check-in**. Se a reserva for de amanhã, o botão não aparece; nesse caso use **Confirmar pagamento** de uma pendente.
3. **Calendário** → aba Spa → clicar em um atendimento.
4. **Mapa de quartos** → avançar um dia com a seta.
5. **Nova reserva no balcão** → tentar um horário ocupado → o painel bloqueia (mesmo motor).
6. Sair → entrar como **spa@aurora.test** → a profissional vê só a própria agenda.

## Checklist do dia (30 min antes)

- [ ] XAMPP: **MySQL ligado**.
- [ ] `php artisan migrate:fresh --seed`: dados frescos relativos ao dia da apresentação.
- [ ] `npm run build` e `php artisan serve` rodando.
- [ ] Navegador 1 (aba normal): **cliente@aurora.test** no site, com duas abas na tela da massagem.
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

Projeto: Aurora Hotel & Spa, plataforma em Laravel 12 + MySQL que reúne
reserva de quartos por diária, aluguel de salas por hora e agendamento
de profissionais do spa, com painel administrativo para a recepção.

Atenciosamente,
Equipe 5
```
