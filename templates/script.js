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

var timeZone = 'America/Los_Angeles'; // OpenSimulator/SL Time

/**
 * Build time zone selector
 */

// Obtenez l'élément avec l'ID #select-timezone
const selectElement = document.querySelector('#select-timezone');

// Effacez le contenu actuel de l'élément #select-timezone
selectElement.innerHTML = '';

// Créez un nouvel élément select
const select = document.createElement('select');

// Ajoutez l'option pour le fuseau horaire actuel
let option = document.createElement('option');
option.value = timeZone;
option.text = 'OpenSim/SL Time';
select.appendChild(option);

// Obtenez le fuseau horaire de l'utilisateur
const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

// Ajoutez l'option pour le fuseau horaire de l'utilisateur si différent du premier choix
if (userTimeZone !== timeZone) {
    option = document.createElement('option');
    option.value = userTimeZone;
    option.text = userTimeZone;
    select.appendChild(option);
}

// Ajoutez des options pour d'autres fuseaux horaires
// const timeZones = ['Europe/Paris', 'Asia/Tokyo', 'Australia/Sydney']; // Remplacez ceci par la liste des fuseaux horaires que vous voulez inclure
const timeZones = moment.tz.names();

for (const tz of timeZones) {
    if (tz !== timeZone && tz !== userTimeZone) {
        option = document.createElement('option');
        option.value = tz;
        option.text = tz;
        select.appendChild(option);
    }
}

// Ajoutez un gestionnaire d'événements change à l'élément select
select.addEventListener('change', function() {
    // Mettez à jour le fuseau horaire et rafraîchissez le calendrier
    timeZone = this.value;
    refreshCalendar(timeZone);
});

// Ajoutez l'élément select à l'élément #select-timezone
selectElement.appendChild(select);
$(select).select2();

$(select).select2().on('change', function() {
    // Mettez à jour le fuseau horaire et rafraîchissez le calendrier
    timeZone = this.value;
    refreshCalendar(timeZone);
});

/**
 * Display a real time clock for America/Los_Angeles timezone
 */
function updateClock() {
    const slTimeElement = document.querySelector('#sltime');
    setInterval(() => {
        const time = moment().tz('America/Los_Angeles').format('MMM D, YYYY hh:mm:ss A');
        slTimeElement.innerHTML = time;
    }, 1000);
}

// Appeler la fonction pour démarrer l'horloge
updateClock();

/**
 * Fetch and display events
 */
function refreshCalendar(timeZone) {
    const eventsContainer = document.getElementById('events-container');
    eventsContainer.innerHTML = ''; // Ajoutez cette ligne pour vider le conteneur d'événements

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
            // const startDate = moment(event.start).tz(timeZone);
            // const endDate = moment(event.end).tz(timeZone);
            const startDate = moment(event.start).tz(timeZone).toDate();
            const endDate = moment(event.end).tz(timeZone).toDate();

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
    })
    .catch(error => {
        // Gérez les erreurs ici
        console.error('Erreur:', error);
    });
}

refreshCalendar(timeZone);
