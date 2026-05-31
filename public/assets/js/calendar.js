(() => {
    const grid    = document.getElementById('events-grid');
    const calWrap = document.getElementById('calendar-wrap');
    const btnGrid = document.getElementById('view-grid');
    const btnCal  = document.getElementById('view-cal');
    if (!grid || !calWrap || !btnGrid || !btnCal) return;

    const MONTH_NAMES = window.calendarMonthNames || ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

    let currentYear  = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;

    const switchTo = (view) => {
        if (view === 'calendar') {
            grid.style.display = 'none';
            calWrap.style.display = 'block';
            btnGrid.classList.remove('is-active');
            btnCal.classList.add('is-active');
            localStorage.setItem('events_view', 'calendar');
            renderCalendar(currentMonth, currentYear);
        } else {
            grid.style.display = '';
            calWrap.style.display = 'none';
            btnGrid.classList.add('is-active');
            btnCal.classList.remove('is-active');
            localStorage.setItem('events_view', 'grid');
        }
    };

    btnGrid.addEventListener('click', () => switchTo('grid'));
    btnCal.addEventListener('click',  () => switchTo('calendar'));

    async function renderCalendar(month, year) {
        calWrap.innerHTML = '<p class="meta" style="padding:24px">Loading...</p>';

        const resp = await fetch(`${window.baseUrl}events/calendar?month=${month}&year=${year}`);
        const data = await resp.json();
        const events = data.events || [];

        const firstDay  = new Date(year, month - 1, 1).getDay();
        const daysCount = new Date(year, month, 0).getDate();
        const startOffset = (firstDay + 6) % 7;

        let html = `<div class="cal-nav">
            <button class="cal-nav-btn" id="cal-prev">&larr;</button>
            <h2 class="cal-month-title">${MONTH_NAMES[month - 1]} ${year}</h2>
            <button class="cal-nav-btn" id="cal-next">&rarr;</button>
        </div>
        <div class="cal-grid">
            <div class="cal-header">Mon</div><div class="cal-header">Tue</div><div class="cal-header">Wed</div>
            <div class="cal-header">Thu</div><div class="cal-header">Fri</div><div class="cal-header">Sat</div><div class="cal-header">Sun</div>`;

        for (let i = 0; i < startOffset; i++) html += '<div class="cal-cell cal-cell--empty"></div>';

        for (let d = 1; d <= daysCount; d++) {
            const dateStr = `${year}-${String(month).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            const dayEvents = events.filter(e => {
                const start = e.start_date ? e.start_date.slice(0,10) : null;
                const end   = e.end_date   ? e.end_date.slice(0,10)   : start;
                return start && dateStr >= start && dateStr <= (end || start);
            });

            const isToday = dateStr === new Date().toISOString().slice(0,10);
            html += `<div class="cal-cell${isToday ? ' cal-cell--today' : ''}">
                <span class="cal-day-num">${d}</span>`;

            dayEvents.forEach(ev => {
                html += `<a class="cal-event cal-event--${ev.status}" href="${window.baseUrl}events/${ev.slug}" title="${ev.title.replace(/"/g,'&quot;')}">${ev.title}</a>`;
            });

            html += '</div>';
        }

        html += '</div>';
        calWrap.innerHTML = html;

        document.getElementById('cal-prev')?.addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 1) { currentMonth = 12; currentYear--; }
            renderCalendar(currentMonth, currentYear);
        });
        document.getElementById('cal-next')?.addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 12) { currentMonth = 1; currentYear++; }
            renderCalendar(currentMonth, currentYear);
        });
    }

    if (localStorage.getItem('events_view') === 'calendar') {
        switchTo('calendar');
    } else {
        switchTo('grid');
    }
})();
