<template>
    <header class="navbar navbar-expand-lg py-0 sticky-top bd-navbar" style="">
        <nav class="container-fluid px-md-3 px-lg-4">
            <!-- App Brand -->
            <a class="navbar-brand d-inline-flex align-items-center m-0 p-0 me-lg-6 me-xl-9 p-1 rounded text-reset"
                href="/">
                Laravel
            </a>

            <div class="collapse navbar-collapse">
                <ul class="navbar-nav navbar-nav-underline ps-lg-5 flex-grow-1">
                    <li v-for="item in mainNavItems" class="nav-item">
                        <RouterLink class="nav-link" :to="item.to" v-if="item.can">{{ item.title }}</RouterLink>
                    </li>
                </ul>
            </div>
            <div class="d-flex align-items-center ms-auto gap-3 me-2 me-lg-3">
                <div class="position-relative">
                    <button class="btn btn-subtle px-2" id="bd-theme" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="theme-icon-active">
                            <i class="fa fa-sun"></i>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" role="menu">
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="light">
                                <i class="fa fa-sun"></i> <span class="ms-2">Claro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="dark">
                                <i class="fa fa-moon"></i> <span class="ms-2">Escuro</span>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" data-bs-theme-value="auto">
                                <i class="fa fa-adjust"></i> <span class="ms-2">Auto</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <router-link :to="'/settings'" class="btn btn-subtle px-2">
                    <i class="fa fa-cog"></i>
                </router-link>

                <button class="navbar-toggler" type="bdNavbar" data-bs-toggle="offcanvas" data-bs-target="#bdNavbar"
                    aria-controls="bdNavbar" aria-label="Toggle navigation"><svg xmlns="http://www.w3.org/2000/svg"
                        width="24" height="24" fill="currentcolor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M2.5 11.5A.5.5.0 013 11h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 7h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5zm0-4A.5.5.0 013 3h10a.5.5.0 010 1H3a.5.5.0 01-.5-.5z">
                        </path>
                    </svg></button>
            </div>
        </nav>
    </header>
</template>

<script setup>
import { RouterLink } from 'vue-router';

const mainNavItems = [
    {
        title: 'Repository',
        to: 'https://github.com/laravel/vue-starter-kit',
        can: () => true,
    },
]
</script>