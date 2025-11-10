<template>
    <section class="content">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="d-flex align-items-center mb-3">
                        <div>
                            <h2 class="mb-0">Olá!</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--/. container -->
    </section>
</template>

<script>
export default {
    name: 'Dashboard',
    data() {
        return {
            user: {
                name: ''
            }
        }
    },
    mounted() {
        this.user = window.user
        //document.title = 'Início | ' + APP_NAME;
    },
    created() {
        console.log(this.user);
    }
}
</script>
