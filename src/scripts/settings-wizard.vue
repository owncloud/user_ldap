<template>
	<section class="padding-add-x2">
		<notificationWrapper></notificationWrapper>
		<h2>
			LDAP Wizard
		</h2>
		<wizardServer :server="config"></wizardServer>
		<wizardUsers :config="config" :is-open="userConfigOpen"></wizardUsers>

		<footer class="margin-add-x2-top pure-g">
			<div class="pure-u-1-2">
				<button @click="returnToList()" class="button-default margin-add-right" v-translate="'core'">Close</button>
				<button @click="backup('restore')" class="button-default" :disabled="!configHasChanges">restore</button>
			</div>
			<div class="pure-u-1-2 content-align-right">
				<button @click="testConfig()" class="button-primary margin-add-right">test</button>
				<button @click="saveConfig()" class="button-primary margin-add-right" :disabled="false">save</button>
			</div>
		</footer>

		<loading-spinner :active="loading"></loading-spinner>
	</section>
</template>
<script>
import wizardServer 	   from './settings-wizard-server.vue';
import wizardUsers         from './settings-wizard-users.vue';
import notificationWrapper from './notification-wrapper.vue';
import _ from 'lodash';

export default {
	name : 'Wizard',
	components : {
		wizardServer,
		wizardUsers,
		notificationWrapper
	},
	data () {
		return {
			config           : false,
			configBackup     : false,
			configHasChanges : false, // diff config agains backup

			loading : false,
			failed  : false
		};
	},
	mounted () {
		this.fetchConfig();
	},
	watch : {
		config : {
			deep : true,
			handler (config) {
				this.configHasChanges = JSON.stringify(config) !== JSON.stringify(this.configBackup);
			}
		},
	},
	computed : {
		id () {
			return this.$route.params.id;
		},

		userConfigOpen () {
			if (!this.configBackup)
				return false;

			return !!this.configBackup.host && !!this.configBackup.port && !!this.configBackup.bindDN && !!this.configBackup.password
		}
	},
	methods: {
		fetchConfig () {
			this.loading = true;
			$.get(OC.generateUrl(`apps/user_ldap/configurations/${this.id}`)).done((config) => {
				this.config = config;
				this.loading = false;
				this.backup('write');
			}).fail(() => {
				this.loading = false;
			});
		},

		saveConfig () {
			this.loading = true;
			$.ajax({
				url    : OC.generateUrl(`apps/user_ldap/configurations/${this.id}`),
				method : 'POST',
				data   : this.config
			}).done((r) => {
				this.fetchConfig(true);
			});
		},

		testConfig () {
			this.loading = true;
			$.ajax({
				url    : OC.generateUrl(`apps/user_ldap/configuration/test`),
				method : 'POST',
				data   : this.config
			}).done((r) => {
				alert(r.message);
				this.loading = false;
			}).fail((e) => {
				alert(e.responseJSON.message);
				this.loading = false;
			});
		},

		discover () {
			this.loading = true;
			$.ajax({
				url    : OC.generateUrl(`apps/user_ldap/configuration/discover`),
				method : 'POST',
				data   : this.config
			}).done((r) => {
				console.log(r);
				this.loading = false;
			}).fail((e) => {
				console.log(e);
				this.loading = false;
			});
		},

		// --- Handle Model backup -----

		backup(action = "write") {
			if (action === "write") {
				this.configBackup = _.cloneDeep(this.config);
				return;
			}
			if (action === "restore") {
				this.config = _.cloneDeep(this.configBackup);
				return;
			}
		},

		returnToList () {
			// if ( _.where(this.$children, { hasChanges : true }).length > 0 )
			// 	if (!confirm(this.t('Discard unsaved changes?')))
			// 		return;

			this.$router.push({
				name: 'List'
			});
		},
	}
};
</script>