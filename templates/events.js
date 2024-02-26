function durationNaturalLanguage(duration) {
    // split the duration into days, hours, minutes
    const days = Math.floor(duration / (1000 * 60 * 60 * 24));
    const hours = Math.floor((duration % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((duration % (1000 * 60 * 60)) / (1000 * 60));

    // build the natural language string

    let durationNL = '';
    if (days > 0) {
        durationNL += `${days} day${days > 1 ? 's' : ''} `;
    }
    if (hours > 0) {
        if (durationNL.length > 0) {
            durationNL += ', ';
        }
        durationNL += `${hours} h `;
    }
    if (minutes > 0 ) {
        if (hours > 0) {
            unit = '';
        } else {
            unit = 'minute ';
        }
        if (durationNL.length > 0) {
            durationNL += ', ';
        }
        durationNL += `${minutes} ${unit}${minutes > 1 ? 's' : ''}`;
    }
    // trim durationNL
    return durationNL.trim();
}

fetch('events.json')
    .then(response => response.json())
    .then(events => {
        const eventsContainer = document.getElementById('events-container');
        events.forEach(event => {
            const eventElement = document.createElement('div');

            const startDate = new Date(event.start);
            const endDate = new Date(event.end);
            const duration = endDate - startDate;
            const durationNL = durationNaturalLanguage(duration);

            const options = { timeZone: 'America/Los_Angeles', year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric' };

            const startLA = startDate.toLocaleString('en-US', options);
            const endLA = endDate.toLocaleString('en-US', options);

            eventElement.innerHTML = `
                <h2>${event.title}</h2>
                <p>${startLA} <span class=duration>(${durationNL})</span></p>
                <p>${event.description}</p>
                <p>${event.tags}</p>
            `;
            eventsContainer.appendChild(eventElement);
        });
    });
//                 <pre>${JSON.stringify(event, null, 2)}</pre>
