<template>
  <section class="content">
    <Page
      title="Configurações"
      subtitle="Acesse as áreas administrativas e gerencie os recursos centrais da plataforma."
    >
      <div class="row g-3 g-md-4">
        <div v-for="item in menuItems" :key="item.label" class="col-12 col-sm-6 col-lg-4">
          <component
            :is="item.to ? RouterLink : 'div'"
            v-bind="item.to ? { to: item.to } : {}"
            class="card h-100 text-decoration-none border-0"
          >
            <div class="card-body d-flex align-items-start p-4">
              <div class="flex-shrink-0 me-3">
                <div
                  class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center settings-card-icon"
                >
                  <component
                    :size="24"
                    :is="item.icon"
                    class="text-primary settings-card-icon-icon"
                  />
                </div>
              </div>
              <div class="flex-grow-1">
                <h5 class="card-title mb-2 fs-6 fw-semibold">
                  {{ item.label }}
                </h5>
                <p class="card-text text-muted small mb-0">
                  {{ item.description }}
                </p>
              </div>
            </div>
          </component>
        </div>
      </div>
    </Page>
  </section>
</template>

<script setup>
import { computed, onMounted } from 'vue';
import { RouterLink } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import Page from '@/components/page/Page.vue';

// Icons
import { IconBuildingStore, IconLibraryPhoto, IconPhotoShare, IconUsers } from '@tabler/icons-vue';

const authStore = useAuthStore();

const availableItems = [
  {
    label: 'Usuários',
    description: 'Gerencie contas e permissões.',
    to: { name: 'settings.users.list' },
    icon: IconUsers,
  },
  {
    label: 'Modelos',
    description: 'Configure modelos e coleções utilizadas nos orçamentos.',
    to: { name: 'settings.models.list' },
    icon: IconPhotoShare,
  },
  {
    label: 'Coleções',
    description: 'Administre as coleções cadastradas para as artes.',
    to: { name: 'settings.collections' },
    icon: IconLibraryPhoto,
  },
  {
    label: 'Tiny ERP',
    description: 'Configure produtos associados à plataforma.',
    to: '/settings/tiny-erp',
    icon: IconBuildingStore,
  },
];

const menuItems = computed(() =>
  availableItems.filter((item) => !item.can || authStore.hasPermission(item.can)),
);

onMounted(() => {
  document.title = 'Configurações';
});
</script>

<style scoped>
.settings-card-icon {
  width: 56px;
  height: 56px;
}
</style>
