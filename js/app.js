var user_ldap = new Vue({
    el: "#user_ldap",
    data: {
        app : {
            name: "User LDAP Settings … or whatever",
            description: "I love all the vue's!"
        }
    },
    methods: {
        letsShout: function(string) {
            alert(string);
        }
    }
})
