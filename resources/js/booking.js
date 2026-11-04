// Componentes Alpine.js dos fluxos de agendamento.
// Buscam a disponibilidade em JSON (AvailabilityController) e montam a grade de horários.

const nextDays = (count = 14) => {
    const days = [];
    const base = new Date();
    for (let i = 0; i < count; i++) {
        const d = new Date(base.getFullYear(), base.getMonth(), base.getDate() + i);
        days.push({
            iso: d.toLocaleDateString('en-CA'), // YYYY-MM-DD no fuso local
            weekday: i === 0 ? 'Hoje' : d.toLocaleDateString('pt-BR', { weekday: 'short' }).replace('.', ''),
            day: d.getDate(),
            month: d.toLocaleDateString('pt-BR', { month: 'short' }).replace('.', ''),
        });
    }
    return days;
};

const fetchSlots = async (url) => {
    const res = await fetch(url, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    return (await res.json()).slots ?? [];
};

const longDate = (iso) => {
    const s = new Date(iso + 'T12:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' });
    return s.charAt(0).toUpperCase() + s.slice(1);
};

export function serviceBooking({ endpoint, single = null }) {
    return {
        days: nextDays(14),
        pro: null,
        proName: '',
        date: null,
        slots: [],
        slot: null,
        loading: false,
        busy: false,

        init() {
            this.date = this.days[0].iso;
            if (single) this.pickPro(single.id, single.name);
        },

        async pickPro(id, name) {
            this.pro = id;
            this.proName = name;
            this.slot = null;
            await this.load();
            await this.skipToAvailableDay();
        },

        // Se o dia escolhido não tem vaga (ex.: já passou o expediente), avança para o próximo com vaga
        async skipToAvailableDay() {
            let i = this.days.findIndex(d => d.iso === this.date);
            while (!this.slots.some(s => s.available) && i < this.days.length - 1) {
                this.date = this.days[++i].iso;
                await this.load();
            }
        },

        pickDate(iso) {
            this.date = iso;
            this.slot = null;
            this.load();
        },

        async load() {
            if (!this.pro || !this.date) return;
            this.loading = true;
            this.slots = await fetchSlots(`${endpoint}?professional=${this.pro}&date=${this.date}`);
            this.loading = false;
        },

        summary() {
            return `${longDate(this.date)} · ${this.slot.time} – ${this.slot.end}`;
        },
    };
}

export function spaceBooking({ endpoint, price }) {
    return {
        days: nextDays(21),
        date: null,
        slots: [],
        start: null,
        end: null,
        loading: false,
        busy: false,

        async init() {
            await this.pickDate(this.days[0].iso);
            let i = 0;
            while (!this.slots.some(s => s.available) && i < this.days.length - 1) {
                await this.pickDate(this.days[++i].iso);
            }
        },

        async pickDate(iso) {
            this.date = iso;
            this.start = this.end = null;
            this.loading = true;
            this.slots = await fetchSlots(`${endpoint}?date=${iso}`);
            this.loading = false;
        },

        // 1º clique: início · 2º clique: estende até o horário (se tudo no meio estiver livre)
        pick(i) {
            const s = this.slots[i];
            if (!s.available) return;

            const extending = this.start !== null && this.end === this.start && i > this.start
                && this.slots.slice(this.start, i + 1).every(x => x.available);

            if (extending) {
                this.end = i;
            } else {
                this.start = this.end = i;
            }
        },

        selected(i) {
            return this.start !== null && i >= this.start && i <= this.end;
        },

        get hours() {
            return this.start === null ? 0 : this.end - this.start + 1;
        },

        get total() {
            return (this.hours * price).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        },

        get startHour() {
            return this.start === null ? '' : parseInt(this.slots[this.start].time, 10);
        },

        get endHour() {
            return this.end === null ? '' : parseInt(this.slots[this.end].end, 10) || 24;
        },

        summary() {
            if (this.start === null) return '';
            return `${longDate(this.date)} · ${this.slots[this.start].time} – ${this.slots[this.end].end}`;
        },
    };
}
