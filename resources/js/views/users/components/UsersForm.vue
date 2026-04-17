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

      <div class="row">
        <div class="col-12 mb-3">
          <label for="role" class="form-label">Papel</label>
          <select
            name="role"
            v-model="form.role"
            id="role"
            class="form-control"
            :class="{ 'is-invalid': form.errors.has('role') }"
            @change="handleRoleChange"
            :disabled="loading"
          >
            <option value="">Selecione um papel...</option>
            <option :value="role.name" v-for="role in roles" :key="role.id">
              {{ translateRole(role.name) }}
            </option>
          </select>
          <has-error :form="form" field="role"></has-error>
        </div>
      </div>

      <!-- Saldo carteira digital e dropshipping (apenas para revendedor) -->
      <div v-if="form.role === 'reseller'" class="row">
        <div class="col-12 col-lg-6 mb-3">
          <label class="form-label" for="wallet_balance">Saldo da carteira digital</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <money
              id="wallet_balance"
              v-model.number="form.wallet_balance"
              v-bind="moneyConfig"
              name="wallet_balance"
              class="form-control"
              :disabled="loading"
            />
          </div>
          <has-error :form="form" field="wallet_balance"></has-error>
        </div>
        <div class="col-12 col-lg-6 mb-3 d-flex align-items-end">
          <div class="form-check mb-2">
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
import { translateRole } from '@/utils/roleTranslations'

const props = defineProps({
  roles: {
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
  role: '',
  is_dropshipping: 0,
  wallet_balance: 0,
  email_verified_at: '',
}))

const moneyConfig = {
  decimal: ',',
  thousands: '.',
  precision: 2,
  prefix: '',
  allowBlank: false,
  min: 0,
  max: null,
  disableNegative: true,
  minimumNumberOfCharacters: 0,
}

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

const handleRoleChange = () => {
  if (form.role !== 'reseller') {
    form.is_dropshipping = 0
    form.wallet_balance = 0
  }
}

// Watch para garantir que is_dropshipping seja 0 quando role não for reseller
watch(() => form.role, (newValue) => {
  if (newValue !== 'reseller') {
    form.is_dropshipping = 0
    form.wallet_balance = 0
  }
})

// Se roles não vier via props, buscar da API
const roles = ref(props.roles || [])

onMounted(async () => {
  if (roles.value.length === 0) {
    try {
      const { data } = await axios.get('v1/roles/list')
      const payload = data?.data ?? data ?? []
      roles.value = Array.isArray(payload) ? payload : []
    } catch (error) {
      console.error('Erro ao carregar papéis:', error)
      roles.value = []
    }
  }
})

defineExpose({ form })
</script>
