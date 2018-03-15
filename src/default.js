// Lets remove some stuff we defenitly don't need

$('script[src*="/core/js/jquery.ocdialog.js"]').remove();
$('script[src*="multiselect.js"]').remove();

// ------------------------------------------------------------ styles ---

require('./less/style.less');

// ------------------------------------------------------------ dependencies ---

import Vue from 'vue';

Vue.config.devtools = true

// ------------------------------------------------------------ translations ---

// TODO: Enable translations
// import GetTextPlugin from 'vue-gettext'
// import translations from '../l10n/translations.json'

// Vue.use(GetTextPlugin, {translations: translations})
// Vue.config.language = OC.getLocale()


// --------------------------------------------------------------- app setup ---

import App from './App.vue'

const userLdap = new Vue({
    render: h => h(App)
});

// --------------------------------- (unfortunately) wait for window to load ---
window.onload = () => userLdap.$mount("[data-app-id='user_ldap']");
