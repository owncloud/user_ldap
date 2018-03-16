<template>
	<main id="user_ldap">
		<h2 class="app-name">
			{{ app.name }}
		</h2>
		<section class="wizard">
			<ul class="wizard-tab-bar">
				<li @click="setUiTab(1)" :class="getUiTabClass(1)">Server</li>
				<li @click="setUiTab(2)" :class="getUiTabClass(2)">User</li>
				<li @click="setUiTab(3)" :class="getUiTabClass(3)">Login Attributes</li>
				<li @click="setUiTab(4)" :class="getUiTabClass(4)">Groups</li>
			</ul>
			<ul class="wizard-contents">
				<li v-show="checkUiTab(1)">
					<h5 class="title">
						Server Settings
					</h5>
					<p>
						Let's do this!!
					</p>
				</li>
				<li v-show="checkUiTab(2)">
					<p>
						Let's do something to the users
					</p>
				</li>
			</ul>
		</section>
		<pre v-if="config.data" v-text="config.data"></pre>
	</main>
</template>
<script>
export default {
	data () {
		return {
			app : {
				name: "User LDAP Wizzard",
				description: "This thing rules"
			},
			config : {
				data    : null,
				loading : false,
				failed  : false
			},
			ui : {
				tab : 1
			}
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
			.fail(() => {

				this.config.loading = false;
				this.config.failed  = true;
			})
		},

		setUiTab (tab) {
			this.ui.tab = parseInt(tab)
		},

		checkUiTab (tab) {
			return (this.ui.tab === tab)
		},

		getUiTabClass (tab) {
			if (this.ui.tab === tab)
				return '-is-active'

			return null
		}
	}
}
</script>
