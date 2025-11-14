<template>
  <section class="content">
    <div class="container py-4">
      <header class="mb-4 text-center text-md-start">
        <h1 class="h3 mb-2 text-primary fw-semibold">Configurações</h1>
        <p class="text-muted mb-0">
          Acesse as áreas administrativas e gerencie os recursos centrais da plataforma.
        </p>
      </header>

      <div class="settings-grid">
        <component
          v-for="item in menuItems"
          :key="item.label"
          :is="item.to ? RouterLink : 'button'"
          v-bind="item.to ? { to: item.to } : { type: 'button' }"
          class="settings-card"
        >
          <div class="settings-card__icon">
            <i :class="['fa-solid', `fa-${item.icon}`]"></i>
          </div>
          <div class="settings-card__content">
            <h2 class="settings-card__title">{{ item.label }}</h2>
            <p class="settings-card__description">{{ item.description }}</p>
          </div>
          <span v-if="item.to" class="settings-card__chevron">
            <i class="fa fa-chevron-right"></i>
          </span>
        </component>
      </div>
    </div>
  </section>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const availableItems = [
  {
    label: 'Usuários',
    description: 'Gerencie contas, permissões e acesso à plataforma.',
    to: null,
    icon: 'users',
  },
  {
    label: 'Modelos',
    description: 'Configure modelos e coleções utilizadas nos orçamentos.',
    to: '/modelos',
    icon: 'shapes',
  },
  {
    label: 'Coleções',
    description: 'Administre as coleções cadastradas para as artes.',
    to: '/settings/colecoes',
    icon: 'layer-group',
  },
  {
    label: 'Catálogo',
    description: 'Visualize e gerencie o catálogo de artes disponíveis.',
    to: '/colecao-arts/catalogo',
    icon: 'photo-film',
  },
];

const menuItems = computed(() =>
  availableItems.filter((item) => !item.can || auth.hasPermission(item.can)),
);

onMounted(() => {
  document.title = 'Configurações';
});
</script>

<style scoped>
.settings-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

@media (min-width: 992px) {
  .settings-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.settings-card {
  border: 1px solid var(--bs-border-color);
  background: var(--bs-body-bg);
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  padding: 1.75rem 1.5rem;
  border-radius: 1rem;
  text-decoration: none;
  box-shadow: 0 0.75rem 1.5rem rgba(15, 15, 15, 0.05);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  gap: 1.25rem;
  color: inherit;
  cursor: pointer;
}

.settings-card:hover {
  transform: translateY(-6px);
  border-color: rgba(13, 110, 253, 0.35);
  box-shadow: 0 1.25rem 2.5rem rgba(13, 110, 253, 0.15);
}

.settings-card__icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(13, 110, 253, 0.12);
  color: var(--bs-primary);
  font-size: 1.75rem;
}

.settings-card__content {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.settings-card__title {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--bs-body-color);
}

.settings-card__description {
  margin: 0;
  font-size: 0.9rem;
  color: var(--bs-secondary-color);
}

.settings-card__chevron {
  color: var(--bs-secondary-color);
  font-size: 1rem;
  transition: transform 0.2s ease, color 0.2s ease;
}

.settings-card:hover .settings-card__chevron {
  transform: translateX(4px);
  color: var(--bs-primary);
}

@media (max-width: 575.98px) {
  .settings-card {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .settings-card__chevron {
    display: none;
  }
}
</style>

