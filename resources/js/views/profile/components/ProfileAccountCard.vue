<template>
  <div class="card bd-card border-0 mb-4">
    <div class="card-body">
      <h5 class="card-title">Conta</h5>
      <p class="card-text">Edite suas informações.</p>

      <div class="position-relative d-inline-block avatar-box" data-bs-toggle="modal" data-bs-target="#avatar-modal">
        <img class="avatar avatar-xl"
          :src="form.avatar ? $asset('storage/' + form.avatar) : $asset('assets/img/avatar.svg')" />

        <div
          class="position-absolute bottom-0 top-0 start-0 end-0 bg-dark rounded-circle text-white d-flex justify-content-center align-items-center avatar-mask">
          <i class="fas fa-camera fa-lg"></i>
        </div>
      </div>

      <form @submit.prevent="updateInfo()">
        <input type="hidden" v-model="form.position_id">

        <div class="row">
          <div class="col-lg-5">
            <div class="mb-3">
              <label for="name" class="form-label">Nome</label>
              <div class="col-sm-12">
                <input v-model="form.name" class="form-control" id="name" type="text"
                  :class="{ 'is-invalid': form.errors.has('name') }" required />
                <has-error :form="form" field="name"></has-error>
              </div>
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">E-mail</label>
              <div class="col-sm-12">
                <input v-model="form.email" class="form-control" id="email" type="email"
                  :class="{ 'is-invalid': form.errors.has('email') }" required />
                <has-error :form="form" field="email"></has-error>
              </div>
            </div>

            <template v-if="companyId">
              <div class="mb-3">
                <label for="position_requirement_id" class="form-label">Centro de custo</label>
                <select v-model="form.position_requirement_id" class="form-control" id="position_requirement_id"
                  name="position_requirement_id" @change="loadPositions()"
                  :class="{ 'is-invalid': form.errors.has('position_requirement_id') }">
                  <option value="">Selecione&hellip;</option>
                  <option :value="r.id" v-for="(r, idx) in positionRequirements" :key="idx">
                    {{ r.cost_center }} - {{ r.area }}
                  </option>
                </select>
                <has-error :form="form" field="position_requirement_id"></has-error>
              </div>

              <div class="mb-3">
                <label for="position_requirement_item_id" class="form-label">Posição</label>
                <select v-model="form.position_requirement_item_id" class="form-control"
                  id="position_requirement_item_id" name="position_requirement_item_id"
                  :class="{ 'is-invalid': form.errors.has('position_requirement_item_id') }">
                  <option :value="p.id" v-for="(p, idx) in positions" :key="idx">
                    {{ p.description }}
                  </option>
                </select>
                <has-error :form="form" field="position_requirement_item_id"></has-error>
              </div>

              <div class="mb-3">
                <label for="directorate_id" class="form-label">Diretoria</label>
                <select v-model="form.directorate_id" class="form-control" id="directorate_id" name="directorate_id"
                  :class="{ 'is-invalid': form.errors.has('directorate_id') }">
                  <option :value="u.id" v-for="(u, idx) in directorates" :key="idx">
                    {{ u.name }}
                  </option>
                </select>
                <has-error :form="form" field="directorate_id"></has-error>
              </div>

              <div class="mb-3">
                <label for="leader_id" class="form-label">Líder</label>
                <select v-model="form.leader_id" name="leader_id" id="leader_id"
                  :class="{ 'is-invalid': form.errors.has('leader_id') }" class="form-control">
                  <option :value="u.id" v-for="(u, idx) in leaders" :key="idx">
                    {{ u.name }} ({{ u.email }})
                  </option>
                </select>
                <has-error :form="form" field="leader_id"></has-error>
              </div>
            </template>

          </div>
        </div>
        <div>
          <button class="btn btn-primary" type="submit" :disabled="loading || saving">
            <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
            <span v-if="!saving"> Salvar as alterações </span>
            <span v-else> Salvando... </span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

const loading = ref(false)
const saving = ref(false)
const positionRequirements = ref([])
const positions = ref([])
const leaders = ref([])
const directorates = ref([])

const form = ref(new Form({
  id: '',
  name: '',
  email: '',
  company_id: 0,
  position_id: 0,
  position_requirement_item_id: 0,
  position_requirement_id: 0,
  leader_id: '',
  directorate_id: '',
  avatar: '',
}))

const companyId = computed(() => window.user.company_id)

function updateInfo() {
  saving.value = true

  form.value
    .put('v1/profile')
    .then(response => {
      Toast.fire({
        icon: 'success',
        title: response.data.message,
      }).then(() => location.reload())
    })
    .catch(() => {
      Toast.fire({
        icon: 'error',
        title: 'Algo deu errado! Por favor, tente novamente',
      })
    })
    .finally(() => {
      saving.value = false
    })
}

function loadProfile() {
  loading.value = true

  return axios
    .get('v1/profile')
    .then(async ({ data }) => {
      form.value.fill(data.data)

      if (form.value.company_id) {
        await Promise.all([
          loadCostCenters(),
          loadPositions(),
          loadDirectorates(),
        ])
      }
    })
    .finally(() => loading.value = false)
}

function loadCostCenters() {
  return axios
    .get(`v1/companies/${form.value.company_id}/position-requirements`)
    .then(({ data }) => {
      positionRequirements.value = data.data
    })
}

function loadPositions() {
  if (!form.value.position_requirement_id) return

  return axios
    .get(`v1/companies/${form.value.company_id}/position-requirements/${form.value.position_requirement_id}`)
    .then(({ data }) => {
      positions.value = data.data.items.map(p => ({
        description: p.description,
        id: p.id
      }))

      loadLeaders()
    })
}

function loadDirectorates() {
  return axios
    .get(`v1/companies/${form.value.company_id}/directorates`)
    .then(({ data }) => {
      directorates.value = data.data
    })
}

function loadLeaders() {
  return axios
    .get(`v1/companies/${form.value.company_id}/position-requirements/${form.value.position_requirement_id}/leaders`)
    .then(({ data }) => {
      leaders.value = data.data
    })
}

onMounted(async () => {
  await loadProfile()
})
</script>

<style lang="scss" scoped>
.avatar-box {
  cursor: pointer;

  .avatar-mask {
    opacity: 0;
    transition: opacity 0.2s ease-in;
  }

  &:hover .avatar-mask {
    opacity: .6;
  }
}
</style>