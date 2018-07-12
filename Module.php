<?php
/**
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailSuite;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	public $oApiMainManager = null;

	/* 
	 * @var $oApiFetchersManager Managers\Fetchers
	 */
	public $oApiFetchersManager = null;
			
	public function init()
	{
		$this->subscribeEvent('AdminPanelWebclient::CreateUser::after', array($this, 'onAfterCreateUser'));
		$this->subscribeEvent('Mail::SaveMessage::before', array($this, 'onBeforeSendOrSaveMessage'));
		$this->subscribeEvent('Mail::SendMessage::before', array($this, 'onBeforeSendOrSaveMessage'));

		$this->oApiMainManager = new Managers\Main\Manager($this);
		$this->oApiFetchersManager = new Managers\Fetchers\Manager($this);
	}

	/***** public functions might be called with web API *****/
	/**
	 * @apiDefine MailSuite MailSuite Module
	 * MailSuite module. It provides PHP and Web APIs for managing fetchers and other MailSuite features.
	 */
	
	/**
	 * @api {post} ?/Api/ GetSettings
	 * @apiName GetSettings
	 * @apiGroup MailSuite
	 * @apiDescription Obtains list of module settings for authenticated user.
	 * 
	 * @apiHeader {string} [Authorization] "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=GetSettings} Method Method name
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetSettings'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List of module settings in case of success, otherwise **false**.
	 * 
	 * @apiSuccess {boolean} Result.Result.AllowFetchers=false Indicates if fetchers are allowed.
	 * 
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetSettings',
	 *	Result: { AllowFetchers: false }
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetSettings',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains list of module settings for authenticated user.
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		return array(
			'AllowFetchers' => $this->getConfig('AllowFetchers', false),
		);
	}
	
	/**
	 * @api {post} ?/Api/ GetFetchers
	 * @apiName GetFetchers
	 * @apiGroup MailSuite
	 * @apiDescription Obtains all fetchers of specified user.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=GetServers} Method Method name
	 * @apiParam {string} [Parameters] JSON.stringified object<br>
	 * {<br>
	 * &emsp; **UserId** *int* (optional) User identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetFetchers'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result List fetchers in case of success, otherwise **false**.
	 * @apiSuccess {int} Result.Result.EntityId Fetcher identifier.
	 * @apiSuccess {string} Result.Result.UUID Fetcher UUID.
	 * @apiSuccess {int} Result.Result.IdUser User identifier.
	 * @apiSuccess {int} Result.Result.IdAccount Identifier of account owns fetcher.
	 * @apiSuccess {boolean} Result.Result.IsEnabled Indicates if fetcher is enabled.
	 * @apiSuccess {string} Result.Result.IncomingServer POP3 server.
	 * @apiSuccess {int} Result.Result.IncomingPort Port of POP3 server.
	 * @apiSuccess {string} Result.Result.IncomingLogin Fetcher account login.
	 * @apiSuccess {boolean} Result.Result.LeaveMessagesOnServer Indicates if messages shouldn't be removed from POP3 server during fetching.
	 * @apiSuccess {string} Result.Result.Folder Where to store emails fetched from POP3 server.
	 * @apiSuccess {boolean} Result.Result.IsOutgoingEnabled Indicates if send message is allowed from this fetcher.
	 * @apiSuccess {string} Result.Result.Name Value of fetcher friendly name.
	 * @apiSuccess {string} Result.Result.Email Value of fetcher email.
	 * @apiSuccess {string} Result.Result.OutgoingServer SMTP server.
	 * @apiSuccess {int} Result.Result.OutgoingPort Port of SMTP server.
	 * @apiSuccess {boolean} Result.Result.OutgoingUseAuth Indicates if SMTP connect should be authenticated.
	 * @apiSuccess {boolean} Result.Result.UseSignature Indicates if signature should be used in outgoing mails.
	 * @apiSuccess {string} Result.Result.Signature Fetcher signature.
	 * @apiSuccess {boolean} Result.Result.IsLocked
	 * @apiSuccess {int} Result.Result.CheckInterval
	 * @apiSuccess {int} Result.Result.CheckLastTime
	 * @apiSuccess {boolean} Result.Result.IncomingUseSsl Indicates if SSL should be used on POP3 server.
	 * @apiSuccess {boolean} Result.Result.OutgoingUseSsl Indicates if SSL should be used on SMTP server.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetFetchers',
	 *	Result: [ { "EntityId": 14, "UUID": "uuid_value", "IdUser": 3, "IdAccount": 12,
	 *				"IsEnabled": true, "IncomingServer": "pop.server.com", "IncomingPort": 995,
	 *				"IncomingLogin": "login_value", "LeaveMessagesOnServer": true, "Folder": "fetch_folder_value",
	 *				"IsOutgoingEnabled": true, "Name": "", "Email": "email_value@server.com",
	 *				"OutgoingServer": "smtp.server.com", "OutgoingPort": 465, "OutgoingUseAuth": true,
	 *				"UseSignature": false, "Signature": "", "IsLocked": false, "CheckInterval": 0,
	 *				"CheckLastTime": 0, "IncomingUseSsl": true, "OutgoingUseSsl": true },
	 *			  ... ]
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'GetFetchers',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Obtains all fetchers of specified user.
	 * @param int $UserId User identifier.
	 * @return array|false
	 */
	public function GetFetchers($UserId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			return $this->oApiFetchersManager->getFetchers($UserId);
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ CreateFetcher
	 * @apiName CreateFetcher
	 * @apiGroup MailSuite
	 * @apiDescription Creates fetcher.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=CreateFetcher} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **AccountId** *int* Account identifier.<br>
	 * &emsp; **Folder** *string* Where to store emails fetched from POP3 server.<br>
	 * &emsp; **IncomingLogin** *string* Fetcher account login.<br>
	 * &emsp; **IncomingPassword** *string* Fetcher account password.<br>
	 * &emsp; **IncomingServer** *string* POP3 server.<br>
	 * &emsp; **IncomingPort** *int* Port of POP3 server.<br>
	 * &emsp; **IncomingUseSsl** *boolean* Indicates if SSL should be used.<br>
	 * &emsp; **LeaveMessagesOnServer** *boolean* Indicates if messages shouldn't be removed from POP3 server during fetching.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'CreateFetcher',
	 *	Parameters: '{	"AccountId": 12, "Folder": "fetch_folder_value", "IncomingLogin": "login_value",
	 *					"IncomingPassword": "pass_value", "IncomingServer": "pop.server.com",
	 *					"IncomingPort": 110, "IncomingUseSsl": false, "LeaveMessagesOnServer": true }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {mixed} Result.Result Identifier of created fetcher in case of success, otherwise **false**.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'CreateFetcher',
	 *	Result: 14
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'CreateFetcher',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Creates fetcher.
	 * @param int $UserId User identifier.
	 * @param int $AccountId Account identifier.
	 * @param string $Folder Where to store emails fetched from POP3 server.
	 * @param string $IncomingLogin Fetcher account login.
	 * @param string $IncomingPassword Fetcher account password.
	 * @param string $IncomingServer POP3 server.
	 * @param int $IncomingPort Port of POP3 server.
	 * @param boolean $IncomingUseSsl Indicates if SSL should be used.
	 * @param boolean $LeaveMessagesOnServer Indicates if messages shouldn't be removed from POP3 server during fetching.
	 * @return int|boolean
	 */
	public function CreateFetcher($UserId, $AccountId, $Folder, $IncomingLogin, $IncomingPassword,
			$IncomingServer, $IncomingPort, $IncomingUseSsl, $LeaveMessagesOnServer)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			$oFetcher = new \Aurora\Modules\MailSuite\Classes\Fetcher($this->GetName());
			$oFetcher->IdUser = $UserId;
			$oFetcher->IdAccount = $AccountId;

			$oFetcher->IncomingServer = $IncomingServer;
			$oFetcher->IncomingPort = $IncomingPort;
			$oFetcher->IncomingLogin = $IncomingLogin;
			$oFetcher->IncomingPassword = $IncomingPassword;
			$oFetcher->IncomingMailSecurity = $IncomingUseSsl ? \MailSo\Net\Enumerations\ConnectionSecurityType::SSL : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE;
			$oFetcher->LeaveMessagesOnServer = $LeaveMessagesOnServer;
			$oFetcher->Folder = $Folder;

			return $this->oApiFetchersManager->createFetcher($oFetcher);
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateFetcher
	 * @apiName UpdateFetcher
	 * @apiGroup MailSuite
	 * @apiDescription Updates fetcher.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=UpdateFetcher} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **FetcherId** *int* Fetcher identifier.<br>
	 * &emsp; **IsEnabled** *boolean* Indicates if fetcher is enabled.<br>
	 * &emsp; **Folder** *string* Where to store emails fetched from POP3 server.<br>
	 * &emsp; **IncomingServer** *string* POP3 server.<br>
	 * &emsp; **IncomingPort** *int* Port of POP3 server.<br>
	 * &emsp; **IncomingUseSsl** *boolean* Indicates if SSL should be used.<br>
	 * &emsp; **LeaveMessagesOnServer** *boolean* Indicates if messages shouldn't be removed from POP3 server during fetching.<br>
	 * &emsp; **IncomingPassword** *string* Fetcher account password.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcher',
	 *	Parameters: '{ "FetcherId": 14, "IsEnabled": true, "Folder": "fetch_folder_name",
	 *		"IncomingServer": "pop.server.com", "IncomingPort": 110, "IncomingUseSsl": false,
	 *		"LeaveMessagesOnServer": true, "IncomingPassword": "pass_value" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if fetcher was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcher',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcher',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates fetcher.
	 * @param int $UserId User identifier.
	 * @param int $FetcherId Fetcher identifier.
	 * @param boolean $IsEnabled Indicates if fetcher is enabled.
	 * @param string $Folder Where to store emails fetched from POP3 server.
	 * @param string $IncomingServer POP3 server.
	 * @param int $IncomingPort Port of POP3 server.
	 * @param boolean $IncomingUseSsl Indicates if SSL should be used.
	 * @param boolean $LeaveMessagesOnServer Indicates if messages shouldn't be removed from POP3 server during fetching.
	 * @param string $IncomingPassword Fetcher account password.
	 * @return boolean
	 */
	public function UpdateFetcher($UserId, $FetcherId, $IsEnabled, $Folder, $IncomingServer, $IncomingPort,
			$IncomingUseSsl, $LeaveMessagesOnServer, $IncomingPassword = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			$oFetcher = $this->oApiFetchersManager->getFetcher($FetcherId);
			if ($oFetcher && $oFetcher->IdUser === $UserId)
			{
				$oFetcher->IsEnabled = $IsEnabled;
				$oFetcher->IncomingServer = $IncomingServer;
				$oFetcher->IncomingPort = $IncomingPort;
				if (isset($IncomingPassword))
				{
					$oFetcher->IncomingPassword = $IncomingPassword;
				}
				$oFetcher->IncomingMailSecurity = $IncomingUseSsl ? \MailSo\Net\Enumerations\ConnectionSecurityType::SSL : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE;
				$oFetcher->LeaveMessagesOnServer = $LeaveMessagesOnServer;
				$oFetcher->Folder = $Folder;

				return $this->oApiFetchersManager->updateFetcher($oFetcher, true);
			}
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateFetcherSmtpSettings
	 * @apiName UpdateFetcherSmtpSettings
	 * @apiGroup MailSuite
	 * @apiDescription Updates fetcher SMTP settings.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=UpdateFetcherSmtpSettings} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **FetcherId** *int* Fetcher identifier.<br>
	 * &emsp; **Email** *string* New value of fetcher email.<br>
	 * &emsp; **Name** *string* New value of fetcher friendly name.<br>
	 * &emsp; **IsOutgoingEnabled** *boolean* Indicates if send message is allowed from this fetcher.<br>
	 * &emsp; **OutgoingServer** *string* SMTP server.<br>
	 * &emsp; **OutgoingPort** *int* Port of SMTP server.<br>
	 * &emsp; **OutgoingUseSsl** *boolean* Indicates if SSL should be used.<br>
	 * &emsp; **OutgoingUseAuth** *boolean* Indicates if SMTP connect should be authenticated.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcherSmtpSettings',
	 *	Parameters: '{ "FetcherId": 14, "Email": "test@email", "Name": "New my name",
	 *		"IsOutgoingEnabled": true, "OutgoingServer": "smtp.server.com", "OutgoingPort": 25,
	 *		"OutgoingUseSsl": false, "OutgoingUseAuth": false }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if fetcher was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcherSmtpSettings',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateFetcherSmtpSettings',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates fetcher.
	 * @param int $UserId User identifier.
	 * @param int $FetcherId Fetcher identifier.
	 * @param string $Email New value of fetcher email.
	 * @param string $Name New value of fetcher friendly name.
	 * @param boolean $IsOutgoingEnabled Indicates if send message is allowed from this fetcher.
	 * @param string $OutgoingServer SMTP server.
	 * @param int $OutgoingPort Port of SMTP server.
	 * @param boolean $OutgoingUseSsl Indicates if SSL should be used.
	 * @param boolean $OutgoingUseAuth Indicates if SMTP connect should be authenticated.
	 * @return boolean
	 */
	public function UpdateFetcherSmtpSettings($UserId, $FetcherId, $Email, $Name, $IsOutgoingEnabled,
			$OutgoingServer, $OutgoingPort, $OutgoingUseSsl, $OutgoingUseAuth)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			$oFetcher = $this->oApiFetchersManager->getFetcher($FetcherId);
			if ($oFetcher && $oFetcher->IdUser === $UserId)
			{
				$oFetcher->IsOutgoingEnabled = $IsOutgoingEnabled;
				$oFetcher->Name = $Name;
				$oFetcher->Email = $Email;
				$oFetcher->OutgoingServer = $OutgoingServer;
				$oFetcher->OutgoingPort = $OutgoingPort;
				$oFetcher->OutgoingMailSecurity = $OutgoingUseSsl ? \MailSo\Net\Enumerations\ConnectionSecurityType::SSL : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE;
				$oFetcher->OutgoingUseAuth = $OutgoingUseAuth;

				return $this->oApiFetchersManager->updateFetcher($oFetcher, false);
			}
		}
		
		return false;
	}
	
	/**
	 * @api {post} ?/Api/ UpdateSignature
	 * @apiName UpdateSignature
	 * @apiGroup MailSuite
	 * @apiDescription Updates fetcher signature.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=UpdateSignature} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **FetcherId** *int* Fetcher identifier.<br>
	 * &emsp; **UseSignature** *boolean* Indicates if signature should be used in outgoing mails.<br>
	 * &emsp; **Signature** *string* Fetcher signature.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateSignature',
	 *	Parameters: '{ "FetcherId": 14, "UseSignature": true, "Signature": "signature_value" }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if signature was updated successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateSignature',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'UpdateSignature',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Updates fetcher signature.
	 * @param int $UserId User identifier.
	 * @param int $FetcherId Fetcher identifier.
	 * @param boolean $UseSignature Indicates if signature should be used in outgoing mails.
	 * @param string $Signature Fetcher signature.
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	public function UpdateSignature($UserId, $FetcherId = null, $UseSignature = null, $Signature = null)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			$oFetcher = $this->oApiFetchersManager->getFetcher($FetcherId);
			if ($oFetcher && $oFetcher->IdUser === $UserId)
			{
				$oFetcher->UseSignature = $UseSignature;
				$oFetcher->Signature = $Signature;
				return $this->oApiFetchersManager->updateFetcher($oFetcher, false);
			}
		}

		return false;
	}
	
	/**
	 * @api {post} ?/Api/ DeleteFetcher
	 * @apiName DeleteFetcher
	 * @apiGroup MailSuite
	 * @apiDescription Deletes fetcher.
	 * 
	 * @apiHeader {string} Authorization "Bearer " + Authentication token which was received as the result of Core.Login method.
	 * @apiHeaderExample {json} Header-Example:
	 *	{
	 *		"Authorization": "Bearer 32b2ecd4a4016fedc4abee880425b6b8"
	 *	}
	 * 
	 * @apiParam {string=MailSuite} Module Module name
	 * @apiParam {string=DeleteFetcher} Method Method name
	 * @apiParam {string} Parameters JSON.stringified object<br>
	 * {<br>
	 * &emsp; **FetcherId** *int* Fetcher identifier.<br>
	 * }
	 * 
	 * @apiParamExample {json} Request-Example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'DeleteFetcher',
	 *	Parameters: '{ "FetcherId": 14 }'
	 * }
	 * 
	 * @apiSuccess {object[]} Result Array of response objects.
	 * @apiSuccess {string} Result.Module Module name.
	 * @apiSuccess {string} Result.Method Method name.
	 * @apiSuccess {boolean} Result.Result Indicates if fetcher was deleted successfully.
	 * @apiSuccess {int} [Result.ErrorCode] Error code
	 * 
	 * @apiSuccessExample {json} Success response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'DeleteFetcher',
	 *	Result: true
	 * }
	 * 
	 * @apiSuccessExample {json} Error response example:
	 * {
	 *	Module: 'MailSuite',
	 *	Method: 'DeleteFetcher',
	 *	Result: false,
	 *	ErrorCode: 102
	 * }
	 */
	/**
	 * Deletes fetcher.
	 * @param int $FetcherId Fetcher identifier.
	 * @return boolean
	 */
	public function DeleteFetcher($UserId, $FetcherId)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::NormalUser);
		
		if ($this->getConfig('AllowFetchers', false))
		{
			$oFetcher = $this->oApiFetchersManager->getFetcher($FetcherId);
			if ($oFetcher && $oFetcher->IdUser === $UserId)
			{
				return $this->oApiFetchersManager->deleteFetcher($FetcherId);
			}
		}
		
		return false;
	}
	/***** public functions might be called with web API *****/
	
	/***** private functions *****/
	public function onAfterCreateUser(&$aData, &$mResult)
	{
		$sEmail = isset($aData['PublicId']) ? $aData['PublicId'] : '';
		$sPassword = isset($aData['Password']) ? $aData['Password'] : '';
		$sQuota = isset($aData['Quota']) ? $aData['Quota'] : null;
		$oUser = \Aurora\System\Api::getUserById($mResult);
		if ($sEmail && $sPassword && $oUser instanceof \Aurora\Modules\Core\Classes\User)
		{
			$this->oApiMainManager->createAccount($sEmail, $sPassword, $sQuota);
			\Aurora\System\Api::GetModuleDecorator('Mail')->CreateAccount($oUser->EntityId, '', $sEmail, $sEmail, $sPassword);
		}
	}
	
	public function onBeforeSendOrSaveMessage(&$aArgs, &$mResult)
	{
		$oFetcher = $this->oApiFetchersManager->getFetcher($aArgs['FetcherID']);
		if ($oFetcher && $oFetcher->IdUser === $aArgs['UserId'])
		{
			$aArgs['Fetcher'] = $oFetcher;
		}
	}
	/***** private functions *****/
}
