// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/*
 * @package    local_entities
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {Calendar} from 'local_entities/fullcalendar';
import Ajax from 'core/ajax';

var initialLocaleCode = "";
var calendarEl = "";

/**
 * Init Calendar
 * @param {entityid} entityid
 * @param {string} locale
 * @param {string} jsondata
 */
export const init = (entityid, locale, jsondata = null) => {
    initialLocaleCode = locale;
    calendarEl = document.getElementById('entity-calendar');
    if (!jsondata) {
        jsondata = getEntityCalendardata(entityid);
    } else {
        renderCalendar(jsondata);
    }


};

const renderCalendar = (events) => {
    var calendar = new Calendar(calendarEl, {
        timeZone: 'UTC',
        displayEventEnd: true,
        headerToolbar: {
          left: 'prev,next today',
          center: 'title',
          right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
        },
        locale: initialLocaleCode,
        buttonIcons: false,
        weekNumbers: true,
        navLinks: true,
        editable: true,
        dayMaxEvents: true,
        events: events,
        buttonText: {
          prev: "Zur\xFCck",
          next: "Vor",
          today: "Heute",
          year: "Jahr",
          month: "Monat",
          week: "Woche",
          day: "Tag",
          list: "Termin\xFCbersicht"
        },
        weekText: "KW",
        weekTextLong: "Woche",
        allDayText: "Ganzt\xE4gig",
        moreLinkText: function(n) {
          return "+ weitere " + n;
        },
        noEventsText: "Keine Ereignisse anzuzeigen",
        buttonHints: {
          prev: function(buttonText) {
            return "Vorherige".concat(affix(buttonText), " ").concat(buttonText);
          },
          next: function(buttonText) {
            return "N\xE4chste".concat(affix(buttonText), " ").concat(buttonText);
          },
          today: function(buttonText) {
            if (buttonText === "Tag") {
              return "Heute";
            }
            return "Diese".concat(affix(buttonText), " ").concat(buttonText);
          }
        },
        viewHint: function(buttonText) {
          // eslint-disable-next-line no-nested-ternary
          var glue = buttonText === "Woche" ? "n" : buttonText === "Monat" ? "s" : "es";
          return buttonText + glue + "ansicht";
        },
        navLinkHint: "Gehe zu $0",
        moreLinkHint: function(eventCnt) {
          return "Zeige " + (eventCnt === 1 ? "ein weiteres Ereignis" : eventCnt + " weitere Ereignisse");
        },
        closeHint: "Schlie\xDFen",
        timeHint: "Uhrzeit",
        eventHint: "Ereignis"
      });

      calendar.render();
};

/**
 * Returns the right affix
 * @param {string} buttonText
 * @returns {string}
 */
function affix(buttonText) {
    // eslint-disable-next-line no-nested-ternary
    return buttonText === "Tag" || buttonText === "Monat" ? "r" : buttonText === "Jahr" ? "s" : "";
}

/**
 * Get Calendardata via WS.
 * @param {integer} entityid
 *
 */
const getEntityCalendardata = (entityid) => {
    let request = {
        methodname: 'local_entities_get_entity_calendardata',
        args: {'id': entityid}
    };
    Ajax.call([request])[0].done(function(data) {
        if (data.json) {
            renderCalendar(JSON.parse(data.json));
        } else {
            // eslint-disable-next-line no-console
            console.log(data.error);
        }
    }).fail();
};
