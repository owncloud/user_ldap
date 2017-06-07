<div class="ldapSettingControls">
	<ul class="ldapSaveState"></ul>
	<button class="ldap_action_save" name="ldap_action_save" type="button">
		<?php p($l->t('Save changes'));?>
	</button>
	<button type="button" class="ldap_action_test_connection" name="ldap_action_test_connection">
		<?php p($l->t('Test Configuration'));?>
	</button>
	<a href="<?php p(link_to_docs('admin-ldap')); ?>"
		target="_blank" rel="noreferrer">
		<img src="<?php print_unescaped(image_path('', 'actions/info.svg')); ?>"
			style="height:1.75ex" />
		<?php p($l->t('Help'));?>
	</a>
</div>
