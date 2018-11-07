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
					<button class="button-primary">Add</button>
				</div>
			</div>
			<div class="pure-g" v-for="(map, mid) in settings.mappings" :key="mid">
				<div class="pure-u-1 form-unify margin-add-bottom">
					<label for="usernameAttribute">Username:</label>
					<user-mapping :reference="'usernameAttribute'" :map="map" class="grow"></user-mapping>
				</div>
				<div class="pure-u-1 form-unify margin-add-bottom">
					<label for="displayNameAttribute">Display Name:</label>
					<user-mapping :reference="'displayNameAttribute'" :map="map" class="grow"></user-mapping>
				</div>
				<div class="pure-u-1 form-unify">
					<label for="displayName2Attribute">2nd Display Name:</label>
					<user-mapping :reference="'displayName2Attribute'" :map="map" class="grow"></user-mapping>
				</div>
			</div>
            <footer class="wizard-section-footer pure-u-1">
				<button class="button-default margin-add-right" disabled>restore</button>
				<button class="button-primary">save &amp; test</button>
            </footer>
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
		<!-- <button @click="discover">discover</button> -->
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
	props: ['users', 'is-open'],
	data () {
		return {
			advancedMode : false,
			settings : {
				mappings : []
			},
			exampleUser : "",
			exampleBaseDN : ""
		};
	},
	watch : {
		users (data, initial) {
            if (initial !== data) {
                this.mapSettings();
                this.writeSettingsBackup();
            }
        }
	},
	computed : {
		gdlf () {
			return btoa(this.exampleUser.toLowerCase()) === 'Z2FuZGFsZg==';
		}
	},
	methods : {
		discover () {
            $.ajax({
                url    : OC.generateUrl(`apps/user_ldap/configurations/${this.$parent.id}/discover`),
                method : 'GET',
                data   : { id : this.$parent.id } 
            }).done((r) => {
                console.log(r);
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