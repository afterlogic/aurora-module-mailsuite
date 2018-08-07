<?php
/**
 * This code is licensed under AfterLogic Software License.
 * For full statements of the license see LICENSE file.
 */
namespace Aurora\Modules\MailSuite\Managers\Aliases\Storages\db;
/**
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @internal
 */
class CommandCreator extends \Aurora\System\Db\AbstractCommandCreator
{
	/**
	 * Creates SQL-query to obtain all aliases for specified mail account.
	 * @param int $iAccountId Account identifier.
	 * @return string
	 */
	public function getAliases($iAccountId)
	{
		$sSql = 'SELECT alias_name, alias_domain, alias_to FROM awm_mailaliases WHERE id_acct = %d';
		
		return sprintf($sSql, $iAccountId);
	}
	
	/**
	 * Creates SQL-query to add new alias with specified name and domain.
	 * @param int $iAccountId Account identifier.
	 * @param string $sName Alias name.
	 * @param string $sDomain Alias domain.
	 * @param string $sToEmail Email of the mailbox where messages should be sent.
	 * @return string
	 */
	public function addAlias($iAccountId, $sName, $sDomain, $sToEmail)
	{
		$sSql = 'INSERT INTO awm_mailaliases ( id_acct, alias_name, alias_domain, alias_to ) VALUES ( %d, %s, %s, %s )';
		
		return sprintf($sSql,
			$iAccountId,
			$this->escapeString($sName),
			$this->escapeString($sDomain),
			$this->escapeString($sToEmail)
		);
	}

	/**
	 * Creates SQL-query to delete alias with specified name and domain.
	 * @param int $iAccountId Account identifier
	 * @param string $sName Alias name.
	 * @param string $sDomain Alias domain.
	 * @return string
	 */
	public function deleteAlias($iAccountId, $sName, $sDomain)
	{
		$sSql = 'DELETE FROM awm_mailaliases WHERE id_acct = %d AND alias_name = %s AND alias_domain = %s';

		return sprintf($sSql,
			$iAccountId,
			$this->escapeString($sName),
			$this->escapeString($sDomain)
		);
	}
}

class CommandCreatorMySQL extends CommandCreator
{
}