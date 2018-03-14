<?php
// vendor_script('user_ldap', 'vue/dist/vue');
// script('user_ldap', 'app');
style('user_ldap', 'style');
?>

<section id="user_ldap">
    <!-- #user_ldap is this instances root -->
    <h2 class="app-name" v-text="app.name"></h2>
    <p v-text="app.description"></p>
    <button type="button" @click="letsShout('LDAAAAAAAAAP')">Shout</button>
</section>


<!-- all of this needs to be in the footer -->
<script src="/apps/user_ldap/vendor/vue/dist/vue.js"></script>
<script src="/apps/user_ldap/l10n/de_DE.js"></script>
<script src="/apps/user_ldap/js/app.js"></script>
