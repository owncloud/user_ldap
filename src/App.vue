<template>
    <main id="user_ldap">
        <h2 class="app-name">
            {{ app.name }}
        </h2>
        <section class="wizard">
            <ul class="wizard-tabs">
                <li @click="tab = 'server'" :class="{ '-is-active' : tab === 'server' }">Server</li>
                <li @click="tab = 'user'"   :class="{ '-is-active' : tab === 'user' }">User</li>
                <li @click="tab = 'login-attr'">Login Attributes</li>
                <li @click="tab = 'groups'">Groups</li>
            </ul>
            <ul class="wizard-contents">
                <li v-show="tab === 'server'">
                    <p>
                        Hier geht's um den server
                    </p>
                </li>
                <li v-show="tab === 'user'">
                    <p>
                        Wir lieben User
                    </p>
                </li>
            </ul>
        </section>
        <pre v-if="config.data" v-text="config.data"></pre>
        <span v-else class="loader"></span>
    </main>
</template>
<script>
export default {
    data () {
        return {
            "app" : {
                "name": "User LDAP Wizzard",
                "description": "This thing rules"
            },
            "config" : {
                "data"    : null,
                "loading" : false,
                "failed"  : false
            },
            "tab" : "server"
        }
    },
    mounted () {
        this.fetchConfig()
    },
    methods: {
        fetchConfig () {
            this.config.loading = true;
            this.config.failed  = false;

            $.get(OC.generateUrl('apps/user_ldap/configurations'))
            .done((data) => {

				this.config.data    = data;
				this.config.loading = false;
            })
            .fail((data) => {

				this.config.loading = false;
				this.config.failed  = true;
			})
        }
    }
}
</script>
