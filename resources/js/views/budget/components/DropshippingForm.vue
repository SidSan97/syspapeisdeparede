<template>
  <div v-if="enabled" class="card mt-3 mb-3">
    <div class="card-body">
      <h5 class="card-title mb-4">Dados de Dropshipping</h5>

      <div class="row">
        <!-- Nome -->
        <div class="col-12 mb-3">
          <label for="dropshipping-name" class="form-label">Nome <span class="text-danger">*</span></label>
          <input
            v-model="formData.name"
            type="text"
            id="dropshipping-name"
            class="form-control"
            placeholder="Nome completo ou Razão Social"
            maxlength="255"
            :class="{ 'is-invalid': errors.name }"
          />
          <div v-if="errors.name" class="invalid-feedback d-block">{{ errors.name }}</div>
        </div>

        <!-- Tipo de Pessoa -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-person-type" class="form-label">Tipo de Pessoa <span class="text-danger">*</span></label>
          <select
            v-model="formData.person_type"
            id="dropshipping-person-type"
            class="form-control"
            :class="{ 'is-invalid': errors.person_type }"
          >
            <option value="">Selecione...</option>
            <option value="PF">Pessoa Física</option>
            <option value="PJ">Pessoa Jurídica</option>
          </select>
          <div v-if="errors.person_type" class="invalid-feedback d-block">{{ errors.person_type }}</div>
        </div>

        <!-- CPF/CNPJ -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-cpf-cnpj" class="form-label">CPF/CNPJ <span class="text-danger">*</span></label>
          <input
            v-model="formData.cpf_cnpj"
            type="text"
            id="dropshipping-cpf-cnpj"
            class="form-control"
            placeholder="000.000.000-00 ou 00.000.000/0000-00"
            maxlength="18"
            @input="formatCPFCNPJ"
            :class="{
              'is-invalid': errors.cpf_cnpj && (cpfCnpjValidationStatus === 'invalid'),
              'is-valid': cpfCnpjValidationStatus === 'valid'
            }"
          />
          <div v-if="errors.cpf_cnpj && cpfCnpjValidationStatus === 'invalid'" class="invalid-feedback d-block">{{ errors.cpf_cnpj }}</div>
          <small v-if="cpfCnpjValidationStatus === 'valid'" class="text-success d-block mt-1">
            <i class="fa fa-check-circle"></i> Documento válido
          </small>
          <small v-else-if="cpfCnpjValidationStatus === 'invalid' && formData.cpf_cnpj.length > 0" class="text-danger d-block mt-1">
            <i class="fa fa-times-circle"></i> Documento inválido
          </small>
        </div>

        <!-- Inscrição Estadual (apenas para PJ) -->
        <div v-if="formData.person_type === 'PJ'" class="col-12 col-md-6 mb-3">
          <label for="dropshipping-ie" class="form-label">Inscrição Estadual <span class="text-danger">*</span></label>
          <input
            v-model="formData.IE"
            type="text"
            id="dropshipping-ie"
            class="form-control"
            placeholder="IE"
            maxlength="18"
            :class="{ 'is-invalid': errors.IE }"
          />
          <div v-if="errors.IE" class="invalid-feedback d-block">{{ errors.IE }}</div>
        </div>

        <!-- Email -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-email" class="form-label">E-mail <span class="text-danger">*</span></label>
          <input
            v-model="formData.email"
            type="email"
            id="dropshipping-email"
            class="form-control"
            placeholder="email@exemplo.com"
            maxlength="255"
            :class="{ 'is-invalid': errors.email }"
          />
          <div v-if="errors.email" class="invalid-feedback d-block">{{ errors.email }}</div>
        </div>

        <!-- Telefone -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-phone" class="form-label">Telefone <span class="text-danger">*</span></label>
          <input
            v-model="formData.phone"
            type="text"
            id="dropshipping-phone"
            class="form-control"
            placeholder="(00) 00000-0000"
            maxlength="15"
            :class="{ 'is-invalid': errors.phone }"
          />
          <div v-if="errors.phone" class="invalid-feedback d-block">{{ errors.phone }}</div>
        </div>

        <!-- CEP -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-cep" class="form-label">CEP <span class="text-danger">*</span></label>
          <input
            v-model="formData.cep"
            type="text"
            id="dropshipping-cep"
            class="form-control"
            placeholder="00000-000"
            maxlength="9"
            @input="formatCEP"
            @blur="fetchAddress"
            :class="{ 'is-invalid': errors.cep }"
          />
          <div v-if="errors.cep" class="invalid-feedback d-block">{{ errors.cep }}</div>
        </div>

        <!-- UF -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-uf" class="form-label">UF <span class="text-danger">*</span></label>
          <input
            v-model="formData.uf"
            type="text"
            id="dropshipping-uf"
            class="form-control"
            placeholder="UF"
            maxlength="2"
            readonly
            :class="{ 'is-invalid': errors.uf }"
          />
          <div v-if="errors.uf" class="invalid-feedback d-block">{{ errors.uf }}</div>
        </div>

        <!-- Estado (nome completo) -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-state" class="form-label">Estado (Nome) <span class="text-danger">*</span></label>
          <input
            v-model="formData.state"
            type="text"
            id="dropshipping-state"
            class="form-control"
            placeholder="Nome do Estado"
            maxlength="30"
            readonly
            :class="{ 'is-invalid': errors.state }"
          />
          <div v-if="errors.state" class="invalid-feedback d-block">{{ errors.state }}</div>
        </div>

        <!-- Cidade -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-city" class="form-label">Cidade <span class="text-danger">*</span></label>
          <input
            v-model="formData.city"
            type="text"
            id="dropshipping-city"
            class="form-control"
            placeholder="Cidade"
            maxlength="255"
            readonly
            :class="{ 'is-invalid': errors.city }"
          />
          <div v-if="errors.city" class="invalid-feedback d-block">{{ errors.city }}</div>
        </div>

        <!-- Bairro -->
        <div class="col-12 col-md-6 mb-3">
          <label for="dropshipping-neighborhood" class="form-label">Bairro <span class="text-danger">*</span></label>
          <input
            v-model="formData.neighborhood"
            type="text"
            id="dropshipping-neighborhood"
            class="form-control"
            placeholder="Bairro"
            maxlength="255"
            readonly
            :class="{ 'is-invalid': errors.neighborhood }"
          />
          <div v-if="errors.neighborhood" class="invalid-feedback d-block">{{ errors.neighborhood }}</div>
        </div>

        <!-- Logradouro -->
        <div class="col-12 mb-3">
          <label for="dropshipping-public-space" class="form-label">Logradouro</label>
          <input
            v-model="formData.public_space"
            type="text"
            id="dropshipping-public-space"
            class="form-control"
            placeholder="Logradouro"
            maxlength="255"
            readonly
          />
        </div>

        <!-- Complemento -->
        <div class="col-12 mb-3">
          <label for="dropshipping-complement" class="form-label">Complemento</label>
          <input
            v-model="formData.complement"
            type="text"
            id="dropshipping-complement"
            class="form-control"
            placeholder="Complemento"
            maxlength="255"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'

