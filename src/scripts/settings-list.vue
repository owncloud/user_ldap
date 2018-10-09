<template>
    <section class="padding-add-x2">
		<h2>
            LDAP Configurations
        </h2>
		<div class="config-list">
			<table>
				<thead>
					<tr>
						<th width="1%">Status</th>
						<th>ID</th>
						<th colspan="2">Server/Port</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="(config, cid) in configurations" :key="cid">
						<td>
							<span class="form-nice-checkbox margin-add-right" :class="{ '-checked': checkBool(config.ldap_configuration_active) }" style="transform:translateY(4px)"></span>
							<span v-if="checkBool(config.ldap_configuration_active)">Active</span>
							<span v-else>Inactive</span>
						</td>
						<td class="text-monospace">
							{{ config.id }}
						</td>
						<td class="text-monospace">
							{{ config.ldap_host | none }}:{{ config.ldap_port | none }}
						</td>
						<td class="content-align-right">
							<button @click="openWizard(config.id)">Edit</button>
						</td>
					</tr>
				</tbody>
			</table>
			<button @click="createNewConfig">Create new config</button>
		</div>
		<loading-spinner :active="loading"></loading-spinner>
    </section>
</template>
<script>
export default {
	name : 'List',
	data () {
		return {
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