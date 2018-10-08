<template id="wizard-users-template">
	<section class="section margin-add-x2-top pure-g">
		<main class="pure-u-2-3"> <!-- Left -->
            <h3 class="margin-remove-top">
                <span>User Mapping</span>
            </h3>
            <div v-if="isActive">
                <div class="pure-g">
                    <div class="pure-u-1 form-unify margin-add-bottom">
                        <label for="example_user" class="icon-person icon-remove-text">User:</label>
                        <input ref="example_user" class="grow" type="text" placeholder="Username, E-Mail or DN" v-model="exampleUser">
                    </div>
                </div>
            </div>
            <footer class="wizard-section-footer pure-u-1" v-if="isActive && isAvailable">
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
						The wizard helps you map fields to the right keys.
					</p>
				</div>
			</div>
        </aside>
    </section>
</template>
<script>
export default {
	props: ['users', 'is-active', 'is-available'],
	data () {
		return {
			exampleUser : null,
			advancedMode : true,
			mapping : {
				username : null,
				display_name : null,
				email : null,
				jpegPhoto : null,
				home_folder : null
			}
		};
	},
	methods : {
		toggleTip ( item, state = true ) {
			this.tips[item] = state;
		},
		toggleBool (item) {
			this.users[item] = !this.users[item];
		}
	}
};
</script>