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

use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use MultiHttpClient;
use WikiPage;

class Hooks {

	public static MultiHttpClient $httpClient;

	/**
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 * @return bool
	 */
	public static function onPageSaveComplete(
		WikiPage $wikiPage,
		UserIdentity $user,
		string $summary,
		int $flags,
		RevisionRecord $revisionRecord,
		EditResult $editResult ): bool {
		$diffLink = self::getDiffLink( $wikiPage, $revisionRecord );
		$config = [
			MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentInstance' ),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentToken' ),
			MediaWikiServices::getInstance()->getMainConfig()->get( 'SummaryToJiraCommentEmail' )
		];
		$issueKeys = self::getJiraIssueKeys( $summary );

		foreach ( $issueKeys as $issueKey ) {
			$summary .= "\n\n" . $diffLink;
			$summary = str_replace( $issueKey, sprintf( "`%s`", $issueKey ), $summary );
			self::sendToJira( $config, $issueKey, $summary );
		}

		return true;
	}

	/**
	 * strip out the issue keys from the summary
	 * @param string $summary
	 * @return array
	 */
	private static function getJiraIssueKeys( $summary ): array {
		$issueKeys = [];
		$issueKeyRegex = '/([A-Z]+-[0-9]+)/';
		$matches = [];
		preg_match_all( $issueKeyRegex, $summary, $matches );
		if ( isset( $matches[1] ) ) {
			$issueKeys = $matches[1];
		}

		return $issueKeys;
	}

	/**
	 * Send the comment to Jira using the Jira API
	 * @param array $config
	 * @param string $issueKey
	 * @param string $summary
	 * @return bool
	 */
	private static function sendToJira( $config, $issueKey, $summary ): bool {
		list( $instance, $token, $email ) = $config;
		$hash = base64_encode( $email . ':' . $token );

		self::$httpClient = new MultiHttpClient( [ 'maxRetries' => 3 ] );

		try {
			$response = self::$httpClient->run( [
				'headers' => [
					'Authorization' => 'Basic ' . $hash,
					'Content-Type' => 'application/json',
				],
				'url' => 'https://' . $instance . '/rest/api/2/issue/' . $issueKey . '/comment',
				'method' => 'POST',
				'body' => json_encode( [
					'body' => $summary
				] )
			] );
		} catch ( \Exception $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the diff link for the revision
	 * @param WikiPage $wikiPage
	 * @param RevisionRecord $revisionRecord
	 * @return string
	 */
	private static function getDiffLink( WikiPage $wikiPage, RevisionRecord $revisionRecord ): string {
		$diffLink = $wikiPage->getTitle()->getFullURL();
		$currentRevision = $revisionRecord->getId();
		$oldRevision = $revisionRecord->getParentId();
		$diffLink .= '?diff=' . $currentRevision . '&oldid=' . $oldRevision;

		return $diffLink;
	}

}
