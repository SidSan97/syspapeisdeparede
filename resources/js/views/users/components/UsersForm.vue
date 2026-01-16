<template>
  <div :aria-busy="loading ? 'true' : 'false'">
    <!-- Skeletons enquanto carrega -->
    <div v-if="loading" aria-hidden="true">
      <div class="row">
        <div class="col-12 col-lg-6 mb-3">
          <div class="placeholder-glow mb-2">
            <span class="placeholder col-4"></span>
          </div>
          <div class="placeholder-glow">
            <span class="placeholder col-12 placeholder-lg"></span>
          </div>
        </div>
        <div class="col-12 col-lg-6 mb-3">
          <div class="placeholder-glow mb-2">
            <span class="placeholder col-3"></span>
          </div>
          <div class="placeholder-glow">
            <span class="placeholder col-12 placeholder-lg"></span>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12 mb-3">
          <div class="placeholder-glow mb-2">
            <span class="placeholder col-5"></span>
          </div>
          <div class="placeholder-glow">
            <span class="placeholder col-8 placeholder-lg"></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Formulário real quando não está carregando -->
    <fieldset v-else :disabled="loading">
      <!-- Primeira linha: Nome e Email -->
      <div class="row">
        <div class="col-12 col-lg-6 mb-3">
          <label class="form-label">Nome</label>
          <input
            v-model="form.name"
            type="text"
            required
            name="name"
            class="form-control"
            :disabled="loading"
          />
          <has-error :form="form" field="name"></has-error>
        </div>
        <div class="col-12 col-lg-6 mb-3">
          <label class="form-label">E-mail</label>
          <input
            v-model="form.email"
            type="email"
            name="email"
            required
            class="form-control"
            :disabled="loading"
          />
          <has-error :form="form" field="email"></has-error>
        </div>
      </div>

      <!-- Segunda linha: Senha e Confirmação de Senha (apenas na criação) -->
      <div v-if="!isEdit" class="row">
        <div class="col-12 col-lg-6 mb-3">
          <label class="form-label">Senha</label>
          <input
            v-model="form.password"
            type="password"
            name="password"
            class="form-control"
            required
            autocomplete="new-password"
            @input="validatePasswordMatch"
            :disabled="loading"
          />
          <has-error :form="form" field="password"></has-error>
        </div>
        <div class="col-12 col-lg-6 mb-3">
          <label class="form-label">Confirmação de Senha</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            name="password_confirmation"
            class="form-control"
            required
            :class="{ 'is-invalid': form.errors.has('password_confirmation') || passwordMismatch }"
            autocomplete="new-password"
            @input="validatePasswordMatch"
            :disabled="loading"
          />
          <has-error :form="form" field="password_confirmation"></has-error>
          <div v-if="passwordMismatch" class="invalid-feedback d-block">
            As senhas não coincidem.
          </div>
        </div>
      </div>

      <!-- Terceira linha: Tipo de Usuário -->
      <div class="row">
        <div class="col-12 mb-3">
          <label for="user_type_id" class="form-label">Tipo de Usuário</label>
          <select
            name="user_type_id"
            v-model="form.user_type_id"
            id="user_type_id"
            class="form-control"
            :class="{ 'is-invalid': form.errors.has('user_type_id') }"
            @change="handleUserTypeChange"
            :disabled="loading"
          >
            <option value="">Selecione um tipo...</option>
            <option :value="typeUser.id" v-for="typeUser in typeUsers" :key="typeUser.id">
              {{ typeUser.name }}
            </option>
          </select>
          <has-error :form="form" field="user_type_id"></has-error>
        </div>
      </div>

      <!-- Checkbox de Dropshipping (apenas para designer) -->
      <div v-if="Number(form.user_type_id) === USER_TYPES.RESELLER" class="row">
        <div class="col-12 mb-3">
          <div class="form-check">
            <input
              class="form-check-input"
              type="checkbox"
              :checked="isDropshippingChecked"
              @change="handleDropshippingChange"
              id="is_dropshipping"
              name="is_dropshipping"
              :disabled="loading"
            />
            <label class="form-check-label" for="is_dropshipping">
              Habilitar dropshipping?
            </label>
          </div>
        </div>
      </div>
    </fieldset>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch, computed } from 'vue'
import axios from 'axios'
import { USER_TYPES } from '@/constants/userTypes'

const props = defineProps({
  typeUsers: {
    type: Array,
    required: false,
    default: () => [],
  },
  isEdit: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const form = reactive(new Form({
  id: '',
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  user_type_id: '',
  is_dropshipping: 0,
  email_verified_at: '',
}))

const passwordMismatch = ref(false)

// Computed para verificar se o checkbox deve estar marcado
const isDropshippingChecked = computed(() => {
  const value = form.is_dropshipping
  // Aceita 1, "1", true, ou qualquer valor truthy como marcado
  return Number(value) === 1 || value === true
})

// Handler para mudança do checkbox
const handleDropshippingChange = (event) => {
  form.is_dropshipping = event.target.checked ? 1 : 0
}

const validatePasswordMatch = () => {
  if (form.password_confirmation) {
    passwordMismatch.value = form.password !== form.password_confirmation
  } else {
    passwordMismatch.value = false
  }
}

const handleUserTypeChange = () => {
  // Se o tipo de usuário não for designer, resetar is_dropshipping para 0
  if (Number(form.user_type_id) !== USER_TYPES.RESELLER) {
    form.is_dropshipping = 0
  }
}

// Watch para garantir que is_dropshipping seja 0 quando user_type_id não for designer
watch(() => form.user_type_id, (newValue) => {
  if (Number(newValue) !== USER_TYPES.RESELLER) {
    form.is_dropshipping = 0
  }
})

// Se typeUsers não vier via props, buscar da API
const typeUsers = ref(props.typeUsers || [])

onMounted(async () => {
  if (typeUsers.value.length === 0) {
    try {
      const { data } = await axios.get('v1/type-users/list')
      const payload = data?.data ?? data ?? []
      typeUsers.value = Array.isArray(payload) ? payload : []
    } catch (error) {
      console.error('Erro ao carregar tipos de usuários:', error)
      typeUsers.value = []
    }
  }
})

defineExpose({ form })
</script>
