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
    // Start from 1970-01-05 which is a monday
    const referenceDate = new Date('1970-01-05');
    const days = Math.floor((d - referenceDate) / (1000 * 60 * 60 * 24));
    return Math.ceil(days / 7);
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


function toSlug(text) {
    return window.slugify(text, { lower: true, strict: true });
}

function updateStatus(message) {
    const statusElement = document.querySelector('#notices');
    statusElement.innerHTML = message;
}

/**
 * Fetch and display events
*/
function refreshCalendar(timeZone) {
    const eventsContainer = document.getElementById('events-container');
    eventsContainer.innerHTML = '';
    updateStatus('Warming up the time machine... <i class="fas fa-spinner fa-spin"></i>');

    fetch('events.json')
    .then(response => response.json())
    .then(events => {

        // Vérifiez que les données sont dans le format attendu
        if (!Array.isArray(events)) {
            throw new Error('Unable to read data source.');
        }
        // add div.status starting to read calendars, with a spinning wheel
        updateStatus('Reading calendars... <i class="fas fa-spinner fa-spin"></i>');        
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
        
        updateStatus('Tidying up... <i class="fas fa-spinner fa-spin"></i>');
        
        Object.keys(eventsByWeek).forEach(weekNumber => {
            const weekElement = document.createElement('div');
            weekElement.id = `week-${weekNumber}`;
            weekElement.classList.add('week');
            
            Object.keys(eventsByWeek[weekNumber]).forEach(day => {
                const dayElement = document.createElement('div');
                const dateObject = new Date(day);
                dayElement.id = `day-${day}`;
                const dayOfWeek = dateObject.toLocaleDateString('fr-FR', { weekday: 'long' });
                dayElement.classList.add('day', `day-${toSlug(dayOfWeek)}`);
                // add "today" class if day is today
                const now = new Date();
                if (now.toISOString().split('T')[0] === day) {
                    dayElement.classList.add('today');
                }
                
                // Créez une nouvelle date à partir de la chaîne de date
                
                // Formatez la date au format long
                const longDate = dateObject.toLocaleDateString(undefined, { dateStyle: 'full' });
                
                // Séparez la date en ses composants
                const [weekday, date, month, year] = longDate.split(' ');
                
                dayElement.innerHTML = `
                <h3>
                <span class=date-weekday>${weekday}</span>
                <span class=date-day>${date}</span>
                <span class=date-month>${month}</span>
                <span class=date-year>${year}</span>
                </h3>
                `;
                
                eventsByWeek[weekNumber][day].forEach(event => {
                    const eventElement = document.createElement('div');
                    eventElement.id = `event-${event.hash}`;
                    eventElement.classList.add('event', ...event.tags.map(tag => `tag-${toSlug(tag)}`));
                    // add "ongoing" class if event is ongoing
                    const now = new Date();
                    if (now >= new Date(event.start) && now <= new Date(event.end)) {
                        eventElement.classList.add('ongoing');
                    }
                    
                    const options = { hour: 'numeric', minute: 'numeric' };
                    const startLA = new Date(event.start).toLocaleString(undefined, { timeZone, hour: 'numeric', minute: 'numeric' });
                    const endLA = new Date(event.end).toLocaleString(undefined, { timeZone, hour: 'numeric', minute: 'numeric' });
                    
                    // const duration = new Date(event.end) - new Date(event.start);
                    
                    const teleportLinks = Object.entries(event.teleport)
                    .map(([key, value]) => `<a class="tplink" href="${value}" target="_blank">${key}</a>`)
                    .join(' ');
                    
                    eventElement.innerHTML = `
                    <h4 class=title>${event.title}</h4>
                    <p class=time><span class=start>${startLA}</span> <span class=end>${endLA}</span></p>
                    <p class=description>${event.description}</p>
                    <p class=teleport>${event.hgurl} <span class=tplinks>${teleportLinks}</span></p>
                    <p class=tags>${event.tags}</p>
                    `;
                    
                    dayElement.appendChild(eventElement);
                });
                
                weekElement.appendChild(dayElement);
            });
            
            eventsContainer.appendChild(weekElement);
        });
        // status finished processing
        updateStatus('');
    })
    .catch(error => {
        // Gérez les erreurs ici
        console.error('Erreur:', error);
    });
}

refreshCalendar(timeZone);


/**
 * Sticky header
 * 
 * included in css:
 *  header {
 *      position: sticky;
 *      top: 0;
 *  }
 * 
 *  .sticky {
 *     background: pink;
 *  } 
 */
const header = document.querySelector('header');
const sticky = header.offsetTop;

window.addEventListener('scroll', function() {
    if (window.pageYOffset > sticky) {
        console.log('sticky');
        header.classList.add('sticky');
    } else {
        console.log('not sticky');
        header.classList.remove('sticky');
    }
});


/**
 * Main menu
 */

document.querySelectorAll('nav li').forEach(function(menuItem) {
    menuItem.addEventListener('click', function() {
        // Cacher toutes les sections
        document.querySelectorAll('section').forEach(function(section) {
          section.style.display = 'none';
        });
    
        // Afficher la section correspondante
        var target = this.getAttribute('data-target');
        document.getElementById(target).style.display = 'block';
    });
});
