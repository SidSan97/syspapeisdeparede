<template>
  <div>
    <div class="mb-3">
      <label class="form-label">Nome</label>
      <input v-model="form.name" type="text" name="name" class="form-control"
        :class="{ 'is-invalid': form.errors.has('name') }" />
      <has-error :form="form" field="name"></has-error>
    </div>
    <div class="mb-3">
      <label class="form-label">E-mail</label>
      <input v-model="form.email" type="text" name="email" class="form-control"
        :class="{ 'is-invalid': form.errors.has('email') }" />
      <has-error :form="form" field="email"></has-error>
    </div>

    <div class="mb-3">
      <label class="form-label">Senha</label>
      <input v-model="form.password" type="password" name="password" class="form-control"
        :class="{ 'is-invalid': form.errors.has('password') }" autocomplete="false" />
      <has-error :form="form" field="password"></has-error>
    </div>

    <div class="mb-3">
      <label for="role" class="form-label">Nível de acesso</label>
      <select name="role" v-model="form.role" id="role" class="form-control"
        :class="{ 'is-invalid': form.errors.has('role') }">
        <option value="">Selecione um nível...</option>
        <option :value="role.name" v-for="role in filteredRoles" :key="role.id">
          {{ translateRolename(role.name) }}
        </option>
      </select>
      <has-error :form="form" field="role"></has-error>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore()

const props = defineProps({
  roles: {
    type: Array,
    required: false,
    default: () => [],
  },
})

const form = reactive(new Form({
  id: '',
  name: '',
  email: '',
  password: '',
  email_verified_at: '',
}))

const roleNames = {
  'super admin': 'Superadministrador',
  'admin': 'Administrador',
}

const translateRolename = (roleName) => {
  return roleNames[roleName] || roleName
}

const filteredRoles = computed(() => {
  return props.roles.filter(role => {
    if (!role) return false;

    if (role.name.toLowerCase() === 'super admin' && !auth.hasRole('super admin')) {
      return false
    }

    return true
  });
})

defineExpose({ form })
</script>
