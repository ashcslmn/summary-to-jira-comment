<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\SummaryToJiraComment;

class Hooks
{

	public static function onMultiContentSave(\MediaWiki\Revision\RenderedRevision $renderedRevision, \MediaWiki\User\UserIdentity $user, \CommentStoreComment $summary, $flags, \Status $hookStatus)
	{
		$config = [
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get('SummaryToJiraCommentInstance'),
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get('SummaryToJiraCommentToken'),
			\MediaWiki\MediaWikiServices::getInstance()->getMainConfig()->get('SummaryToJiraCommentEmail')
		];

		$issueKeys = self::getJiraIssueKeys($summary->text);

		foreach ($issueKeys as $issueKey) {
			self::sendToJira($config, $issueKey, $summary->text, $hookStatus);
		}

		return true;
	}

	private static function getJiraIssueKeys($summary)
	{
		$issueKeys = [];
		$issueKeyRegex = '/([A-Z]+-[0-9]+)/';
		$matches = [];
		preg_match_all($issueKeyRegex, $summary, $matches);
		if (isset($matches[1])) {
			$issueKeys = $matches[1];
		}
		return $issueKeys;
	}

	private static function sendToJira($config, $issueKey, $summary, $hookStatus)
	{

		list($instance, $token, $email) = $config;

		$curl = curl_init();

		$hash = base64_encode($email . ':' . $token);

		curl_setopt_array(
			$curl,
			array(
				CURLOPT_URL => 'https://' . $instance . '/rest/api/2/issue/' . $issueKey . '/comment',
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => '',
				CURLOPT_MAXREDIRS => 10,
				CURLOPT_TIMEOUT => 0,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_CUSTOMREQUEST => 'POST',
				CURLOPT_POSTFIELDS => json_encode([
					'body' => trim(str_replace($issueKey, '', $summary))

				]),
				CURLOPT_HTTPHEADER => array(
					'Authorization: Basic ' . $hash,
					'Content-Type: application/json',
				),
			)
		);

		curl_exec($curl);

		curl_close($curl);
	}
}
