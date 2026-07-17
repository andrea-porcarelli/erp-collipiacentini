import App from "./app.js";

const DAY_NAMES = ["LUN", "MAR", "MER", "GIO", "VEN", "SAB", "DOM"];

const state = {
    weekStart: null,      // 'YYYY-MM-DD'
    selectedDate: null,   // 'YYYY-MM-DD'
    groupBy: "product",   // 'product' | 'slot'
    partnerId: null,
    weekDays: [],         // [{date, orders_count, has_bookings}]
    selectedSlot: null,   // {productId, productLabel, date, time}
    arrivalsFilters: { order_status: "all", check_in: "all", search: "" },
    pendingCheckin: {},   // participantId -> newStatus
    currentOrderId: null,
};

const config = window.calendarConfig || {};

// Aggiunge/sottrae giorni a una stringa "YYYY-MM-DD" restando su componenti locali.
// Evita il drift di toISOString() quando il fuso locale è avanti rispetto a UTC
// (es. CEST +02:00 farebbe scivolare la data al giorno precedente).
function shiftDate(dateStr, days) {
    const [y, m, d] = dateStr.split("-").map(Number);
    const date = new Date(y, m - 1, d);
    date.setDate(date.getDate() + days);
    const yy = date.getFullYear();
    const mm = String(date.getMonth() + 1).padStart(2, "0");
    const dd = String(date.getDate()).padStart(2, "0");
    return `${yy}-${mm}-${dd}`;
}

function fmtWeekLabel(days) {
    if (!days || days.length === 0) return "—";
    const months = ["GEN","FEB","MAR","APR","MAG","GIU","LUG","AGO","SET","OTT","NOV","DIC"];
    const first = new Date(days[0].date + "T00:00:00");
    const last = new Date(days[days.length - 1].date + "T00:00:00");
    return `${first.getDate()} ${months[first.getMonth()]} - ${last.getDate()} ${months[last.getMonth()]}`;
}

function withPartner(params) {
    if (state.partnerId) {
        params.partner_id = state.partnerId;
    }
    return params;
}

function loadWeek() {
    if (!state.partnerId) return;
    App.ajax({
        path: config.urls.week,
        method: "get",
        data: withPartner({ week_start: state.weekStart }),
    }).then((res) => {
        state.weekDays = res.days;
        renderWeekLabel(res.label);
        renderDaysStrip();
        // se selezione fuori range settimana, la porto sul primo giorno con prenotazioni,
        // altrimenti sull'oggi se presente, altrimenti sul primo.
        if (!state.selectedDate || !res.days.find((d) => d.date === state.selectedDate)) {
            const todayInWeek = res.days.find((d) => d.date === config.today);
            const firstBooked = res.days.find((d) => d.has_bookings);
            state.selectedDate = (todayInWeek || firstBooked || res.days[0]).date;
        }
        highlightSelectedDay();
        loadDay();
    }).catch(() => {
        toastError("Errore nel caricamento della settimana");
    });
}

function renderWeekLabel(label) {
    document.querySelectorAll(".js-week-label").forEach((el) => {
        el.textContent = label || fmtWeekLabel(state.weekDays);
    });
}

function renderDaysStrip() {
    const strip = document.getElementById("calendar-days-strip");
    if (!strip) return;
    const buttons = strip.querySelectorAll(".calendar-day-btn");
    state.weekDays.forEach((day, index) => {
        const btn = buttons[index];
        if (!btn) return;
        const d = new Date(day.date + "T00:00:00");
        btn.dataset.date = day.date;
        btn.querySelector(".calendar-day-name").textContent = DAY_NAMES[index];
        btn.querySelector(".calendar-day-num").textContent = d.getDate();
        btn.classList.toggle("has-bookings", !!day.has_bookings);
        btn.classList.toggle("is-today", day.date === config.today);
    });
}

function highlightSelectedDay() {
    document.querySelectorAll(".calendar-day-btn").forEach((btn) => {
        btn.classList.toggle("is-active", btn.dataset.date === state.selectedDate);
    });
}

function loadDay() {
    if (!state.partnerId || !state.selectedDate) return;
    const container = document.getElementById("calendar-day-content");
    if (container) container.innerHTML = '<div class="calendar-loading">Caricamento…</div>';
    App.ajax({
        path: config.urls.day,
        method: "get",
        data: withPartner({ date: state.selectedDate, group_by: state.groupBy }),
    }).then((res) => {
        if (container) container.innerHTML = res.html;
        // Se avevo uno slot selezionato, ripristino evidenziazione
        applySlotSelection();
    }).catch(() => {
        if (container) container.innerHTML = '<div class="calendar-empty-state"><p>Errore nel caricamento del giorno.</p></div>';
    });
}

