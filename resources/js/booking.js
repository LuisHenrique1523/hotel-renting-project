// Componente Alpine.js do fluxo de reserva de um espaço.
// Busca a disponibilidade em JSON (AvailabilityController) e se adapta à forma de cobrança
// da categoria: por hora (grade de horários) ou por dia/mês (calendário + duração).

const iso = (d) => d.toLocaleDateString('en-CA'); // YYYY-MM-DD no fuso local

const money = (v) => Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });

const longDate = (value) => {
    const s = new Date(value + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
    return s.charAt(0).toUpperCase() + s.slice(1);
};

const nextDays = (count) => {
    const base = new Date();
    return Array.from({ length: count }, (_, i) => {
        const d = new Date(base.getFullYear(), base.getMonth(), base.getDate() + i);
        return {
            iso: iso(d),
            weekday: i === 0 ? 'Hoje' : d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
            day: d.getDate(),
            month: d.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', ''),
        };
    });
};

const getJson = async (url) => {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    return res.ok ? res.json() : null;
};

export function spaceBooking(cfg) {
    // cfg: { billing, price, shared, min, max, capacity, units: [singular, plural], hoursUrl, daysUrl, quoteUrl }
    return {
        billing: cfg.billing,
        guests: 1,
        quantity: cfg.min,
        busy: false,
        loading: false,

        // ── por hora ──
        strip: nextDays(21),
        date: null,
        slots: [],
        start: null,
        end: null,

        // ── por dia / mês ──
        calendar: {},      // { 'YYYY-MM-DD': { available, reason, left } }
        cursor: null,      // 1º dia do mês exibido
        quote: null,
        quoting: false,

        async init() {
            this.guests = cfg.shared ? 1 : Math.min(cfg.capacity, Math.max(1, Math.round(cfg.capacity / 2)));

            if (this.billing === 'hour') {
                await this.pickDate(this.strip[0].iso);
                for (let i = 1; !this.slots.some(s => s.available) && i < this.strip.length; i++) {
                    await this.pickDate(this.strip[i].iso);
                }
                return;
            }

            this.loading = true;
            const data = await getJson(cfg.daysUrl);
            (data?.days ?? []).forEach(d => this.calendar[d.date] = d);
            this.loading = false;

            // Abre o calendário já no mês do primeiro dia livre (ex.: escritório com contrato em andamento)
            const first = Object.values(this.calendar).find(d => d.available);
            const base = first ? new Date(first.date + 'T12:00:00') : new Date();
            this.cursor = new Date(base.getFullYear(), base.getMonth(), 1);
            if (first) this.pickStart(first.date);

            this.$watch('quantity', () => this.refreshQuote());
            this.$watch('guests', () => this.refreshQuote());
        },

        unitLabel(n) {
            return `${n} ${n === 1 ? cfg.units[0] : cfg.units[1]}`;
        },

        // ── Por hora ───────────────────────────────────────────

        async pickDate(value) {
            this.date = value;
            this.start = this.end = null;
            this.loading = true;
            this.slots = (await getJson(`${cfg.hoursUrl}?date=${value}`))?.slots ?? [];
            this.loading = false;
        },

        // 1º clique: início · 2º clique: estende até o horário (se tudo no meio estiver livre)
        pick(i) {
            if (!this.slots[i].available) return;

            const extending = this.start !== null && this.end === this.start && i > this.start
                && this.slots.slice(this.start, i + 1).every(x => x.available);

            if (extending) {
                this.end = i;
            } else {
                this.start = this.end = i;
                // Mínimo da categoria (ex.: auditório = 2h): já estende se possível
                const minEnd = i + cfg.min - 1;
                if (cfg.min > 1 && minEnd < this.slots.length && this.slots.slice(i, minEnd + 1).every(x => x.available)) {
                    this.end = minEnd;
                }
            }
        },

        selected(i) {
            return this.start !== null && i >= this.start && i <= this.end;
        },

        get hours() {
            return this.start === null ? 0 : this.end - this.start + 1;
        },

        get startHour() {
            return this.start === null ? '' : parseInt(this.slots[this.start].time, 10);
        },

        hourSummary() {
            if (this.start === null) return '';
            return `${longDate(this.date)} · ${this.slots[this.start].time} – ${this.slots[this.end].end}`;
        },

        hourProblem() {
            if (this.start === null) return 'Escolha um horário na agenda.';
            if (this.hours < cfg.min) return `Mínimo de ${this.unitLabel(cfg.min)} para este espaço.`;
            if (this.hours > cfg.max) return `Máximo de ${this.unitLabel(cfg.max)} por reserva.`;
            return null;
        },

        // ── Por dia / mês ──────────────────────────────────────

        get monthLabel() {
            if (!this.cursor) return '';
            const s = this.cursor.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        get grid() {
            if (!this.cursor) return [];
            const y = this.cursor.getFullYear(), m = this.cursor.getMonth();
            const offset = (new Date(y, m, 1).getDay() + 6) % 7; // semana começa na segunda
            const days = new Date(y, m + 1, 0).getDate();
            const cells = Array.from({ length: offset }, () => null);
            for (let d = 1; d <= days; d++) {
                const key = iso(new Date(y, m, d));
                cells.push({ day: d, iso: key, info: this.calendar[key] ?? null });
            }
            return cells;
        },

        shiftMonth(n) {
            this.cursor = new Date(this.cursor.getFullYear(), this.cursor.getMonth() + n, 1);
        },

        canPrev() {
            const now = new Date();
            return this.cursor && (this.cursor.getFullYear() > now.getFullYear() || this.cursor.getMonth() > now.getMonth());
        },

        canNext() {
            const last = Object.keys(this.calendar).pop();
            return this.cursor && last && iso(new Date(this.cursor.getFullYear(), this.cursor.getMonth() + 1, 1)) <= last;
        },

        pickStart(value) {
            const info = this.calendar[value];
            if (!info || !info.available) return;
            this.date = value;
            this.refreshQuote();
        },

        // Dias cobertos pela seleção (só para destacar no calendário quando é por dia)
        inRange(value) {
            if (!this.date) return false;
            if (this.billing !== 'day') return value === this.date;
            const start = new Date(this.date + 'T12:00:00');
            const end = new Date(start); end.setDate(end.getDate() + this.quantity - 1);
            const d = new Date(value + 'T12:00:00');
            return d >= start && d <= end;
        },

        async refreshQuote() {
            if (this.billing === 'hour' || !this.date) return;
            this.quantity = Math.min(cfg.max, Math.max(cfg.min, parseInt(this.quantity, 10) || cfg.min));
            this.guests = Math.min(cfg.capacity, Math.max(1, parseInt(this.guests, 10) || 1));
            this.quoting = true;
            const params = new URLSearchParams({ date: this.date, quantity: this.quantity, guests: this.guests });
            this.quote = await getJson(`${cfg.quoteUrl}?${params}`);
            this.quoting = false;
        },

        // ── Comum ──────────────────────────────────────────────

        get submitQuantity() {
            return this.billing === 'hour' ? this.hours : this.quantity;
        },

        get total() {
            const people = cfg.shared ? (parseInt(this.guests, 10) || 1) : 1;
            if (this.billing !== 'hour') return this.quote ? money(this.quote.total) : '—';
            return money(this.hours * cfg.price * people);
        },

        get ready() {
            if (this.busy) return false;
            if (this.billing === 'hour') return this.hourProblem() === null;
            return !!(this.date && this.quote && this.quote.available && !this.quoting);
        },
    };
}
