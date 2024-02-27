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

function getUniqueWeekNumber(d) {
    const referenceDate = new Date('1970-01-01');
    const days = Math.floor((d - referenceDate) / (1000 * 60 * 60 * 24));
    return Math.floor(days / 7);
}

const timeZone = 'America/Los_Angeles';

/**
 * Fetch and display events
 */

fetch('events.json')
.then(response => response.json())
.then(events => {
        // Vérifiez que les données sont dans le format attendu
        if (!Array.isArray(events)) {
            throw new Error('Les données récupérées ne sont pas un tableau');
        }

        const eventsByWeek = {};

        events.forEach(event => {
            // Vérifiez que chaque événement a une propriété 'start'
            if (!event.hasOwnProperty('start')) {
                throw new Error('Un événement n\'a pas de propriété \'start\'');
            }

            // const startDate = new Date(event.start).toLocaleString(undefined, { timeZone });
            // const endDate = new Date(event.end).toLocaleString(undefined, { timeZone });
            const startDate = moment(event.start).tz(timeZone);
            const endDate = moment(event.end).tz(timeZone);
        
            const weekNumber = getUniqueWeekNumber(startDate);
        
            if (!eventsByWeek[weekNumber]) {
                eventsByWeek[weekNumber] = {};
            }

            const day = startDate.toISOString().split('T')[0]; // Obtenez la date complète au format YYYY-MM-DD

            if (!eventsByWeek[weekNumber][day]) {
                eventsByWeek[weekNumber][day] = [];
            }

            eventsByWeek[weekNumber][day].push(event);
        });

        const eventsContainer = document.getElementById('events-container');

        Object.keys(eventsByWeek).forEach(weekNumber => {
            const weekElement = document.createElement('div');
            // weekElement.innerHTML = `<h2>Week ${weekNumber}</h2>`;

            Object.keys(eventsByWeek[weekNumber]).forEach(day => {
                const dayElement = document.createElement('div');
    
                // Créez une nouvelle date à partir de la chaîne de date
                const dateObject = new Date(day);

                // Formatez la date au format long
                const longDate = dateObject.toLocaleDateString(undefined, { dateStyle: 'full' });
                
                // Séparez la date en ses composants
                const [weekday, date, month, year] = longDate.split(' ');
                
                dayElement.innerHTML = `
                    <h3>
                        <span>${weekday}</span>
                        <span>${date}</span>
                        <span>${month}</span>
                        <span>${year}</span>
                    </h3>
                `;

                eventsByWeek[weekNumber][day].forEach(event => {
                    const eventElement = document.createElement('div');
                    const options = { hour: 'numeric', minute: 'numeric' };
                    const startLA = new Date(event.start).toLocaleString(undefined, { timeZone, hour: 'numeric', minute: 'numeric' });
                    const endLA = new Date(event.end).toLocaleString(undefined, { timeZone, hour: 'numeric', minute: 'numeric' });

                    // const duration = new Date(event.end) - new Date(event.start);

                    eventElement.innerHTML = `
                        <h4>${event.title}</h4>
                        <p>${startLA} - ${endLA}</p>
                        <p>${event.description}</p>
                        <p>${event.tags}</p>
                    `;
                    dayElement.appendChild(eventElement);
                });

                weekElement.appendChild(dayElement);
            });

            eventsContainer.appendChild(weekElement);
        });
    });
    // <pre>${JSON.stringify(event, null, 2)}</pre>
