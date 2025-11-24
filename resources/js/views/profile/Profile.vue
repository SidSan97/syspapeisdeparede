<template>
  <div class="profile-page">
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
    <ProfilePasswordCard />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ProfilePasswordCard from './components/ProfilePasswordCard.vue'

const loading = ref(false)
const saving = ref(false)

const form = ref(new Form({
  id: '',
  name: '',
  email: '',
  avatar: '',
}))

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
    })
    .finally(() => loading.value = false)
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
