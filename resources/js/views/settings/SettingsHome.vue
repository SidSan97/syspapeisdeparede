<template>
  <section class="content">
    <Page title="Configurações" subtitle="Acesse as áreas administrativas e gerencie os recursos centrais da plataforma.">
        <div class="row g-3 g-md-4">
            <div
                v-for="item in menuItems"
                :key="item.label"
                class="col-12 col-sm-6 col-lg-4"
            >
                <component
                    :is="item.to ? RouterLink : 'div'"
                    v-bind="item.to ? { to: item.to } : {}"
                    class="card h-100 text-decoration-none"
                >
                    <div class="card-body d-flex align-items-start p-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center settings-card-icon">
                                <i :class="['fa-solid', `fa-${item.icon}`, 'text-primary', 'fs-5']"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-2 fw-semibold">{{ item.label }}</h5>
                            <p class="card-text text-muted small mb-0">{{ item.description }}</p>
                        </div>
                        <div class="flex-shrink-0 ms-2 align-self-center" v-if="item.to">
                            <i class="fa fa-chevron-right text-muted"></i>
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

const auth = useAuthStore();

const availableItems = [
  {
    label: 'Usuários',
    description: 'Gerencie contas, permissões e acesso à plataforma.',
    to: { name: 'UserList' },
    icon: 'users',
  },
  {
    label: 'Modelos',
    description: 'Configure modelos e coleções utilizadas nos orçamentos.',
    to: { name: 'ModelList' },
    icon: 'shapes',
  },
  {
    label: 'Coleções',
    description: 'Administre as coleções cadastradas para as artes.',
    to: '/settings/colecoes',
    icon: 'layer-group',
  },
  {
    label: 'Tiny ERP',
    description: 'Configure produtos associados à plataforma.',
    to: '/settings/tiny-erp',
    icon: 'store',
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
.settings-card-icon {
  width: 56px;
  height: 56px;
}

@media (max-width: 575.98px) {
  .fa-chevron-right {
    display: none;
  }
}
</style>