function applySlotSelection() {
    if (!state.selectedSlot) return;
    document.querySelectorAll(".js-slot-card").forEach((card) => {
        const match =
            card.dataset.productId === String(state.selectedSlot.productId) &&
            card.dataset.date === state.selectedSlot.date &&
            card.dataset.time === state.selectedSlot.time;
        card.classList.toggle("is-selected", match);
    });
}

function selectSlot(card) {
    state.selectedSlot = {
        productId: parseInt(card.dataset.productId, 10),
        productLabel: card.dataset.productLabel,
        date: card.dataset.date,
        time: card.dataset.time,
    };
    applySlotSelection();
    loadArrivals();
    if (isMobileViewport()) {
        openArrivalsDrawer();
    }
}

function isMobileViewport() {
    return window.matchMedia("(max-width: 991.98px)").matches;
}

function openArrivalsDrawer() {
    const column = document.getElementById("calendar-arrivals-column");
    if (!column) return;
    column.classList.add("is-open");
    document.body.classList.add("calendar-drawer-open");
}

function closeArrivalsDrawer() {
    const column = document.getElementById("calendar-arrivals-column");
    if (!column) return;
    column.classList.remove("is-open");
    document.body.classList.remove("calendar-drawer-open");
}

function loadArrivals() {
    if (!state.selectedSlot) return;
    const arrivals = document.getElementById("calendar-arrivals");
    const body = document.getElementById("calendar-arrivals-body");
    if (arrivals) arrivals.removeAttribute("data-empty");
    if (body) body.innerHTML = '<div class="calendar-loading">Caricamento…</div>';
    App.ajax({
        path: config.urls.slotOrders,
        method: "get",
        data: withPartner({
            product_id: state.selectedSlot.productId,
            date: state.selectedSlot.date,
            time: state.selectedSlot.time,
            order_status: state.arrivalsFilters.order_status,
            check_in: state.arrivalsFilters.check_in,
            search: state.arrivalsFilters.search,
        }),
    }).then((res) => {
        if (body) body.innerHTML = res.html;
    }).catch(() => {
        if (body) body.innerHTML = '<div class="calendar-arrivals-placeholder"><p>Errore</p></div>';
    });
}

function openOrderModal(orderId) {
    state.currentOrderId = orderId;
    state.pendingCheckin = {};
    const body = document.getElementById("calendar-checkin-body");
    if (body) body.innerHTML = '<div class="calendar-loading">Caricamento…</div>';
    $("#modal-order-checkin").modal("show");
    App.ajax({
        path: `${config.urls.orderDetail}/${orderId}`,
        method: "get",
    }).then((res) => {
        if (body) body.innerHTML = res.html;
        const title = document.querySelector(".js-checkin-title");
        const number = body?.querySelector(".calendar-checkin-content")?.dataset.orderNumber;
        if (title && number) title.textContent = `#MTK-${number}`;
    }).catch(() => {
        if (body) body.innerHTML = '<div class="calendar-empty-state"><p>Errore nel caricamento dell\'ordine.</p></div>';
    });
}

function markPendingCheckin(select) {
    const row = select.closest(".cc-ticket");
    if (!row) return;
    const participantId = parseInt(row.dataset.participantId, 10);
    const original = select.dataset.original;
    const value = select.value;
    if (value === original) {
        delete state.pendingCheckin[participantId];
    } else {
        state.pendingCheckin[participantId] = value;
    }
    row.classList.toggle("is-dirty", value !== original);
    // aggiorna contatore live
    updateCheckinCounter();
}

function markAllArrived() {
    document.querySelectorAll(".js-cc-status").forEach((select) => {
        if (select.value !== "checked_in") {
            select.value = "checked_in";
            markPendingCheckin(select);
        }
    });
}

function updateCheckinCounter() {
    const rows = document.querySelectorAll(".cc-ticket");
    let checked = 0;
    rows.forEach((row) => {
        const select = row.querySelector(".js-cc-status");
        if (select && select.value === "checked_in") checked++;
    });
    const el = document.querySelector('[data-role="checkin-count"]');
    if (el) el.textContent = checked;
}

function saveCheckin() {
    const payload = Object.entries(state.pendingCheckin).map(([id, status]) => ({
        id: parseInt(id, 10),
        status,
    }));
    if (payload.length === 0) {
        $("#modal-order-checkin").modal("hide");
        return;
    }
    App.ajax({
        path: config.urls.batchStatus,
        method: "post",
        data: { _method: "PUT", participants: payload },
    }).then(() => {
        toastSuccess("Check-in aggiornati");
        $("#modal-order-checkin").modal("hide");
        loadArrivals();
    }).catch((err) => {
        toastError(err?.responseJSON?.message || "Errore nel salvataggio");
    });
}

