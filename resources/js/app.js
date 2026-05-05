import './bootstrap';

import { createApp } from 'vue';
import { createPinia } from 'pinia';

import router from '@/router';
import { initializeTheme } from './lib/theme';

const app = createApp({});
app.use(router);
app.use(createPinia());

import { Form } from 'vform';
import { HasError, AlertError } from 'vform/src/components/bootstrap5';
window.Form = Form;

app.component(HasError.name, HasError);
app.component(AlertError.name, AlertError);

import { VueDatePicker } from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
app.component('VueDatePicker', VueDatePicker);

import money from 'v-money3';
app.use(money, {
  decimal: ',',
  thousands: '.',
  precision: 2,
});
import { Money3Component } from 'v-money3';
app.component('money', Money3Component);

import NotFound from './components/NotFound.vue';
app.component('not-found', NotFound);

import PaginationNav from './components/pagination/PaginationNav.vue';
app.component('pagination', PaginationNav);

initializeTheme();

app.mount('#app');
