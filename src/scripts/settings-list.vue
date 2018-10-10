<template>
    <section class="padding-add-x2">
		<h2 v-translate>
        	LDAP Configuration
        </h2>
		<div class="settings-list">
			<table>
				<thead>
					<tr>
						<th width="1%" v-translate="'core'">Status</th>
						<th v-translate="'core'">ID</th>
						<th colspan="2">Server/Port</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(config, cid) in configurations" :key="cid">
						<td>
							<span class="form-nice-checkbox margin-add-right" :class="{ '-checked': checkBool(config.ldap_configuration_active) }" style="transform:translateY(4px)"></span>
							<span v-if="checkBool(config.ldap_configuration_active)">Active</span>
							<span v-else v-translate>Inactive</span>
						</td>
						<td class="text-monospace">
							{{ config.id }}
						</td>
						<td class="text-monospace">
							{{ config.ldap_host | none }}:{{ config.ldap_port | none }}
						</td>
						<td class="content-align-right">
							<button @click="openWizard(config.id)">Edit</button>
							<span @click="deleteConfig(config.id)" class="icon icon-delete margin-add-left"></span>
						</td>
					</tr>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="99">
							<button @click="createNewConfig" class="button-primary float-right" v-translate>Create new config</button>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<loading-spinner :active="loading"></loading-spinner>
    </section>
</template>
<script>
export default {
	name : 'List',
	data () {
		return {
			core : 'core',
			app : 'user_ldap',
			configurations : null,
			loading : false,
			failed : false
		};
	},
	filters : {
		none(string) {
			if (!_.isEmpty(string)) {
				return string;
			}
			return 'none';
		}
	},
	mounted () {
		this.fetchConfigs();
	},
	methods : {
		fetchConfigs () {
			this.loading = true;
			this.failed  = false;

			$.get(OC.generateUrl('apps/user_ldap/configurations')).done((configurations) => {
				this.configurations = configurations;
				this.loading = false;
			}).fail(() => {
				this.loading = false;
				this.failed  = true;
			});
		},
		createNewConfig () {
			$.post(OC.generateUrl('apps/user_ldap/configurations')).done((data) => {
				this.configurations.push(data);
				this.openWizard(data.id);
			});
		},
		deleteConfig (id) {
			if(confirm(`Delete config ${id}?`)) {
				$.ajax({
					url : OC.generateUrl('apps/user_ldap/configurations/' + id),
					method : 'DELETE'
				}).done((data) => {
					this.configurations = _.without(this.configurations, _.findWhere(this.configurations, { 'id' : id }));
					this.failed  = false;
				}).fail((data) => {
					this.failed  = true;
				});
			}
		},
		openWizard (id) {
			this.$router.push({
				name: 'Wizard',
				params : {
					id
				}
			});
		},
		checkBool (value) {
			if (value === '0' || value === '')
				return null;
			else if (value)
				return true;
			else
				return false;
		}
	}
};
</script>