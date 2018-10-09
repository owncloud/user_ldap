// -------------------------- Lets remove some stuff we defenitly don't need ---

$('script[src*="/core/js/jquery.ocdialog.js"]').remove();
$('script[src*="multiselect.js"]').remove();

// Components

import List   from './settings-list.vue';
import Wizard from './settings-wizard.vue';

// Libs

import Vue       from 'vue/dist/vue.js';
import VueRouter from 'vue-router';

Vue.use(VueRouter);


// --- Global Components

Vue.component('loading-spinner', require('./loading-spinner.vue'));

const router = new VueRouter({
	routes : [
		{
			path: '/',
			redirect: '/list'
		},
		{
			path: '/list',
			component: List
		},
		{
			path: '/wizard/:id',
			component: Wizard,
			name : 'Wizard'
		}
	]
});

// --------------------------------------------------------------- app setup ---

const user_ldap = new Vue({
	router,
	template : '<router-view></router-view>',
	data : {
		name : 'User Ldap'
	}
});

// Japp … we need to wait for a ready DOM
$(document).ready(() => {
	user_ldap.$mount('#user_ldap_mount');
});