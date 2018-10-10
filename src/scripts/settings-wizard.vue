<template>
    <section class="padding-add-x2">
        <h2>
            LDAP Wizard
        </h2>
		<wizardServer :server="config" :is-active="true"></wizardServer>
		<wizardUsers :users="config" :is-available="userMappingAvailable" :is-active="true"></wizardUsers>
		<button @click="returnToList" class="margin-add-x2-top float-right" v-translate>
			Close
		</button>
		<loading-spinner :active="loading"></loading-spinner>
    </section>
</template>
<script>
import wizardServer from './settings-wizard-server.vue';
import wizardUsers from './settings-wizard-users.vue';

export default {
	name : 'Wizard',
	components : {
		wizardServer,
		wizardUsers
	},
	data () {
		return {
			config  : false,
			loading : false,
			failed  : false
		};
	},
	mounted () {
		this.fetchConfig();
	},
	watch : {
		'config.ldap_turn_off_cert_check' (val) {
			if (val === '0') {
				this.config.ldap_turn_off_cert_check = false;
			}
		},
		'config.ldap_host' (val) {
			if (_.isEmpty(val)) {
				this.config.ldap_host = null;
			}
		},
		'config.ldap_port' (val) {
			if (_.isEmpty(val)) {
				this.config.ldap_port = null;
			}
		},
		'config.ldap_dn' (val) {
			if (_.isEmpty(val)) {
				this.config.ldap_dn = null;
			}
		}
	},
	computed : {
		id () {
			return this.$route.params.id;
		},
		userMappingAvailable () {
			return !!this.config.ldap_host && !!this.config.ldap_port && !!this.config.ldap_dn;
		}
	},
	methods: {
		fetchConfig () {
			this.loading = true;
			this.failed  = false;

			$.get(OC.generateUrl(`apps/user_ldap/configurations/${this.id}`)).done((config) => {
				this.config = config;
				this.loading = false;
			}).fail(() => {
				this.loading = false;
				this.failed  = true;
			});
		},
		returnToList () {
			if ( _.where(this.$children, { hasChanges : true }).length > 0 )
				if (!confirm(this.t('Discard unsaved changes?')))
					return;

			this.$router.push({
				name: 'List'
			});
		},
	}
};
</script>