const props = defineProps({
  enabled: {
    type: Boolean,
    default: false
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits(['update:modelValue', 'validate'])

const formData = reactive({
  name: '',
  person_type: '',
  cpf_cnpj: '',
  IE: '',
  email: '',
  phone: '',
  cep: '',
  uf: '',
  state: '',
  city: '',
  neighborhood: '',
  public_space: '',
  complement: ''
})

const errors = reactive({})

// Status de validação do CPF/CNPJ em tempo real
const cpfCnpjValidationStatus = ref('') // '', 'valid', 'invalid'

// Observar mudanças no formData e emitir para o pai
watch(() => formData, (newValue) => {
  emit('update:modelValue', { ...newValue })
}, { deep: true })

// Observar mudanças no modelValue do pai
watch(() => props.modelValue, (newValue) => {
  if (newValue) {
    Object.assign(formData, {
      name: newValue.name || '',
      person_type: newValue.person_type || '',
      cpf_cnpj: newValue.cpf_cnpj || '',
      IE: newValue.IE || '',
      email: newValue.email || '',
      phone: newValue.phone || '',
      cep: newValue.cep || '',
      uf: newValue.uf || '',
      state: newValue.state || '',
      city: newValue.city || '',
      neighborhood: newValue.neighborhood || '',
      public_space: newValue.public_space || '',
      complement: newValue.complement || ''
    })
  }
}, { immediate: true, deep: true })

// Observar person_type para limpar IE quando mudar de PJ para PF
watch(() => formData.person_type, (newValue, oldValue) => {
  if (oldValue === 'PJ' && newValue !== 'PJ') {
    formData.IE = ''
    if (errors.IE) {
      delete errors.IE
    }
  }
})

// Observar enabled para limpar dados quando desabilitar
watch(() => props.enabled, (newValue) => {
  if (!newValue) {
    Object.keys(formData).forEach(key => {
      formData[key] = ''
    })
    Object.keys(errors).forEach(key => {
      delete errors[key]
    })
    cpfCnpjValidationStatus.value = ''
  }
})

function formatCEP(event) {
  let value = event.target.value.replace(/\D/g, '')
  if (value.length > 5) {
    value = value.substring(0, 5) + '-' + value.substring(5, 8)
  }
  formData.cep = value
}

function formatCPFCNPJ(event) {
  let value = event.target.value.replace(/\D/g, '')
  let formatted = ''

  // Resetar status se o campo estiver vazio
  if (value.length === 0) {
    cpfCnpjValidationStatus.value = ''
    if (errors.cpf_cnpj) {
      delete errors.cpf_cnpj
    }
    formData.cpf_cnpj = ''
    return
  }

  // Se length <= 11, tratar como CPF, senão como CNPJ
  if (value.length <= 11) {
    // Limitar a 11 dígitos para CPF
    value = value.substring(0, 11)

    // Formatar como CPF: 000.000.000-00
    if (value.length > 0) {
      formatted = value
      if (value.length > 3) {
        formatted = value.substring(0, 3) + '.' + value.substring(3)
      }
      if (value.length > 6) {
        formatted = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6)
      }
      if (value.length > 9) {
        formatted = value.substring(0, 3) + '.' + value.substring(3, 6) + '.' + value.substring(6, 9) + '-' + value.substring(9, 11)
      }
    }

    // Validar CPF apenas quando tiver 11 dígitos completos
    if (value.length === 11) {
      if (validateCPF(value)) {
        cpfCnpjValidationStatus.value = 'valid'
        if (errors.cpf_cnpj) {
          delete errors.cpf_cnpj
        }
      } else {
        cpfCnpjValidationStatus.value = 'invalid'
        errors.cpf_cnpj = 'CPF inválido'
      }
    } else {
      // Ainda digitando, não validar ainda
      cpfCnpjValidationStatus.value = ''
      if (errors.cpf_cnpj === 'CPF inválido') {
        delete errors.cpf_cnpj
      }
    }
  } else {
    // Limitar a 14 dígitos para CNPJ
    value = value.substring(0, 14)

    // Formatar como CNPJ: 00.000.000/0000-00
    if (value.length > 0) {
      if (value.length <= 2) {
        formatted = value
      } else if (value.length <= 5) {
        formatted = value.substring(0, 2) + '.' + value.substring(2)
      } else if (value.length <= 8) {
        formatted = value.substring(0, 2) + '.' + value.substring(2, 5) + '.' + value.substring(5)
      } else if (value.length <= 12) {
        formatted = value.substring(0, 2) + '.' + value.substring(2, 5) + '.' + value.substring(5, 8) + '/' + value.substring(8)
      } else {
        formatted = value.substring(0, 2) + '.' + value.substring(2, 5) + '.' + value.substring(5, 8) + '/' + value.substring(8, 12) + '-' + value.substring(12, 14)
      }
    }

    // Validar CNPJ apenas quando tiver 14 dígitos completos
    if (value.length === 14) {
      if (validateCNPJ(value)) {
        cpfCnpjValidationStatus.value = 'valid'
        if (errors.cpf_cnpj) {
          delete errors.cpf_cnpj
        }
      } else {
        cpfCnpjValidationStatus.value = 'invalid'
        errors.cpf_cnpj = 'CNPJ inválido'
      }
    } else {
      // Ainda digitando, não validar ainda
      cpfCnpjValidationStatus.value = ''
      if (errors.cpf_cnpj === 'CNPJ inválido') {
        delete errors.cpf_cnpj
      }
    }
  }

  formData.cpf_cnpj = formatted
}

function validateCPF(cpf) {
  cpf = cpf.replace(/\D/g, '')

  if (cpf.length !== 11) return false
  if (/^(\d)\1+$/.test(cpf)) return false // Todos os dígitos iguais

  let sum = 0
  let remainder

  // Validar primeiro dígito verificador
  for (let i = 1; i <= 9; i++) {
    sum += parseInt(cpf.substring(i - 1, i)) * (11 - i)
  }
  remainder = (sum * 10) % 11
  if (remainder === 10 || remainder === 11) remainder = 0
  if (remainder !== parseInt(cpf.substring(9, 10))) return false

  // Validar segundo dígito verificador
  sum = 0
  for (let i = 1; i <= 10; i++) {
    sum += parseInt(cpf.substring(i - 1, i)) * (12 - i)
  }
  remainder = (sum * 10) % 11
  if (remainder === 10 || remainder === 11) remainder = 0
  if (remainder !== parseInt(cpf.substring(10, 11))) return false

  return true
}

function validateCNPJ(cnpj) {
  cnpj = cnpj.replace(/\D/g, '')

  if (cnpj.length !== 14) return false
  if (/^(\d)\1+$/.test(cnpj)) return false // Todos os dígitos iguais

  let length = cnpj.length - 2
  let numbers = cnpj.substring(0, length)
  const digits = cnpj.substring(length)
  let sum = 0
  let pos = length - 7

  // Validar primeiro dígito verificador
  for (let i = length; i >= 1; i--) {
    sum += numbers.charAt(length - i) * pos--
    if (pos < 2) pos = 9
  }

  let result = sum % 11 < 2 ? 0 : 11 - (sum % 11)
  if (result !== parseInt(digits.charAt(0))) return false

  // Validar segundo dígito verificador
  length = length + 1
  numbers = cnpj.substring(0, length)
  sum = 0
  pos = length - 7

  for (let i = length; i >= 1; i--) {
    sum += numbers.charAt(length - i) * pos--
    if (pos < 2) pos = 9
  }

  result = sum % 11 < 2 ? 0 : 11 - (sum % 11)
  if (result !== parseInt(digits.charAt(1))) return false

  return true
}

async function fetchAddress() {
  const cep = formData.cep.replace(/\D/g, '')

  if (cep.length !== 8) {
    return
  }

  try {
    const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`)
    const data = await response.json()

    if (!data.erro && data.cep) {
      formData.uf = data.uf || ''
      formData.state = data.estado || ''
      formData.city = data.localidade || ''
      formData.neighborhood = data.bairro || ''
      formData.public_space = data.logradouro || ''
    } else {
      formData.uf = ''
      formData.state = ''
      formData.city = ''
      formData.neighborhood = ''
      formData.public_space = ''
    }
  } catch (error) {
    console.error('Erro ao buscar endereço:', error)
    formData.uf = ''
    formData.state = ''
    formData.city = ''
    formData.neighborhood = ''
    formData.public_space = ''
  }
}

function validate() {
  if (!props.enabled) {
    return true
  }

  let isValid = true
  const newErrors = {}

  // Validar campos obrigatórios
  if (!formData.name?.trim()) {
    newErrors.name = 'O campo nome é obrigatório'
    isValid = false
  }

  if (!formData.person_type) {
    newErrors.person_type = 'Selecione o tipo de pessoa'
    isValid = false
  }

  if (!formData.cpf_cnpj?.trim()) {
    newErrors.cpf_cnpj = 'O campo CPF/CNPJ é obrigatório'
    isValid = false
  } else {
    // Validar CPF/CNPJ
    const cleanValue = formData.cpf_cnpj.replace(/\D/g, '')
    if (cleanValue.length <= 11) {
      if (cleanValue.length !== 11 || !validateCPF(cleanValue)) {
        newErrors.cpf_cnpj = 'CPF inválido'
        isValid = false
      }
    } else {
      if (cleanValue.length !== 14 || !validateCNPJ(cleanValue)) {
        newErrors.cpf_cnpj = 'CNPJ inválido'
        isValid = false
      }
    }
  }

  // Inscrição Estadual é obrigatória apenas para PJ
  if (formData.person_type === 'PJ' && !formData.IE?.trim()) {
    newErrors.IE = 'O campo Inscrição Estadual é obrigatório'
    isValid = false
  }

  if (!formData.email?.trim()) {
    newErrors.email = 'O campo e-mail é obrigatório'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
    newErrors.email = 'E-mail inválido'
    isValid = false
  }

  if (!formData.phone?.trim()) {
    newErrors.phone = 'O campo telefone é obrigatório'
    isValid = false
  }

  if (!formData.cep?.trim()) {
    newErrors.cep = 'O campo CEP é obrigatório'
    isValid = false
  }

  if (!formData.uf?.trim()) {
    newErrors.uf = 'O campo estado (UF) é obrigatório'
    isValid = false
  }

  if (!formData.state?.trim()) {
    newErrors.state = 'O campo estado é obrigatório'
    isValid = false
  }

  if (!formData.city?.trim()) {
    newErrors.city = 'O campo cidade é obrigatório'
    isValid = false
  }

  if (!formData.neighborhood?.trim()) {
    newErrors.neighborhood = 'O campo bairro é obrigatório'
    isValid = false
  }

  Object.keys(errors).forEach(key => delete errors[key])
  Object.assign(errors, newErrors)

  emit('validate', isValid)
  return isValid
}

defineExpose({
  validate,
  getData: () => ({ ...formData })
})
</script>

<style scoped>
.form-control[readonly] {
  background-color: #304f6f30;
  cursor: not-allowed;
}
</style>

