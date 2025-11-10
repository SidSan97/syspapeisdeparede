import _ from 'lodash';
import moment from 'moment';

export function useUtils() {
    
    function dateInTheFuture(date) {
        return moment().diff(moment(date + ' Z'), 'minutes') < 0;
    }

    function timeAgo(time) {
        return moment(time + ' Z').utc().local().fromNow();
    }

    function localTime(time) {
        return moment(time + ' Z').utc().local().format('MMMM Do YYYY, h:mm:ss A');
    }

    function truncate(string, length = 70) {
        return _.truncate(string, {
            length: length,
            separator: /,? +/
        });
    }

    const debouncer = _.debounce(callback => callback(), 500);

    function slugify(text) {
        return text.toString().toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-');
    }

    return {
        dateInTheFuture,
        timeAgo,
        localTime,
        truncate,
        debouncer,
        slugify,
    };
}
