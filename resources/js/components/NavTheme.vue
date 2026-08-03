<script setup>
import { IconPercentage50, IconDeviceDesktop, IconMoon, IconSun } from '@tabler/icons-vue';

import light from '@assets/img/themes/light.svg';
import dark from '@assets/img/themes/dark.svg';
import system from '@assets/img/themes/system.svg';

import { useTheme } from '@/composables/useTheme';
import BaseDropdown from '@/components/common/BaseDropdown.vue';
import { IconChevronRight } from '@tabler/icons-vue';

const { setTheme, theme } = useTheme();

const themes = [
  { key: 'light', label: 'Claro', icon: IconSun, preview: light },
  { key: 'dark', label: 'Escuro', icon: IconMoon, preview: dark },
  { key: 'system', label: 'Corresponder ao sistema', icon: IconDeviceDesktop, preview: system },
];
</script>

<template>
  <BaseDropdown align="start" direction="start">
    <template #trigger="{ open, toggle }">
      <button
        class="dropdown-item"
        :class="{ show: open }"
        id="bd-theme"
        type="button"
        :aria-expanded="open"
        @click.prevent="toggle"
      >
        <IconPercentage50 :size="18" class="me-2" />
        Tema
      </button>
    </template>

    <template #default="{ close }">
      <li v-for="t in themes" :key="t.key">
        <button
          class="dropdown-item"
          :class="{ active: theme === t.key }"
          @click="
            setTheme(t.key);
            close();
          "
        >
          <input
            class="form-check-input align-middle my-0 me-2"
            type="radio"
            name="theme"
            :value="t.key"
            :checked="t.key === theme"
          />
          <img :src="t.preview" class="me-2" />
          <span>{{ t.label }}</span>
        </button>
      </li>
    </template>
  </BaseDropdown>
</template>

<style scoped>
.dropdown,
.dropdown :deep(> div) {
  width: 100%;
}
</style>
