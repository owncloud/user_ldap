<template>
    <main id="user_ldap">
        <h2 class="app-name">
            {{ app.name }}
        </h2>
        <h3>
            {{ app.description }}
        </h3>
        <p>
            Here is the current config:
        </p>
        <pre v-if="config.data" v-text="config.data"></pre>
        <span v-else class="loader"></span>
    </main>
</template>
<script>
export default {
    data () {
        return {
            "app" : {
                "name": "User LDAP Settings … or whatever",
                "description": "I love all the vue's!"
            },
            "config" : {
                "data"    : null,
                "loading" : false,
                "failed"  : false
            }
        }
    },
    computed: {
        otto () {
            return "Otto ist cool";
        }
    },
    mounted () {
        this.fetchConfig()
    },
    methods: {
        fetchConfig () {
            this.config.loading = true;
            this.config.failed  = false;

            $.get(OC.generateUrl('apps/user_ldap/ajax/getConfiguration.php'))
            .done((data) => {

                if (data.status != "success") {
                    this.config.loading = false;
                    this.config.failed  = true;
                }
                else {
                    this.config.data    = data.configuration;
                    this.config.loading = false;
                }
            })
        }
    }
}
</script>
