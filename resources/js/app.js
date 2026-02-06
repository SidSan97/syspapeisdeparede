/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */
import './bootstrap';
import { createApp } from 'vue';

import '../css/brand.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

import './plugins/sweetalert2';

import { initializeTheme } from './lib/theme';
initializeTheme();

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */
const app = createApp({});

import { createPinia } from 'pinia';
app.use(createPinia());

import router from '@/router';
app.use(router);

app.config.globalProperties.$asset = function (path) {
  const basePath = window.LaravelApp.assetUrl || '';
  return basePath + path;
};

import momentPlugin from './plugins/moment';
app.use(momentPlugin);

import { Form } from 'vform';
import { HasError, AlertError } from 'vform/src/components/bootstrap5';

window.Form = Form;

app.component(HasError.name, HasError);
app.component(AlertError.name, AlertError);

import VCalendar from 'v-calendar';
import 'v-calendar/style.css';

app.use(VCalendar, { componentPrefix: 'vc' });

import money from 'v-money3';
app.use(money, {
  decimal: ',',
  thousands: '.',
  precision: 2,
});
import { Money3Component } from 'v-money3';
app.component('money', Money3Component);

import VueProgressBar from '@aacassandra/vue3-progressbar';

const options = {
  color: '#579dff',
  failedColor: '#874b4b',
  thickness: '5px',
  transition: {
    speed: '0.2s',
    opacity: '0.6s',
    termination: 300,
  },
  autoRevert: true,
  location: 'top',
  inverse: false,
};

app.use(VueProgressBar, options);

import { mask } from 'vue-the-mask';
app.directive('mask', mask);

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

import ExampleComponent from './components/ExampleComponent.vue';
app.component('example-component', ExampleComponent);

import NotFound from './components/NotFound.vue';
app.component('not-found', NotFound);

import PaginationNav from './components/pagination/PaginationNav.vue';
app.component('pagination', PaginationNav);

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

app.mount('#app');
