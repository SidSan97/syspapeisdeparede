<template>
  <div>
    <div class="row align-items-center mb-3">
      <div class="col-auto">
        <img
          class="avatar avatar-xl"
          :src="avatarSrc"
          @click="openAvatarModal"
          style="cursor: pointer"
        />
      </div>

      <div class="col-auto">
        <button class="btn btn-default" type="button" @click="openAvatarModal">Alterar foto</button>
      </div>
    </div>

    <form @submit.prevent="updateInfo">
      <input type="hidden" v-model="profileMeta.id" />

      <div class="row">
        <div class="col-lg-5">
          <BaseInput
            id="name"
            v-model.trim="formData.name"
            label="Nome"
            type="text"
            :class="{ 'is-invalid': errors.name }"
            form-group-class="mb-3"
            required
          >
            <template #bottom>
              <div v-if="errors.name" class="invalid-feedback d-block">
                {{ errors.name }}
              </div>
            </template>
          </BaseInput>

          <BaseInput
            id="email"
            v-model.trim="formData.email"
            label="E-mail"
            type="email"
            :class="{ 'is-invalid': errors.email }"
            form-group-class="mb-3"
            required
          >
            <template #bottom>
              <div v-if="errors.email" class="invalid-feedback d-block">
                {{ errors.email }}
              </div>
            </template>
          </BaseInput>
        </div>
      </div>

      <div>
        <button class="btn btn-primary" type="submit" :disabled="loading || saving">
          <span
            v-if="saving"
            class="spinner-border spinner-border-sm"
            role="status"
            aria-hidden="true"
          ></span>
          <span v-if="!saving">Salvar as alteracoes</span>
          <span v-else>Salvando...</span>
        </button>
      </div>
    </form>

    <ProfileAvatarModal ref="avatarModal" />
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import BaseInput from '@/components/common/BaseInput.vue';
import ProfileAvatarModal from './ProfileAvatarModal.vue';
import { useToast } from '@/composables/useToast';
import { http } from '@/lib/http';

const toast = useToast();
const loading = ref(false);
const saving = ref(false);
const avatarModal = ref(null);

const profileMeta = reactive({
  id: '',
  avatarUrl: '',
});

const formData = reactive({
  name: '',
  email: '',
});

const errors = reactive({
  name: '',
  email: '',
});

const avatarSrc = computed(() => profileMeta.avatarUrl);

const clearErrors = () => {
  Object.keys(errors).forEach((key) => {
    errors[key] = '';
  });
};

function openAvatarModal() {
  avatarModal.value?.open();
}

const loadProfile = async () => {
  loading.value = true;

  try {
    const { data } = await http.get('v1/profile');
    const profile = data.data || {};

    profileMeta.id = profile.id || '';
    profileMeta.avatarUrl = profile.avatar_url || '';
    formData.name = profile.name || '';
    formData.email = profile.email || '';
    clearErrors();
  } finally {
    loading.value = false;
  }
};

const updateInfo = async () => {
  if (saving.value) return;

  saving.value = true;
  clearErrors();

  try {
    await http.put('v1/profile', {
      id: profileMeta.id,
      name: formData.name,
      email: formData.email,
    });

    toast.success('Informacões atualizadas com sucesso!');
    await loadProfile();
  } catch (error) {
    if (error.response?.data?.errors) {
      const backendErrors = error.response.data.errors;
      Object.keys(backendErrors).forEach((field) => {
        if (Object.prototype.hasOwnProperty.call(errors, field)) {
          errors[field] = backendErrors[field][0];
        }
      });
    }

    const message =
      {
        400: 'Dados inválidos!',
        401: 'Voce nao tem permissão!',
        422: 'Dados inválidos!',
        500: 'Erro interno. Tente novamente mais tarde!',
      }[error.response?.status] || 'Algo deu errado!';

    toast.error(message);
  } finally {
    saving.value = false;
  }
};

onMounted(async () => {
  await loadProfile();
});
</script>
