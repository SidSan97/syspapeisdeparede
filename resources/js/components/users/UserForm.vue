<template>
  <div :aria-busy="loading ? 'true' : 'false'">
    <!-- Skeletons enquanto carrega -->
    <div v-if="loading" aria-hidden="true">
      <div class="h5 placeholder-glow mb-2">
        <span class="placeholder col-8"></span>
      </div>
      <div class="h5 placeholder-glow mb-2">
        <span class="placeholder col-8 placeholder-lg"></span>
      </div>
      <div class="h5 placeholder-glow mb-2">
        <span class="placeholder col-8 placeholder-lg"></span>
      </div>
    </div>

    <fieldset v-else :disabled="loading">
      <div class="row align-items-center mb-3">
        <div class="col-auto" v-if="form.id">
          <img
            class="avatar avatar-xl"
            :src="avatarUrl"
            style="cursor: pointer"
            @click="openAvatarModal"
          />
        </div>
        <div class="col-auto">
          <button
            v-if="form.id"
            class="btn btn-default"
            type="button"
            :disabled="loading"
            @click="openAvatarModal"
          >
            Alterar foto
          </button>
          <small v-else class="text-muted d-block">
            Salve o usuário para poder definir um avatar.
          </small>
          <small v-if="form.id && form.role === 'reseller'" class="text-muted d-block mt-1">
            Esse será o logotipo usado na impressão dos orçamentos.
          </small>
        </div>
      </div>

      <BaseInput
        v-model="form.name"
        label="Nome"
        name="name"
        type="text"
        required
        :disabled="loading"
        form-group-class="mb-3"
        :class="{ 'is-invalid': form.errors.has('name') }"
      >
        <template #bottom>
          <has-error :form="form" field="name"></has-error>
        </template>
      </BaseInput>

      <BaseInput
        v-model="form.email"
        label="E-mail"
        name="email"
        type="email"
        required
        :disabled="loading"
        form-group-class="mb-3"
        :class="{ 'is-invalid': form.errors.has('email') }"
      >
        <template #bottom>
          <has-error :form="form" field="email"></has-error>
        </template>
      </BaseInput>

      <div v-if="!form.id" class="row">
        <BaseInput
          v-model="form.password"
          label="Senha"
          name="password"
          type="password"
          required
          autocomplete="new-password"
          :disabled="loading"
          form-group-class="mb-3"
          :class="{ 'is-invalid': form.errors.has('password') }"
        >
          <template #bottom>
            <has-error :form="form" field="password"></has-error>
          </template>
        </BaseInput>

        <BaseInput
          v-model="form.password_confirmation"
          label="Confirme a senha"
          name="password_confirmation"
          type="password"
          required
          autocomplete="new-password"
          :disabled="loading"
          form-group-class="mb-3"
          :class="{
            'is-invalid': form.errors.has('password_confirmation'),
          }"
        >
          <template #bottom>
            <has-error :form="form" field="password_confirmation"></has-error>
          </template>
        </BaseInput>
      </div>

      <div class="form-group mb-3">
        <BaseInputLabel for="role">Papel</BaseInputLabel>
        <RoleSelect
          id="role"
          v-model="form.role"
          :class="{ 'is-invalid': form.errors.has('role') }"
          :disabled="loading"
          placeholder="Selecione um papel..."
        />
        <has-error :form="form" field="role"></has-error>
      </div>

      <div v-if="form.role === 'reseller'" class="form-group mb-3">
        <BaseInputLabel for="reseller_id">Revendedor vinculado</BaseInputLabel>
        <ResellerSelect
          id="reseller_id"
          v-model="form.reseller_id"
          :selected-reseller="selectedReseller"
          :invalid="form.errors.has('reseller_id')"
          :disabled="loading"
        />
        <has-error :form="form" field="reseller_id"></has-error>
      </div>

      <div v-if="form.role === 'reseller'" class="row">
        <div class="col-12 mb-3">
          <div class="form-check">
            <input
              class="form-check-input"
              type="checkbox"
              v-model="form.is_dropshipping"
              id="is_dropshipping"
              name="is_dropshipping"
              :disabled="loading"
            />
            <label class="form-check-label" for="is_dropshipping"> Habilitar dropshipping? </label>
          </div>
        </div>
      </div>

      <div class="form-group mb-3">
        <BaseInputLabel for="wallet_balance">Crédito inicial</BaseInputLabel>
        <money
          id="wallet_balance"
          v-model.number="form.wallet_balance"
          v-bind="moneyConfig"
          class="form-control"
          :disabled="loading"
        />
        <has-error :form="form" field="wallet_balance"></has-error>
      </div>
    </fieldset>

    <UserAvatarModal
      v-if="form.id"
      ref="avatarModal"
      :user-id="form.id"
      @uploaded="onAvatarUploaded"
    />
  </div>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import BaseInput from '@/components/common/BaseInput.vue';
import BaseInputLabel from '@/components/common/BaseInputLabel.vue';
import RoleSelect from '@/components/users/RoleSelect.vue';
import ResellerSelect from '@/components/users/ResellerSelect.vue';
import UserAvatarModal from '@/components/users/UserAvatarModal.vue';

const moneyConfig = {
  decimal: ',',
  thousands: '.',
  precision: 2,
  prefix: '',
  allowBlank: false,
  min: 0,
  max: null,
  disableNegative: true,
};

const props = defineProps({
  loading: {
    type: Boolean,
    default: false,
  },
});

const form = reactive(
  new Form({
    id: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: '',
    is_dropshipping: false,
    wallet_balance: '',
    reseller_id: null,
  }),
);

// Objeto completo do revendedor vinculado, usado apenas para exibir o rótulo
// inicial no ResellerSelect (não é enviado no payload do formulário).
const selectedReseller = ref(null);

// URL do avatar atual, usada apenas para exibição (não é enviada no payload do formulário).
const avatarUrl = ref('');
const avatarModal = ref(null);

function openAvatarModal() {
  avatarModal.value?.open();
}

function onAvatarUploaded(url) {
  avatarUrl.value = url;
}

// Garantir que is_dropshipping e reseller_id sejam limpos quando role não for reseller.
watch(
  () => form.role,
  (newValue) => {
    if (newValue !== 'reseller') {
      form.is_dropshipping = false;
      form.reseller_id = null;
      selectedReseller.value = null;
    }
  },
);

defineExpose({ form, selectedReseller, avatarUrl });
</script>