function toastSuccess(msg) {
    if (typeof toastr !== "undefined") toastr.success(msg);
}
function toastError(msg) {
    if (typeof toastr !== "undefined") toastr.error(msg);
}

function bindEvents() {
    // Selettore partner (god/operator/company)
    const partnerSelect = document.querySelector(".js-calendar-partner-select");
    if (partnerSelect) {
        partnerSelect.addEventListener("change", () => {
            const value = partnerSelect.value ? parseInt(partnerSelect.value, 10) : null;
            state.partnerId = value;
            if (!value) return;
            const url = new URL(window.location.href);
            url.searchParams.set("partner_id", value);
            window.history.replaceState({}, "", url.toString());
            loadWeek();
        });
    }

    // Navigazione settimana: sempre ±7 giorni, allineata a lunedì lato server.
    document.querySelectorAll(".calendar-week-nav").forEach((btn) => {
        btn.addEventListener("click", () => {
            const direction = btn.dataset.direction;
            state.weekStart = shiftDate(state.weekStart, direction === "prev" ? -7 : 7);
            state.selectedDate = null;
            loadWeek();
        });
    });

    // Oggi
    document.querySelectorAll(".js-calendar-today").forEach((btn) => {
        btn.addEventListener("click", () => {
            state.weekStart = config.weekStart;
            state.selectedDate = config.today;
            loadWeek();
        });
    });

    // Toggle Prodotto / Fascia oraria
    document.querySelectorAll(".calendar-groupby .segmented-option").forEach((btn) => {
        btn.addEventListener("click", () => {
            state.groupBy = btn.dataset.group;
            document.querySelectorAll(".calendar-groupby .segmented-option").forEach((b) => {
                b.classList.toggle("active", b === btn);
            });
            loadDay();
        });
    });

    // Click giorno
    document.addEventListener("click", (e) => {
        const btn = e.target.closest(".calendar-day-btn");
        if (!btn || !btn.dataset.date) return;
        state.selectedDate = btn.dataset.date;
        highlightSelectedDay();
        loadDay();
    });

    // Toggle accordion
    document.addEventListener("click", (e) => {
        const header = e.target.closest(".js-accordion-toggle");
        if (!header) return;
        const item = header.closest(".calendar-accordion-item");
        const body = item.querySelector(".calendar-accordion-body");
        const isOpen = item.classList.toggle("is-open");
        header.setAttribute("aria-expanded", isOpen ? "true" : "false");
        if (body) {
            if (isOpen) body.removeAttribute("hidden");
            else body.setAttribute("hidden", "");
        }
    });

    // Click slot card
    document.addEventListener("click", (e) => {
        const card = e.target.closest(".js-slot-card");
        if (!card) return;
        selectSlot(card);
    });

    // Filtri e ricerca arrivals
    document.addEventListener("change", (e) => {
        const filter = e.target.closest(".js-arrivals-filter");
        if (filter) {
            state.arrivalsFilters[filter.dataset.filter] = filter.value;
            loadArrivals();
        }
    });

    const search = document.querySelector(".js-arrivals-search");
    if (search) {
        search.addEventListener("keyup", App.debounce(() => {
            state.arrivalsFilters.search = search.value.trim();
            loadArrivals();
        }, 300));
    }

    // Click ordine nel pannello arrivi
    document.addEventListener("click", (e) => {
        const item = e.target.closest(".js-arrival-item");
        if (!item) return;
        openOrderModal(parseInt(item.dataset.orderId, 10));
    });

    // Chiusura drawer arrivi (mobile)
    document.addEventListener("click", (e) => {
        if (e.target.closest(".js-arrivals-close")) {
            closeArrivalsDrawer();
        }
        if (e.target.matches("#calendar-arrivals-backdrop")) {
            closeArrivalsDrawer();
        }
    });

    // Se ridimensiono da mobile a desktop mentre il drawer è aperto, lo chiudo per evitare inconsistenze.
    window.addEventListener("resize", () => {
        if (!isMobileViewport()) {
            closeArrivalsDrawer();
        }
    });

    // Cambio stato singolo partecipante
    document.addEventListener("change", (e) => {
        const select = e.target.closest(".js-cc-status");
        if (!select) return;
        markPendingCheckin(select);
    });

    // Segna tutti come arrivati
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".js-cc-all-arrived")) return;
        markAllArrived();
    });

    // Salva
    document.addEventListener("click", (e) => {
        if (!e.target.closest(".js-checkin-save")) return;
        saveCheckin();
    });
}

function init() {
    state.weekStart = config.weekStart;
    state.partnerId = config.partnerId;
    bindEvents();
    if (state.partnerId) {
        loadWeek();
    }
}

document.addEventListener("DOMContentLoaded", init);
