import moment from 'moment';

moment.locale('pt-br')

export default {
  install(app) {
    app.config.globalProperties.$myDate = (date, format = 'DD/MM/YYYY') => {
      return moment(date).format(format);
    };

    app.config.globalProperties.$myDateTime = (date, format = 'DD/MM/YYYY hh:mm') => {
      return moment(date).format(format);
    };

    app.config.globalProperties.$myCalendarTime = (date, format = 'DD/MM/YYYY hh:mm') => {
      return moment(date).calendar(null, { sameElse: 'DD/MM/YYYY hh:mm' });
    };
  }
};
