const mixin = {
	methods : {
		t(string, scope = 'user_ldap') {
			return t(scope, string);
		}
	}
};

const directive = {
	bind (el, binding) {
		el.innerText = t(binding.value, el.innerText.trim());
	}
};

export {
	mixin,
	directive
};