<?php
/**
 * This code is licensed under AfterLogic Software License.
 * For full statements of the license see LICENSE file.
 */
namespace Aurora\Modules\MtaConnector\Managers\Domains\Storages\db;
/**
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @internal
 */
class Storage extends \Aurora\Modules\MtaConnector\Managers\Domains\Storages\DefaultStorage
{
	protected $oConnection;
	protected $oCommandCreator;

	/**
	 * 
	 * @param \Aurora\System\Managers\AbstractManager $oManager
	 */
	public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
	{
		parent::__construct($oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator = new CommandCreator();
	}

	/**
	 * Creates domain.
	 * @param int $iTenantId Tenant identifier.
	 * @param int $sDomainName Domain name.
	 * @return boolean
	 */
	public function createDomain($iTenantId, $sDomainName)
	{
		$mResult = $this->oConnection->Execute($this->oCommandCreator->createDomain($iTenantId, $sDomainName));

		$this->throwDbExceptionIfExist();
		
		return $mResult;
	}
	
	/**
	 * Obtains all domains for specified tenant.
	 * @param int $iTenantId Tenant identifier.
	 * @return array|boolean
	 */
	public function getDomains($iTenantId)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getDomains($iTenantId)))
		{
			$mResult = [];
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if ($oRow)
				{
					$mResult[] = [
						'Id' => $oRow->id_domain,
						'Name' => $oRow->name,
						'Count' => is_int($oRow->count) ? $oRow->count : 0
					];
				}
			}
		}
		
		$this->throwDbExceptionIfExist();
		
		return $mResult;
	}
	
	/**
	 * Obtains domain with specified identifier.
	 * @param int $iDomainId Domain identifier.
	 * @return string|boolean
	 */
	public function getDomain($iDomainId)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getDomain($iDomainId)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$mResult = [
					'Name' => $oRow->name,
					'Count' => is_int($oRow->count) ? $oRow->count : 0
				];
			}
		}
		
		$this->throwDbExceptionIfExist();
		
		return $mResult;
	}

	/**
	 * Deletes domain.
	 * @param int $iDomainId domain identifier.
	 * @return boolean
	 */
	public function deleteDomain($iDomainId)
	{
		$mResult = $this->oConnection->Execute($this->oCommandCreator->deleteDomain($iDomainId));

		$this->throwDbExceptionIfExist();
		
		return $mResult;
	}

	/**
	 * Obtains domain members.
	 * @param int $iDomainId Domain identifier.
	 * @return string|boolean
	 */
	public function getDomainMembers($iDomainId)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getDomainMembers($iDomainId)))
		{
			$mResult = [];
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if ($oRow)
				{
					$mResult[] = [
						'UserId' => $oRow->id_user,
						'Email' => $oRow->mail_inc_login
					];
				}
			}
		}

		$this->throwDbExceptionIfExist();

		return $mResult;
	}
}