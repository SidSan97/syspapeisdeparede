<script setup>
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import { useTheme } from '@/composables/useTheme';
import { IconDeviceDesktop, IconMoon, IconSun } from '@tabler/icons-vue';

const { setTheme, theme } = useTheme();

const themes = [
  { key: 'light', label: 'Claro', icon: IconSun },
  { key: 'dark', label: 'Escuro', icon: IconMoon },
  { key: 'system', label: 'Auto', icon: IconDeviceDesktop },
];
</script>

<template>
  <div class="position-static">
    <BaseDropdown align="end">
      <template #trigger="{ open, toggle }">
        <button
          class="btn btn-subtle px-2"
          :class="{ show: open }"
          id="bd-theme"
          type="button"
          :aria-expanded="open"
          @click.prevent="toggle"
        >
          <span class="theme-icon-active">
            <IconMoon v-if="theme === 'dark'" :size="18" />
            <IconDeviceDesktop v-if="theme === 'system'" :size="18" />
            <IconSun v-if="theme === 'light'" :size="18" />
          </span>
        </button>
      </template>

      <li v-for="t in themes" :key="t.key">
        <button class="dropdown-item" :class="{ active: theme === t.key }" @click="setTheme(t.key)">
          <component :is="t.icon" :size="18" class="me-2" />
          <span>{{ t.label }}</span>
        </button>
      </li>
    </BaseDropdown>
  </div>
</template>
