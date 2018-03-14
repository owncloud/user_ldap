var user_ldap = new Vue({
    el: "#user_ldap",
    data: {
        app : {
            name: "User LDAP Settings … or whatever",
            description: "I love all the vue's!"
        },
        config : {
            data    : null,
            loading : false,
            failed  : false
        }
    },
    mounted: function() {
        this.fetchConfig()
    },
    methods: {
        fetchConfig: function() {
            var ref = this;

            this.config.loading = true;
            this.config.failed  = false;

            $.get(OC.generateUrl('apps/user_ldap/ajax/getConfiguration.php'))
            .done(function(data) {

                if (data.status != "success") {
                    ref.config.loading = false;
                    ref.config.failed  = true;
                }
                else {
                    ref.config.data    = data.configuration;
                    ref.config.loading = false;
                }
            })
        }
    }
})
