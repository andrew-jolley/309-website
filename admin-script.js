document.addEventListener('DOMContentLoaded', function() {
    const events = JSON.parse(localStorage.getItem('events')) || [];

    function renderAdminEventList() {
        const adminEventList = document.getElementById('adminEventList');
        adminEventList.innerHTML = '';
        events.forEach((event, index) => {
            const listItem = document.createElement('li');
            listItem.classList.add('list-group-item', 'd-flex', 'justify-content-between');
            listItem.innerHTML = `
                <div>
                    <strong>${new Date(event.date).toLocaleDateString()}</strong> - ${event.title}<br>
                    <small>${event.description}</small>
                </div>
            `;
            
            const deleteButton = document.createElement('button');
            deleteButton.classList.add('btn', 'btn-danger', 'btn-sm');
            deleteButton.innerText = 'Delete';
            deleteButton.addEventListener('click', function() {
                events.splice(index, 1);
                localStorage.setItem('events', JSON.stringify(events));
                renderAdminEventList();
            });

            listItem.appendChild(deleteButton);
            adminEventList.appendChild(listItem);
        });
    }

    document.getElementById('addEventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const eventDate = document.getElementById('eventDate').value;
        const eventTitle = document.getElementById('eventTitle').value;
        const eventDescription = document.getElementById('eventDescription').value;

        events.push({ date: eventDate, title: eventTitle, description: eventDescription });
        localStorage.setItem('events', JSON.stringify(events));
        
        document.getElementById('eventDate').value = '';
        document.getElementById('eventTitle').value = '';
        document.getElementById('eventDescription').value = '';
        document.getElementById('addEventModal').querySelector('.btn-close').click();
        renderAdminEventList();
    });

    renderAdminEventList();
});
