<template>
  <div class="card bd-card border-0 mb-4">
    <div class="card-body">
      <h5 class="card-title">Alterar senha</h5>
      <p class="card-text">Enviaremos um email de confirmação quando você alterar sua senha,
        então aguarde esse email após o envio.</p>

      <form @submit.prevent="updatePassword()">
        <div class="row">
          <div class="col-lg-5">
            <div class="mb-3">
              <label for="current_password" class="form-label">Senha atual</label>

              <div class="col-sm-12">
                <input v-model="form.current_password" class="form-control" id="current_password" type="password"
                  :class="{ 'is-invalid': form.errors.has('current_password') }" required />
                <has-error :form="form" field="current_password"></has-error>
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="new_password">Nova senha</label>
              <input v-model="form.new_password" class="form-control" id="new_password" type="password"
                :class="{ 'is-invalid': form.errors.has('new_password') }" required />
              <has-error :form="form" field="new_password"></has-error>
              <div class="form-text">
                Certifique-se de que tenha pelo menos 15 caracteres OU pelo menos 8 caracteres incluindo
                um número e uma letra minúscula.
              </div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="confirm_password">Confirme a senha</label>
              <input v-model="form.confirm_password" class="form-control" id="confirm_password" type="password"
                :class="{ 'is-invalid': form.errors.has('confirm_password') }" required />
              <has-error :form="form" field="confirm_password"></has-error>
              <div class="form-text">Certifique-se de que corresponde à nova senha acima</div>
            </div>
          </div>
        </div>

        <button type="submit" class="btn btn-primary" :disabled="saving">
          <span v-if="saving" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          <span v-if="!saving"> Alterar senha </span>
          <span v-else> Salvando... </span>
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const saving = ref(false)
const form = ref(new Form({
  id: '',
  password: '',
  new_password: '',
  confirm_password: '',
}))

function updatePassword() {
  saving.value = true

  form.value
    .post('v1/change-password')
    .then(response => {
      form.value.reset()

      Toast.fire({
        icon: 'success',
        title: response.data.message,
      })
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
</script>
