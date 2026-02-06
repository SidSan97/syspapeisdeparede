<script setup>
import { useTheme } from '@/composables/useTheme';

const { setTheme, theme } = useTheme();
</script>

<template>
  <div class="position-relative">
    <button
      class="btn btn-subtle px-2"
      id="bd-theme"
      type="button"
      data-bs-toggle="dropdown"
      aria-expanded="false"
    >
      <span class="theme-icon-active">
        <i v-if="theme === 'dark'" class="fa fa-moon"></i>
        <i v-if="theme === 'system'" class="fa fa-adjust"></i>
        <i v-if="theme === 'light'" class="fa fa-sun"></i>
      </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" role="menu">
      <li>
        <button
          class="dropdown-item"
          :class="{ active: theme === 'light' }"
          @click="setTheme('light')"
        >
          <i class="fa fa-sun"></i> <span class="ms-2">Claro</span>
        </button>
      </li>
      <li>
        <button
          class="dropdown-item"
          :class="{ active: theme === 'dark' }"
          @click="setTheme('dark')"
        >
          <i class="fa fa-moon"></i> <span class="ms-2">Escuro</span>
        </button>
      </li>
      <li>
        <button
          class="dropdown-item"
          :class="{ active: theme === 'system' }"
          @click="setTheme('system')"
        >
          <i class="fa fa-adjust"></i> <span class="ms-2">Auto</span>
        </button>
      </li>
    </ul>
  </div>
</template>
