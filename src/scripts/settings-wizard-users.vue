<template>
	<section class="section margin-add-x2-top pure-g" :class="{ 'opacity-half' : !isOpen }">
        <div class="pure-u-1">
            <h3 class="margin-remove-top">
                User Mapping
            </h3>
		</div>
		<main class="pure-u-2-3"> <!-- Left -->
			<div class="pure-g">
				<div class="pure-u-2-5 form-unify margin-add-bottom">
					<label for="example_user" class="icon-person icon-remove-text">User:</label>
					<input ref="example_user" class="grow" type="text" placeholder="Username, E-Mail or DN" v-model="exampleUser" :disabled="!isOpen">
				</div>
				<div class="pure-u-3-5 form-unify margin-add-bottom">
					<label for="example_base_dn" class="margin-add-left">Base DN:</label>
					<input ref="example_base_dn" class="grow" type="text" v-model="exampleBaseDN" :disabled="!isOpen">
					<button class="button-primary" @click="discover()">Add</button>
				</div>
			</div>
			<div class="pure-g" v-for="(attribute, key) in mapTemplate" :key="key">
				<div class="pure-u-1 form-unify margin-add-bottom">
					<label class="min-width-160" for="usernameAttribute">{{ attribute }}:</label>
					<user-mapping :reference="'usernameAttribute'" :map="discovery" class="grow"></user-mapping>
				</div>
			</div>
		</main>
		<aside class="pure-u-1-3">
			<div class="margin-add-x2-left">
				<div class="tipp-box">
					<h3>
						2. Let's do some magic
					</h3>
					<p>
						The wizard helps you to create a simple thingy and you can map stuff and all is good.
					</p>
				</div>
			</div>
        </aside>
    </section>
</template>
<script>
import gdlf 	   from './gdlf.vue';
import userMapping from './settings-wizard-user-mapping.vue';

export default {
	components : {
		gdlf,
		userMapping
	},
	props: ['config', 'is-open'],
	data () {
		return {
			exampleUser   : "gauss",
			exampleBaseDN : "dc=example,dc=com",

			discovery : null,
			mapTemplate : [
				"baseDN",
				"loginFilter",
				"loginFilterMode",
				"loginFilterEmail",
				"loginFilterUsername",
				"loginFilterAttributes",
				"usernameAttribute",
				"expertUsernameAttr",
				"displayName2Attribute",
				"emailAttribute",
				"homeFolderNamingRule",
				"quotaAttribute",
				"quotaDefault",
				"type",
				"filterObjectclass",
				"filterGroups",
				"filter",
				"filterMode",
				"uuidAttribute",
				"displayNameAttribute",
				"additionalSearchAttributes"
			],

			mappings : []
		};
	},
	computed : {
		gdlf () {
			return btoa(this.exampleUser.toLowerCase()) === 'Z2FuZGFsZg==';
		}
	},
	methods : {
		discover () {
			this.$parent.loading = true;
            $.ajax({
                url    : OC.generateUrl(`apps/user_ldap/configuration/discover/${this.exampleUser}`),
                method : 'POST',
                data   : {
					id        : this.config.id,
					host      : this.config.host,
					port      : this.config.port,
					bindDN    : this.config.bindDN,
					password  : this.config.password,
					userTrees : [{
						baseDN : this.exampleBaseDN
					}]
				}
            }).done((r) => {
				this.discovery = _.values(r)[0];
            }).fail(() => {
				this.discovery = null;
			}).always(() => {
				this.$parent.loading = false;
			});
		},

		toggleTip ( item, state = true ) {
			this.tips[item] = state;
		},
		
		toggleBool (item) {
			this.users[item] = !this.users[item];
		},
		
		mapSettings() {
             this.settings = _.clone(_.pick(this.users, _.keys(this.settings)));
        },

        writeSettingsBackup() {
            this.settingsBackup = JSON.stringify(_.pick(this.server, _.keys(this.settings)));
        },

        restoreFromBackup() {
            this.settings = JSON.parse(this.settingsBackup);
        },
	}
};
</script>