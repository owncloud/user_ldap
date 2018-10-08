<template>
	<section class="section pure-g">
        <div class="pure-u-1">
            <h3 class="margin-remove-top">
                Setup LDAP Server
            </h3>
        </div>
		<main class="pure-u-2-3"> <!-- Left -->
            <div v-if="isActive">
                <div class="pure-g pure-g-padded">
                    <div class="pure-u-2-3 pure-u-xl-3-5 form-unify margin-add-bottom">
                        <label for="ldap_host" class="min-width-80">Hostname:</label>
                        <input ref="ldap_host" class="font-monospace grow" type="url" placeholder="ldaps://" v-model="settings.ldap_host" v-on:focus="toggleTip('ldap_host')" v-on:blur="toggleTip('ldap_host', false)">
                    </div>
                    <div class="pure-u-1-3 pure-u-xl-2-5 form-unify margin-add-bottom">
                        <label for="ldap_port" class="margin-add-left">Port:</label>
                        <input ref="ldap_port" class="font-monospace grow" type="number" min="0" max="65535" v-model="settings.ldap_port">
                        <button class="button-primary icon icon-sync icon-remove-text" :disabled="!settings.ldap_host">autodetect</button>
                    </div>
                </div>

                <div class="pure-g pure-g-padded">
                    <div class="pure-u-1 form-unify margin-add-bottom">
                        <label for="ldap_dn" class="min-width-80">User-DN:</label>
                        <input ref="ldap_port" class="font-monospace grow" type="text" placeholder="User Distinguished Name" v-model="settings.ldap_dn" v-on:focus="toggleTip('ldap_dn')" v-on:blur="toggleTip('ldap_dn', false)">
                    </div>
                    <div class="pure-u-1 form-unify">
                        <label for="ldap_agent_password" class="min-width-80">Password:</label>
                        <input ref="ldap_agent_password" type="password" class="grow" v-model="settings.ldap_agent_password" v-on:focus="toggleTip('ldap_agent_password')" v-on:blur="toggleTip('ldap_agent_password', false)">
                    </div>
                </div>

                <div class="pure-g pure-g-padded margin-add-top" v-if="advancedMode">
                    <div class="pure-u-2-3 pure-u-xl-3-5 form-unify margin-add-bottom">
                        <label for="ldap_backup_host" class="min-width-80">Backup Host:</label>
                        <input ref="ldap_backup_host" class="font-monospace grow" type="url" placeholder="ldaps://" v-model="settings.ldap_backup_host" v-on:focus="toggleTip('ldap_host')" v-on:blur="toggleTip('ldap_host', false)">
                    </div>
                    <div class="pure-u-1-3 pure-u-xl-2-5 form-unify margin-add-bottom">
                        <label for="ldap_backup_port" class="margin-add-left">Port:</label>
                        <input ref="ldap_backup_port" class="font-monospace grow" type="number" min="0" max="65535" v-model="settings.ldap_backup_port">
                        <button class="button-primary icon icon-sync icon-remove-text" :disabled="!settings.ldap_backup_host">autodetect</button>
                    </div>

                    <div class="pure-u-1-2 form-unify margin-add-bottom">
                        <label for="ldap_cache_ttl" class="min-width-80">Cache TTL:</label>
                        <input ref="ldap_cache_ttl" class="font-monospace grow" type="number" placeholder="Cache TTL" v-model="settings.ldap_cache_ttl">
                    </div>

                    <div class="pure-u-1-2 form-items-centered margin-add-bottom content-align-right">
                        <span class="form-nice-checkbox margin-add-right" :class="{ '-checked': settings.ldap_turn_off_cert_check }" @click="settings.ldap_turn_off_cert_check = !settings.ldap_turn_off_cert_check"></span>
                        <label>Turn off SSL certificate validation</label>
                    </div>
                </div>
            </div>
            <footer class="wizard-section-footer pure-u-1" v-if="isActive">
                <div class="pure-g">
                    <div class="pure-u-1-2 content-align-center-v">
                        <span class="form-nice-checkbox margin-add-right" :class="{ '-checked': advancedMode }" @click="advancedMode = !advancedMode"></span>
                        <label>show advanced settings</label>
                    </div>
                    <div class="pure-u-1-2 content-align-right">
                        <button class="button-default margin-add-right" disabled>restore</button>
                        <button @click="saveSettings()" class="button-primary">save &amp; test</button>
                    </div>
                </div>
            </footer>
		</main>
		<aside class="pure-u-1-3">
			<div class="margin-add-x2-left">
				<div class="tipp-box margin-remove-top">
                    <h3>
                        1. Getting started
                    </h3>
                    <p>
                        Please setup basic Server settings to get the party started!
                    </p>
				</div>
				<div class="tipp-box" v-if="tips.ldap_host">
					<p>
						You can omit the protocol, except if you require SSL. Then start with <code class="uk-padding-remove">ldaps://</code>
					</p>
				</div>
				<div class="tipp-box" v-if="tips.ldap_dn">
					<p>
						The <strong>Distinguished Name</strong> <i>(DN)</i> of the client user with which the bind shall be done. For anonymous access, leave DN and Password empty. For example:
					</p>
					<code>
						uid=agent,dc=example,dc=com
					</code>
				</div>
				<div class="tipp-box" v-if="tips.ldap_agent_password">
					<p>
						Leave empty for anonymous access.
					</p>
				</div>
			</div>
        </aside>
    </section>
</template>
<script>
export default {
	props: ['server', 'is-active'],
	data () {
		return {
			advancedMode : false,
			tips : {
				ldap_host : false,
				ldap_dn : false,
				ldap_agent_password : false
            },
            settings : {}
		};
    },
    watch : {
        server (data, initial) {
            if (initial !== data) {
                this.mapSettings();
            }
        }
    },
	methods : {
		toggleTip ( item, state = true ) {
			this.tips[item] = state;
        },
        saveSettings () {
            $.post(OC.generateUrl(`apps/user_ldap/configurations/${this.$parent.id}`), { config: this.settings } ).then((data) => {
                this.$parent.fetchConfig(true);
			});
        },
        mapSettings() {
             this.settings = _.pick(this.server,
                'ldap_host',
                'ldap_port',
                'ldap_dn',
                'ldap_agent_password',

                'ldap_backup_host',
                'ldap_backup_port',
                'ldap_cache_ttl',
                'ldap_turn_off_cert_check'
            );
        }
	}
};
</script>