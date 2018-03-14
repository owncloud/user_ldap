<template>
	<section class="section pure-g">
        <div class="pure-u-1">
            <h3 class="margin-remove-top">
                Setup LDAP Server
            </h3>
        </div>
		<main class="pure-u-2-3"> <!-- Left -->
            <div class="pure-g">
                <div class="pure-u-2-3 pure-u-xl-3-5 form-unify margin-add-bottom">
                    <label for="host" class="min-width-80">Hostname:</label>
                    <input ref="host" class="font-monospace grow" type="url" placeholder="ldaps://" v-model="server.host" v-on:focus="toggleTip('host')" v-on:blur="toggleTip('host', false)">
                </div>
                <div class="pure-u-1-3 pure-u-xl-2-5 form-unify margin-add-bottom">
                    <label for="port" class="margin-add-left">Port:</label>
                    <input ref="port" class="font-monospace grow" type="number" min="0" max="65535" v-model="server.port">
                    
                    <button class="button-primary" @click="autodetectPort()" :disabled="!server.host">detect</button>
                </div>
            </div>

            <div class="pure-g">
                <div class="pure-u-1 form-unify margin-add-bottom">
                    <label for="bindDN" class="min-width-80">Bind-DN:</label>
                    <input ref="bindDN" class="font-monospace grow" type="text" placeholder="User Distinguished Name" v-model="server.bindDN" v-on:focus="toggleTip('bindDN')" v-on:blur="toggleTip('bindDN', false)">
                </div>
                <div class="pure-u-1 form-unify">
                    <label for="password" class="min-width-80">Password:</label>
                    <input ref="password" type="password" class="grow" v-model="server.password" v-on:focus="toggleTip('password')" v-on:blur="toggleTip('password', false)">
                </div>
                <!-- <div class="pure-u-1 form-unify">
                    <label for="ldap_base_dn" class="min-width-80" v-translate>Base-DN:</label>
                    <input ref="ldap_base_dn" type="text" class="font-monospace grow" v-model="server.ldap_base_dn">
                    <button class="button-primary" @click="autodetectBaseDn()" :disabled="!computedBaseDn">detect</button>
                </div> -->
            </div>

            <div class="pure-g margin-add-top" v-if="advancedMode">
                <div class="pure-u-2-3 pure-u-xl-3-5 form-unify margin-add-bottom">
                    <label for="backupHost" class="min-width-80">Backup Host:</label>
                    <input ref="backupHost" class="font-monospace grow" type="url" placeholder="ldaps://" v-model="server.backupHost" v-on:focus="toggleTip('host')" v-on:blur="toggleTip('host', false)">
                </div>
                <div class="pure-u-1-3 pure-u-xl-2-5 form-unify margin-add-bottom">
                    <label for="backupPort" class="margin-add-left">Port:</label>
                    <input ref="backupPort" class="font-monospace grow" type="number" min="0" max="65535" v-model="server.backupPort">
                    <button class="button-primary icon icon-sync icon-remove-text" :disabled="!server.backupHost">autodetect</button>
                </div>

                <div class="pure-u-1-2 form-unify margin-add-bottom">
                    <label for="cacheTTL" class="min-width-80">Cache TTL:</label>
                    <input ref="cacheTTL" class="font-monospace grow" type="number" min="0" placeholder="Cache TTL" v-model="server.cacheTTL">
                </div>

                <div class="pure-u-1-2 form-unify margin-add-bottom">
                    <label for="timeout" class="min-width-80">Timeout:</label>
                    <input ref="timeout" class="font-monospace grow" type="number" min="0" placeholder="Timeout" v-model="server.timeout">
                </div>

                <div class="pure-u-1-2 form-items-centered margin-add-bottom content-align-right">
                    <span class="form-nice-checkbox margin-add-right" :class="{ '-checked': server.turnOffCertCheck }" @click="server.turnOffCertCheck = !server.turnOffCertCheck"></span>
                    <label>Turn off SSL certificate validation</label>
                </div>
            </div>
            <footer class="wizard-section-footer pure-u-1">
                <div class="pure-g">
                    <div class="pure-u-1 content-align-center-v">
                        <span class="form-nice-checkbox margin-add-right" :class="{ '-checked': advancedMode }" @click="advancedMode = !advancedMode"></span>
                        <label>show advanced settings</label>
                    </div>
                </div>
            </footer>
		</main>
		<aside class="pure-u-1-3">
			<div class="margin-add-x2-left">
				<div class="tipp-box">
                    <h3>
                        1. Getting started
                    </h3>
                    <p>
                        Please setup basic Server settings to get the party started!
                    </p>
				</div>
                <transition name="fade">
                    <div class="tipp-box" v-if="tips.host">
                        <p>
                            You can omit the protocol, except if you require SSL. Then start with <code class="uk-padding-remove">ldaps://</code>
                        </p>
                    </div>
                </transition>
                <transition name="fade">
                    <div class="tipp-box" v-if="tips.bindDN">
                        <p>
                            The <strong>Distinguished Name</strong> <i>(DN)</i> of the client user with which the bind shall be done. For anonymous access, leave DN and Password empty. For example:
                        </p>
                        <code>
                            uid=agent,dc=example,dc=com
                        </code>
                    </div>
                </transition>
                <div class="tipp-box" v-if="tips.password">
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
	props: ['server', 'is-open'],
	data () {
		return {
			advancedMode : false,
			tips : {
				host     : false,
				bindDN   : false,
				password : false
            },
		};
    },
    computed : {
        computedBaseDn () {
            if (this.server.bindDN) {
                let elements = this.server.bindDN.split(',');

                if (elements.length >= 3)
                    return elements.slice(-3).join();
            }
            return null;
        }
    },
	methods : {
		toggleTip ( item, state = true ) {
			this.tips[item] = state;
        },

        saveSettings () {
            $.ajax({
                url    : OC.generateUrl(`apps/user_ldap/configurations/${this.$parent.id}`),
                method : 'POST',
                data   : this.settings
            }).done((r) => {
                this.$parent.fetchConfig(true);
			});
        },

        // --- fooo ---

        autodetectPort () {
            if (!this.server.host) {
                return;
            }
            else if (this.server.host.search('ldap://') === 0) {
                this.server.port = 389
            }
            else if (this.server.host.search('ldaps://') === 0) {
                this.server.port = 636
            }
        },

        autodetectBaseDn () {
            this.server.ldap_base_dn = this.computedBaseDn;
            this.$forceUpdate();
        }
	}
};
</script>