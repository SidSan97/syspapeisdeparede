<template>
  <div>
    <h3 class="h5 mt-4 mb-4">Senha</h3>

    <form @submit.prevent="updatePassword">
      <div class="row">
        <div class="col-lg-5">
          <BaseInput
            id="current_password"
            v-model.trim="formData.current_password"
            label="Senha atual"
            type="password"
            :class="{ 'is-invalid': errors.current_password }"
            form-group-class="mb-3"
            required
          >
            <template #bottom>
              <div v-if="errors.current_password" class="invalid-feedback d-block">
                {{ errors.current_password }}
              </div>
            </template>
          </BaseInput>

          <BaseInput
            id="password"
            v-model.trim="formData.password"
            label="Nova senha"
            type="password"
            :class="{ 'is-invalid': errors.password }"
            form-group-class="mb-3"
            required
          >
            <template #bottom>
              <div v-if="errors.password" class="invalid-feedback d-block">
                {{ errors.password }}
              </div>
              <div class="form-text">
                Certifique-se de que tenha pelo menos 15 caracteres OU pelo menos 8 caracteres
                incluindo um numero e uma letra minuscula.
              </div>
            </template>
          </BaseInput>

          <BaseInput
            id="password_confirmation"
            v-model.trim="formData.password_confirmation"
            label="Confirme a senha"
            type="password"
            :class="{ 'is-invalid': errors.password_confirmation }"
            form-group-class="mb-3"
            required
          >
            <template #bottom>
              <div v-if="errors.password_confirmation" class="invalid-feedback d-block">
                {{ errors.password_confirmation }}
              </div>
              <div class="form-text">Certifique-se de que corresponde a nova senha acima</div>
            </template>
          </BaseInput>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" :disabled="saving">
        <span
          v-if="saving"
          class="spinner-border spinner-border-sm"
          role="status"
          aria-hidden="true"
        ></span>
        <span v-if="!saving"> Alterar senha </span>
        <span v-else> Salvando... </span>
      </button>
    </form>
  </div>
</template>

<script setup>
import BaseInput from '@/components/common/BaseInput.vue';
import { useToast } from '@/composables/useToast';
import { http } from '@/lib/http';
import { reactive, ref } from 'vue';

const toast = useToast();
const saving = ref(false);

const formData = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const errors = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const clearErrors = () => {
  Object.keys(errors).forEach((key) => {
    errors[key] = '';
  });
};

const resetForm = () => {
  Object.keys(formData).forEach((key) => {
    formData[key] = '';
  });
};

const updatePassword = async () => {
  if (saving.value) return;

  saving.value = true;
  clearErrors();

  try {
    await http.put('v1/profile/password', {
      current_password: formData.current_password,
      password: formData.password,
      password_confirmation: formData.password_confirmation,
    });

    resetForm();
    toast.success('Senha atualizada com sucesso!');
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
        401: 'Voce não tem permissao!',
        422: 'Dados inválidos!',
        500: 'Erro interno. Tente novamente mais tarde!',
      }[error.response?.status] || 'Algo deu errado!';

    toast.error(message);
  } finally {
    saving.value = false;
  }
};
</script>
