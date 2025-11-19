/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth';

import 'bootstrap-icons/font/bootstrap-icons.css'

import router from '@/router'

import './plugins/sweetalert2'

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

const app = createApp({});

app.use(createPinia())

const auth = useAuthStore();
if (window.LaravelApp.user) {
  auth.setUser({
    user: window.LaravelApp.user,
    roles: window.LaravelApp.roles || [],
    permissions: window.LaravelApp.permissions || [],
    direct_permissions: window.LaravelApp.direct_permissions || [],
  });
  auth.ready = true;
} else {
  // 
}

app.use(router)

app.config.globalProperties.$asset = function (path) {
  const basePath = window.LaravelApp.assetUrl || ''
  return basePath + path
}

import './utils/dark-mode-toggler'

import momentPlugin from './plugins/moment'
app.use(momentPlugin)

import ExampleComponent from './components/ExampleComponent.vue';
app.component('example-component', ExampleComponent);

// app.component('app-header', () => import('./components/AppHeader.vue'));
// app.component('app-sidebar', () => import('./components/AppSidebar.vue'));
import AppLayout from './layouts/AppLayout.vue';
app.component('app-layout', AppLayout);

import NotFound from './components/NotFound.vue';
app.component('not-found', NotFound);

import { Bootstrap5Pagination } from 'laravel-vue-pagination';
app.component('pagination', Bootstrap5Pagination);

import { Form } from "vform";
import { HasError, AlertError } from "vform/src/components/bootstrap5";

window.Form = Form;

app.component(HasError.name, HasError);
app.component(AlertError.name, AlertError);

import VCalendar from 'v-calendar';
import 'v-calendar/style.css';

app.use(VCalendar, {componentPrefix: 'vc'})

import money from 'v-money3'
app.use(money, {
    decimal: ',',
    thousands: '.',
    precision: 2
})
import { Money3Component } from 'v-money3'
app.component('money', Money3Component);

import VueProgressBar from "@aacassandra/vue3-progressbar";

const options = {
    color: "#579dff",
    failedColor: "#874b4b",
    thickness: "5px",
    transition: {
        speed: "0.2s",
        opacity: "0.6s",
        termination: 300,
    },
    autoRevert: true,
    location: "top",
    inverse: false,
};

app.use(VueProgressBar, options)

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

app.mount('#app');
