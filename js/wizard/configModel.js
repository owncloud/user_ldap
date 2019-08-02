/**
 * Copyright (c) 2015, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

OCA = OCA || {};

(function() {

	/**
	 * @classdesc this class represents a server configuration. It communicates
	 * with the ownCloud server to ensure to always have the up to date LDAP
	 * configuration. It sends various events that views can listen to and
	 * provides methods so they can modify the configuration based upon user
	 * input. This model is also extended by so-called "detectors" who let the
	 * ownCloud server try to auto-detect settings and manipulate the
	 * configuration as well.
	 *
	 * @constructor
	 */
	var ConfigModel = function() {};

	ConfigModel.prototype = {
		/** @constant {number} */
		FILTER_MODE_ASSISTED: 0,
		/** @constant {number} */
		FILTER_MODE_RAW: 1,

		/**
		 * initializes the instance. Always call it after creating the instance.
		 *
		 * @param {OCA.LDAP.Wizard.WizardDetectorQueue} detectorQueue
		 */
		init: function (detectorQueue) {
			this.mappings = {
				'ldapHost'                      : 'ldap_host',
				'ldapPort'                      : 'ldap_port',
				'ldapBackupHost'                : 'ldap_backup_host',
				'ldapBackupPort'                : 'ldap_backup_port',
				'ldapOverrideMainServer'        : 'ldap_override_main_server',
				'ldapAgentName'                 : 'ldap_dn',
				'ldapAgentPassword'             : 'ldap_agent_password',
				'ldapBase'                      : 'ldap_base',
				'ldapBaseUsers'                 : 'ldap_base_users',
				'ldapBaseGroups'                : 'ldap_base_groups',
				'ldapUserFilterObjectclass'     : 'ldap_userfilter_objectclass',
				'ldapUserFilterGroups'          : 'ldap_userfilter_groups',
				'ldapUserFilter'                : 'ldap_userlist_filter',
				'ldapUserFilterMode'            : 'ldap_user_filter_mode',
				'ldapLoginFilter'               : 'ldap_login_filter',
				'ldapLoginFilterMode'           : 'ldap_login_filter_mode',
				'ldapLoginFilterEmail'          : 'ldap_loginfilter_email',
				'ldapLoginFilterUsername'       : 'ldap_loginfilter_username',
				'ldapLoginFilterAttributes'     : 'ldap_loginfilter_attributes',
				'ldapGroupFilter'               : 'ldap_group_filter',
				'ldapGroupFilterMode'           : 'ldap_group_filter_mode',
				'ldapGroupFilterObjectclass'    : 'ldap_groupfilter_objectclass',
				'ldapGroupFilterGroups'         : 'ldap_groupfilter_groups',
				'ldapUserName'                  : 'ldap_user_name',
				'ldapUserDisplayName'           : 'ldap_display_name',
				'ldapUserDisplayName2'          : 'ldap_user_display_name_2',
				'ldapGroupDisplayName'          : 'ldap_group_display_name',
				'ldapTLS'                       : 'ldap_tls',
				'ldapQuotaDefault'              : 'ldap_quota_def',
				'ldapQuotaAttribute'            : 'ldap_quota_attr',
				'ldapEmailAttribute'            : 'ldap_email_attr',
				'ldapGroupMemberAssocAttr'      : 'ldap_group_member_assoc_attribute',
				'ldapCacheTTL'                  : 'ldap_cache_ttl',
				'ldapNetworkTimeout'            : 'ldap_network_timeout',
				'homeFolderNamingRule'          : 'home_folder_naming_rule',
				'turnOffCertCheck'              : 'ldap_turn_off_cert_check',
				'ldapConfigurationActive'       : 'ldap_configuration_active',
				'ldapAttributesForUserSearch'   : 'ldap_attributes_for_user_search',
				'ldapAttributesForGroupSearch'  : 'ldap_attributes_for_group_search',
				'ldapExpertUsernameAttr'        : 'ldap_expert_username_attr',
				'ldapExpertUUIDUserAttr'        : 'ldap_expert_uuid_user_attr',
				'ldapExpertUUIDGroupAttr'       : 'ldap_expert_uuid_group_attr',
				'hasMemberOfFilterSupport'      : 'has_memberof_filter_support',
				'useMemberOfToDetectMembership' : 'use_memberof_to_detect_membership',
				'lastJpegPhotoLookup'           : 'last_jpegPhoto_lookup',
				'ldapNestedGroups'              : 'ldap_nested_groups',
				'ldapPagingSize'                : 'ldap_paging_size',
				'ldapExperiencedAdmin'          : 'ldap_experienced_admin',
				'ldapDynamicGroupMemberURL'     : 'ldap_dynamic_group_member_url',
				'ldapUuidUserAttribute'         : 'ldap_uuid_user_attribute',
				'ldapUuidGroupAttribute'        : 'ldap_uuid_group_attribute'
			};

			this.modifyingAjaxCalls = [];
			/** @type {object} holds the configuration in key-value-pairs */
			this.configuration     = {};
			/** @type {object} holds the subscribers that listen to the events */
			this.subscribers       = {};
			/** @type {Array} holds registered detectors */
			this.detectors         = [];
			/** @type {boolean} whether a configuration is currently loading */
			this.loadingConfig = false;

			if(detectorQueue instanceof OCA.LDAP.Wizard.WizardDetectorQueue) {
				/** @type {OCA.LDAP.Wizard.WizardDetectorQueue} */
				this.detectorQueue = detectorQueue;
			}
		},

		translateForBackend : function (key) {
			for (var i in this.mappings){
				if (this.mappings[i] == key){
					return i;
				}
			}
		},

		/**
		 * loads a specified configuration
		 *
		 * @param {string} [configID] - the configuration id (or prefix)
		 */
		load: function (configID) {
			if(this.loadingConfig) {
				return;
			}
			this._resetDetectorQueue();

			this.configID = configID;
			var url = OC.generateUrl('apps/user_ldap/ajax/getConfiguration.php');
			var params = OC.buildQueryString({id: configID});
			this.loadingConfig = true;
			var model = this;
			$.post(url, params, function (result) { model._processLoadConfig(model, result) });
		},

		/**
		 * creates a new LDAP configuration
		 *
		 * @param {boolean} [copyCurrent] - if true, the current configuration
		 * is copied, otherwise a blank one is created.
		 */
		newConfig: function(copyCurrent) {
			this._resetDetectorQueue();

			var url = OC.generateUrl('apps/user_ldap/ajax/getNewServerConfigPrefix.php');
			var params = {};
			if(copyCurrent === true) {
				params['copyConfig'] = this.configID;
			}
			params = OC.buildQueryString(params);
			var model = this;
			copyCurrent = _.isUndefined(copyCurrent) ? false : copyCurrent;
			$.post(url, params, function (result) { model._processNewConfigPrefix(model, result, copyCurrent) });
		},

		/**
		 * deletes the current configuration. This method will not ask for
		 * confirmation, if desired it needs to be ensured by the caller.
		 *
		 * @param {string} [configID] - the configuration id (or prefix)
		 */
		deleteConfig: function(configID) {
			var url = OC.generateUrl('apps/user_ldap/ajax/deleteConfiguration.php');
			var params = OC.buildQueryString({id: configID});
			var model = this;
			$.post(url, params, function (result) { model._processDeleteConfig(model, result, configID) });
		},

		/**
		 * @callback wizardCallBack
		 * @param {ConfigModel} [model]
		 * @param {OCA.LDAP.Wizard.WizardDetectorGeneric} [detector]
		 * @param {object} [result] - response from the ajax request
		 */

		/**
		 * calls an AJAX endpoint at ownCloud. This method should be called by
		 * detectors only!
		 *
		 * @param {string} [params] - as return by OC.buildQueryString
		 * @param {wizardCallBack} [callback]
		 * @param {OCA.LDAP.Wizard.WizardDetectorGeneric} [detector]
		 * @returns {jqXHR}
		 */
		callWizard: function(params, callback, detector) {
			return this.callAjax('wizard.php', params, callback, detector);
		},

		/**
		 * calls an AJAX endpoint at ownCloud. This method should be called by
		 * detectors only!
		 *
		 * @param {string} destination - the desired end point
		 * @param {string} [params] - as return by OC.buildQueryString
		 * @param {wizardCallBack} [callback]
		 * @param {OCA.LDAP.Wizard.WizardDetectorGeneric} [detector]
		 * @returns {jqXHR}
		 */
		callAjax: function(destination, params, callback, detector) {
			var url = OC.generateUrl('apps/user_ldap/ajax/' + destination);
			var model = this;
			return $.post(url, params, function (result) {
				if (result['changes']) {
					var changes = {};
					$.each(result['changes'], function(key, value) {
						key = model.mappings[key] || key;
						changes[key] = value;
					});
					result['changes'] = changes;
				}
				if (result['options']) {
					var options = {};
					$.each(result['options'], function(key, value) {
						key = model.mappings[key] || key;
						options[key] = value;
					});
					result['options'] = options;
				}
				callback(model, detector, result);
			});
		},

		/**
		 * setRequested Event
		 *
		 * @event ConfigModel#setRequested
		 * @type{object} - empty
		 */

		/**
		 * modifies a configuration key. If a provided configuration key does
		 * not exist or the provided value equals the current setting, false is
		 * returned. Otherwise ownCloud server will be called to save the new
		 * value, an event will notify when this is done. True is returned when
		 * the request is sent, however it does not mean whether saving was
		 * successful or not.
		 *
		 * This method is supposed to be called by views, after the user did a
		 * change which needs to be saved.
		 *
		 * @param {string} [key]
		 * @param {string|number} [value]
		 * @returns {boolean}
		 * @fires {ConfigModel#setRequested}
		 */
		set: function(key, value) {
			if(_.isUndefined(this.configuration[key])) {
				console.warn('will not save undefined key: ' + key);
				return false;
			}
			if(this.configuration[key] === value) {
				return false;
			}
			this._broadcast('setRequested', {});
			var url = OC.generateUrl('apps/user_ldap/ajax/wizard.php');
			var objParams = {
				id: this.configID,
				action: 'save',
				cfgkey: this.translateForBackend(key) || key,
				cfgval: value
			};
			var strParams = OC.buildQueryString(objParams);
			var model = this;
			var ajaxCall = $.post(url, strParams, function(result) {
				objParams.cfgkey = key;
				model._processSetResult(model, result, objParams);
				// remove this ajax call from the list to clean up
				model.modifyingAjaxCalls = _.without(model.modifyingAjaxCalls, ajaxCall);
			});
			this.modifyingAjaxCalls.push(ajaxCall);
			return true;
		},

		/**
		 * configUpdated Event
		 *
		 * object property is a key-value-pair of the configuration key as index
		 * and its value.
		 *
		 * @event ConfigModel#configUpdated
		 * @type{object}
		 */

		/**
		 * updates the model's configuration data. This should be called only,
		 * when a new configuration value was received from the ownCloud server.
		 * This is typically done by detectors, but never by views.
		 *
		 * Cancels with false if old and new values already match.
		 *
		 * @param {string} [key]
		 * @param {string} [value]
		 * @returns {boolean}
		 * @fires ConfigModel#configUpdated
		 */
		update: function(key, value) {
			if(this.configuration[key] === value) {
				return false;
			}
			if(!_.isUndefined(this.configuration[key])) {
				// don't write e.g. count values to the configuration
				// they don't go as feature, yet
				this.configuration[key] = value;
			}
			var configPart = {};
			configPart[key] = value;
			this._broadcast('configUpdated', configPart);
		},

		/**
		 * @typedef {object} FeaturePayload
		 * @property {string} feature
		 * @property {Array} data
		 */

		/**
		 * informs about a detected LDAP "feature" (wider sense). For examples,
		 * the detected object classes for users or groups
		 *
		 * @param {FeaturePayload} payload
		 */
		inform: function(payload) {
			this._broadcast('receivedLdapFeature', payload);
		},

		/**
		 * @typedef {object} ErrorPayload
		 * @property {string} message
		 * @property {string} relatedKey
		 */

		/**
		 * broadcasts an error message, if a wizard reply ended up in an error.
		 * To be called by detectors.
		 *
		 * @param {ErrorPayload} payload
		 */
		gotServerError: function(payload) {
			this._broadcast('serverError', payload);
		},

		/**
		 * detectionStarted Event
		 *
		 * @event ConfigModel#detectionStarted
		 * @type{string} - the target configuration key that is being
		 * auto-detected
		 */

		/**
		 * lets the model broadcast the info that a detector starts to run
		 *
		 * supposed to be called by detectors only
		 *
		 * @param {string} [key]
		 * @fires ConfigModel#detectionStarted
		 */
		notifyAboutDetectionStart: function(key) {
			this._broadcast('detectionStarted', key);
		},

		/**
		 * detectionCompleted Event
		 *
		 * @event ConfigModel#detectionCompleted
		 * @type{string} - the target configuration key that was
		 * auto-detected
		 */

		/**
		 * lets the model broadcast the info that a detector run was completed
		 *
		 * supposed to be called by detectors only
		 *
		 * @param {string} [key]
		 * @fires ConfigModel#detectionCompleted
		 */
		notifyAboutDetectionCompletion: function(key) {
			this._broadcast('detectionCompleted', key);
		},

		/**
		 * @callback listenerCallback
		 * @param {OCA.LDAP.Wizard.WizardTabGeneric|OCA.LDAP.Wizard.WizardView} [view]
		 * @param  {object} [params]
		 */

		/**
		 * registers a listener to an event
		 *
		 * the idea is that only views listen.
		 *
		 * @param {string} [name] - the event name
		 * @param {listenerCallback} [fn]
		 * @param {OCA.LDAP.Wizard.WizardTabGeneric|OCA.LDAP.Wizard.WizardView} [context]
		 */
		on: function(name, fn, context) {
			if(_.isUndefined(this.subscribers[name])) {
				this.subscribers[name] = [];
			}
			this.subscribers[name].push({fn: fn, context: context});
		},

		/**
		 * Execute the callback after all the "set" calls have been made. This should ensure the
		 * callbacks always use the latest configuration, without race conditions.
		 * Additional arguments passed to this function will be forwarded to the callback function
		 *
		 * @param {function} [callback] - the function to be executed
		 * @param {object} [thisContext] - the context where the callback will run ("this" will be
		 * that object for that function)
		 */
		executeAfterSet: function(callback, thisContext) {
			// get the rest of the parameters (excluding "callback" and "thisContext") as an array
			var additionalArgs = Array.prototype.slice.call(arguments, 2);
			// $.when(ajaxCall1, ajaxCall2, ajaxCall3, ...).then()
			$.when.apply($, this.modifyingAjaxCalls).always(function(){
				// make sure we forward the parameters to the callback function, and use the appropiate
				// context
				callback.apply(thisContext, additionalArgs);
			});
		},

		/**
		 * starts a configuration test on the ownCloud server
		 */
		requestConfigurationTest: function() {
			var url = OC.generateUrl('apps/user_ldap/ajax/testConfiguration.php');
			var params = OC.buildQueryString({id: this.configID});
			var model = this;
			this.executeAfterSet(function(){
				$.post(url, params, function(result) { model._processTestResult(model, result) });
				//TODO: make sure only one test is running at a time
			});
		},

		/**
		 * the view may request a call to the wizard, for instance to fetch
		 * object classes or groups
		 *
		 * @param {string} featureKey
		 * @param {Object} [additionalParams]
		 */
		requestWizard: function(featureKey, additionalParams) {
			var model = this;
			var detectorCount = this.detectors.length;
			var found = false;
			for(var i = 0; i < detectorCount; i++) {
				if(this.detectors[i].runsOnFeatureRequest(featureKey)) {
					found = true;
					(function (detector) {
						model.detectorQueue.add(function() {
							return detector.run(model, model.configID, additionalParams);
						});
					})(model.detectors[i]);
				}
			}
			if(!found) {
				console.warn('No detector found for feature ' + featureKey);
			}
		},

		/**
		 * resets the detector queue
		 *
		 * @private
		 */
		_resetDetectorQueue: function() {
			if(!_.isUndefined(this.detectorQueue)) {
				this.detectorQueue.reset();
			}
		},

		/**
		 * detectors can be registered herewith
		 *
		 * @param {OCA.LDAP.Wizard.WizardDetectorGeneric} [detector]
		 */
		registerDetector: function(detector) {
			if(detector instanceof OCA.LDAP.Wizard.WizardDetectorGeneric) {
				this.detectors.push(detector);
			}
		},

		/**
		 * emits an event
		 *
		 * @param {string} [name] - the event name
		 * @param {*} [params]
		 * @private
		 */
		_broadcast: function(name, params) {
			if(_.isUndefined(this.subscribers[name])) {
				return;
			}
			var subscribers = this.subscribers[name];
			var subscriberCount = subscribers.length;
			for(var i = 0; i < subscriberCount; i++) {
				if(_.isUndefined(subscribers[i]['fn'])) {
					console.warn('callback method is not defined. Event ' + name);
					continue;
				}
				subscribers[i]['fn'](subscribers[i]['context'], params);
			}
		},

		/**
		 * ConfigModel#configLoaded Event
		 *
		 * @event ConfigModel#configLoaded
		 * @type {object} - LDAP configuration as key-value-pairs
		 */

		/**
		 * @typedef {object} ConfigLoadResponse
		 * @property {string} [status]
		 * @property {object} [configuration] - only present if status equals 'success'
		 */

		/**
		 * processes the ajax response of a configuration load request
		 *
		 * @param {ConfigModel} [model]
		 * @param {ConfigLoadResponse} [result]
		 * @fires ConfigModel#configLoaded
		 * @private
		 */
		_processLoadConfig: function(model, result) {
			model.configuration = {};
			if(result['status'] === 'success') {
				$.each(result['configuration'], function(key, value) {
					key = model.mappings[key] || key;
					model.configuration[key] = value;
				});
			}
			model.loadingConfig = false;
			model._broadcast('configLoaded', model.configuration);
		},

		/**
		 * @typedef {object} ConfigSetPayload
		 * @property {boolean} [isSuccess]
		 * @property {string} [key]
		 * @property {string} [value]
		 * @property {string} [errorMessage]
		 */

		/**
		 * ConfigModel#setCompleted Event
		 *
		 * @event ConfigModel#setCompleted
		 * @type {ConfigSetPayload}
		 */

		/**
		 * @typedef {object} ConfigSetResponse
		 * @property {string} [status]
		 * @property {object} [message] - might be present only in error cases
		 */

		/**
		 * processes the ajax response of a configuration key set request
		 *
		 * @param {ConfigModel} [model]
		 * @param {ConfigSetResponse} [result]
		 * @param {object} [params] - the original changeSet
		 * @fires ConfigModel#configLoaded
		 * @private
		 */
		_processSetResult: function(model, result, params) {
			var isSuccess = (result['status'] === 'success');
			if(isSuccess) {
				model.configuration[params.cfgkey] = params.cfgval;
			}
			var payload = {
				isSuccess: isSuccess,
				key: params.cfgkey,
				value: model.configuration[params.cfgkey],
				errorMessage: _.isUndefined(result['message']) ? '' : result['message']
			};
			model._broadcast('setCompleted', payload);

			// let detectors run
			// NOTE: detector's changes will not result in new _processSetResult
			// calls, … in case they interfere it is because of this ;)
			if(_.isUndefined(model.detectorQueue)) {
				console.warn("DetectorQueue was not set, detectors will not be fired");
				return;
			}
			var detectorCount = model.detectors.length;
			for(var i = 0; i < detectorCount; i++) {
				if(model.detectors[i].triggersOn(params.cfgkey)) {
					(function (detector) {
						model.detectorQueue.add(function() {
							return detector.run(model, model.configID);
						});
					})(model.detectors[i]);
				}
			}
		},

		/**
		 * @typedef {object} ConfigTestPayload
		 * @property {boolean} [isSuccess]
		 */

		/**
		 * ConfigModel#configurationTested Event
		 *
		 * @event ConfigModel#configurationTested
		 * @type {ConfigTestPayload}
		 */

		/**
		 * @typedef {object} StatusResponse
		 * @property {string} [status]
		 */

		/**
		 * processes the ajax response of a configuration test request
		 *
		 * @param {ConfigModel} [model]
		 * @param {StatusResponse} [result]
		 * @fires ConfigModel#configurationTested
		 * @private
		 */
		_processTestResult: function(model, result) {
			var payload = {
				isSuccess: (result['status'] === 'success')
			};
			model._broadcast('configurationTested', payload);
		},

		/**
		 * @typedef {object} BasicConfigPayload
		 * @property {boolean} [isSuccess]
		 * @property {string} [configPrefix] - the new config ID
		 * @property {string} [errorMessage]
		 */

		/**
		 * ConfigModel#newConfiguration Event
		 *
		 * @event ConfigModel#newConfiguration
		 * @type {BasicConfigPayload}
		 */

		/**
		 * @typedef {object} NewConfigResponse
		 * @property {string} [status]
		 * @property {string} [configPrefix]
		 * @property {object} [defaults] - default configuration values
		 * @property {string} [message] - might only appear with status being
		 * not 'success'
		 */

		/**
		 * processes the ajax response of a new configuration request
		 *
		 * @param {ConfigModel} [model]
		 * @param {NewConfigResponse} [result]
		 * @param {boolean} [copyCurrent]
		 * @fires ConfigModel#newConfiguration
		 * @fires ConfigModel#configLoaded
		 * @private
		 */
		_processNewConfigPrefix: function(model, result, copyCurrent) {
			var isSuccess = (result['status'] === 'success');
			var payload = {
				isSuccess: isSuccess,
				configPrefix: result['configPrefix'],
				errorMessage: _.isUndefined(result['message']) ? '' : result['message']
			};
			model._broadcast('newConfiguration', payload);

			if(isSuccess) {
				this.configID = result['configPrefix'];
				if(!copyCurrent) {
					model.configuration = {};
					$.each(result['defaults'], function(key, value) {
						key = model.mappings[key] || key;
						model.configuration[key] = value;
					});
					// view / tabs need to update with new blank config
					model._broadcast('configLoaded', model.configuration);
				}
			}
		},

		/**
		 * ConfigModel#deleteConfiguration Event
		 *
		 * @event ConfigModel#deleteConfiguration
		 * @type {BasicConfigPayload}
		 */

		/**
		 * processes the ajax response of a delete configuration request
		 *
		 * @param {ConfigModel} [model]
		 * @param {StatusResponse} [result]
		 * @param {string} [configID]
		 * @fires ConfigModel#deleteConfiguration
		 * @private
		 */
		_processDeleteConfig: function(model, result, configID) {
			var isSuccess = (result['status'] === 'success');
			var payload = {
				isSuccess: isSuccess,
				configPrefix: configID,
				errorMessage: _.isUndefined(result['message']) ? '' : result['message']
			};
			model._broadcast('deleteConfiguration', payload);
		}
	};

	OCA.LDAP.Wizard.ConfigModel = ConfigModel;
})();
