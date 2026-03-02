document.addEventListener('DOMContentLoaded', function() {
    const events = JSON.parse(localStorage.getItem('events')) || [];

    function renderCalendar(month, year) {
        const calendar = document.getElementById('calendar');
        calendar.innerHTML = ''; // Clear the calendar
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month).getDay();

        // Set current month and year in header
        const monthNames = ["January", "February", "March", "April", "May", "June",
                            "July", "August", "September", "October", "November", "December"];
        document.getElementById('currentMonthYear').innerText = `${monthNames[month]} ${year}`;

        // Fill in the days of the calendar
        for (let i = 0; i < firstDay; i++) {
            const emptyDiv = document.createElement('div');
            calendar.appendChild(emptyDiv);
        }

        for (let i = 1; i <= daysInMonth; i++) {
            const dayDiv = document.createElement('div');
            dayDiv.innerText = i;

            // Check if there's an event on this date
            const event = events.find(event => new Date(event.date).getDate() === i &&
                                               new Date(event.date).getMonth() === month &&
                                               new Date(event.date).getFullYear() === year);
            if (event) {
                const eventDiv = document.createElement('div');
                eventDiv.classList.add('event');
                eventDiv.innerHTML = `<strong>${event.title}</strong><br><small>${event.description}</small>`;
                dayDiv.appendChild(eventDiv);
            }

            calendar.appendChild(dayDiv);
        }
    }

    function renderListView() {
        const eventList = document.getElementById('eventList');
        eventList.innerHTML = ''; // Clear the list
        events.forEach(event => {
            const listItem = document.createElement('li');
            listItem.classList.add('list-group-item');
            listItem.innerHTML = `
                <strong>${new Date(event.date).toLocaleDateString()}</strong> - ${event.title}<br>
                <small>${event.description}</small>
            `;
            eventList.appendChild(listItem);
        });
    }

    // Initialize the calendar with the current month and year
    const today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();

    document.getElementById('listViewBtn').addEventListener('click', function() {
        document.getElementById('listView').classList.remove('d-none');
        document.getElementById('calendarView').classList.add('d-none');
    });

    document.getElementById('calendarViewBtn').addEventListener('click', function() {
        document.getElementById('listView').classList.add('d-none');
        document.getElementById('calendarView').classList.remove('d-none');
    });

    document.getElementById('prevMonth').addEventListener('click', function() {
        currentMonth = (currentMonth === 0) ? 11 : currentMonth - 1;
        currentYear = (currentMonth === 11) ? currentYear - 1 : currentYear;
        renderCalendar(currentMonth, currentYear);
    });

    document.getElementById('nextMonth').addEventListener('click', function() {
        currentMonth = (currentMonth === 11) ? 0 : currentMonth + 1;
        currentYear = (currentMonth === 0) ? currentYear + 1 : currentYear;
        renderCalendar(currentMonth, currentYear);
    });

    renderCalendar(currentMonth, currentYear);
    renderListView();
});
