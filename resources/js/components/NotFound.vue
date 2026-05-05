<script setup>
import { IconAlertCircle } from '@tabler/icons-vue';
</script>

<template>
  <section class="content">
    <div class="container">
      <div class="row">
        <div class="col-12">
          <div class="card mb-3">
            <div class="card-body p-0">
              <div class="text-center p-4">
                <IconAlertCircle :size="64" class="icon text-muted m-4" />
                <h4>Não há página neste endereço</h4>
                <p class="text-muted mb-4">
                  Verifique o URL e tente de novo ou use a barra de pesquisa para encontrar o que
                  precisa.
                </p>
              </div>
            </div>
          </div>
          <!-- /.card -->
        </div>
        <!-- /.col-12 -->
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </section>
</template>
